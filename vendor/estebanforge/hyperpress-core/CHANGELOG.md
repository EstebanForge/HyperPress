# Changelog

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
