=== API for HTMX ===
Contributors: tcattd
Tags: htmx, ajax, hypermedia, hyperscript, alpine, alpinejs
Stable tag: 0.9.0
Requires at least: 6.3
Tested up to: 6.5
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

An un-official WordPress plugin that adds HTMX to your WordPress site. Intended for software developers.

== Description ==
An un-official WordPress plugin that adds HTMX to your WordPress site. It enables a new endpoint /wp-htmx/v1/ from where you can load any HTMX template.

HTMX is a javascript library that allows you to access AJAX, WebSockets and Server Sent Events directly in HTML, using attributes, without writing any javascript.
It re-uses an "old" concept, [Hypermedia](https://hypermedia.systems/), to deal the modern web in a more HTML like and natural way.

Check the [full feature set at here](https://github.com/TCattd/HTMX-API-WP).

This plugin will include HTMX library by default, locally from the plugin folder. If you enable Alpine.js and/or Hyperscript, it will include them too, also locally.

The plugin has the option, opt-in and not enforced, to include this 3rd party libraries from CDN (using unpkg.com service). You must explicitly enable it to use it, for privacy and security reasons.

== Installation ==
1. Install API-for-HTMX from WordPress repository. Plugins > Add New > Search for: API-for-HTMX. Activate it.
2. Configure API-for-HTMX at Settings > HTMX Options.
3. Enjoy.

== Frequently Asked Questions ==
= Where is the FAQ? =
You can [read the full FAQ at GitHub](https://github.com/TCattd/HTMX-API-WP/blob/master/FAQ.md).

= Suggestions, Support? =
Please, open [a discussion](https://github.com/TCattd/HTMX-API-WP/discussions).

= Found a Bug or Error? =
Please, open [an issue](https://github.com/TCattd/HTMX-API-WP/issues).

== Screenshots ==
1. Main options page.

== Upgrade Notice ==
Nothing to see here.

== Changelog ==
[Check the changelog at GitHub](https://github.com/TCattd/HTMX-API-WP/blob/master/CHANGELOG.md).
