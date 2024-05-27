# HTMX API for WordPress

An un-official WordPress plugin that adds [HTMX](https://htmx.org) to your WordPress site.

It enables a new endpoint `/wp-htmx/v1/` from where you can load any HTMX template.

<div align="center">

[![HTMX API for WordPress Demo](https://img.youtube.com/vi/6mrRA5QIcRw/0.jpg)](https://www.youtube.com/watch?v=6mrRA5QIcRw "HTMX API for WordPress Demo")

<small>

[Check the video](https://www.youtube.com/watch?v=6mrRA5QIcRw)

</small>

</div>

## HTMX what?

HTMX is a javascript library that allows you to access AJAX, WebSockets and Server Sent Events directly in HTML, using attributes, without writing any javascript.
It re-uses an "old" concept, [Hypermedia](https://hypermedia.systems/), to deal the modern web in a more HTML like and natural way.

Unless you're trying to build a Google Docs clone a competitor, HTMX allows to build modern web applications, even SPA, without the need to write a single line of javascript.

For a better explanation, and demos, check the following video:

<div align="center">

[![You don't need a frontend framework by Andrew Schmelyun](https://img.youtube.com/vi/Fuz-jLIo2g8/0.jpg)](https://www.youtube.com/watch?v=Fuz-jLIo2g8)

</div>

## Why mix it with WordPress?

Beacause I share the same sentiment as Carson Gross, the creator of HTMX, that the software stack used to build the web today, has become too complex without a good reason (most of the times). And, just as him, I also want to see the world burn.

(Seriously) Because HTMX is awesome and WordPress is awesome (sometimes). So, why not?

I'm using this in production in a few projects and it's working great, stable and ready to use. So I decided to share it with the world.

I just took this idea-thing off from the tangled mess that this was inside a project, and made it into an standalone plugin that should work for everyone.

It might have some bugs. But the idea is to open it up and improve it over time.

So, if you find any bug, please, report it.

## How to use

After installation, you can use HTMX templates in your theme. Any theme.

Create a `htmx-templates` folder in your theme's root directory. This plugin has a demo folder that you can copy to your theme. Don't put your templates inside the demo folder located in the plugin's directory, because it will be deleted when you update the plugin.

Inside your `htmx-templates`, create as many templates as you want. All files must end with `.htmx.php`.

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

### How to pass data to the template

You can pass data to the template using URL parameters (GET/POST). For example:

```
/wp-htmx/v1/live-search?search=hello
/wp-htmx/v1/related-posts?category_id=5
```

All of those parameters (with their values) will be available inside the template as an array named: `$hxvals`

### No Swap response templates

HTMX allows you to use templates that don't return any HTML, but do some processing in the background, on you server. Those templates can still send a response back (using HTTP headers) to be used if desired. Check [Swapping](https://htmx.org/docs/#swapping) for more info.

For this, and for convenience, you can use the `noswap/` folder/endpoint. For example:

```
/wp-htmx/v1/noswap/save-user?user_id=5&name=John&last_name=Doe
/wp-htmx/v1/noswap/delete-user?user_id=5
```

In this examples, the `save-user` and `delete-user` templates will not return any HTML, but will do some processing in the background. They will be loaded from the `htmx-templates/noswap` folder.

```
htmx-templates/noswap/save-user.htmx.php
htmx-templates/noswap/delete-user.htmx.php
```

You can pass data to this templates, in the exact same way as you do with the regular templates.

Nothing stops you from using regular templates to do the same thing, or another folder all together. And mix and match or order your templates in any way you want. This is mentioned here, just as a convenience feature for those that want to use it.

### HTMX extensions (and Hyperscrypt / Alpine.js)

This plugin comes with [HTMX](https://htmx.org) already integrated and enabled.

You can enable any HTMX extension in the plugin's options page: Settings > HTMX Options.

You can also enable [Hyperscript](https://hyperscript.org) and/or [Alpine.js](https://alpinejs.dev) in the same options page.

## Security

Every call to the `wp-htmx` endpoint, will automatically check for a valid nonce. If the nonce is not valid, the call will be rejected.

The nonce itself is auto-generated and added to all HTMX requests automatically, using HTMX own htmx:configRequest event.

If you are new to HTMX, please read the [security section](https://htmx.org/docs/#security) of the official documentation. Don't forget that HTMX requires that you validate and sanitize any data you receive from the user. Something us, devs, used to do all the time, but now it seems to have been forgotten in newer generations of software developers.

If you don't know about how WordPress recommends to do data Sanitization and Escaping, please read the [official documentation](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/), for [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/) and [Escaping Data](https://developer.wordpress.org/apis/security/escaping/).

### REST Endpoint

The plugin will perform basic sanitization of calls to the new REST endpoint, `wp-htmx`, to avoid security issues, like a directory traversal attack. Also it will limit you so you can't use it to access any file outside the `htmx-templates` folder inside your own theme.

The params and their values passed to the endpoint, vía GET or POST, will be sanitized with `sanitize_key()` and `sanitize_text_field()` respectively.

Filters `hxwp/sanitize_param_key` and `hxwp/sanitize_param_value` are available to modify the sanitization process if needed.

Do your homework and make sure you are returning non sanitized data back to the user or using it in a way that could be a security issue for your site. HTMX requires that you validate and sanitize any data you receive from the user. Don't forget that.

## Examples

Check out the showcase/demo theme at [TCattd/HTMX-WordPress-Theme](https://github.com/TCattd/HTMX-WordPress-Theme).

## Suggestions, Support

Please, open [a discussion](https://github.com/TCattd/HTMX-API-WP/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/TCattd/HTMX-API-WP/issues).

## Changelog

[Available here](https://github.com/TCattd/HTMX-API-WP/blob/master/CHANGELOG.md).
