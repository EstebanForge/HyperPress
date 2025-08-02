# Changelog

# 2.1.0 / 2025-XX-YY
- **NEW:** **HyperBlocks** - Revolutionary PHP-based block creation system with **two complementary approaches**
  - **Fluent API**: Carbon Fields-style PHP-only block development (no JavaScript required)
  - **block.json**: WordPress-standard JSON blocks (no custom JavaScript required)
  - **Unified Editor**: Both approaches use the same React editor component for consistent UX
  - **Zero JavaScript**: Build complex Gutenberg blocks using only PHP
- **NEW:** Auto-discovery system for blocks in `/hyperblocks/` directories
- **NEW:** Server-side rendering engine with secure PHP template execution
- **NEW:** Custom component system with `<RichText>` and `<InnerBlocks>` support
- **NEW:** REST API endpoints for dynamic block field definitions and live previews
- **NEW:** Reusable field groups for consistent block development
- **NEW:** Comprehensive documentation and demo blocks included
- **NEW:** Backward compatible with existing WordPress block ecosystem

# 2.0.7 / 2025-08-02
- **IMPROVEMENT:** Added a `hmapi/before_template_load` action hook that fires before each hypermedia template is loaded, providing a centralized point for common template preparation logic. Thanks @eduwass.
- **FIX:** Added `stripslashes_deep()` to the `hm_ds_read_signals()` function to remove WordPress "magic quotes" slashes from GET requests, ensuring proper JSON decoding for Datastar signals. Thanks @eduwass.
- Updated Datastar JS library to the latest version.
- Updated Datastar PHP SDK to the latest version.

# 2.0.6 / 2025-07-23
- **FIX:** Updated Datastar.js enqueue to use `wp_enqueue_script_module()` for proper ES module support (WordPress 6.5+). Thanks @eduwass for the report.

# 2.0.5 / 2025-07-11
- **NEW:** Added a suite of Datastar helper functions (`hm_ds_*`) to simplify working with Server-Sent Events (SSE), including functions for patching elements, managing signals, and executing scripts.
- **IMPROVEMENT:** The admin settings page now dynamically displays tabs based on the selected active library (HTMX, Alpine Ajax, or Datastar), reducing UI clutter.
- **REFACTOR:** Centralized plugin option management by introducing a `get_options()` method in the main plugin class.
- **REFACTOR:** Improved the structure of the admin options page for better maintainability and separation of concerns.
- **FIX:** Several bugfixes and improvements.

# 2.0.0 / 2025-06-06
- Renamed plugin to "HyperPress: Modern Hypermedia for WordPress" to reflect broader support for multiple hypermedia libraries.
- **NEW:** Added support for Datastar.js hypermedia library.
- **NEW:** Added support for Alpine Ajax hypermedia library.
- **NEW:** Template engine now supports both `.hm.php` (primary) and `.htmx.php` (legacy) extensions.
- **NEW:** Template engine now supports both `hypermedia` (primary) and `htmx-templates` (legacy) theme directories.
- **NEW:** Added `hm_get_endpoint_url()` helper function to get the API endpoint URL.
- **NEW:** Added `hmapi_enpoint_url()` helper function to echo the API endpoint URL in templates.
- **NEW:** Added `hm_is_library_mode()` helper function to detect when plugin is running as a Composer library.
- **NEW:** Comprehensive programmatic configuration via `hmapi/default_options` filter for all plugin settings.
- **NEW:** Library mode automatically hides admin interface when plugin is used as a Composer dependency.
- **NEW:** Enhanced Composer library integration with automatic version conflict resolution.
- **NEW:** Fixed Strauss namespace prefixing to include WPSettings template files via `override_autoload` configuration.
- **IMPROVED:** Enhanced admin interface with a new informational card displaying the API endpoint URL.
- **IMPROVED:** The `$hmvals` variable is now available in templates, containing the request parameters.
- **IMPROVED:** Better detection of library vs plugin mode based on WordPress active_plugins list.
- **IMPROVED:** Complete documentation for programmatic configuration with real-world examples.
- **BACKWARD COMPATIBILITY:** All `hxwp_*` functions are maintained as deprecated aliases for `hmapi_*` functions.
- **BACKWARD COMPATIBILITY:** The legacy `$hxvals` variable is still available in templates for backward compatibility.
- **BACKWARD COMPATIBILITY:** Dual nonce system supports both `hmapi_nonce` (new) and `hxwp_nonce` (legacy).
- **BACKWARD COMPATIBILITY:** Legacy filter hooks (`hxwp/`) are preserved alongside new `hmapi/` prefixed filters.
- **BACKWARD COMPATIBILITY:** The plugin now intelligently sends the correct nonce with the request header, ensuring compatibility with legacy themes.
- **DOCUMENTATION:** Updated `README.md` with comprehensive library usage guide and reorganized structure for better flow.

# 1.3.0 / 2025-05-11
- Updated HTMX, HTMX extensions, Hyperscript and Alpine.js to their latest versions.
- Added the ability to use this plugin as a library, using composer. This allows you to use HTMX in your own plugins or themes, without the need to install this plugin. The plugin/library will determine if a greater instance of itself is already loaded. If so, it will use that instance. Otherwise, it will load a new one. So, no issues with multiple instances of the same library on different plugins or themes.
- Added a new way to load different HTMX templates path, using the filter `hxwp/register_template_path`. This allows you to register a new template path for your plugin or theme, without overriding the default template path, or stepping on the toes of other plugins or themes that may be using the same template path. This is useful if you want to use HTMX in your own plugin or theme, without having to worry about conflicts with other plugins or themes.
- Introduced a colon (`:`) as the explicit separator for namespaced template paths (e.g., `my-plugin:path/to/template`). This provides a clear distinction between plugin/theme-specific templates and templates in the active theme's default `htmx-templates` directory. Requests for explicitly namespaced templates that are not found will result in a 404 and will not fall back to the theme's default directory.
- Now using PSR-4 autoloading for the plugin's code.

# 1.0.0 / 2024-08-25
- Promoted plugin to stable :)
- Updated to HTMX and its extensions.
- Added a helper to validate requests. Automatically handles nonce and action validation.

# 0.9.1 / 2024-07-05
- Released on WordPress.org official plugins repository.

# 0.9.0 / 2024-06-30
- Updated to HTMX 2.0.0
- More WP.org plugin guidelines compliance.

# 0.3.2 / 2024-05-26
- More WP.org plugin guidelines compliance.

# 0.3.1 / 2024-05-15
- Fixed a bug in the wp_localize_script() call. Thanks @mwender for the report.

# 0.3.0 / 2024-05-07
- WP.org plugin guidelines compliance.
- Changed hxwp_send_header_response() behavior to include a nonce by default. First argument is the nonce. Second argument, an array with the data. Check the htmx-demo.htmx.php template for an updated example.

# 0.2.0 / 2024-04-26
- Added [Alpine.js](https://alpinejs.dev/) support. Now you can use HTMX with Alpine.js, Hyperscript, or both.

# 0.1.15 / 2024-04-13
- Fixes sanitization for form elements that allows multiple values. Thanks @mwender for the report. [Discussion #8](https://github.com/EstebanForge/HTMX-API-WP/discussions/8).

# 0.1.14 / 2024-03-06
- Added option to add the `hx-boost` (true) attribute to any enabled theme, automatically. This enables HTMX's boost feature, globally. Learn more [here](https://htmx.org/attributes/hx-boost/).

# 0.1.12 / 2024-02-22
- Added Composer support. Thanks @mwender!
- Fixed a bug on how the plugin obtains the active theme path. Thanks again @mwender for the report and fix :)
- Added a filter to allow the user to change the default path for the HTMX templates. Thanks @mwender for the suggestion.

# 0.1.11 / 2024-02-21
- Added WooCommerce compatibility. Thanks @carlosromanxyz for the suggestion.

# 0.1.10 / 2024-02-20
- Added a showcase/demo theme to demonstrate how to use HTMX with WordPress. The theme is available at [EstebanForge/HTMX-WordPress-Theme](https://github.com/EstebanForge/HTMX-WordPress-Theme).
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
