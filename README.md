# HTMX API for WordPress

An un-official WordPress plugin that adds [HTMX](https://htmx.org) to your WordPress site.

It enables a new endpoint `/wp-htmx/v1/` from where you can load any HTMX template.

## Why?

Because HTMX is awesome and WordPress is awesome (sometimes). So, why not?

I'm using this in production in a few projects and it's working great, stable and ready to use. So I decided to share it with the world.

## How to use

After installation, you can use HTMX templates in your theme. Any theme.

Create a `templates-htmx` folder in your theme's root directory. This plugin has a demo folder that you can copy to your theme. Don't put your templates inside the demo folder located in the plugin's directory, because it will be deleted when you update the plugin.

Inside your `templates-htmx`, create as many templates as you want. All files must end with `.htmx.php`.

For example:

```
templates-htmx/live-search.htmx.php
templates-htmx/related-posts.htmx.php
templates-htmx/private/author.htmx.php
templates-htmx/private/author-posts.htmx.php
```

Check the demo template at `templates-htmx/demo.htmx.php` to see how to use it.


Then, in your theme, use HTMX to GET/POST to the endpoint corresponding to the template you want to load, without the `.htmx.php` extension:

```
/wp-htmx/v1/live-search
/wp-htmx/v1/related-posts
/wp-htmx/v1/private/author
/wp-htmx/v1/private/author-posts
```

### How to pass data to the template

You can pass data to the template using URL parameters (GET/POST). For example:

```
/wp-htmx/v1/live-search?search=hello
/wp-htmx/v1/related-posts?category_id=5
```

All of those parameters (with their values) will be available inside the template as an array named: `$params`

### HTMX extensions and Hyperscrypt

This plugin comes with [HTMX](https://htmx.org) already integrated and enabled.

You can enable any HTMX extension in the plugin's options page: Settings > HTMX Options.

You can also enable [Hyperscript](https://hyperscript.org) in the same options page.

## Security

Every call to the `wp-htmx` endpoint, will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all HTMX requests automatically, using HTMX own htmx:configRequest event.

### REST Endpoint

The plugin will perform basic sanitization of calls to the new REST endpoint, `wp-htmx`, to avoid security issues, like a directory traversal attack. Also it will limit you so you can't use it to access any file outside the `templates-htmx` folder inside your own theme.

The params and their values passed to the endpoint, vía GET or POST, will be sanitized with `sanitize_key()` and `sanitize_text_field()` respectively.

Filters `hxwp/sanitize_param_key` and `hxwp/sanitize_param_value` are available to modify the sanitization process if needed.

Do your homework and make sure you are returning non sanitized data back to the user or using it in a way that could be a security issue for your site. HTMX requires that you validate and sanitize any data you receive from the user. Don't forget that.

## Suggestions, Support

Please, open [a discussion](https://github.com/TCattd/HTMX-API-WP/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/TCattd/HTMX-API-WP/issues).

## Changelog

[Available here](https://github.com/TCattd/HTMX-API-WP/blob/master/CHANGELOG.md).
