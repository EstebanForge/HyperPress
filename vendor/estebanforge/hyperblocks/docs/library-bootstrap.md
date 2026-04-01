# HyperBlocks — Library Bootstrap

This document covers how HyperBlocks initializes itself, how the version-resolution system works, and how to configure it correctly when vendored inside another plugin or theme.

---

## How bootstrap works

HyperBlocks uses the same candidate-election bootstrap pattern as HyperFields. The goal is to allow multiple copies of HyperBlocks to coexist (e.g. a theme and a plugin both require it) while ensuring only one instance — the highest-version one — actually initializes.

**Sequence**:

1. Each copy of `bootstrap.php` is included by Composer via `autoload.files`.
2. Each inclusion registers the copy as a candidate in `$GLOBALS['hyperblocks_api_candidates']` with its version and path.
3. After all plugins/theme are loaded, `after_setup_theme` (priority 0) fires `hyperblocks_select_and_load_latest()`.
4. The candidate with the highest version wins and calls `hyperblocks_run_initialization_logic()`.
5. `hyperblocks_run_initialization_logic()` defines all `HYPERBLOCKS_*` constants and calls `WordPress\Bootstrap::init()`.
6. Subsequent candidates are silently skipped (guarded by `HYPERBLOCKS_INSTANCE_LOADED`).

HyperBlocks also triggers HyperFields initialization in the same `bootstrap.php` if the HyperFields standalone plugin is not already active. This means requiring `estebanforge/hyperblocks` in your Composer dependencies is the only step needed — you do not need to require or bootstrap HyperFields separately.

---

## Constants

After `hyperblocks_run_initialization_logic()` runs, these constants are defined:

| Constant | Description |
|---|---|
| `HYPERBLOCKS_VERSION` | Version string read from `composer.json`. |
| `HYPERBLOCKS_PATH` | Absolute path to the HyperBlocks root directory (trailing slash). |
| `HYPERBLOCKS_ABSPATH` | Alias of `HYPERBLOCKS_PATH`. |
| `HYPERBLOCKS_PLUGIN_FILE` | Absolute path to `bootstrap.php`. |
| `HYPERBLOCKS_PLUGIN_URL` | Public URL to the HyperBlocks root (trailing slash). |
| `HYPERBLOCKS_BOOTSTRAP_LOADED` | Set when `bootstrap.php` is first included. Prevents double-include. |
| `HYPERBLOCKS_INSTANCE_LOADED` | Set when initialization logic runs. Ensures single initialization. |
| `HYPERBLOCKS_LOADED_VERSION` | The version string of the instance that won election. |
| `HYPERBLOCKS_INSTANCE_LOADED_PATH` | Absolute path of the winning `bootstrap.php`. |

---

## Standard usage — flat vendor directory

The most common case: a plugin requires `estebanforge/hyperblocks` and loads its own Composer autoloader.

```
wp-content/plugins/my-plugin/
├── my-plugin.php
└── vendor/
    └── estebanforge/
        ├── hyperblocks/
        └── hyperfields/
```

```php
// my-plugin.php

$autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// HyperBlocks bootstrap is included automatically via Composer autoload.files.
// No further initialization is required — blocks may be registered from init onwards.

add_action('init', function (): void {
    use HyperBlocks\Block\Block;
    use HyperBlocks\Block\Field;
    use HyperBlocks\Registry;

    Registry::getInstance()->registerFluentBlock(
        Block::make('My Block')
            ->setName('my-plugin/my-block')
            ->addFields([Field::make('text', 'heading', 'Heading')])
            ->setRenderTemplateFile('blocks/my-block.hb.php')
    );
});
```

---

## Usage inside a class (plugins_loaded pattern)

When your plugin defers setup to a bootstrap class, define constants at the top of the main plugin file so URL resolution works correctly even after `plugins_loaded` runs.

```php
// my-plugin.php

define('MY_PLUGIN_FILE', __FILE__);
define('MY_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define('MY_PLUGIN_URL',  plugin_dir_url(__FILE__));

add_action('plugins_loaded', function (): void {
    require_once MY_PLUGIN_DIR . 'vendor/autoload.php';
    MyPlugin\Bootstrap::init();
});
```

```php
// src/Bootstrap.php

namespace MyPlugin;

class Bootstrap
{
    public static function init(): void
    {
        add_action('init', [self::class, 'registerBlocks']);
    }

    public static function registerBlocks(): void
    {
        \HyperBlocks\Config::registerBlockPath(MY_PLUGIN_DIR . 'blocks');
    }
}
```

---

## Monorepo / Bedrock / symlinked plugins

In setups where the plugins directory is outside the standard `wp-content/plugins` path, or where plugin directories are symlinks, `HYPERBLOCKS_PLUGIN_URL` is resolved via `plugins_url()` which uses WordPress' own plugin registration — not the filesystem path. This is reliable.

```
web/app/plugins/my-plugin/     ← WP registration (may be a symlink)
packages/my-plugin/
├── my-plugin.php
└── vendor/estebanforge/hyperblocks/
```

```php
// my-plugin.php — plugin_dir_url() resolves against WP's plugin registration, not the symlink target.

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// bootstrap.php is included by autoload.files — nothing else needed.
```

---

## When HyperBlocks is used as a library nested inside a larger plugin

If your plugin itself ships as a Composer library required by another plugin (a chain like `my-core-plugin` → `estebanforge/hyperblocks`), the bootstrap chain still works. Each level's `bootstrap.php` registers its copy as a candidate. The version with the highest number wins.

The only thing to verify is that your autoloader is loaded before `after_setup_theme` (priority 0) fires. Because `plugins_loaded` fires before `after_setup_theme`, loading the autoloader on `plugins_loaded` is safe.

---

## Manually triggering initialization (edge cases)

In non-WordPress environments (WP-CLI scripts, testing without Brain Monkey) `add_action` may not exist. Guard your initialization:

```php
if (function_exists('add_action')) {
    require_once __DIR__ . '/vendor/autoload.php';
    // bootstrap.php is included by autoload.files
}
```

For PHPUnit tests, define `HYPERBLOCKS_TESTING_MODE` before loading the autoloader to bypass the direct-access guards in source files:

```php
// tests/bootstrap.php
define('ABSPATH', __DIR__ . '/../');
define('HYPERBLOCKS_TESTING_MODE', true);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mocks/wp-mocks.php';
\HyperBlocks\Config::reset();
\HyperBlocks\Config::init();
```

---

## Relationship with HyperFields bootstrap

HyperBlocks' `bootstrap.php` includes HyperFields' `bootstrap.php` from the vendored path when it is not already loaded:

```
vendor/estebanforge/hyperblocks/bootstrap.php
  └── requires vendor/estebanforge/hyperfields/bootstrap.php
```

HyperFields' own guards (`HYPERFIELDS_BOOTSTRAP_LOADED`, `HYPERFIELDS_INSTANCE_LOADED`) prevent double-initialization. If the HyperFields standalone plugin is active, it registers its copy as a candidate first; HyperBlocks' vendored copy also registers — whichever version is higher wins and initializes. Both copies coexist safely.

If you are building a plugin that requires both HyperBlocks and HyperFields as direct Composer dependencies, requiring only `estebanforge/hyperblocks` is sufficient — HyperFields is pulled in transitively and bootstrapped automatically.

---

## Checking initialization state

```php
// Has the winning instance been selected and initialized?
if (defined('HYPERBLOCKS_INSTANCE_LOADED')) {
    // Safe to use HyperBlocks classes
}

// Which version won?
echo HYPERBLOCKS_LOADED_VERSION;

// Has bootstrap.php been included at least once?
if (defined('HYPERBLOCKS_BOOTSTRAP_LOADED')) {
    // autoloader is available
}
```
