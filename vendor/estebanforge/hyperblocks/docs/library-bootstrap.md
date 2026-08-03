# HyperBlocks — Library Bootstrap

How HyperBlocks initializes itself, and how to configure it when vendored inside another plugin or theme.

---

## How bootstrap works

HyperBlocks self-initializes via `HyperBlocks\WordPress\Bootstrap::init()`, which carries a first-to-boot guard (the Carbon Fields pattern). The goal is to let multiple copies of HyperBlocks coexist (a theme and a plugin both ship it) while only one actually bootstraps.

**Sequence**:

1. Composer includes `bootstrap.php` via `autoload.files`.
2. `bootstrap.php` schedules `WordPress\Bootstrap::init()` at `after_setup_theme` (priority 0).
3. The first copy to reach `init()` claims the namespace-scoped constant `HyperBlocks\WordPress\LOADED` and wins; it sets `Config` runtime identity and hooks block registration.
4. Every later copy sees `LOADED` defined and returns before doing any work.

The guard is built with `__NAMESPACE__`, so it is prefix-safe: unprefixed copies share `HyperBlocks\WordPress\LOADED` and elect one winner; a namespace-prefixed copy (Mozart) lives under a different namespace, defines a different constant, and boots independently with real isolation. First-to-boot wins, not newest. Prefix if you need version determinism across divergent copies.

`bootstrap.php` also triggers HyperFields' bootstrap from the vendored copy when running standalone, so requiring `estebanforge/hyperblocks` is the only step needed; you do not bootstrap HyperFields separately.

Runtime identity lives on `HyperBlocks\Config` (prefix-safe), not global constants:

- `Config::VERSION` - semantic version (mirrors `composer.json`)
- `Config::$abspath` - library root path with trailing slash, set at init
- `Config::$pluginUrl` - public URL with trailing slash, or `''` when not web-reachable
- `Config::$pluginFile` - absolute path to the bootstrap file

HyperBlocks defines no `HYPERBLOCKS_*` constants.

---

## Standard usage — flat vendor directory

The common case: a plugin requires `estebanforge/hyperblocks` and loads its own Composer autoloader.

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
// No further initialization is required; blocks may be registered from init onwards.

add_action('init', function (): void {
    $block = \HyperBlocks\Block\Block::make('My Block')
        ->setName('my-plugin/my-block')
        ->addFields([\HyperBlocks\Block\Field::make('text', 'heading', 'Heading')])
        ->setRenderTemplateFile('blocks/my-block.hb.php');
    \HyperBlocks\Registry::getInstance()->registerFluentBlock($block);
});
```

---

## Usage inside a class (plugins_loaded pattern)

When your plugin defers setup to a bootstrap class, define constants at the top of the main plugin file so URL resolution works after `plugins_loaded`.

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

In setups where the plugins directory is outside the standard `wp-content/plugins` path, or where plugin directories are symlinks, asset URLs resolve against the web-accessible WordPress content roots (plugins, mu-plugins, content, active theme dirs). `Bootstrap::init()` always runs regardless of reachability — it does not gate boot on the URL. When a copy sits outside every content root (e.g. a Bedrock root composer vendor outside the document root), `Config::$pluginUrl` is empty and the editor-asset enqueue bails gracefully: it logs the "not reachable from any web-accessible WordPress content root" notice and returns, so fluent blocks still render on the front end but will not appear in the inserter. The `bootstrap.php` ABSPATH guard also prevents a root-vendor copy from scheduling `init()` ahead of a plugin-bundled copy in Bedrock's load order. For the inserter to work, load HyperBlocks from a web-reachable copy bundled inside a plugin under `wp-content/`.

```
web/app/plugins/my-plugin/     <- WP registration (may be a symlink)
packages/my-plugin/
├── my-plugin.php
└── vendor/estebanforge/hyperblocks/
```

```php
// my-plugin.php

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// bootstrap.php is included by autoload.files; nothing else needed.
```

---

## Manually triggering initialization (edge cases)

A single-consumer plugin may skip the `after_setup_theme` scheduling and call init directly, which runs immediately and is still guarded against double-init by `LOADED` and `Config::isInitialized()`:

```php
add_action('plugins_loaded', static function (): void {
    require_once MY_PLUGIN_DIR . 'vendor/autoload.php';
    \HyperBlocks\WordPress\Bootstrap::init();
}, 0);
```

In non-WordPress environments (WP-CLI scripts, testing without Brain Monkey) `add_action` may not exist. Guard the autoloader load:

```php
if (function_exists('add_action')) {
    require_once __DIR__ . '/vendor/autoload.php';
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

HyperFields' own first-to-boot `LOADED` guard prevents double-initialization. If the HyperFields standalone plugin is active alongside a vendored copy, each is prefix-isolated and boots independently.

Requiring only `estebanforge/hyperblocks` is sufficient; HyperFields is pulled in transitively and bootstrapped automatically.

---

## Checking initialization state

```php
use HyperBlocks\Config;

// Has this copy been bootstrapped?
if (Config::isInitialized()) {
    // Safe to use HyperBlocks classes; Config::$abspath is set.
}

// Library root path and URL (prefix-safe, empty until init runs).
$root = Config::$abspath;
$url  = Config::$pluginUrl;
```
