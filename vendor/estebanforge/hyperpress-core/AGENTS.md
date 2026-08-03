## Project Overview

HyperPress-Core is the Composer library that contains the HyperPress runtime.

It owns:
- API routing and rendering
- Asset loading for HTMX, Alpine, Datastar
- Admin options and compatibility logic
- Block integration and HyperFields/HyperBlocks wiring

## Scope Rules

- Keep `api-for-htmx` as a thin WordPress plugin adapter.
- Put runtime logic changes in this package (`HyperPress-Core`).
- Preserve backwards compatibility for public hooks, constants, and helper functions.

## Installation & loading

**As the WordPress.org plugin:** install "HyperPress" (package `api-for-htmx`, which bundles this library). Nothing else to do; the plugin activates the runtime.

**As a Composer library** (`estebanforge/hyperpress-core`): vendor it inside a host plugin and require that plugin's own autoloader. HyperPress-Core self-initializes via `HyperPress\Bootstrap::init()` (scheduled at `after_setup_theme`, priority 0, behind a first-to-boot guard) and also pulls in and bootstraps HyperFields and HyperBlocks.

```php
// my-plugin.php
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
```

The `Settings → HyperPress` page is hidden in library mode; re-enable with `add_filter('hyperpress/admin/show_menu', '__return_true')`. Full install/host-plugin details: [docs/installation.md](docs/installation.md) and [docs/developer-configuration.md](docs/developer-configuration.md).

## Bedrock / Composer-managed WordPress sites

When this library is installed **transitively** into a Bedrock-style project, Composer places it in the project **root `vendor/`** (outside `wp-content/`), because the package is `type: library` and Bedrock's `installer-paths` only route `wordpress-plugin` / `wordpress-muplugin` / `wordpress-theme` types. That root-vendor copy is not under any web-accessible WordPress content root, so its frontend assets (HTMX/Alpine/Datastar) cannot be served.

`Bootstrap::init()` still runs from such a copy — it does **not** gate boot on web-reachability. `Config::$pluginUrl` is simply empty, and asset enqueues bail gracefully instead of emitting 404ing URLs; the rest of the runtime (REST routing, rendering, options) is unaffected. The `bootstrap.php` ABSPATH guard also keeps a root-vendor copy from bootstrapping early in Bedrock's `wp-config` load order. For assets to serve, load the library from a web-reachable copy.

**Recommended pattern for plugins that bundle HyperPress-Core:** ship it inside the plugin's own committed `vendor/` (e.g. `wp-content/plugins/<your-plugin>/vendor/estebanforge/hyperpress-core/`) and load the plugin's own `vendor/autoload.php`. That copy is web-reachable, so `Config::$pluginUrl` resolves and assets enqueue. Do not rely on a Bedrock root-vendor copy to serve assets; it never can.
