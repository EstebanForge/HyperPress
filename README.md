# Hypermedia API for WordPress

An unofficial WordPress plugin that enables the use of [HTMX](https://htmx.org), [Alpine AJAX](https://alpine-ajax.js.org/), [Datastar](https://data-star.dev/) and other hypermedia libraries on your WordPress site, theme, and/or plugins. Intended for software developers.

Adds a new endpoint `/wp-html/v1/` from which you can load any hypermedia template.

<div align="center">

[![Hypermedia API for WordPress Demo](https://img.youtube.com/vi/6mrRA5QIcRw/0.jpg)](https://www.youtube.com/watch?v=6mrRA5QIcRw "Hypermedia API for WordPress Demo")

<small>

[Check the video](https://www.youtube.com/watch?v=6mrRA5QIcRw)

</small>

</div>

## Hypermedia what?

[Hypermedia](https://hypermedia.systems/) is a "new" concept that allows you to build modern web applications, even SPAs, without the need to write a single line of JavaScript. A forgotten concept that was popular in the 90s and early 2000s, but has been forgotten by newer generations of software developers.

HTMX, Alpine Ajax and Datastar are JavaScript libraries that allows you to access AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes, without writing any JavaScript.

Unless you're trying to build a Google Docs clone or a competitor, Hypermedia allows you to build modern web applications, even SPAs, without the need to write a single line of JavaScript.

For a better explanation and demos, check the following video:

<div align="center">

[![You don't need a frontend framework by Andrew Schmelyun](https://img.youtube.com/vi/Fuz-jLIo2g8/0.jpg)](https://www.youtube.com/watch?v=Fuz-jLIo2g8)

</div>

## Why mix it with WordPress?

Because I share the same sentiment as Carson Gross, the creator of HTMX, that the software stack used to build the web today has become too complex without good reason (most of the time). And, just like him, I also want to see the world burn.

(Seriously) Because Hypermedia is awesome, and WordPress is awesome (sometimes). So, why not?

I'm using this in production for a few projects, and it's working great, stable, and ready to use. So, I decided to share it with the world.

I took this idea out of the tangled mess it was inside a project and made it into a standalone plugin that should work for everyone.

It might have some bugs, but the idea is to open it up and improve it over time.

So, if you find any bugs, please report them.

## Installation

Install it directly from the WordPress.org plugin repository. On the plugins install page, search for: Hypermedia API

Or download the zip from the [official plugin repository](https://wordpress.org/plugins/api-for-htmx/) and install it from your WordPress plugins install page.

Activate the plugin. Configure it to your liking on Settings > Hypermedia API.

### Installation via Composer
If you want to use this plugin as a library, you can install it via Composer. This allows you to use hypermedia libraries in your own plugins or themes, without the need to install this plugin.

```bash
composer require estebanforge/hypermedia-api-wordpress
```

This plugin/library will determine which instance of itself is the newer one when WordPress is loading. Then, it will use the newer instance between all competing plugins or themes. This is to avoid conflicts with other plugins or themes that may be using the same library for their Hypermedia implementation.

## How to use

After installation, you can use hypermedia templates in any theme.

This plugin will include the active hypermedia library by default, locally from the plugin folder. Libraries like HTMX, Alpine.js, Hyperscript, and Datastar are supported.

The plugin has an opt-in option, not enforced, to include these third-party libraries from a CDN (using the unpkg.com service). You must explicitly enable this option for privacy and security reasons.

Create a `hypermedia` folder in your theme's root directory. This plugin includes a demo folder that you can copy to your theme. Don't put your templates inside the demo folder located in the plugin's directory, because it will be deleted when you update the plugin.

Inside your `hypermedia` folder, create as many templates as you want. All files must end with `.hm.php`.

For example:

```
hypermedia/live-search.hm.php
hypermedia/related-posts.hm.php
hypermedia/private/author.hm.php
hypermedia/private/author-posts.hm.php
```

Check the demo template at `hypermedia/demo.hm.php` to see how to use it.

Then, in your theme, use your Hypermedia library to GET/POST to the `/wp-html/v1/` endpoint corresponding to the template you want to load, without the file extension:

```
/wp-html/v1/live-search
/wp-html/v1/related-posts
/wp-html/v1/private/author
/wp-html/v1/private/author-posts
```

### Helper Functions

You can use the `hmapi_get_endpoint_url()` helper function to generate the URL for your hypermedia templates. This function will automatically add the `/wp-html/v1/` prefix. The hypermedia file extension (`.hm.php`) is not needed, the API will resolve it automatically.

For example:

```php
echo hmapi_get_endpoint_url( 'live-search' );
```

Or,

```php
hmapi_endpoint_url( 'live-search' );
```

Will call the template located at:

```
/hypermedia/live-search.hm.php
```
And will load it from the URL:

```
http://your-site.com/wp-html/v1/live-search
```

This will output:

```
http://your-site.com/wp-html/v1/live-search
```

#### Backward Compatibility

For backward compatibility, the old `hxwp_api_url()` function is still available as an alias for `hmapi_get_endpoint_url()`. However, we recommend updating your code to use the new function names as the old ones are deprecated and may be removed in future versions.

Other helper functions available:
- `hmapi_send_header_response()` / `hxwp_send_header_response()` (deprecated alias)
- `hmapi_die()` / `hxwp_die()` (deprecated alias)
- `hmapi_validate_request()` / `hxwp_validate_request()` (deprecated alias)

### How to pass data to the template

You can pass data to the template using URL parameters (GET/POST). For example:

```
/wp-html/v1/live-search?search=hello
/wp-html/v1/related-posts?category_id=5
```

All of those parameters (with their values) will be available inside the template as an array named: `$hmvals`.

### No Swap response templates

Hypermedia libraries allow you to use templates that don't return any HTML but perform some processing in the background on your server. These templates can still send a response back (using HTTP headers) if desired. Check [Swapping](https://htmx.org/docs/#swapping) for more info.

For this purpose, and for convenience, you can use the `noswap/` folder/endpoint. For example:

```
/wp-html/v1/noswap/save-user?user_id=5&name=John&last_name=Doe
/wp-html/v1/noswap/delete-user?user_id=5
```

In this examples, the `save-user` and `delete-user` templates will not return any HTML, but will do some processing in the background. They will be loaded from the `hypermedia/noswap` folder.

```
hypermedia/noswap/save-user.hm.php
hypermedia/noswap/delete-user.hm.php
```

You can pass data to these templates in the exact same way as you do with regular templates.

Nothing stops you from using regular templates to do the same thing or using another folder altogether. You can mix and match or organize your templates in any way you want. This is mentioned here just as a convenience feature for those who want to use it.

### Choosing a Hypermedia Library

This plugin comes with [HTMX](https://htmx.org), [Alpine Ajax](https://alpine-ajax.js.org/) and [Datastar](https://data-star.dev/) already integrated and enabled.

You can choose which library to use in the plugin's options page: Settings > Hypermedia API.

In the case of HTMX, you can also enable any of its extensions in the plugin's options page: Settings > Hypermedia API.

#### Local vs CDN Loading

The plugin includes local copies of all libraries for privacy and offline development. You can choose to load from:

1. **Local files** (default): Libraries are served from your WordPress installation
2. **CDN**: Optional CDN loading from jsdelivr.net. Will always load the latest version of the library.

#### Build System Integration

For developers, the plugin includes npm scripts to download the latest versions of all libraries locally:

```bash
# Download all libraries
npm run download:all

# Download specific library
npm run download:htmx
npm run download:alpine
npm run download:hyperscript
npm run download:datastar
npm run download:all
```

This ensures your local development environment stays in sync with the latest library versions.

## Using Hypermedia Libraries in your plugin

You can definitely use hypermedia libraries and this Hypermedia API for WordPress in your plugin. You are not limited to using it only in your theme.

The plugin provides the filter: `hmapi/register_template_path`

This filter allows you to register a new template path for your plugin or theme. It expects an associative array where keys are your chosen namespaces and values are the absolute paths to your template directories.

For example, if your plugin slug is `my-plugin`, you can register a new template path like this:

```php
add_filter( 'hmapi/register_template_path', function( $paths ) {
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
echo hmapi_get_endpoint_url( 'my-plugin:template-name' );

// Loads the template from: YOUR_PLUGIN_PATH/hypermedia/parts/header.hm.php
echo hmapi_get_endpoint_url( 'my-plugin:parts/header' );
```

This will output the URL for the template from the path associated with the `my-plugin` namespace. If the namespace is not registered, or the template file does not exist within that registered path (or is not allowed due to sanitization rules), the request will result in a 404 error. Templates requested with an explicit namespace do not fall back to the theme's default `hypermedia` directory.

For templates located directly in your active theme's `hypermedia` directory (or its subdirectories), you would call them without a namespace:

```php
// Loads: wp-content/themes/your-theme/hypermedia/live-search.hm.php
echo hmapi_get_endpoint_url( 'live-search' );

// Loads: wp-content/themes/your-theme/hypermedia/subfolder/my-listing.hm.php
echo hmapi_get_endpoint_url( 'subfolder/my-listing' );
```

## Using as a Composer Library (Programmatic Configuration)

If you include this plugin as a Composer dependency in your own plugin or theme, it will automatically avoid loading multiple copies and only the latest version will be initialized.

### Detecting Library Mode

The plugin exposes a helper function `hmapi_is_library_mode()` to detect if it is running as a library (not as an active plugin). This is determined automatically based on whether the plugin is in the active plugins list and whether it is running in the admin area.

When in library mode, the plugin will not register its admin options/settings page in wp-admin.

### Programmatic Configuration via Filters

You can configure the plugin programmatically using WordPress filters instead of using the admin interface. This is particularly useful when the plugin is used as a library or when you want to force specific configurations.

All plugin settings can be controlled using the `hmapi/default_options` filter. This filter allows you to override any default option value:

```php
add_filter('hmapi/default_options', function($defaults) {
    // Configure the active hypermedia library
    $defaults['active_library'] = 'htmx'; // Options: 'htmx', 'alpinejs', 'datastar'

    // Configure CDN loading
    $defaults['load_from_cdn'] = false; // true = CDN, false = local files

    // HTMX-specific settings
    $defaults['load_hyperscript'] = true; // Load Hyperscript with HTMX
    $defaults['load_alpinejs_with_htmx'] = false; // Load Alpine.js with HTMX
    $defaults['set_htmx_hxboost'] = false; // Auto add hx-boost="true" to body
    $defaults['load_htmx_backend'] = false; // Load HTMX in WP Admin

    // Alpine.js settings
    $defaults['load_alpinejs_backend'] = false; // Load Alpine.js in WP Admin

    // Datastar settings
    $defaults['load_datastar_backend'] = false; // Load Datastar in WP Admin

    // HTMX Extensions (enable any extension by setting to true)
    $defaults['load_extension_ajax-header'] = false;
    $defaults['load_extension_alpine-morph'] = false;
    $defaults['load_extension_class-tools'] = false;
    $defaults['load_extension_client-side-templates'] = false;
    $defaults['load_extension_debug'] = false;
    $defaults['load_extension_disable'] = false;
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
    $defaults['load_extension_web-sockets'] = false;
    $defaults['load_extension_ws'] = false;

    return $defaults;
});
```

#### Common Configuration Examples

**Complete HTMX Setup with Extensions:**
```php
add_filter('hmapi/default_options', function($defaults) {
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
add_filter('hmapi/default_options', function($defaults) {
    $defaults['active_library'] = 'alpinejs';
    $defaults['load_from_cdn'] = true; // Use CDN for latest version
    $defaults['load_alpinejs_backend'] = true;

    return $defaults;
});
```

**Datastar Configuration:**
```php
add_filter('hmapi/default_options', function($defaults) {
    $defaults['active_library'] = 'datastar';
    $defaults['load_from_cdn'] = false;
    $defaults['load_datastar_backend'] = true;

    return $defaults;
});
```

**Production-Ready Configuration (CDN with specific extensions):**
```php
add_filter('hmapi/default_options', function($defaults) {
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

#### Register Custom Template Paths

Register custom template paths for your plugin or theme:

```php
add_filter('hmapi/register_template_path', function($paths) {
    $paths['my-plugin'] = plugin_dir_path(__FILE__) . 'hypermedia/';
    $paths['my-theme'] = get_template_directory() . '/custom-hypermedia/';
    return $paths;
});
```

#### Customize Sanitization

Modify the sanitization process for parameters:

```php
// Customize parameter key sanitization
add_filter('hmapi/sanitize_param_key', function($sanitized_key, $original_key) {
    // Custom sanitization logic
    return $sanitized_key;
}, 10, 2);

// Customize parameter value sanitization
add_filter('hmapi/sanitize_param_value', function($sanitized_value, $original_value) {
    // Custom sanitization logic
    return $sanitized_value;
}, 10, 2);
```

#### Disable Admin Interface Completely

If you want to configure everything programmatically and hide the admin interface:

```php
// Force library mode to hide admin interface
add_filter('hmapi/default_options', function($defaults) {
    // Your configuration here
    return $defaults;
});

// Optional: Remove the admin menu entirely (if you have admin access)
add_action('admin_menu', function() {
    remove_submenu_page('options-general.php', 'hypermedia-api-options');
}, 999);
```

#### Environment-Based Configuration

Configure different settings based on environment:

```php
add_filter('hmapi/default_options', function($defaults) {
    if (wp_get_environment_type() === 'production') {
        // Production settings
        $defaults['active_library'] = 'htmx';
        $defaults['load_from_cdn'] = true;
        $defaults['load_extension_debug'] = false;
    } else {
        // Development settings
        $defaults['active_library'] = 'htmx';
        $defaults['load_from_cdn'] = false; // Local files for offline dev
        $defaults['load_extension_debug'] = true;
        $defaults['load_htmx_backend'] = true; // Easier debugging
    }

    return $defaults;
});
```

**Note:** All filters should be added before the `plugins_loaded` action fires, preferably in your plugin's main file or theme's `functions.php`.

## Security

Every call to the `wp-html` endpoint will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all HTMX requests automatically, using HTMX's own `htmx:configRequest` event.

If you are new to Hypermedia, please read the [security section](https://htmx.org/docs/#security) of the official documentation. Remember that Hypermedia requires you to validate and sanitize any data you receive from the user. This is something developers used to do all the time, but it seems to have been forgotten by newer generations of software developers.

If you are not familiar with how WordPress recommends handling data sanitization and escaping, please read the [official documentation](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) on [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/).

### REST Endpoint

The plugin will perform basic sanitization of calls to the new REST endpoint, `wp-html`, to avoid security issues like directory traversal attacks. It will also limit access so you can't use it to access any file outside the `hypermedia` folder within your own theme.

The parameters and their values passed to the endpoint via GET or POST will be sanitized with `sanitize_key()` and `sanitize_text_field()`, respectively.

Filters `hmapi/sanitize_param_key` and `hmapi/sanitize_param_value` are available to modify the sanitization process if needed. For backward compatibility, the old filters `hxwp/sanitize_param_key` and `hxwp/sanitize_param_value` are still supported but deprecated.

Do your due diligence and ensure you are not returning unsanitized data back to the user or using it in a way that could pose a security issue for your site. Hypermedia requires that you validate and sanitize any data you receive from the user. Don't forget that.

## Examples

Check out the showcase/demo theme at [EstebanForge/Hypermedia-Theme-WordPress](https://github.com/EstebanForge/Hypermedia-Theme-WordPress).

## Suggestions, Support

Please, open [a discussion](https://github.com/EstebanForge/hypermedia-api-wordpress/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/EstebanForge/hypermedia-api-wordpress/issues).

## FAQ
[FAQ available here](https://github.com/EstebanForge/hypermedia-api-wordpress/blob/main/FAQ.md).

## Changelog

[Changelog available here](https://github.com/EstebanForge/hypermedia-api-wordpress/blob/main/CHANGELOG.md).

## Contributing

You are welcome to contribute to this plugin.

If you have a feature request or a bug report, please open an issue on the [GitHub repository](https://github.com/EstebanForge/hypermedia-api-wordpress/issues).

If you want to contribute with code, please open a pull request.

## License

This plugin is licensed under the GPLv2 or later.

You can find the full license text in the `license.txt` file.
