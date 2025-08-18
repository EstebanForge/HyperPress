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
add_filter( 'hyperpress/register_template_path', function( $paths ) {
    // Ensure YOUR_PLUGIN_PATH is correctly defined, e.g., plugin_dir_path( __FILE__ )
    // 'my-plugin' is the namespace.
    $paths['my-plugin'] = YOUR_PLUGIN_PATH . 'hypermedia/';

    return $paths;
});
```

Assuming `YOUR_PLUGIN_PATH` is already defined and points to your plugin's root directory, the above code registers the `my-plugin` namespace to point to `YOUR_PLUGIN_PATH/hypermedia/`.

Then, you can use the new template path in your plugin like this, using a colon `:` to separate the namespace from the template file path (which can include subdirectories):

```php
// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/template-name.hm.php
echo hp_get_endpoint_url( 'my-plugin:template-name' );

// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/parts/header.hm.php
echo hp_get_endpoint_url( 'my-plugin:parts/header' );
```

This will output the URL for the template from the path associated with the `my-plugin` namespace. If the namespace is not registered, or the template file does not exist within that registered path (or is not allowed due to sanitization rules), the request will result in a 404 error. Templates requested with an explicit namespace do not fall back to the theme's default `hypermedia` directory.

For templates located directly in your active theme's `hypermedia` directory (or its subdirectories), you would call them without a namespace:

```php
// Loads: wp-content/themes/your-theme/hypermedia/live-search.hm.php
echo hp_get_endpoint_url( 'live-search' );

// Loads: wp-content/themes/your-theme/hypermedia/subfolder/my-listing.hm.php
echo hp_get_endpoint_url( 'subfolder/my-listing' );
```

## Using as a Composer Library (Programmatic Configuration)

If you require this project as a Composer dependency, it will automatically be loaded. The `bootstrap.php` file is registered in `composer.json` and ensures that the plugin's bootstrapping logic is safely included only once, even if multiple plugins or themes require it. You do not need to manually `require` or `include` any file.

### Detecting Library Mode

The plugin exposes a helper function `hp_is_library_mode()` to detect if it is running as a library (not as an active plugin). This is determined automatically based on whether the plugin is in the active plugins list and whether it is running in the admin area.

When in library mode, the plugin will not register its admin options/settings page in wp-admin.

### Programmatic Configuration via Filters

You can configure the plugin programmatically using WordPress filters instead of using the admin interface. This is particularly useful when the plugin is used as a library or when you want to force specific configurations.

All plugin settings can be controlled using the `hyperpress/default_options` filter. This filter allows you to override any default option value:

```php
add_filter('hyperpress/default_options', function($defaults) {
    // General Settings
    $defaults['active_library'] = 'htmx'; // 'htmx', 'alpinejs', or 'datastar'
    $defaults['load_from_cdn'] = false;  // `true` to use CDN, `false` for local files

    // HTMX Core Settings
    $defaults['load_hyperscript'] = true;
    $defaults['load_alpinejs_with_htmx'] = false;
    $defaults['set_htmx_hxboost'] = false;
    $defaults['load_htmx_backend'] = false;

    // Alpine Ajax Settings
    $defaults['load_alpinejs_backend'] = false;

    // Datastar Settings
    $defaults['load_datastar_backend'] = false;

    // HTMX Extensions - Enable by setting to `true`
    $defaults['load_extension_ajax-header'] = false;
    $defaults['load_extension_alpine-morph'] = false;
    $defaults['load_extension_class-tools'] = false;
    $defaults['load_extension_client-side-templates'] = false;
    $defaults['load_extension_debug'] = false;
    $defaults['load_extension_disable-element'] = false; // Note: key is 'disable-element'
    $defaults['load_extension_event-header'] = false;
    $defaults['load_extension_head-support'] = false;
    $defaults['load_extension_include-vals'] = false;
    $defaults['load_extension_json-enc'] = false;
    $defaults['load_extension_loading-states'] = false;
    $defaults['load_extension_method-override'] = false;
    $defaults['load_extension_morphdom-swap'] = false;
    $defaults['load_extension_multi-swap'] = false;
    $defaults['load_extension_path-deps'] = false;
    $defaults['load_extension_preload'] = false;
    $defaults['load_extension_remove-me'] = false;
    $defaults['load_extension_response-targets'] = false;
    $defaults['load_extension_restored'] = false;
    $defaults['load_extension_sse'] = false;
    $defaults['load_extension_ws'] = false;

    return $defaults;
});
```

#### Common Configuration Examples

**Complete HTMX Setup with Extensions:**
```php
add_filter('hyperpress/default_options', function($defaults) {
    $defaults['active_library'] = 'htmx';
    $defaults['load_from_cdn'] = false; // Use local files
    $defaults['load_hyperscript'] = true;
    $defaults['set_htmx_hxboost'] = true; // Progressive enhancement
    $defaults['load_htmx_backend'] = true; // Use in admin too

    // Enable commonly used HTMX extensions
    $defaults['load_extension_debug'] = true;
    $defaults['load_extension_loading-states'] = true;
    $defaults['load_extension_preload'] = true;
    $defaults['load_extension_sse'] = true;

    return $defaults;
});
```

**Alpine Ajax Setup:**
```php
add_filter('hyperpress/default_options', function($defaults) {
    $defaults['active_library'] = 'alpinejs';
    $defaults['load_from_cdn'] = true; // Use CDN for latest version
    $defaults['load_alpinejs_backend'] = true;

    return $defaults;
});
```

**Datastar Configuration:**
```php
add_filter('hyperpress/default_options', function($defaults) {
    $defaults['active_library'] = 'datastar';
    $defaults['load_from_cdn'] = false;
    $defaults['load_datastar_backend'] = true;

    return $defaults;
});
```

**Production-Ready Configuration (CDN with specific extensions):**
```php
add_filter('hyperpress/default_options', function($defaults) {
    $defaults['active_library'] = 'htmx';
    $defaults['load_from_cdn'] = true; // Better performance
    $defaults['load_hyperscript'] = true;
    $defaults['set_htmx_hxboost'] = true;

    // Enable production-useful extensions
    $defaults['load_extension_loading-states'] = true;
    $defaults['load_extension_preload'] = true;
    $defaults['load_extension_response-targets'] = true;

    return $defaults;
});
```

## Register Custom Template Paths

Register custom template paths for your plugin or theme:

```php
add_filter('hyperpress/register_template_path', function($paths) {
    $paths['my-plugin'] = plugin_dir_path(__FILE__) . 'hypermedia/';
    $paths['my-theme'] = get_template_directory() . '/custom-hypermedia/';
    return $paths;
});
```

## Customize Sanitization

Modify the sanitization process for parameters:

```php
// Customize parameter key sanitization
add_filter('hyperpress/sanitize_param_key', function($sanitized_key, $original_key) {
    // Custom sanitization logic
    return $sanitized_key;
}, 10, 2);

// Customize parameter value sanitization
add_filter('hyperpress/sanitize_param_value', function($sanitized_value, $original_value) {
    // Custom sanitization logic for single values
    return $sanitized_value;
}, 10, 2);

// Customize array parameter value sanitization
add_filter('hyperpress/sanitize_param_array_value', function($sanitized_array, $original_array) {
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

## Disable Admin Interface Completely

If you want to configure everything programmatically and hide the admin interface, define the `HYPERPRESS_LIBRARY_MODE` constant in your `wp-config.php` or a custom plugin file. This will prevent the settings page from being added.

```php
// In wp-config.php or a custom plugin file
define('HYPERPRESS_LIBRARY_MODE', true);

// You can then configure the plugin using filters as needed
add_filter('hyperpress/default_options', function($defaults) {
    // Your configuration here. See above for examples.
    return $defaults;
});
```
