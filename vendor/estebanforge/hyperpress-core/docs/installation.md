# Installation

Install it directly from the WordPress.org plugin repository. On the plugins install page, search for: HyperPress (or Hypermedia)

Or download the zip from the [official plugin repository](https://wordpress.org/plugins/api-for-htmx/) and install it from your WordPress plugins install page.

Activate the plugin. Configure it to your liking on Settings > HyperPress.

## Installation via Composer
If you want to use this plugin as a library, you can install it via Composer. This allows you to use hypermedia libraries in your own plugins or themes, without the need to install this plugin.

```bash
composer require estebanforge/hyperpress-core
```

HyperPress-Core self-initializes via `HyperPress\Bootstrap::init()`, scheduled at `after_setup_theme` (priority 0). A namespace-scoped first-to-boot guard means the first copy loaded wins; there is no multi-instance version election and no Jetpack autoloader dependency. Runtime identity (version, paths, URL, endpoint) lives on `HyperPress\Config` as prefix-safe class constants and static properties, not global `define()` constants. Prefixing the namespace (e.g. with Mozart) gives each consumer a fully isolated copy.

When installed as a Composer library the `Settings → HyperPress` page is hidden by default. Configure via filters, or re-enable the admin UI with `add_filter('hyperpress/admin/show_menu', '__return_true');` — see [Developer Configuration](./developer-configuration.md#re-enable-the-admin-settings-page-in-library-mode).

### Vendoring inside a host plugin

HyperPress-Core bundles HyperFields and HyperBlocks as Composer dependencies and explicitly requires their `bootstrap.php` files, so their initialization hooks fire when HyperPress-Core loads. When you vendor HyperPress-Core inside a host plugin, require the host `vendor/autoload.php` (Composer's stock autoloader runs the `autoload.files` chain, which loads each library's `bootstrap.php`).

If your host plugin uses a classmap-only autoloader that skips Composer `autoload.files`, trigger the chain explicitly on `plugins_loaded`:

```php
// my-plugin.php
add_action('plugins_loaded', static function (): void {
    $files = [
        MY_PLUGIN_PATH . 'vendor/estebanforge/hyperpress-core/bootstrap.php',
        MY_PLUGIN_PATH . 'vendor/estebanforge/hyperfields/bootstrap.php',
        MY_PLUGIN_PATH . 'vendor/estebanforge/hyperblocks/bootstrap.php',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
        }
    }
}, 0);
```

Each `bootstrap.php` schedules its own `Bootstrap::init()` at `after_setup_theme` (priority 0) with a namespace-scoped first-to-boot guard, so requiring all three is safe and idempotent.

## Bedrock-style sites

HyperPress-Core is `type: library`, so when a Bedrock project requires it transitively Composer installs it in the project **root `vendor/`**, outside `wp-content/`. That copy is not under any web-accessible WordPress content root, so its frontend assets (HTMX/Alpine/Datastar) cannot be served.

`Bootstrap::init()` still runs from such a copy — it does not gate boot on web-reachability. `Config::$pluginUrl` is simply empty and asset enqueues bail gracefully (no 404ing URLs); the rest of the runtime (REST routing, rendering, options) is unaffected. The bootstrap scheduler runs before the `ABSPATH` guard, so the root-vendor copy initializes reliably even in Bedrock's `wp-config` load order: when `add_action()` is not yet available it writes the `after_setup_theme` registration straight into `$GLOBALS['wp_filter']` in the preinitialized-hooks format. On a dual-copy Bedrock site (root `vendor/` plus a plugin-bundled copy) the root copy wins the `autoload.files` race; remove it via Composer `replace` + `composer update --lock` (never `rm`) so the web-reachable copy wins.

The recommended fix is the normal plugin-bundling pattern above: ship HyperPress-Core inside a host plugin's committed `vendor/` under `wp-content/` and require that plugin's `vendor/autoload.php`. That copy is web-reachable, so assets enqueue. Do not rely on the Bedrock root-vendor copy to serve assets.
