# Changelog

# 0.1.14 / 2024-03-06
- Added option to add the `hx-boost` (true) attribute to any enabled theme, automatically. This enables HTMX's boost feature, globally. Learn more [here](https://htmx.org/attributes/hx-boost/).

# 0.1.12 / 2024-02-22
- Added Composer support. Thanks @mwender!
- Fixed a bug on how the plugin obtains the active theme path. Thanks again @mwender for the report and fix :)
- Added a filter to allow the user to change the default path for the HTMX templates. Thanks @mwender for the suggestion.

# 0.1.11 / 2024-02-21
- Added WooCommerce compatibility. Thanks @carlosromanxyz for the suggestion.

# 0.1.10 / 2024-02-20
- Added a showcase/demo theme to demonstrate how to use HTMX with WordPress. The theme is available at [TCattd/HTMX-WordPress-Theme](https://github.com/TCattd/HTMX-WordPress-Theme).
- hxwp_api_url() helper now accepts a path to be appended to the API URL. Just like WP's home_url().
- Keeps line breaks on sanitization of hxvals. Thanks @texorama!
- Added option to enable HTMX to load at the WordPress backend (wp-admin). Thanks @texorama for the suggestion.

# 0.1.8 / 2024-02-14
- HTMX and Hyperscript are now retrieved using NPM.
- Fixes loading extensions from local/CDN and their paths. Thanks @agencyhub!

# 0.1.7 / 2023-12-27
- Bugfixes.

# 0.1.6 / 2023-12-18
- Merged `noswap/` folder into `htmx-templates/` folder. Now, all templates are inside `htmx-templates/` folder.

# 0.1.5 / 2023-12-15
- Renamed `hxparams` to `hxvals` to match HTMX terminology.
- Added hxwp_die() function to be used on templates (`noswap/` included). This functions will die() the script, but sending a 200 status code so HTMX can process the response and along with a header HX-Error on it, with the message included, so it can be used on the client side.

# 0.1.4 / 2023-12-13
- Renamed `void/` endpoint to `noswap/` to match HTMX terminology, better showing the purpose of this endpoint.
- Better path sanitization for template files.
- Added `hxwp_send_header_response` function to send a Response Header back to the client, to allow for non-visual responses (`noswap/`) to execute some logic on the client side. Refer to the [Response Headers](https://htmx.org/docs/#response-headers) and [HX-Trigger](https://htmx.org/headers/hx-trigger/) sections to know more about this.

# 0.1.3 / 2023-12-04
- Added filters and actions to inject HTMX meta tag configuration. Refer to the [documentation](https://htmx.org/docs/#config) for more information.
- Added new endpoint to wp-htmx to allow non visual responses to be executed, vía /void/ endpoint.

# 0.1.1 / 2023-12-01
- First public release.
