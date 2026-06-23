# Changelog

## [1.2.0] - 2026-06-23

### Added
- `hyperpress/options` filter — canonical entry point for programmatic configuration. Applied LAST in the resolution chain so library consumers always win, even when a stored database option exists.
- `hyperpress/configured` action — fires once per request from `Main::run()` after the merged options are resolved. Receives the final options array.
- `hp_get_options(): array` — helper wrapping `OptionsResolver::resolve()` for external code that needs to read the merged options.
- `hp_get_option(string $key, mixed $default = null): mixed` — singular accessor. Returns `$default` when the key is missing or null.
- `HyperPress\OptionsResolver` — single source of truth for option resolution. `Main::getOptions()`, `Config::getOptions()`, and `Assets::getOptions()` all delegate to it. Per-request cache keyed by `(blog_id, $htmx_extensions)` so multisite `switch_to_blog()` stays correct.

### Changed
- Admin options page (`Settings → HyperPress`) is now hidden by default when HyperPress-Core is consumed as a Composer library (no `hyperpress.php` or `api-for-htmx.php` entry point active). The page remains available in plugin mode, unchanged. Gate evaluated on `init` (not construction) so library consumers can register `hyperpress/admin/show_menu` until the last moment.
- Library consumers can opt in by returning a truthy value from the new `hyperpress/admin/show_menu` filter: `add_filter('hyperpress/admin/show_menu', '__return_true');`.
- `HyperPress\Admin\Options::isEnabled(): bool` — new public static helper exposing the gate logic for consumers and tests.
- Option resolution is now consistent across `Main`, `Config`, and `Assets`. Default `active_library` is `datastar` everywhere (previously `Main` defaulted to `htmx` while `Config`/`Assets` defaulted to `datastar`).

### Fixed
- `OptionsResolver::defaults()` synthesizes HTMX extension option keys with underscores (e.g. `load_extension_head_support`) to match the shape Admin writes and stores. Previously `hp_get_option('load_extension_head-support')` returned 0 even when the admin had enabled it.
- `Main::$options` is now nullable (`?Options $options = null`) so any code touching the public property on a frontend request no longer triggers a "Typed property must not be accessed before initialization" fatal.

### Deprecated
- `hyperpress/config/default_options` filter — applied to defaults only, before DB read. A stored option always wins. Use `hyperpress/options` instead.
- `hyperpress/assets/default_options` filter — same caveat. Use `hyperpress/options` instead.

## [1.1.8] - 2026-04-29

### Added
- `hp_is_rate_limited()` — generic, side-effect-free rate limit helper for any HyperPress endpoint (HTML, HTMX, Alpine AJAX, Datastar `@get`/`@post`). Does not send headers or SSE responses.

### Fixed
- `hp_ds_is_rate_limited()` now delegates the actual rate-limit check to `hp_is_rate_limited()` and only sends SSE error feedback when the request is actually blocked. Previously, calling this helper in a non-rate-limited request would still trigger `hp_ds_sse()`, sending `text/event-stream` headers and breaking regular HTML endpoints.

### Changed
- Demo templates (`datastar-demo.hp.php`, `noswap/datastar-demo.hp.php`) now use `hp_is_rate_limited()` instead of `hp_ds_is_rate_limited()` since they are regular HTML endpoints, not SSE streams.
- Documentation updated to clearly distinguish `hp_is_rate_limited()` (generic) from `hp_ds_is_rate_limited()` (SSE-only).

## [1.1.5] - 2026-04-28

### Added
- Jetpack Autoloader integration for Composer package conflict management.
  - Added `automattic/jetpack-autoloader` dependency.
  - Enabled Composer plugin allow-list entry for Jetpack Autoloader.

### Changed
- Bootstrap loading flow now attempts `vendor/autoload_packages.php` before `vendor/autoload.php` when running outside a vendor tree.
- `composer.json` — bumped package version to `1.1.5`.

## [1.1.4] - 2026-04-28

### Fixed
- Datastar PHP SDK namespace references now use upstream `starfederation\datastar\...` class names in helpers, runtime bootstrap checks, and SDK detection (`includes/helpers.php`, `src/Main.php`, `src/Libraries/DatastarLib.php`), restoring compatibility with current `starfederation/datastar-php` autoloading.

### Changed
- `composer.json` — bumped package version to `1.1.4`.

### Credits
- Thanks @web-maverick1 on GitHub for the heads up.

## [1.1.0] - 2026-04-07

### Added
- `context7.json` — Context7 service integration configuration for `estebanforge/hyperpress-core`, enabling AI-powered documentation and code examples lookup via the Context7 platform.

### Changed
- `composer.json` — bumped version to 1.1.0; refreshed `composer.lock` with latest dependency upgrades (108 packages reinstalled from lock file).

## [1.0.5] - 2026-04-01

### Changed
- `composer.json`: removed redundant VCS repository entries for `estebanforge/hyperfields` and `estebanforge/hyperblocks`; both packages are published on Packagist and resolve correctly through path repos (local dev) or Packagist (production/CI) without explicit VCS pointers.

## [1.0.4] - 2026-04-01

### Added
- `bootstrap.php` now explicitly requires `vendor/estebanforge/hyperfields/bootstrap.php` and `vendor/estebanforge/hyperblocks/bootstrap.php` when loaded outside a vendor tree. This ensures HyperFields and HyperBlocks are fully initialized (candidate election + WordPress hook wiring) when HyperPress-Core is used as a standalone library, not only when it is a Composer dependency of another plugin.
- `composer.json`: added path repository entries for `../HyperFields` and `../HyperBlocks` so local monorepo development symlinks the live source trees instead of Packagist snapshots.

### Fixed
- PHP version floor corrected from `>=8.1` to `>=8.2`, matching the effective minimum set by both HyperFields and HyperBlocks.

## [1.0.3] - 2026-03-29

### Changed
- Version bump.

## [1.0.2] - 2026-03-29

### Changed
- Version bump.

## [1.0.1] - 2026-03-29

### Changed
- Version bump.

## [1.0.0] - 2026-03-29

### Added
- Initial release. Core HyperPress runtime extracted from the monolithic `api-for-htmx` plugin into a standalone Composer library (`estebanforge/hyperpress-core`).
- API routing (`HyperPress\Router`) — registers the `/wp-html/v1/` REST namespace; resolves hypermedia template requests.
- Rendering pipeline (`HyperPress\Render`) — locates and executes `.hp.php`, `.hm.php`, `.hb.php`, `.htmx.php`, `.hmedia.php` templates from theme `hypermedia/` directories and registered paths.
- Asset management — conditional enqueueing of HTMX, Alpine.js, and Datastar libraries based on admin options.
- Admin options (`HyperPress\Config`) — settings page and persistent configuration store with WordPress filter integration.
- Compatibility layer (`HyperPress\Compatibility`) — browser and library capability detection.
- Theme support (`HyperPress\Theme`) — registers theme features required by the hypermedia template system.
- Main orchestrator (`HyperPress\Main`) — wires router, render, config, compatibility, and theme support; single `run()` entry point.
- Block integration (`HyperPress\Blocks\Registry`, `HyperPress\Blocks\RestApi`) — singleton block registry and REST endpoints for the Gutenberg editor; initialized as part of `hyperpress_run_initialization_logic`.
- Candidate-election bootstrap (`bootstrap.php`) — identical version-resolution pattern to HyperFields/HyperBlocks; multiple vendored copies elect the highest version at `after_setup_theme` (priority 0).
- `HYPERPRESS_BOOTSTRAP_LOADED` and `HYPERPRESS_INSTANCE_LOADED` guards prevent duplicate initialization.
- Constants: `HYPERPRESS_VERSION`, `HYPERPRESS_ABSPATH`, `HYPERPRESS_BASENAME`, `HYPERPRESS_PLUGIN_URL`, `HYPERPRESS_PLUGIN_FILE`, `HYPERPRESS_ENDPOINT` (`wp-html`), `HYPERPRESS_LEGACY_ENDPOINT` (`wp-htmx`), `HYPERPRESS_TEMPLATE_DIR`, `HYPERPRESS_TEMPLATE_EXT`, `HYPERPRESS_ENDPOINT_VERSION`.
- Helpers and backward-compatibility shims loaded from `includes/helpers.php` and `includes/backward-compatibility.php`.
- `hyperpress_register_candidate_for_tests()` test helper for re-registration in PHPUnit bootstraps.
- Full unit test suite (Pest v4, Brain Monkey) with 59 assertions covering router, render, config, compatibility, theme, main, blocks, and endpoint logic.
- Tooling: `.php-cs-fixer.dist.php`, `phpunit.xml`, `Pest.php`, `scripts/version-bump.sh`, `composer.json` scripts (`test`, `test:unit`, `test:coverage`, `cs:fix`, `production`, `version-bump`).
