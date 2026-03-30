# Installation

Install it directly from the WordPress.org plugin repository. On the plugins install page, search for: HyperPress (or Hypermedia)

Or download the zip from the [official plugin repository](https://wordpress.org/plugins/api-for-htmx/) and install it from your WordPress plugins install page.

Activate the plugin. Configure it to your liking on Settings > HyperPress.

## Installation via Composer
If you want to use this plugin as a library, you can install it via Composer. This allows you to use hypermedia libraries in your own plugins or themes, without the need to install this plugin.

```bash
composer require estebanforge/hyperpress
```

This plugin/library will determine which instance of itself is the newer one when WordPress is loading. Then, it will use the newer instance between all competing plugins or themes. This is to avoid conflicts with other plugins or themes that may be using the same library for their Hypermedia implementation.
