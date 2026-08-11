# HyperPress-Core library bootstrap

HyperPress-Core is a Composer library (`estebanforge/hyperpress-core`). It bundles HyperFields and HyperBlocks as dependencies and brings the whole stack up on its own. This document is the self-contained bootstrap guide for a consumer that requires HyperPress-Core directly and may never open the HyperFields or HyperBlocks docs.

## Self-initialization (zero-config)

HyperPress-Core ships a `bootstrap.php` (registered in its `composer.json` under `autoload.files`) that schedules `HyperPress\Bootstrap::init()` on `after_setup_theme` (priority 0). Composer's files-autoload runs this entry once per process, so loading your plugin's `vendor/autoload.php` is all that is required: the REST router, renderer, assets, admin, and block wiring all come up without any consumer code.

The same `bootstrap.php` also requires the bundled HyperFields and HyperBlocks `bootstrap.php` files when HyperPress-Core runs standalone (not from inside a vendor tree), so those two libraries self-initialize too. You do not bootstrap them separately.

### Reliable across every load order

The scheduling is reliable even in the early-load window where `add_action()` is not yet available. That window occurs when a drop-in (`object-cache.php`, `advanced-cache.php`), a must-use plugin, or `wp-config.php` pulls in a Composer autoloader before `wp-includes/plugin.php` loads (common in Bedrock, where `wp-config.php` requires `vendor/autoload.php` before `application.php` defines `ABSPATH`). In that window `bootstrap.php` writes the `after_setup_theme` registration straight into `$GLOBALS['wp_filter']` in the preinitialized-hooks raw-array format; WordPress core converts that into a real `WP_Hook` when `plugin.php` loads (`WP_Hook::build_preinitialized_hooks`, since WP 4.7, Trac #38929). The scheduler runs before the `ABSPATH` guard, so the registration lands whether or not `ABSPATH` is defined yet.

As a side effect the runtime now also comes up under WP-CLI, where the scheduling previously never landed.

### Background and API contexts

`Bootstrap::init()` short-circuits the heavy WordPress wiring (but still loads helpers and constants) when the request is a background or API context: `DOING_CRON`, `DOING_AJAX`, `REST_REQUEST`, `XMLRPC_REQUEST`, or `WP_CLI`. This is intentional: those contexts do not render pages or enqueue frontend assets. The helper functions remain available.

## Explicit override (optional)

Calling `HyperPress\Bootstrap::init()` explicitly after your autoloader is still supported as an optional deterministic override, for example to pin a specific `plugin_file` or `plugin_url`. It is idempotent and safe under the first-to-boot election guard. It is no longer required for correctness.

```php
require_once __DIR__ . '/vendor/autoload.php';

\HyperPress\Bootstrap::init([
    'plugin_file' => __FILE__,
    'plugin_url'  => plugin_dir_url(__FILE__) . 'vendor/estebanforge/hyperpress-core/',
]);
```

## Duplicate-load protection

The first copy to reach `init()` claims the namespace-scoped `HyperPress\LOADED` constant and wins; any later copy bails before bootstrapping. So two plugins that both ship HyperPress-Core do not double-init or fatal. This is first-to-boot, not newest-wins. If you need guaranteed isolation across divergent versions, prefix the namespace with [Mozart](https://github.com/coenjacobs/mozart).

Runtime identity lives on `HyperPress\Config` (prefix-safe), not global constants:

- `Config::VERSION`
- `Config::$abspath`
- `Config::$pluginFile`
- `Config::$pluginUrl`
- `Config::$basename`

## Bedrock / Composer-managed sites

HyperPress-Core is `type: library`, so when a Bedrock project requires it transitively Composer installs it in the project root `vendor/`, outside `wp-content/`. That copy is not under any web-accessible WordPress content root, so its frontend assets (HTMX, Alpine, Datastar) cannot be served over HTTP.

`Bootstrap::init()` still runs from such a copy. It does **not** gate boot on web-reachability. `Config::$pluginUrl` is simply empty and asset enqueues bail gracefully instead of emitting 404ing URLs; the rest of the runtime (REST routing, rendering, options) is unaffected.

### Dual-copy sites

If a Bedrock project has HyperPress-Core in two places at once (a root `vendor/` copy pulled transitively, plus a copy bundled inside a plugin under `wp-content/`), the **root** copy wins Composer's `autoload.files` race, because `wp-config.php` requires the root `vendor/autoload.php` first. Only the winning bootstrap's code runs, so an updated bootstrap only takes effect when the winning copy contains it. To make the plugin-bundled (web-reachable) copy win, remove the root copy with Composer `replace`:

1. Confirm why it is there: `composer why estebanforge/hyperpress-core`.
2. Add to the **root** `composer.json`:
   `"replace": { "estebanforge/hyperpress-core": "*" }`.
3. `composer update estebanforge/hyperpress-core --lock`. Composer removes the directory itself and drops the `autoload.files` and classmap entries.

**Never `rm` the root `vendor/` copy.** Composer's files-loader does a bare `require $file` with no `file_exists` guard, so deleting the file while the autoload entry survives fatals every request; `composer dump-autoload` regenerates from `installed.json` (which still lists the package) and re-emits the dead path. `replace` plus `update --lock` is the only safe removal.

The same dual-copy concern applies to the bundled HyperFields and HyperBlocks if they also appear at the Bedrock root. On such a site, `replace` all three.

**Recommended pattern:** ship HyperPress-Core inside the host plugin's own committed `vendor/` (for example `wp-content/plugins/<your-plugin>/vendor/estebanforge/hyperpress-core/`) and require that plugin's `vendor/autoload.php`. That copy is web-reachable, so `Config::$pluginUrl` resolves and assets enqueue. Do not rely on a Bedrock root-vendor copy to serve assets; it never can.

## Classmap-only autoloaders

Some host plugins use a classmap-only autoloader that skips Composer `autoload.files`. In that case the `bootstrap.php` chain never runs and the library does not self-initialize. Trigger the chain explicitly on `plugins_loaded`:

```php
add_action('plugins_loaded', function () {
    $bootstraps = [
        __DIR__ . '/vendor/estebanforge/hyperfields/bootstrap.php',
        __DIR__ . '/vendor/estebanforge/hyperblocks/bootstrap.php',
        __DIR__ . '/vendor/estebanforge/hyperpress-core/bootstrap.php',
    ];
    foreach ($bootstraps as $b) {
        if (file_exists($b)) {
            require_once $b;
        }
    }
}, 5);
```

See [installation.md](installation.md#vendoring-inside-a-host-plugin) for the full vendoring walkthrough.
