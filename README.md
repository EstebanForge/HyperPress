# HTMX API for WordPress

An unofficial WordPress plugin that enables the use of [HTMX](https://htmx.org) on your WordPress site, theme, and/or plugins. Intended for software developers.

Adds a new endpoint `/wp-htmx/v1/` from which you can load any HTMX template.

<div align="center">

[![HTMX API for WordPress Demo](https://img.youtube.com/vi/6mrRA5QIcRw/0.jpg)](https://www.youtube.com/watch?v=6mrRA5QIcRw "HTMX API for WordPress Demo")

<small>

[Check the video](https://www.youtube.com/watch?v=6mrRA5QIcRw)

</small>

</div>

## HTMX what?

HTMX is a JavaScript library that allows you to access AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes, without writing any JavaScript. It reuses an "old" concept, [Hypermedia](https://hypermedia.systems/), to handle the modern web in a more HTML-like and natural way.

Unless you're trying to build a Google Docs clone or a competitor, HTMX allows you to build modern web applications, even SPAs, without the need to write a single line of JavaScript.

For a better explanation and demos, check the following video:

<div align="center">

[![You don't need a frontend framework by Andrew Schmelyun](https://img.youtube.com/vi/Fuz-jLIo2g8/0.jpg)](https://www.youtube.com/watch?v=Fuz-jLIo2g8)

</div>

## Why mix it with WordPress?

Because I share the same sentiment as Carson Gross, the creator of HTMX, that the software stack used to build the web today has become too complex without good reason (most of the time). And, just like him, I also want to see the world burn.

(Seriously) Because HTMX is awesome, and WordPress is awesome (sometimes). So, why not?

I'm using this in production for a few projects, and it's working great, stable, and ready to use. So, I decided to share it with the world.

I took this idea out of the tangled mess it was inside a project and made it into a standalone plugin that should work for everyone.

It might have some bugs, but the idea is to open it up and improve it over time.

So, if you find any bugs, please report them.

## Installation

Install it directly from the WordPress.org plugin repository. On the plugins install page, search for: HTMX API

Or download the zip from the [official plugin repository](https://wordpress.org/plugins/api-for-htmx/) and install it from your WordPress plugins install page.

Activate the plugin. Configure it to your liking on Settings > HTMX Options.

### Installation via Composer
If you want to use this plugin as a library, you can install it via Composer. This allows you to use HTMX in your own plugins or themes, without the need to install this plugin.

```bash
composer require estebanforge/api-for-htmx
```

This plugin/library will determine which instance of itself is the newer one when WordPress is loading. Then, it will use the newer instance between all competing plugins or themes. This is to avoid conflicts with other plugins or themes that may be using the same library for their HTMX implementation.

## How to use

After installation, you can use HTMX templates in any theme.

This plugin will include the HTMX library by default, locally from the plugin folder. If you enable Alpine.js and/or Hyperscript, they will also be included locally.

The plugin has an opt-in option, not enforced, to include these third-party libraries from a CDN (using the unpkg.com service). You must explicitly enable this option for privacy and security reasons.

Create an `htmx-templates` folder in your theme's root directory. This plugin includes a demo folder that you can copy to your theme. Don't put your templates inside the demo folder located in the plugin's directory, because it will be deleted when you update the plugin.

Inside your `htmx-templates` folder, create as many templates as you want. All files must end with `.htmx.php`.

For example:

```
htmx-templates/live-search.htmx.php
htmx-templates/related-posts.htmx.php
htmx-templates/private/author.htmx.php
htmx-templates/private/author-posts.htmx.php
```

Check the demo template at `htmx-templates/demo.htmx.php` to see how to use it.

Then, in your theme, use HTMX to GET/POST to the `/wp-htmx/v1/` endpoint corresponding to the template you want to load, without the `.htmx.php` extension:

```
/wp-htmx/v1/live-search
/wp-htmx/v1/related-posts
/wp-htmx/v1/private/author
/wp-htmx/v1/private/author-posts
```

### Helper

You can use the `hxwp_api_url()` helper function to generate the URL for your HTMX templates. This function will automatically add the `/wp-htmx/v1/` prefix and remove the `.htmx.php` extension that files must have to be loaded by the API.

For example:

```php
echo hxwp_api_url( 'live-search' );
```

Will call the template located at:

```
/htmx-templates/live-search.htmx.php
```
And will load it from the URL:

```
http://your-site.com/wp-htmx/v1/live-search
```

This will output:

```
http://your-site.com/wp-htmx/v1/live-search
```

### How to pass data to the template

You can pass data to the template using URL parameters (GET/POST). For example:

```
/wp-htmx/v1/live-search?search=hello
/wp-htmx/v1/related-posts?category_id=5
```

All of those parameters (with their values) will be available inside the template as an array named: `$hxvals`

### No Swap response templates

HTMX allows you to use templates that don't return any HTML but perform some processing in the background on your server. These templates can still send a response back (using HTTP headers) if desired. Check [Swapping](https://htmx.org/docs/#swapping) for more info.

For this purpose, and for convenience, you can use the `noswap/` folder/endpoint. For example:

```
/wp-htmx/v1/noswap/save-user?user_id=5&name=John&last_name=Doe
/wp-htmx/v1/noswap/delete-user?user_id=5
```

In this examples, the `save-user` and `delete-user` templates will not return any HTML, but will do some processing in the background. They will be loaded from the `htmx-templates/noswap` folder.

```
htmx-templates/noswap/save-user.htmx.php
htmx-templates/noswap/delete-user.htmx.php
```

You can pass data to these templates in the exact same way as you do with regular templates.

Nothing stops you from using regular templates to do the same thing or using another folder altogether. You can mix and match or organize your templates in any way you want. This is mentioned here just as a convenience feature for those who want to use it.

### HTMX extensions (and Hyperscrypt / Alpine.js)

This plugin comes with [HTMX](https://htmx.org) already integrated and enabled.

You can enable any HTMX extension in the plugin's options page: Settings > HTMX Options.

You can also enable [Hyperscript](https://hyperscript.org) and/or [Alpine.js](https://alpinejs.dev) in the same options page.

## Using HTMX in your plugin

You can definitely use HTMX and this HTMX API for WordPress in your plugin. You are not limited to using it only in your theme.

The plugin provides the filter: `hxwp/register_template_path`

This filter allows you to register a new template path for your plugin or theme. It expects an associative array where keys are your chosen namespaces and values are the absolute paths to your template directories.

For example, if your plugin slug is `my-plugin`, you can register a new template path like this:

```php
add_filter( 'hxwp/register_template_path', function( $paths ) {
    // Ensure YOUR_PLUGIN_PATH is correctly defined, e.g., plugin_dir_path( __FILE__ )
    // 'my-plugin' is the namespace.
    $paths['my-plugin'] = YOUR_PLUGIN_PATH . 'htmx-templates/';

    return $paths;
});
```

Assuming `YOUR_PLUGIN_PATH` is already defined and points to your plugin's root directory, the above code registers the `my-plugin` namespace to point to `YOUR_PLUGIN_PATH/htmx-templates/`.

Then, you can use the new template path in your plugin like this, using a colon `:` to separate the namespace from the template file path (which can include subdirectories):

```php
// Loads the template from: YOUR_PLUGIN_PATH/htmx-templates/template-name.htmx.php
echo hxwp_api_url( 'my-plugin:template-name' );

// Loads the template from: YOUR_PLUGIN_PATH/htmx-templates/parts/header.htmx.php
echo hxwp_api_url( 'my-plugin:parts/header' );
```

This will load the template from the path associated with the `my-plugin` namespace. If the namespace is not registered, or the template file does not exist within that registered path (or is not allowed due to sanitization rules), the request will result in a 404 error. Templates requested with an explicit namespace do not fall back to the theme's default `htmx-templates` directory.

For templates located directly in your active theme's `htmx-templates` directory (or its subdirectories), you would call them without a namespace:

```php
// Loads: wp-content/themes/your-theme/htmx-templates/live-search.htmx.php
echo hxwp_api_url( 'live-search' );

// Loads: wp-content/themes/your-theme/htmx-templates/subfolder/my-listing.htmx.php
echo hxwp_api_url( 'subfolder/my-listing' );
```

## Security

Every call to the `wp-htmx` endpoint will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all HTMX requests automatically, using HTMX's own `htmx:configRequest` event.

If you are new to HTMX, please read the [security section](https://htmx.org/docs/#security) of the official documentation. Remember that HTMX requires you to validate and sanitize any data you receive from the user. This is something developers used to do all the time, but it seems to have been forgotten by newer generations of software developers.

If you are not familiar with how WordPress recommends handling data sanitization and escaping, please read the [official documentation](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) on [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/).

### REST Endpoint

The plugin will perform basic sanitization of calls to the new REST endpoint, `wp-htmx`, to avoid security issues like directory traversal attacks. It will also limit access so you can't use it to access any file outside the `htmx-templates` folder within your own theme.

The parameters and their values passed to the endpoint via GET or POST will be sanitized with `sanitize_key()` and `sanitize_text_field()`, respectively.

Filters `hxwp/sanitize_param_key` and `hxwp/sanitize_param_value` are available to modify the sanitization process if needed.

Do your due diligence and ensure you are not returning unsanitized data back to the user or using it in a way that could pose a security issue for your site. HTMX requires that you validate and sanitize any data you receive from the user. Don't forget that.

## Examples

Check out the showcase/demo theme at [EstebanForge/HTMX-WordPress-Theme](https://github.com/EstebanForge/HTMX-WordPress-Theme).

## Suggestions, Support

Please, open [a discussion](https://github.com/EstebanForge/HTMX-API-WP/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/EstebanForge/HTMX-API-WP/issues).

## FAQ
[FAQ available here](https://github.com/EstebanForge/HTMX-API-WP/blob/main/FAQ.md).

## Changelog

[Changelog available here](https://github.com/EstebanForge/HTMX-API-WP/blob/main/CHANGELOG.md).
