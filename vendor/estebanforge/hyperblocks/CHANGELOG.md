# Changelog

## [1.0.4] - 2026-04-01

### Added
- HyperFields bootstrap integration: `bootstrap.php` now triggers `vendor/estebanforge/hyperfields/bootstrap.php` when HyperBlocks is loaded standalone (outside a context where the HyperFields plugin is already active). This ensures HyperFields Registry, Assets, and TemplateLoader are initialized without any extra setup from the host project.
- `docs/hyperblocks.md` — comprehensive API reference covering all classes, methods, configuration keys, REST endpoints, WordPress filters, and security model.
- `docs/hyperblocks-examples.md` — eleven copy-ready examples covering simple blocks, hero banners, field groups, group field override precedence, `<RichText>`/`<InnerBlocks>` pseudo-components, `block.json` blocks, procedural helpers, and manual rendering.
- `docs/library-bootstrap.md` — bootstrap internals, constants reference, and setup guides for flat vendor, `plugins_loaded` pattern, monorepo/Bedrock, and nested library scenarios.
- `AGENTS.md` — full agent/developer reference mirroring `HyperFields/AGENTS.md` conventions.
- `README.md` now includes: full field-types table, template variable extraction explanation, `<RichText>`/`<InnerBlocks>` component documentation, REST API endpoint table, block discovery path registration examples, and a procedural helpers quick-start.

### Fixed
- `examples/hero-banner-block.php` — removed calls to `Block::setDescription()` which does not exist on the `Block` class.
- `examples/field-groups-example.php` — removed calls to `FieldGroup::setDescription()` and `Block::setDescription()` which do not exist.
- `README.md` PHP version requirement corrected from 8.1+ to 8.2+ (HyperFields sets the effective minimum).

## [1.0.3] - 2026-03-29

### Changed
- Version bump.

## [1.0.2] - 2026-03-29

### Changed
- Version bump.

## [1.0.1] - 2026-03-29

### Added
- Candidate-election bootstrap system: `bootstrap.php` now implements the same version-resolution pattern as HyperFields. Multiple vendored copies of HyperBlocks elect the highest version at `after_setup_theme` (priority 0).
- `HYPERBLOCKS_BOOTSTRAP_LOADED` and `HYPERBLOCKS_INSTANCE_LOADED` guards prevent duplicate initialization.
- Version automatically read from `composer.json` at bootstrap time.

### Changed
- Tooling and project infrastructure unified: `.gitignore`, `.php-cs-fixer.dist.php`, `Pest.php`, `phpunit.xml`, `scripts/version-bump.sh` added or standardized.
- `composer.json` scripts consolidated; `version-bump` script added.
- WordPress mock stubs in `tests/mocks/wp-mocks.php` expanded and cleaned up.
- `Config`, `Registry`, `Block`, `Field`, `FieldGroup`, `Renderer`, `RestApi`, `WordPress\Bootstrap`, and `helpers.php` refined for consistency with the finalized API surface.
- `README.md` condensed to focus on installation and quick-start.

## [1.0.0] - 2026-01-27

### Added
- Initial release. Core classes extracted from HyperPress and migrated to the `HyperBlocks\` namespace.
- `Block` — fluent builder for Gutenberg blocks with `setName()`, `setIcon()`, `addFields()`, `addFieldGroup()`, `setRenderTemplate()`, `setRenderTemplateFile()`.
- `Field` — typed block field wrapper delegating to `HyperFields\Field` for sanitization and validation.
- `FieldGroup` — reusable named set of fields attachable to multiple blocks.
- `Registry` — singleton managing block and field-group registrations; `generateBlockAttributes()`, `getMergedFields()`, block discovery.
- `Config` — static configuration store with WordPress filter integration (`hyperblocks/config/defaults`, `hyperblocks/config/override`).
- `Renderer` — PHP template executor supporting file-based and inline string templates; `<RichText>` and `<InnerBlocks>` pseudo-component parsing; path validation against allowlist.
- `WordPress\Bootstrap` — WordPress hook wiring for block registration, REST API, and editor asset enqueueing.
- `RestApi` — REST endpoints `GET /block-fields` and `POST /render-preview` under the `hyperblocks/v1` namespace.
- `src/helpers.php` — procedural `hyperblocks_*` helper functions.
- Block auto-discovery via `Config::registerBlockPath()` and WordPress filters (`hyperblocks/blocks/register_fluent_paths`, `hyperblocks/blocks/register_json_paths`, etc.).
- `block.json` block support: auto-discovery, registration, and REST field/preview endpoints.
- Full unit test suite (Pest v4, Brain Monkey) covering `Block`, `Field`, `Config`, and `Registry`.
- Example blocks (`hero-banner`, `feature-card`, `content-box`) with `.hb.php` templates.
