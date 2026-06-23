# Changelog

# 3.3.1 / 2026-06-23
- **DEPS:** Refreshed path-repo `composer.lock` references for `estebanforge/hyperfields` and `estebanforge/hyperpress-core` to pull the latest local source (version pins unchanged: hyperfields `1.2.4`, hyperpress-core `1.2.0`). Vendored `estebanforge/hyperblocks` retained at `1.1.3`.
- **DEPS:** `symfony/console` and friends resolved to `^7.4` (down from `v8.1.0`), restoring the `PHP >= 8.2` floor declared in `composer.json`. `symfony/console v8.1.0` hard-required PHP `>=8.4.1` and was pulling the stack above the plugin's declared minimum.

# 3.3.0 / 2026-06-23
- **DEPS:** Updated `estebanforge/hyperpress-core` to 1.2.0.
- **NEW:** Programmatic configuration surface — library consumers (and theme/plugin authors who want code-only config) now have a single canonical filter for tweaking HyperPress options without touching the admin page:
  - `hyperpress/options` filter — applied last in the resolution chain, so it always wins over stored database options. Replaces the two legacy filters (`hyperpress/config/default_options`, `hyperpress/assets/default_options`) which are now deprecated and only consulted as a BC fallback.
  - `hyperpress/configured` action — fires once per request after `Main::run()` has resolved the final options array.
  - `hp_get_options(): array` and `hp_get_option(string $key, mixed $default = null): mixed` helpers — read the merged options from anywhere.
  - Full reference and migration recipe: `HyperPress-Core/docs/developer-configuration.md`.
- **CHANGED:** When HyperPress-Core is consumed as a Composer library (no `hyperpress.php` / `api-for-htmx.php` entry point active), the `Settings → HyperPress` page is hidden by default. Library consumers can opt in by returning a truthy value from the `hyperpress/admin/show_menu` filter. Plugin mode (this adapter active) is unchanged.
- **CHANGED:** Default `active_library` aligned to `datastar` everywhere (previously `Main::getOptions()` defaulted to `htmx` while `Config` and `Assets` defaulted to `datastar`).
- **FIX:** `OptionsResolver::defaults()` now synthesizes HTMX extension option keys with underscores (e.g. `load_extension_head_support`) to match the shape Admin writes and stores. `hp_get_option('load_extension_<key>')` now returns the correct value instead of silently defaulting to 0.
- **FIX:** `Main::$options` is now nullable so any code touching the public property on a frontend request no longer triggers a "Typed property must not be accessed before initialization" fatal.

# 3.2.6 / 2026-04-29
- **DEPS:** Updated `estebanforge/hyperpress-core` to 1.1.8
- **NEW:** Added `hp_is_rate_limited()` — generic, side-effect-free rate limit helper for any HyperPress endpoint. Use this in regular HTML/HTMX/Alpine templates; it does not send headers or SSE responses.
- **FIX:** `hp_ds_is_rate_limited()` no longer sends SSE headers on non-blocked requests. It now delegates the check to `hp_is_rate_limited()` and only sends SSE error feedback when the request is actually rate limited. This fixes the bug where calling `hp_ds_is_rate_limited()` in a regular `.hp.php` template would flip `Content-Type` from `text/html` to `text/event-stream`.
- **DOCS:** Updated `datastar-helpers.md`, `helper-functions.md`, and `security.md` to clearly distinguish `hp_is_rate_limited()` (generic) from `hp_ds_is_rate_limited()` (SSE-only).

# 3.2.5 / 2026-04-28
- **NEW:** Added `hp_ds_send_html()` helper for plain `text/html` responses from Datastar endpoints. Returns raw HTML without SSE framing; Datastar's `@get`/`@post` actions auto-detect `text/html` and morph elements by ID.
- **FIX:** Updated default `datastar_version` from stale `1.0.0-rc.1` to `1.0.1`, matching the CDN bundle already in use.

# 3.2.4 / 2026-04-28
- **FIX:** Datastar PHP SDK namespace references now use upstream `starfederation\datastar\...` class names in bundled HyperPress-Core helpers/runtime checks, restoring compatibility with current `starfederation/datastar-php` autoloading.
- **FIX:** Guarded Jetpack `vendor/autoload_packages.php` loading to WordPress runtime contexts only (`function_exists('wp_normalize_path')`) to prevent CLI/test fatal errors.
- **DEPS:** Added/activated Jetpack autoloader integration in bundled stack.
- **RELEASE:** Updated plugin package version to `3.2.4`.
- **CREDITS:** Thanks @web-maverick1 on GitHub for the heads up.

# 3.2.2 / 2026-04-25
- **FIX:** Fixed multiselect field form submission in HyperFields
  - Added hidden `<select class="hf-multiselect-hidden">` to multiselect template for proper form submission
  - Updated `multiselect-enhanced.js` to prefer sibling hidden select over name-based lookup (with fallback)
  - Resolves issue where multiselect values were not submitted with forms
- **DEPS:** Updated Datastar JS library from 1.0.0-beta.11 to 1.0.1
- **DEPS:** Bumped bundled vendor package metadata (HyperFields, HyperBlocks, HyperPress-Core)
- **RELEASE:** Updated plugin header version to 3.2.2
- **RELEASE:** Updated README.txt stable tag to 3.2.2
- **RELEASE:** Updated SECURITY.md supported version to 3.2.2

# 3.2.0 / 2026-04-14
- **DEPS:** Updated `estebanforge/hyperfields` to 1.2.0 (Major Feature: React Integration)
  - **NEW:** `ReactField` class extends `Field` with modern React-powered UI components for options pages
  - **NEW:** Automatic React asset loading when `ReactField` instances are detected
  - **NEW:** Supports 10 field types with React components: text, textarea, number, email, url, color, image, checkbox, select
  - **NEW:** Uses WordPress `@wordpress/components` for consistent admin UI experience
  - **NEW:** Media library integration for image fields with live thumbnail preview
  - **NEW:** WordPress color picker with alpha channel support for color fields
  - **NEW:** Progressive enhancement approach - HTML fields work as-is, React is opt-in via `ReactField::make()`
  - **NEW:** Zero breaking changes - existing `Field::make()` code continues to work unchanged
  - **NEW:** ReactField API methods: `setReactProp()`, `setReactComponent()`, `setUseReact()`, `getReactComponent()`, `getReactProps()`
  - **NEW:** Build system with Webpack 5 configuration for React asset compilation
  - **NEW:** Enhanced CSS with WooCommerce-inspired design system, CSS variables, responsive design, and dark mode support
  - **NEW:** One-line migration from `Field::make()` to `ReactField::make()` for modern UI
  - **NEW:** Complete documentation: React examples, implementation guide, and version bump guide
  - **NEW:** `composer build-assets` script for standalone asset building
  - **NEW:** `composer production` now automatically builds React assets when npm is available
  - **IMPROVED:** OptionsPage integration with auto-detection and enqueuing of React dependencies
  - **FIX:** ImageField component null check for `wp.media` object with graceful fallback
  - **DX:** Mixed rendering supported - use React for complex fields, HTML for simple ones
- **DEPS:** Updated `estebanforge/hyperblocks` to 1.1.0
  - **NEW:** Context7 integration for AI-powered documentation and code examples lookup
  - **NEW:** `context7.json` configuration package management
- **DEPS:** Updated `estebanforge/hyperpress-core` to 1.1.0
  - **NEW:** Context7 integration for improved documentation discoverability
  - **UPDATED:** Refreshed `composer.lock` with latest dependency upgrades
- **DX:** Enhanced build automation with graceful npm detection and fallback
- **DX:** Cross-platform React asset build support (Linux, macOS, Windows)

# 3.1.1 / 2026-04-01
- **FIX:** Synced plugin header `Version` to `3.1.1` (was stale at `3.0.5`).
- **FIX:** `README.txt` `Stable tag` corrected to `3.1.1` with proper spacing.
- **FIX:** `SECURITY.md` supported version bumped to `3.1.1`.
- **FIX:** `scripts/version-bump.sh` SECURITY.md sed patterns now match the actual table format (no trailing pipe after emoji).
- **DEPS:** Removed VCS repository entries for `estebanforge/hyperfields` and `estebanforge/hyperblocks` from `composer.json`; both packages resolve via Packagist in CI/production and via path repos in local monorepo development.
- **DEPS:** Added path repository entries for `../HyperFields` and `../HyperBlocks` with `symlink: false` so local development mirrors files into `vendor/` (required for WordPress.org distribution where `vendor/` is committed).
- **DEPS:** PHP floor corrected from `>=8.1` to `>=8.2`, matching the effective minimum set by HyperFields and HyperBlocks.
- **DEPS:** Vendored `estebanforge/hyperfields` updated to 1.1.9; `estebanforge/hyperblocks` updated to 1.0.4 (includes HyperFields bootstrap chaining for standalone use).

# 3.1.0 / 2026-03-29
- **NEW:** Added `hyperpress/render/invalid_route_output` filter to replace the invalid route/missing template response with custom HTML or a `.html`/`.htm` file.
- **ARCHITECTURE:** Converted HyperPress into a thin WordPress plugin adapter that loads `estebanforge/hyperpress-core` from Composer.
- **ARCHITECTURE:** Composer package type set to `wordpress-plugin`; plugin now depends on `estebanforge/hyperpress-core:^1`.
- **NEW:** Added a built-in **HyperPress Data Tools** page under `Tools` using HyperFields Export/Import UI for `hyperpress_options`.
- **FIX:** Added robust load-once guards in adapter bootstrap to prevent duplicate path loading and autoloader collisions.
- **FIX:** Added defensive WordPress hook guards (`function_exists`) in adapter bootstrap for CLI/test contexts.
- **FIX:** Restored complete selector hook wiring in plugin entrypoint (`hyperpress`, `hyperfields`, and `hyperblocks` latest-instance selectors).
- **TESTING:** Added/updated adapter contract tests for bootstrap wiring, plugin entrypoint wiring, and Composer adapter contract.
- **TESTING:** `composer test` now self-heals by running `composer update` when `vendor/bin/pest` is missing, then executes tests.
- **DX:** Standardized adapter Composer scripts (`production`, `test`, `test:unit`, `cs:fix`, `cs:check`, `version-bump`).
- **DX:** Fixed `version-bump` workflow to update all release-critical files in one run:
  - `composer.json`
  - plugin entrypoint header (`api-for-htmx.php`)
  - `README.txt` stable tag
  - `SECURITY.md` supported versions
- **RELEASE:** Corrected plugin `.gitignore` for release workflow (keeps `vendor/` tracked, ignores local caches/artifacts, and no longer ignores `scripts/`).
- **FIX:** HyperFields Export/Import diff preview integration fix included in stack usage:
  - jsondiffpatch now loads before inline diff render
  - loading label encoding corrected
  - explicit fallback message when diff library is unavailable

# 3.0.4 / 2026-01-08
- **NEW:** Added Pest v4 testing framework with PHPUnit v12 support
- **NEW:** Added pestphp/pest-plugin-browser for browser testing capabilities
- **IMPROVEMENT:** Wrapped all helper functions with `function_exists()` checks to prevent conflicts when users have plugins/themes with similarly named functions
- **IMPROVEMENT:** Wrapped all deprecated functions with `function_exists()` checks for better compatibility
- **UPDATED:** HyperFields dependency constraint relaxed to `^1` for better forward compatibility
- **CLEANUP:** Removed `mockery/mockery` dependency (using Brain Monkey only for WordPress mocking)
- **CLEANUP:** Removed `pcov/clobber` dependency (conflicted with Pest v4 requirements)
- **UPDATED:** All test scripts now use Pest instead of PHPUnit
- **UPDATED:** PHPUnit bumped from ^10.5 to ^12.0 (required by Pest v4)

# 3.0.3 / 2025-12-07
- **IMPROVEMENT:** Removed unused vendor-prefixed autoloader references for cleaner codebase
- **IMPROVEMENT:** Simplified Assets.php library mode URL detection
- **IMPROVEMENT:** Optimized Composer autoloader with production settings
- **FIX:** Removed obsolete WPSettingsOptions class reference from backward-compatibility layer. Thanks @texorama for the report.
- **CLEANUP:** Removed leftover TemplateLoader initialization code (TemplateLoader is internal to HyperFields and not used by HyperPress). Thanks @texorama for the report.

# 3.0.2 / 2025-11-23
- Updated all hypermedia libraries to their latest versions:
  - HTMX and all HTMX extensions
  - Alpine.js and Alpine AJAX
  - Datastar.js
- Updated package.json with new library versions
- Maintained backward compatibility for all existing functionality

# 3.0.1 / 2025-08-30
- Released on [wp.org](https://wordpress.org/plugins/api-for-htmx/).

# 3.0.0 / 2025-08-21
- **NEW:** **HyperBlocks** - Sane PHP-based block creation system, no JavaScript required, with **two complementary approaches**
  - **Fluent API**: PHP-only block development.
  - **block.json**: WordPress-standard JSON blocks.
  - **Unified Editor**: Both approaches use the same React editor component for consistent UX.
  - **Zero JavaScript**: Build complex Gutenberg blocks using only PHP.
- **NEW:** **HyperFields** - PHP-based field creation system, no JavaScript required, for use with Gutenberg blocks, metaboxes and custom option pages.
- **NEW:** Auto-discovery system for blocks in `/hyperblocks/` directories
- **NEW:** Server-side rendering engine with secure PHP template execution
- **NEW:** Custom component system with `<RichText>` and `<InnerBlocks>` support
- **NEW:** REST API endpoints for dynamic block field definitions and live previews
- **NEW:** Reusable field groups for consistent block development
- **NEW:** Comprehensive documentation and demo blocks included
- **NEW:** Compatible with existing WordPress block ecosystem
- **BREAKING CHANGE:** The project's namespace has been updated from `HMApi` to `HyperPress` for clarity and branding. All public-facing helper functions have been renamed from `hm_` to `hp_`. Key constants and nonce identifiers have also been updated (`HMAPI_ABSPATH` is now `HYPERPRESS_ABSPATH`, and `hmapi_nonce` is now `hyperpress_nonce`). A backward-compatibility layer has been included to minimize disruption (ex: HyperPress provides alias for old now-deprecated functions). However, a major version bump was required to signal significant changes on new HyperPress v3.

# 2.0.7 / 2025-08-02
- **IMPROVEMENT:** Added a `hyperpress/before_template_load` action hook that fires before each hypermedia template partial is loaded, providing a centralized point for common template preparation logic. Thanks @eduwass.
- **FIX:** Added `stripslashes_deep()` to the `hm_ds_read_signals()` function to remove WordPress "magic quotes" slashes from GET requests, ensuring proper JSON decoding for Datastar signals. Thanks @eduwass.
- Updated Datastar JS library to the latest version.
- Updated Datastar PHP SDK to the latest version.

# 2.0.6 / 2025-07-23
- **FIX:** Updated Datastar.js enqueue to use `wp_enqueue_script_module()` for proper ES module support (WordPress 6.5+). Thanks @eduwass for the report.

# 2.0.5 / 2025-07-11
- **NEW:** Added a suite of Datastar helper functions (`hm_ds_*`) to simplify working with Server-Sent Events (SSE), including functions for patching elements, managing signals, and executing scripts.
- **IMPROVEMENT:** The admin settings page now dynamically displays tabs based on the selected active library (HTMX, Alpine Ajax, or Datastar), reducing UI clutter.
- **REFACTOR:** Centralized plugin option management by introducing a `getOptions()` method in the main plugin class.
- **REFACTOR:** Improved the structure of the admin options page for better maintainability and separation of concerns.
- **FIX:** Several bugfixes and improvements.

# 2.0.0 / 2025-06-06
- Renamed plugin to "HyperPress: Modern Hypermedia for WordPress" to reflect broader support for multiple hypermedia libraries.
- **NEW:** Added support for Datastar.js hypermedia library.
- **NEW:** Added support for Alpine Ajax hypermedia library.
- **NEW:** Template engine now supports both `.hm.php` (primary) and `.htmx.php` (legacy) extensions.
- **NEW:** Template engine now supports both `hypermedia` (primary) and `htmx-templates` (legacy) theme directories.
- **NEW:** Added `hm_get_endpoint_url()` helper function to get the API endpoint URL.
- **NEW:** Added `hyperpress_enpoint_url()` helper function to echo the API endpoint URL in templates.
- **NEW:** Added `hm_is_library_mode()` helper function to detect when plugin is running as a Composer library.
- **NEW:** Comprehensive programmatic configuration via `hyperpress/default_options` filter for all plugin settings.
- **NEW:** Library mode automatically hides admin interface when plugin is used as a Composer dependency.
- **NEW:** Enhanced Composer library integration with automatic version conflict resolution.
- **NEW:** Fixed Strauss namespace prefixing to include WPSettings template files via `override_autoload` configuration.
- **IMPROVED:** Enhanced admin interface with a new informational card displaying the API endpoint URL.
- **IMPROVED:** The `$hmvals` variable is now available in templates, containing the request parameters.
- **IMPROVED:** Better detection of library vs plugin mode based on WordPress active_plugins list.
- **IMPROVED:** Complete documentation for programmatic configuration with real-world examples.
- **BACKWARD COMPATIBILITY:** All `hxwp_*` functions are maintained as deprecated aliases for `hyperpress_*` functions.
- **BACKWARD COMPATIBILITY:** The legacy `$hxvals` variable is still available in templates for backward compatibility.
- **BACKWARD COMPATIBILITY:** Dual nonce system supports both `hyperpress_nonce` (new) and `hxwp_nonce` (legacy).
- **BACKWARD COMPATIBILITY:** Legacy filter hooks (`hxwp/`) are preserved alongside new `hyperpress/` prefixed filters.
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
