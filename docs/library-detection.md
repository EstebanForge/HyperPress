# Library Detection Functions

**`hm_is_library_mode(): bool`**

Detects whether the plugin is running as a WordPress plugin or as a Composer library. Useful for conditional functionality.

```php
if (hm_is_library_mode()) {
    // Running as composer library - no admin interface
    // Configure via filters only
    add_filter('hmapi/default_options', function($defaults) {
        $defaults['active_library'] = 'htmx';
        return $defaults;
    });
} else {
    // Running as WordPress plugin - full functionality available
    add_action('admin_menu', 'my_admin_menu');
}

// Datastar-specific library mode configuration
if (hm_is_library_mode()) {
    // Configure Datastar for production use as library
    add_filter('hmapi/default_options', function($defaults) {
        $defaults['active_library'] = 'datastar';
        $defaults['load_from_cdn'] = false; // Use local files for reliability
        $defaults['load_datastar_backend'] = true; // Enable in wp-admin
        return $defaults;
    });

    // Register custom SSE endpoints for the plugin using this library
    add_filter('hmapi/register_template_path', function($paths) {
        $paths['my-plugin'] = plugin_dir_path(__FILE__) . 'datastar-templates/';
        return $paths;
    });
} else {
    // Plugin mode - users can configure via admin interface
    // Add custom Datastar functionality only when running as main plugin
    add_action('wp_enqueue_scripts', function() {
        if (get_option('hmapi_active_library') === 'datastar') {
            wp_add_inline_script('datastar', 'console.log("Datastar ready for SSE!");');
        }
    });
}
```
