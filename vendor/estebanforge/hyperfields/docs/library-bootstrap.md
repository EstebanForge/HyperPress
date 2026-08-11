# LibraryBootstrap — Composer / Vendored Usage

`HyperFields\LibraryBootstrap::init()` is the entry point when HyperFields is
used as a Composer dependency inside another plugin rather than as a standalone
plugin itself.

## When to call it

Call it once, after your autoloader is loaded and before any HyperFields class
is used. The method is idempotent — repeated calls are no-ops.

During bootstrap, HyperFields also initializes transfer-audit logging hooks
automatically (`HyperFields\Transfer\AuditLogger`). No extra setup is required
to start recording export/import audit events.

```php
$autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (class_exists('\HyperFields\LibraryBootstrap')) {
    \HyperFields\LibraryBootstrap::init([
        'plugin_file' => __FILE__,
        'plugin_url'  => plugin_dir_url(__FILE__) . 'vendor/estebanforge/hyperfields/',
    ]);
}
```

## Auto-bootstrap (zero-config)

HyperFields ships a `bootstrap.php` (registered in the library's own
`composer.json` under `autoload.files`) that self-initializes by scheduling
`LibraryBootstrap::init()` on `after_setup_theme` (priority 0). Composer's
files-autoload runs this entry once per process, so loading your plugin's
`vendor/autoload.php` is all that is required: `Config`, `Registry`, `Assets`,
`TemplateLoader`, `Transfer\AuditLogger`, and `CacheInvalidator` all come up
without any consumer code.

This is reliable across every WordPress load order, including the early-load
window where `add_action()` is not yet available. That window occurs when a
drop-in (`object-cache.php`, `advanced-cache.php`), a must-use plugin, or
`wp-config.php` pulls in a Composer autoloader before `wp-includes/plugin.php`
loads (common in Bedrock, where `wp-config.php` requires `vendor/autoload.php`
before `application.php` defines `ABSPATH`). In that window `bootstrap.php`
writes the `after_setup_theme` registration straight into `$GLOBALS['wp_filter']`
in the preinitialized-hooks raw-array format; WordPress core converts that into
a real `WP_Hook` when `plugin.php` loads (`WP_Hook::build_preinitialized_hooks`,
since WP 4.7, Trac #38929). The scheduler runs before the `ABSPATH` guard, so
the registration lands whether or not `ABSPATH` is defined yet.

The asset layer also self-heals when `Config::$pluginUrl` is empty (see
*URL resolution and graceful degradation*): every enqueue resolves its URL
from the library's own root. So on a web-reachable copy the assets load even
if `init()` were somehow delayed.

### Bedrock dual-copy sites

If a Bedrock project has HyperFields in two places at once (a root `vendor/`
copy pulled transitively, plus a copy bundled inside a plugin under
`wp-content/`), the **root** copy wins Composer's `autoload.files` race,
because `wp-config.php` requires the root `vendor/autoload.php` first. Only the
winning bootstrap's code runs, so an updated bootstrap only takes effect when
the winning copy contains it. To make the plugin-bundled (web-reachable) copy
win, remove the root copy with Composer `replace`:

1. Confirm why it is there: `composer why estebanforge/hyperfields`.
2. Add to the **root** `composer.json`:
   `"replace": { "estebanforge/hyperfields": "*" }`.
3. `composer update estebanforge/hyperfields --lock`. Composer removes the
   directory itself and drops the `autoload.files` and classmap entries.

**Never `rm` the root `vendor/` copy.** Composer's files-loader does a bare
`require $file` with no `file_exists` guard, so deleting the file while the
autoload entry survives fatals every request; `composer dump-autoload`
regenerates from `installed.json` (which still lists the package) and re-emits
the dead path. `replace` plus `update --lock` is the only safe removal.

### Explicit override (optional)

Calling `LibraryBootstrap::init()` explicitly after your autoloader is still
supported as an optional deterministic override, for example to pin a specific
`plugin_file` or `plugin_url`. It is idempotent, safe under the cross-copy
election guard, and bypasses the auto-bootstrap entirely. It is no longer
required for correctness.

## Duplicate-load protection

The first copy to reach `init()` claims the namespace-scoped
`HyperFields\LOADED` constant and wins; any later copy bails before
bootstrapping. So two plugins that both ship HyperFields do not double-init or
fatal. This is first-to-boot, not newest-wins. If you need guaranteed
isolation across divergent versions, prefix the namespace with
[Mozart](https://github.com/coenjacobs/mozart). A prefixed copy lives under a
different namespace and boots independently; see the HyperFields repository for
a ready-to-use config.

## Arguments

| Key | Type | Description |
|---|---|---|
| `plugin_file` | `string` | Absolute path to the **host** plugin's main file. Used as the base for URL resolution. |
| `plugin_url` | `string` | Public URL to the HyperFields library root (trailing slash). |
| `base_dir` | `string` | Absolute path to the HyperFields library root. Defaults to the directory containing `LibraryBootstrap.php`. |

## URL resolution and graceful degradation

When `plugin_url` is omitted, `init()` calls `resolve_plugin_url()`, which
delegates to `HyperFields\LibraryBootstrap::resolveContentUrl()`. That resolver
walks the web-accessible WordPress content roots (`WP_PLUGIN_DIR`,
`WPMU_PLUGIN_DIR`, `WP_CONTENT_DIR`, and the active theme template/stylesheet
directories), canonicalising both the query path and each root with
`realpath()` / `wp_normalize_path()`, and returns the first root that prefixes
the library's `base_dir` plus the relative remainder as the URL. It returns
`''` when the library sits under none of them.

`init()` always runs (it does not gate boot on web-reachability): it claims the
namespace identity, loads the procedural API, and registers hooks regardless of
whether the URL resolves. When the copy is not web-reachable,
`Config::$pluginUrl` is simply empty.

Asset enqueues (`TemplateLoader`, `Assets`, `AdminPage`, `OptionsPage`, and
`Admin\ExportImportUI`) do not bail on an empty `Config::$pluginUrl`. They all
route through `LibraryBootstrap::resolveAssetBaseUrl()`, whose final tier
resolves the URL from the library's own root via `resolveContentUrl()`. Admin
and field CSS/JS therefore still enqueue as long as the library directory sits
under a web-accessible content root, even when `init()` never ran. When the
copy is genuinely not web-reachable (for example, a Bedrock root vendor outside
the document root), `resolveContentUrl()` returns `''` and the enqueues bail
rather than emit a 404ing URL.

Server-side functionality (the field registry, options pages, export/import,
cache invalidation, audit logging) is **not** available until `init()` has run:
`Registry`, `Assets`, `TemplateLoader`, `Transfer\AuditLogger`, and
`CacheInvalidator` are all initialized exclusively inside `init()`. With the zero-config bootstrap
(see *Auto-bootstrap (zero-config)*), `init()` runs reliably at
`after_setup_theme` across every load order, so these subsystems come up
without an explicit call. The explicit `init()` call shown above is an optional
override (for example to pin a specific copy or URL), not a requirement.

The scheduler runs **above** the `ABSPATH` guard, so a root-vendor copy's
`bootstrap.php` schedules `init()` even when included before `ABSPATH` is
defined (Bedrock loads the root autoloader in `wp-config`, before `ABSPATH`
exists). On a dual-copy site the root copy wins the `autoload.files` race and
its `init()` runs against whichever class copy wins the SPL election; see
*Bedrock dual-copy sites* under Auto-bootstrap for the `replace` removal.

Pass explicit `plugin_file` and `plugin_url` args when you want to pin a
specific copy as the winner regardless of load order, or when the library
lives in a non-standard location whose URL the resolver cannot infer.

## Examples

### Standard plugin (flat vendor directory)

The most common case: HyperFields vendored directly inside your plugin.

```
wp-content/plugins/my-plugin/
├── my-plugin.php
└── vendor/estebanforge/hyperfields/
```

```php
// my-plugin.php

$autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (class_exists('\HyperFields\LibraryBootstrap')) {
    \HyperFields\LibraryBootstrap::init([
        'plugin_file' => __FILE__,
        'plugin_url'  => plugin_dir_url(__FILE__) . 'vendor/estebanforge/hyperfields/',
    ]);
}
```

### Bootstrapping inside a class (plugins_loaded pattern)

When your plugin defers setup to a bootstrap class hooked on `plugins_loaded`,
pass the constants defined at the top of the main plugin file.

```php
// my-plugin.php

define('MY_PLUGIN_FILE', __FILE__);
define('MY_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    $autoload = plugin_dir_path(MY_PLUGIN_FILE) . 'vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists('\HyperFields\LibraryBootstrap')) {
        \HyperFields\LibraryBootstrap::init([
            'plugin_file' => MY_PLUGIN_FILE,
            'plugin_url'  => MY_PLUGIN_URL . 'vendor/estebanforge/hyperfields/',
        ]);
    }

    MyPlugin\Plugin::get_instance();
});
```

### Monorepo / Bedrock layout with symlinked plugins

In monorepos or Bedrock-style setups the `vendor` directory is often outside
the WP plugins directory, or the plugin directory itself is a symlink. Auto-
detection breaks here. Define constants from the host plugin's own known URL.

```
web/app/plugins/my-plugin/          ← registered with WP (may be a symlink)
packages/my-plugin/
├── my-plugin.php
└── vendor/estebanforge/hyperfields/
```

```php
// my-plugin.php — constants are safe because plugin_dir_url() resolves
// against WP's own plugin registration, not the filesystem path.

\HyperFields\LibraryBootstrap::init([
    'plugin_file' => __FILE__,
    'plugin_url'  => plugin_dir_url(__FILE__) . 'vendor/estebanforge/hyperfields/',
]);
```

### Using the Export/Import UI after bootstrapping

Once `LibraryBootstrap::init()` has run, `ExportImportUI` assets enqueue
correctly. Wire it from `admin_enqueue_scripts` on your specific page only.

```php
add_action('admin_menu', function () {
    $hook = add_submenu_page(
        'my-plugin',
        'Data Tools',
        'Data Tools',
        'manage_options',
        'my-plugin-data-tools',
        'my_plugin_render_data_tools_page'
    );

    add_action('admin_enqueue_scripts', function (string $suffix) use ($hook) {
        if ($suffix === $hook) {
            \HyperFields\Admin\ExportImportUI::enqueuePageAssets();
        }
    });
});

function my_plugin_render_data_tools_page(): void {
    echo \HyperFields\Admin\ExportImportUI::render(
        options: ['my_plugin_options' => 'My Plugin Settings'],
        title:   'Data Tools',
    );
}
```
