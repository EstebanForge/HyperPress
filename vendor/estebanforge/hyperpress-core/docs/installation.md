# Installation

Install it directly from the WordPress.org plugin repository. On the plugins install page, search for: HyperPress (or Hypermedia)

Or download the zip from the [official plugin repository](https://wordpress.org/plugins/api-for-htmx/) and install it from your WordPress plugins install page.

Activate the plugin. Configure it to your liking on Settings > HyperPress.

## Installation via Composer
If you want to use this plugin as a library, you can install it via Composer. This allows you to use hypermedia libraries in your own plugins or themes, without the need to install this plugin.

```bash
composer require estebanforge/hyperpress
```

This plugin/library will determine which instance of itself is the newer one when WordPress is loading. Then, it will use the newer instance between all competing plugins or themes. This is to avoid conflicts with other plugins or themes that may be using the same library for their Hypermedia implementation.

When installed as a Composer library the `Settings → HyperPress` page is hidden by default. Configure via filters, or re-enable the admin UI with `add_filter('hyperpress/admin/show_menu', '__return_true');` — see [Developer Configuration](./developer-configuration.md#re-enable-the-admin-settings-page-in-library-mode).

### Host plugins using the Jetpack Autoloader

#### Consumers MUST directly require `automattic/jetpack-autoloader`

**Non-obvious gate (caused a staging outage).** Jetpack's manifest is
only generated when `automattic/jetpack-autoloader` is a **direct** require of
*your* plugin's `composer.json`. Transitive presence (pulled in via the Hyper
libraries — HyperPress-Core vendors HyperFields and HyperBlocks) does **not**
trigger adoption: Jetpack installs but stays inert, Composer's native classmap
runs instead, and a stale bundled class can shadow the elected-newest init
(fatal). Verified empirically.

So every distributable plugin vendoring HyperPress-Core (or any Hyper library)
must add it directly:

```json
{
  "require": { "automattic/jetpack-autoloader": "^2", "estebanforge/hyperpress-core": "^1" },
  "config": { "allow-plugins": { "automattic/jetpack-autoloader": true } }
}
```

Then rebuild with `--optimize-autoloader` and confirm the manifest exists:
`test -f vendor/composer/jetpack_autoload_classmap.php`. Full detail and the
exact failure trace in `estebanforge/hyperfields` → `docs/library-bootstrap.md`.

#### `files` autoload entries do not execute under Jetpack

HyperPress-Core bundles HyperFields and HyperBlocks as Composer dependencies,
and its own `bootstrap.php` explicitly requires their `bootstrap.php` files so
their version-election and asset hooks fire. This works when HyperPress-Core's
`bootstrap.php` is executed — which Composer's stock autoloader does via
autoload `files`.

However, if the **host** plugin that pulls HyperPress-Core in uses
[`automattic/jetpack-autoloader`](https://packagist.org/packages/automattic/jetpack-autoloader),
**Composer autoload `files` entries are not executed.** The Jetpack Autoloader
maps classes for lazy loading but skips the `files` auto-includes. The chain
breaks at the first link:

```
host bootstrap.php (autoload.files)  ← never executed by Jetpack Autoloader
  └── HyperPress-Core bootstrap.php   ← never reached
        └── HyperFields/HyperBlocks bootstrap.php  ← never reached
```

Symptoms in a Jetpack-Autoloader host:
- HyperPress options page (if re-enabled) renders with **no CSS** — unstyled
  inputs, no cards, no spacing.
- HyperFields-backed option pages in the host plugin look the same.
- Blocks from HyperBlocks are missing from the Gutenberg inserter.
- `HYPERPRESS_PLUGIN_URL`, `HYPERFIELDS_PLUGIN_URL`, `HYPERBLOCKS_PLUGIN_URL`
  are all undefined.

The classes are still autoloadable, so nothing fatals outright. The libraries
just never initialise.

**Fix.** The host plugin must explicitly trigger the bootstrap chain on
`plugins_loaded` (priority 0):

```php
// my-plugin.php

add_action('plugins_loaded', static function (): void {
    $paths = [
        'hyperpress'  => MY_PLUGIN_PATH . 'vendor/estebanforge/hyperpress/bootstrap.php',
        'hyperfields' => MY_PLUGIN_PATH . 'vendor/estebanforge/hyperfields/bootstrap.php',
        'hyperblocks' => MY_PLUGIN_PATH . 'vendor/estebanforge/hyperblocks/bootstrap.php',
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
        }
    }

    // Each init function is idempotent (guarded by its *_INSTANCE_LOADED
    // constant), so calling all three is safe even though they reference
    // each other transitively.
    $version = defined('MY_PLUGIN_VERSION') ? MY_PLUGIN_VERSION : '1.0.0';

    if (function_exists('hyperpress_run_initialization_logic')) {
        hyperpress_run_initialization_logic($paths['hyperpress'], $version);
    }
    if (function_exists('hyperfields_run_initialization_logic')) {
        hyperfields_run_initialization_logic($paths['hyperfields'], $version);
    }
    if (function_exists('hyperblocks_run_initialization_logic')) {
        hyperblocks_run_initialization_logic($paths['hyperblocks'], $version);
    }
}, 0);
```

Calling the init functions directly skips the multi-instance candidate election
and runs init immediately. For a single-consumer plugin this is correct and
faster. See the per-library docs for details:
[HyperFields](../../HyperFields/docs/library-bootstrap.md) and
[HyperBlocks](../../HyperBlocks/docs/library-bootstrap.md).
