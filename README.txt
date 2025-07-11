=== Hypermedia API for WordPress ===
Contributors: tcattd
Tags: hypermedia, ajax, htmx, hyperscript, alpinejs, datastar
Stable tag: 2.0.1
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

An unofficial WordPress plugin that enables the use of Hypermedia on your WordPress site, theme, and/or plugins. Intended for software developers.

== Description ==
An unofficial WordPress plugin that enables the use of Hypermedia on WordPress. Adds a new endpoint `/wp-html/v1/` from which you can load any Hypermedia template.

Hypermedia is a concept that extends the idea of hypertext by allowing for more complex interactions and data representations. It enables the use of AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes, without writing any JavaScript. It reuses an "old" concept, [Hypermedia](https://hypermedia.systems/), to handle the modern web in a more HTML-like and natural way.

Check the [full feature set at here](https://github.com/EstebanForge/Hypermedia-API-WordPress).

This plugin include several Hypermedia libraries by default, locally from the plugin folder. Currently, it includes:

- [HTMX](https://htmx.org/) with [Hyperscript](https://hyperscript.org/).
- [Alpine Ajax](https://alpine-ajax.js.org/) with [Alpine.js](https://alpinejs.dev/).
- [Datastar](https://data-star.dev/).

The plugin has an opt-in option, not enforced, to include these third-party libraries from a CDN (using the unpkg.com service). You must explicitly enable this option for privacy and security reasons.

== Installation ==
1. Install Hypermedia-API-WordPress from WordPress repository. Plugins > Add New > Search for: Hypermedia API or API-for-HTMX. Activate it.
2. Configure Hypermedia-API-WordPress at Settings > Hypermedia API.
3. Enjoy.

== Frequently Asked Questions ==
= Where is the FAQ? =
You can [read the full FAQ at GitHub](https://github.com/EstebanForge/Hypermedia-API-WordPress/blob/main/FAQ.md).

= Suggestions, Support? =
Please, open [a discussion](https://github.com/EstebanForge/Hypermedia-API-WordPress/discussions).

= Found a Bug or Error? =
Please, open [an issue](https://github.com/EstebanForge/Hypermedia-API-WordPress/issues).

== Screenshots ==
1. Main options page.

== Upgrade Notice ==
Nothing to see here.

== Changelog ==
[Check the changelog at GitHub](https://github.com/EstebanForge/Hypermedia-API-WordPress/blob/master/CHANGELOG.md).
