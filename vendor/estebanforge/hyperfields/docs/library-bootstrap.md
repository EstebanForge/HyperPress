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

## Arguments

| Key | Type | Description |
|---|---|---|
| `plugin_file` | `string` | Absolute path to the **host** plugin's main file. Used as the base for URL resolution. |
| `plugin_url` | `string` | Public URL to the HyperFields library root (trailing slash). |
| `base_dir` | `string` | Absolute path to the HyperFields library root. Defaults to the directory containing `LibraryBootstrap.php`. |
| `version` | `string` | Version string used for asset cache-busting. Defaults to the value in the library's `composer.json`. |

## Why explicit args are required for vendored usage

`LibraryBootstrap::init()` has a URL auto-detection fallback: when `plugin_url`
is omitted it calls `resolve_plugin_url()`, which uses `plugin_dir_path()` to
walk up from the library's `base_dir` and find the enclosing WP plugin root.

This works when the library's files sit directly under a path that WordPress
recognises as a plugin directory. It **silently fails** in environments where:

- The vendor directory is symlinked (common in monorepos and some Docker setups).
- The WordPress plugins path differs between local and staging/production
  (e.g. different mount points, Bedrock-style layouts).
- The library is nested more than one level inside a non-standard directory
  structure.

When auto-detection fails, `HYPERFIELDS_PLUGIN_URL` is set to an empty string.
`TemplateLoader::enqueueAssets()` checks for this and returns early, so the
`hyperpress-admin` stylesheet handle is never registered. Any subsequent call to
`wp_add_inline_style('hyperpress-admin', ...)` (e.g. inside
`ExportImportUI::enqueueDiffAssets()`) silently no-ops because WordPress
requires the handle to be registered before inline styles can be attached to it.
The result is a page with no layout CSS — a bug that is easy to miss locally but
reliably breaks on remote environments.

**Always pass explicit `plugin_file` and `plugin_url` args.** Auto-detection
exists only as a convenience for simple, standalone-plugin setups and should not
be relied upon in vendored contexts.

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
    'version'     => '1.2.3', // optional: pin to your vendored version
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
