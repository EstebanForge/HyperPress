# Developer Configuration

This guide centralizes developer-focused setup and integration previously found in the README. It covers asset management, plugin integration, programmatic configuration, and advanced overrides.

## Managing Frontend Libraries

For developers, the plugin includes npm scripts to download the latest versions of all libraries locally:

```bash
# Update all libraries
npm run update-all

# Update specific library
npm run update-htmx
npm run update-alpinejs
npm run update-hyperscript
npm run update-datastar
npm run update-all
```

This ensures your local development environment stays in sync with the latest library versions.

## Using Hypermedia Libraries in your plugin

You can definitely use hypermedia libraries and HyperPress for WordPress in your plugin. You are not limited to using it only in your theme.

The plugin provides the filter: `hyperpress/register_template_path`

This filter allows you to register a new template path for your plugin or theme. It expects an associative array where keys are your chosen namespaces and values are the absolute paths to your template directories.


For example, if your plugin slug is `my-plugin`, you can register a new template path like this:

```php
add_filter( 'hyperpress/render/register_template_path', function( $paths ) {
    // Ensure YOUR_PLUGIN_PATH is correctly defined, e.g., plugin_dir_path( __FILE__ )
    // 'my-plugin' is the namespace.
    $paths['my-plugin'] = YOUR_PLUGIN_PATH . 'hypermedia/';

    return $paths;
});
```

Assuming `YOUR_PLUGIN_PATH` is already defined and points to your plugin's root directory, the above code registers the `my-plugin` namespace to point to `YOUR_PLUGIN_PATH/hypermedia/`.

Then, you can use the new template path in your plugin like this, using a colon `:` to separate the namespace from the template file path (which can include subdirectories):

```php
// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/template-name.hp.php
echo hp_get_endpoint_url( 'my-plugin:template-name' );

// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/parts/header.hp.php
echo hp_get_endpoint_url( 'my-plugin:parts/header' );
```

This will output the URL for the template from the path associated with the `my-plugin` namespace. If the namespace is not registered, or the template file does not exist within that registered path (or is not allowed due to sanitization rules), the request will result in a 404 error. Templates requested with an explicit namespace do not fall back to the theme's default `hypermedia` directory.

For templates located directly in your active theme's `hypermedia` directory (or its subdirectories), you would call them without a namespace:

```php
// Loads: wp-content/themes/your-theme/hypermedia/live-search.hp.php
echo hp_get_endpoint_url( 'live-search' );

// Loads: wp-content/themes/your-theme/hypermedia/subfolder/my-listing.hp.php
echo hp_get_endpoint_url( 'subfolder/my-listing' );
```

## Using as a Composer Library (Programmatic Configuration)

If you require this project as a Composer dependency, it will automatically be loaded. The `bootstrap.php` file is registered in `composer.json` and ensures that the plugin's bootstrapping logic is safely included only once, even if multiple plugins or themes require it. You do not need to manually `require` or `include` any file.

### Detecting Library Mode

The plugin exposes a helper function `hp_is_library_mode()` to detect if it is running as a library (not as an active plugin). This is determined automatically based on whether the plugin is in the active plugins list and whether it is running in the admin area.

When in library mode, the plugin will not register its admin options/settings page in wp-admin. To opt in, return a truthy value from the `hyperpress/admin/show_menu` filter:

```php
// Library consumer: enable the HyperPress admin page
add_filter('hyperpress/admin/show_menu', '__return_true');
```

### Programmatic Configuration via Filters

You can configure the plugin programmatically using WordPress filters instead of using the admin interface. This is particularly useful when the plugin is used as a library or when you want to force specific configurations.

All plugin settings can be controlled using the `hyperpress/options` filter. **This is the canonical entry point and always wins** — it runs after the stored database option has been read, so library consumers do not need to call `update_option()` to override values that the admin page (or another plugin) may have saved.

```php
add_filter('hyperpress/options', function (array $options): array {
    // General Settings
    $options['active_library'] = 'htmx'; // 'htmx', 'alpinejs', or 'datastar'
    $options['load_from_cdn'] = true;    // `true` to use CDN, `false` for local files

    // HTMX Core Settings
    $options['load_hyperscript'] = true;
    $options['load_alpinejs_with_htmx'] = false;
    $options['set_htmx_hxboost'] = false;
    $options['load_htmx_backend'] = false;

    // Alpine Ajax Settings
    $options['load_alpinejs_backend'] = false;

    // Datastar Settings
    $options['load_datastar_backend'] = false;

    // HTMX Extensions - Enable by setting to `1` (or `true`)
    $options['load_extension_ajax-header'] = 0;
    $options['load_extension_alpine-morph'] = 0;
    $options['load_extension_class-tools'] = 0;
    $options['load_extension_client-side-templates'] = 0;
    $options['load_extension_debug'] = 0;
    $options['load_extension_disable-element'] = 0;
    $options['load_extension_event-header'] = 0;
    $options['load_extension_head-support'] = 0;
    $options['load_extension_include-vals'] = 0;
    $options['load_extension_json-enc'] = 0;
    $options['load_extension_loading-states'] = 0;
    $options['load_extension_method-override'] = 0;
    $options['load_extension_morphdom-swap'] = 0;
    $options['load_extension_multi-swap'] = 0;
    $options['load_extension_path-deps'] = 0;
    $options['load_extension_preload'] = 0;
    $options['load_extension_remove-me'] = 0;
    $options['load_extension_response-targets'] = 0;
    $options['load_extension_restored'] = 0;
    $options['load_extension_sse'] = 0;
    $options['load_extension_ws'] = 0;

    return $options;
});
```

The filter receives the merged options array (defaults + stored DB values) and is the LAST step in the resolution chain. The keys you can set include the full set documented under [Common Configuration Examples](#common-configuration-examples) below.

#### Reading Options in Code

Use the `hp_get_options()` helper to read the resolved options array from anywhere:

```php
$active = hp_get_options()['active_library']; // 'htmx', 'alpinejs', or 'datastar'

if (hp_get_options()['load_extension_sse'] ?? 0) {
    // SSE extension enabled; register SSE endpoints...
}
```

For single-value reads, use `hp_get_option($key, $default = null)` instead — it wraps `hp_get_options()` with `??` semantics so missing or null keys return the default:

```php
$active = hp_get_option('active_library', 'datastar');
$sse_on = (bool) hp_get_option('load_extension_sse', 0);
```

Both helpers call the same resolver as the internal `Main::getOptions()`, `Config::getOptions()`, and `Assets::getOptions()` — values stay in sync across subsystems.

#### Reacting After Configuration

The `hyperpress/configured` action fires once per request after `Main::run()` has resolved the final options array. Use it when you need to perform setup that depends on the merged configuration:

```php
add_action('hyperpress/configured', function (array $options): void {
    if (($options['active_library'] ?? '') === 'datastar') {
        // Register Datastar-specific SSE endpoints, log, etc.
    }
});
```

#### Common Configuration Examples

**Complete HTMX Setup with Extensions:**
```php
add_filter('hyperpress/options', function (array $options): array {
    $options['active_library'] = 'htmx';
    $options['load_from_cdn'] = false; // Use local files
    $options['load_hyperscript'] = 1;
    $options['set_htmx_hxboost'] = 1; // Progressive enhancement
    $options['load_htmx_backend'] = 1; // Use in admin too

    // Enable commonly used HTMX extensions
    $options['load_extension_debug'] = 1;
    $options['load_extension_loading-states'] = 1;
    $options['load_extension_preload'] = 1;
    $options['load_extension_sse'] = 1;

    return $options;
});
```

**Alpine Ajax Setup:**
```php
add_filter('hyperpress/options', function (array $options): array {
    $options['active_library'] = 'alpinejs';
    $options['load_from_cdn'] = true; // Use CDN for latest version
    $options['load_alpinejs_backend'] = 1;

    return $options;
});
```

**Datastar Configuration:**
```php
add_filter('hyperpress/options', function (array $options): array {
    $options['active_library'] = 'datastar';
    $options['load_from_cdn'] = false;
    $options['load_datastar_backend'] = 1;

    return $options;
});
```

**Production-Ready Configuration (CDN with specific extensions):**
```php
add_filter('hyperpress/options', function (array $options): array {
    $options['active_library'] = 'htmx';
    $options['load_from_cdn'] = true; // Better performance
    $options['load_hyperscript'] = 1;
    $options['set_htmx_hxboost'] = 1;

    // Enable production-useful extensions
    $options['load_extension_loading-states'] = 1;
    $options['load_extension_preload'] = 1;
    $options['load_extension_response-targets'] = 1;

    return $options;
});
```

#### Deprecated Filters

The legacy filters below are still honored for backwards compatibility but should not be used in new code. Both are applied to the defaults BEFORE the database option is read, so a stored option always wins over them — meaning library consumers would otherwise need to call `update_option()` after the filter to take effect. `hyperpress/options` (above) runs LAST and avoids that footgun.

- `hyperpress/config/default_options` — used to filter defaults before DB read (Config subsystem)
- `hyperpress/assets/default_options` — used to filter defaults before DB read (Assets subsystem)

Migration is mechanical: rename the filter, change the parameter name from `$defaults` to `$options`, and the body remains the same. WP will emit a deprecation notice via `_deprecated_filter()` the first time each legacy filter fires.

## Register Custom Template Paths

Register custom template paths for your plugin or theme:

```php
add_filter('hyperpress/render/register_template_path', function($paths) {
    $paths['my-plugin'] = plugin_dir_path(__FILE__) . 'hypermedia/';
    $paths['my-theme'] = get_template_directory() . '/custom-hypermedia/';
    return $paths;
});
```

## Override Invalid Route Response

When a template is missing or an invalid route is requested, you can return a simple HTML response or a `.html`/`.htm` file path. This only runs for these error types: `missing-template-name`, `invalid-route`, `template-not-found`.

```php
add_filter('hyperpress/render/invalid_route_output', function($output, $error_type, $template_name, $template_path) {
    return plugin_dir_path(__FILE__) . 'hypermedia/invalid-route.html';
}, 10, 4);
```

```php
add_filter('hyperpress/render/invalid_route_output', function($output, $error_type, $template_name) {
    return '<h1>Not found</h1><p>The requested template is missing.</p>';
}, 10, 3);
```

## Customize Sanitization

Modify the sanitization process for parameters:

```php
// Customize parameter key sanitization
add_filter('hyperpress/render/sanitize_param_key', function($sanitized_key, $original_key) {
    // Custom sanitization logic
    return $sanitized_key;
}, 10, 2);

// Customize parameter value sanitization
add_filter('hyperpress/render/sanitize_param_value', function($sanitized_value, $original_value) {
    // Custom sanitization logic for single values
    return $sanitized_value;
}, 10, 2);

// Customize array parameter value sanitization
add_filter('hyperpress/render/sanitize_param_array_value', function($sanitized_array, $original_array) {
    // Custom sanitization logic for array values
    return array_map('esc_html', $sanitized_array);
}, 10, 2);
```

## Customize Asset Loading

For developers who need fine-grained control over where JavaScript libraries are loaded from, the plugin provides filters to override asset URLs for all libraries. These filters work in both plugin and library mode, giving you complete flexibility.

**Available Asset Filters:**

- `hyperpress/assets/htmx_url` - Override HTMX library URL
- `hyperpress/assets/htmx_version` - Override HTMX library version
- `hyperpress/assets/hyperscript_url` - Override Hyperscript library URL
- `hyperpress/assets/hyperscript_version` - Override Hyperscript library version
- `hyperpress/assets/alpinejs_url` - Override Alpine.js library URL
- `hyperpress/assets/alpinejs_version` - Override Alpine.js library version
- `hyperpress/assets/alpine_ajax_url` - Override Alpine Ajax library URL
- `hyperpress/assets/alpine_ajax_version` - Override Alpine Ajax library version
- `hyperpress/assets/datastar_url` - Override Datastar library URL
- `hyperpress/assets/datastar_version` - Override Datastar library version
- `hyperpress/assets/htmx_extension_url` - Override HTMX extension URLs
- `hyperpress/assets/htmx_extension_version` - Override HTMX extension versions

**Filter Parameters:**

Each filter receives the following parameters:
- `$url` - Current URL (CDN or local)
- `$load_from_cdn` - Whether CDN loading is enabled
- `$asset` - Asset configuration array with `local_url` and `local_path`
- `$is_library_mode` - Whether running in library mode

For HTMX extensions, additional parameters:
- `$ext_slug` - Extension slug (e.g., 'loading-states', 'sse')

**Common Use Cases:**

**Load from Custom CDN:**
```php
// Use your own CDN for all libraries
add_filter('hyperpress/assets/htmx_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    return 'https://your-cdn.com/js/htmx@2.0.3.min.js';
}, 10, 4);

add_filter('hyperpress/assets/datastar_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    return 'https://your-cdn.com/js/datastar@1.0.0.min.js';
}, 10, 4);
```

**Custom Local Paths for Library Mode:**
```php
// Override asset URLs when running as library with custom vendor structure
add_filter('hyperpress/assets/htmx_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if ($is_library_mode) {
        // Load from your custom assets directory
        return content_url('plugins/my-plugin/assets/htmx/htmx.min.js');
    }
    return $url;
}, 10, 4);

add_filter('hyperpress/assets/datastar_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if ($is_library_mode) {
        return content_url('plugins/my-plugin/assets/datastar/datastar.min.js');
    }
    return $url;
}, 10, 4);
```

**Version-Specific Loading:**
```php
// Force specific versions for compatibility
add_filter('hyperpress/assets/alpinejs_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    return 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js';
}, 10, 4);

add_filter('hyperpress/assets/alpinejs_version', function($version, $load_from_cdn, $asset, $is_library_mode) {
    return '3.13.0';
}, 10, 4);
```

**Conditional Loading Based on Environment:**
```php
// Different sources for different environments
add_filter('hyperpress/assets/datastar_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if (wp_get_environment_type() === 'production') {
        return 'https://your-production-cdn.com/datastar.min.js';
    } elseif (wp_get_environment_type() === 'staging') {
        return 'https://staging-cdn.com/datastar.js';
    } else {
        // Development - use local file
        return $asset['local_url'];
    }
}, 10, 4);
```

**HTMX Extensions from Custom Sources:**
```php
// Override specific HTMX extensions
add_filter('hyperpress/assets/htmx_extension_url', function($url, $ext_slug, $load_from_cdn, $is_library_mode) {
    // Load SSE extension from custom source
    if ($ext_slug === 'sse') {
        return 'https://your-custom-cdn.com/htmx-extensions/sse.js';
    }

    // Load all extensions from your CDN
    return "https://your-cdn.com/htmx-extensions/{$ext_slug}.js";
}, 10, 4);
```

**Library Mode with Custom Vendor Directory Detection:**
```php
// Handle non-standard vendor directory structures
add_filter('hyperpress/assets/htmx_url', function($url, $load_from_cdn, $asset, $is_library_mode) {
    if ($is_library_mode && empty($url)) {
        // Custom detection for non-standard paths
        $plugin_path = plugin_dir_path(__FILE__);
        if (strpos($plugin_path, '/vendor-custom/') !== false) {
            $custom_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $plugin_path);
            return $custom_url . 'assets/libs/htmx.min.js';
        }
    }
    return $url;
}, 10, 4);
```

**Complete Asset Override Example:**
```php
// Override all hypermedia library URLs for a custom setup
function my_plugin_override_hypermedia_assets() {
    $base_url = 'https://my-custom-cdn.com/hypermedia/';

    // HTMX
    add_filter('hyperpress/assets/htmx_url', function() use ($base_url) {
        return $base_url . 'htmx@2.0.3.min.js';
    });

    // Hyperscript
    add_filter('hyperpress/assets/hyperscript_url', function() use ($base_url) {
        return $base_url . 'hyperscript@0.9.12.min.js';
    });

    // Alpine.js
    add_filter('hyperpress/assets/alpinejs_url', function() use ($base_url) {
        return $base_url . 'alpinejs@3.13.0.min.js';
    });

    // Alpine Ajax
    add_filter('hyperpress/assets/alpine_ajax_url', function() use ($base_url) {
        return $base_url . 'alpine-ajax@1.3.0.min.js';
    });

    // Datastar
    add_filter('hyperpress/assets/datastar_url', function() use ($base_url) {
        return $base_url . 'datastar@1.0.0.min.js';
    });

    // HTMX Extensions
    add_filter('hyperpress/assets/htmx_extension_url', function($url, $ext_slug) use ($base_url) {
        return $base_url . "htmx-extensions/{$ext_slug}.js";
    }, 10, 2);
}

// Apply overrides only in library mode
add_action('plugins_loaded', function() {
    if (function_exists('hp_is_library_mode') && hp_is_library_mode()) {
        my_plugin_override_hypermedia_assets();
    }
});
```

These filters provide maximum flexibility for developers who need to:
- Host libraries on their own CDN for performance/security
- Use custom builds or versions
- Handle non-standard vendor directory structures
- Implement environment-specific loading strategies
- Ensure asset availability in complex deployment scenarios

## Re-enable the Admin Settings Page in Library Mode

When HyperPress-Core is loaded as a Composer library (no `hyperpress.php` or `api-for-htmx.php` plugin entry point active), the `Settings → HyperPress` page is hidden by default. Configure everything programmatically via filters, or opt back in by returning a truthy value from the `hyperpress/admin/show_menu` filter:

```php
// In your plugin or theme, before the after_setup_theme hook fires
add_filter('hyperpress/admin/show_menu', '__return_true');
```

The filter is only consulted in library mode; plugin users see no behavior change. The same filter is the gate `HyperPress\Admin\Options::isEnabled()` exposes for programmatic introspection:

```php
if (class_exists('\\HyperPress\\Admin\\Options') && \HyperPress\Admin\Options::isEnabled()) {
    // The admin page will render; safe to assume the menu is registered
}
```

See [Library Detection](./library-detection.md) for the full `hp_is_library_mode()` reference.
