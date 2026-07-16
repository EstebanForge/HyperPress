# Changelog

## [1.3.0] - 2026-07-16

### Changed
- **`scripts/version-bump.sh` gained non-interactive flag support.** The script previously prompted interactively for the new version only; it now resolves the target version from flags first, falling back to the interactive prompt only when called with no arguments:
  - `--patch` / `--minor` / `--major` — compute the next version from the current `composer.json` version using a shared `bump_version()` helper (e.g. `1.2.3` + `--minor` → `1.3.0`).
  - `--version X.Y.Z` — explicit target, validated against `^[0-9]+\.[0-9]+\.[0-9]+$` and rejected if equal to the current version.
  - `-h` / `--help` prints usage; unknown arguments exit `2`.
  - Emits a final `RESULT: <cur> -> <new>` line for machine-parseable output.
  - No flags = unchanged interactive behavior (backwards compatible).
- No library or runtime changes; no API additions.

## [1.2.0] - 2026-07-07

### Security
- Auto-discovery of fluent block definition files now requires a WordPress-style file header, fixing a bug that broke virtually every standard WordPress/ACF theme. Themes following the de-facto `/blocks/<slug>/{block.json,init.php,render.php}` layout co-locate `render.php` files that expect to be included by WordPress's block renderer with `$block`, `$attributes`, and `$content` in scope. HyperBlocks' `Registry::discoverAndLoadFluentBlocks()` globbed every registered block path and `require_once`d every `.hb.php`/`.php` match on `init`, executing those `render.php` files out of render context — echoing their markup before `<!DOCTYPE html>`, accessing `$block` as undefined, and producing a cascade of warnings plus a fully broken page. This was not consumer-specific: any theme using the WP-standard `/blocks/<slug>/` layout was broken the moment HyperBlocks loaded. The library now parses each candidate file's header via `get_file_data()` (reading only the first 8 KB, never executing) and only `require_once`s files that declare a non-empty `HyperBlocks Block:` header. WP-native `render.php` / `init.php` never carry it and are skipped without execution. The check lives in a new `Registry::isFluentBlockFile()` helper, backed by the `Registry::FLUENT_BLOCK_HEADER` constant.

### Added
- `hyperblocks/blocks/auto_discover_theme_blocks` filter — gates `WordPress\Bootstrap::registerDefaultPaths()` auto-registration of the active theme's `/blocks` directories (parent + child) as discovery paths. Defaults to `true` (strict back-compat); a developer whose theme uses `/blocks` for WP-native/ACF blocks can opt out entirely with `add_filter('hyperblocks/blocks/auto_discover_theme_blocks', '__return_false')`. This is the second of two independent gates against the `/blocks/<slug>/render.php` footgun (the first being the `HyperBlocks Block:` header check), adding defense-in-depth without breaking any existing consumer. Scope is intentionally narrow: it affects only theme `/blocks` auto-registration — the library's own bundled blocks (`HYPERBLOCKS_PATH/blocks`, registered in `initializeConfig()`) and explicitly `Config::registerBlockPath()` directories are unaffected.
- `tests/Unit/DiscoveryHeaderTest.php` — pins the header requirement: a WP-native `render.php` (echoes output, no header) is not executed; a headered file is loaded; a mixed directory loads only headered files; the header is parsed correctly from a docblock; and the `hyperblocks/blocks/register_fluent_blocks` filter bypasses the header check (explicit consent).
- `tests/Unit/ThemeAutoDiscoveryFilterTest.php` — pins the filter: theme `/blocks` is auto-registered by default; `__return_true` preserves the default; `__return_false` opts out; and the filter does not affect explicitly registered paths.
- `get_file_data()` and `__return_true()` / `__return_false()` mocks in `tests/mocks/wp-mocks.php`, faithfully porting WordPress core semantics (the `get_file_data()` regex and `_cleanup_header_comment()` logic are line-for-line ports of `wp-includes/functions.php`). `add_filter()` / `apply_filters()` mocks now dispatch real callbacks so filter-driven code paths are testable.

### Changed
- Fluent block definition files loaded via auto-discovery must now declare a `HyperBlocks Block: <title>` docblock header. This mirrors the convention WordPress uses for plugin, theme, and dropin headers and makes HyperBlocks definition files explicit and opt-in. Files pointed at directly via the `hyperblocks/blocks/register_fluent_blocks` filter are exempt (naming a file directly is explicit consumer consent), as is any consumer's own `require_once`. Explicit `Config::registerBlockPath()` directories are still scanned but with the header check applied, so pointing HyperBlocks at a theme's `/blocks` tree is now safe.
- `examples/hero-banner-block.php`, `examples/field-groups-example.php`, and the HyperPress-Core consumer file `hyperblocks/fluent-demos/fluent-demos.hb.php` now carry the `HyperBlocks Block:` header. The header approach is namespace-agnostic (the fluent-demos file uses `HyperPress\Blocks\Registry`, not `HyperBlocks\`), which is why a `str_contains('HyperBlocks')` content-sniff was rejected — it would have missed consumer files using proxy/aliased namespaces.
- `AGENTS.md` and `docs/` updated to document the header requirement, the discovery flow, and the new filter.

### Upgrade notes
- If you ship fluent block definition files (files that call `Block::make(...)` and `Registry::getInstance()->registerFluentBlock(...)` and are loaded via auto-discovery or `Config::registerBlockPath()`), add a `HyperBlocks Block: <title>` line to the file's top docblock. Files loaded via your own `require_once` or via the `hyperblocks/blocks/register_fluent_blocks` filter are unaffected. If your theme's `/blocks` directory holds only WP-native/ACF blocks and you prefer to disable theme auto-registration entirely, `add_filter('hyperblocks/blocks/auto_discover_theme_blocks', '__return_false')`.

## [1.1.9] - 2026-07-06

### Security
- Hardened the path-containment check in `Block::validateTemplatePath()` and `Renderer::validateTemplatePath()`. Both used `str_starts_with($real, $base)` without a trailing separator, so a registered base like `/var/www/blocks` incorrectly treated an unregistered sibling directory `/var/www/blocks-evil` as "inside" the allowed base. `Renderer::validateTemplatePath()` was the reachable vector: an absolute `file:` path skips the relative-resolution loop and lands directly in the prefix check, so a crafted absolute path into a prefix-colliding sibling directory would render an arbitrary file. The check now requires the base plus a trailing separator (and accepts an exact match on the base itself). `Block::validateTemplatePath()` was mitigated in practice by its `..` rejection (no way to reach a sibling without `..`), but is fixed too for defense-in-depth. Added `tests/Unit/RendererTemplatePathSecurityTest.php` proving the sibling-prefix escape via `Renderer::render()` is rejected and that legitimate relative templates still render.

### Changed
- Template-path validation is now decoupled from block auto-discovery. Previously `Config::registerBlockPath()` drove both behaviors from a single `block_paths` key: a directory registered merely so `Block::setRenderTemplateFile()` could resolve render templates stored there was also globbed by `Registry::discoverAndLoadFluentBlocks()` and every `.hb.php`/`.php` in it was `require_once`d as a block definition on `init`, fatalling when a template expected a render context (`TypeError: Cannot access offset of type string on string`). The split is additive and strictly backwards-compatible: `block_paths` keeps its current meaning (discovery + validation), and a new `template_paths` set is validation-only and never scanned.
  - `Config::registerBlockPath($path)` (no options) is unchanged: discovery + validation.
  - `Config::registerBlockPath($path, ['discover' => false])` registers for validation only.
  - `Config::registerTemplatePath($path)` is a one-liner equivalent of the above.
  - `Config::getTemplatePaths()` returns validation-only paths; `Config::getTemplateValidationPaths()` returns the deduplicated union (of `block_paths` + `template_paths`) used by `Block::validateTemplatePath()` and `Renderer::validateTemplatePath()`, so a template-only registration still resolves templates while staying out of the discovery glob.
  - `Registry::discoverAndLoadFluentBlocks()` reads `block_paths` only; the intent is now documented at the scan site.
  - Added the `hyperblocks_register_template_path()` procedural helper.
  - Registered paths are now normalized (trailing slashes stripped) so `/foo/bar` and `/foo/bar/` collapse to one entry instead of inflating the allowlist.
  - The discovery glob's one-level-deep bound (native PHP `glob()` has no globstar, so the pattern matches `base/<dir>/<file>` only; files directly in the base or nested two-plus levels deep are not discovered) is now documented as intentional and pinned by a regression test. Making discovery recursive would re-introduce the fatal class this change eliminates and requires a major-version bump.
  - Removed `Config::validate()` and `Config::save()` (and their `ConfigTest` cases): both were unreachable dead code (`validate()` was only called by `save()`, which had zero callers). Re-add with coverage together if config validation is needed later.
  - Added `tests/Unit/TemplatePathDiscoveryTest.php` covering the template-only, default-discovery, `discover => false`, union/dedup, one-level-deep, and trailing-slash-normalization cases.

## [1.1.7] - 2026-07-06

### Fixed
- Fluent-API blocks now appear in the Gutenberg inserter and parse correctly in saved post content. Previously `register_block_type` referenced the `hyperblocks-editor` script handle, but the handle was never registered with WordPress and `enqueueEditorScript()` (now `registerEditorScript()`) was a guarded no-op, so the client never ran `wp.blocks.registerBlockType()` and existing block instances surfaced as "This block contains unexpected or invalid content."
  - Added the missing `assets/js/editor.js` (vanilla JS, no build step): reads `window.hyperBlocksConfig` (injected server-side as `{ name, title, icon }` per block) and registers each block client-side with no-op `edit`/`save`, since blocks remain dynamic and server-rendered via `render_callback`. Guards against duplicate registration via `wp.blocks.getBlockType()`.
  - Wired `Bootstrap::registerEditorScript()` to **register** (not enqueue) the script when the file exists (the guard now passes). Registering rather than enqueueing is required because the call runs on `init`, which fires on every request including the public front end; enqueueing there would load the Gutenberg bundle (`wp-blocks`, `wp-element`, `wp-components`) on every page. Core enqueues the handle in the editor only, via the existing `editor_script` argument passed to `register_block_type`.
  - Prefer the canonical `HYPERBLOCKS_PLUGIN_URL` constant for URL resolution so the script loads correctly when HyperBlocks is vendored inside a consumer plugin; cast the `filemtime` version to string; declare `wp-dom-ready` as a dependency since `editor.js` calls `wp.domReady`.
  - The `wp_add_inline_script` call that seeds `window.hyperBlocksConfig` remains attached to the registered handle and prints in the editor when core enqueues it.
  - Extended the `tests/mocks/wp-mocks.php` capture helper (added a `wp_register_script` mock alongside the existing enqueue/inline captures) and added `tests/Unit/EditorScriptTest.php` asserting the handle, URL, full dependency list (including `wp-dom-ready`), in-footer flag, inline-config injection, the editor-context-only guarantee (no enqueue call), and the no-block no-registration path.

## [1.1.6] - 2026-07-06

### Added
- Fluent `Block` setters for optional Gutenberg metadata that previously required a `block.json` — `setCategory()`, `setDescription()`, `setKeywords()`, and `setStyle()`. Metadata is forwarded to `register_block_type` only when set, so existing fluent blocks with defaults behave exactly as before. Pure addition, fully backwards compatible.

## [1.1.4] - 2026-07-03

### Changed
- Updated `estebanforge/hyperfields` dependency to pick up the latest HyperFields release.

## [1.1.3] - 2026-04-28

### Added
- Jetpack Autoloader integration for Composer package conflict management.
  - Added `automattic/jetpack-autoloader` dependency.
  - Enabled Composer plugin allow-list entry for Jetpack Autoloader.

### Changed
- Bootstrap loading flow now attempts `vendor/autoload_packages.php` before `vendor/autoload.php` when running outside a vendor tree.

## [1.1.0] - 2026-04-14

### Added
- Context7 integration: `context7.json` added for documentation package management and discoverability.

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
