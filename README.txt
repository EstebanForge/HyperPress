=== API for HTMX ===
Contributors: tcattd
Tags: htmx, ajax, hypermedia, hyperscript, alpinejs
Stable tag: 1.2.0
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

An unofficial WordPress plugin that enables the use of HTMX on your WordPress site, theme, and/or plugins. Intended for software developers.

== Description ==
An unofficial WordPress plugin that enables the use of HTMX on WordPress. Adds a new endpoint `/wp-htmx/v1/` from which you can load any HTMX template.

HTMX is a JavaScript library that allows you to access AJAX, WebSockets, and Server-Sent Events directly in HTML using attributes, without writing any JavaScript. It reuses an "old" concept, [Hypermedia](https://hypermedia.systems/), to handle the modern web in a more HTML-like and natural way.

Check the [full feature set at here](https://github.com/EstebanForge/HTMX-API-WP).

This plugin will include the HTMX library by default, locally from the plugin folder. If you enable Alpine.js and/or Hyperscript, they will also be included locally.

The plugin has an opt-in option, not enforced, to include these third-party libraries from a CDN (using the unpkg.com service). You must explicitly enable this option for privacy and security reasons.

== Installation ==
1. Install API-for-HTMX from WordPress repository. Plugins > Add New > Search for: API-for-HTMX. Activate it.
2. Configure API-for-HTMX at Settings > HTMX Options.
3. Enjoy.

== Frequently Asked Questions ==
= Where is the FAQ? =
You can [read the full FAQ at GitHub](https://github.com/EstebanForge/HTMX-API-WP/blob/main/FAQ.md).

= Suggestions, Support? =
Please, open [a discussion](https://github.com/EstebanForge/HTMX-API-WP/discussions).

= Found a Bug or Error? =
Please, open [an issue](https://github.com/EstebanForge/HTMX-API-WP/issues).

== Screenshots ==
1. Main options page.

== Upgrade Notice ==
Nothing to see here.

== Changelog ==
[Check the changelog at GitHub](https://github.com/EstebanForge/HTMX-API-WP/blob/master/CHANGELOG.md).
