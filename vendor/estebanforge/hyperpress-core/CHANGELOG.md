# Changelog

## [1.5.5] - 2026-08-17

### Added
- **Opt-in strict template mode for the `/wp-html/v1/` render endpoint.** By design the endpoint renders any template shipped in the registered template directories, which keeps third-party adoption friction low but leaves authorization to per-template `hp_validate_request()` calls. Strict mode closes that fail-open default for deployments that want it, without breaking existing sites: it is OFF by default and enabled with `add_filter('hyperpress/render/strict_mode', '__return_true')`. Output with the filter off is unchanged; the only default-path difference is one additional no-op `apply_filters('hyperpress/render/strict_mode')` call per render. When enabled, a template loads only if explicitly registered — namespaced templates via their existing `hyperpress/render/register_template_path` registration (already an explicit opt-in), non-namespaced theme-relative templates via the new `hyperpress/render/registered_templates` allowlist (exact-name match, no prefix matching). Unregistered requests receive a dedicated "Template Not Registered" page (also customizable through `hyperpress/render/invalid_route_output`, error type `template-not-registered`). The decision logic lives in the pure `Render::templateMatchesRegistration()` so the security contract is unit-tested directly. See docs/security.md, "Strict Template Mode".

### Security
- Mitigation for audit finding M1 (docs/SECURITY-AUDIT.md in the dev environment): unauthenticated GET rendering of unregistered templates. Default behavior intentionally unchanged.
- **HyperPress option imports now run through field sanitizers.** The options page registers a `hyperfields/import/sanitize_fields` provider (HyperFields 1.5.5 seam) so Data Tools imports of `hyperpress_options` sanitize every defined field through `Field::sanitizeValue()` instead of trusting the uploaded file's self-attested schema. Addresses audit finding L3 for this plugin's option group.
- **`hp_is_rate_limited()` documented as best-effort.** WordPress has no built-in atomic read-modify-write over transients, so the counter can under-count under concurrent requests. The helper's docblock now states the guarantee (approximate abuse protection, not a hard limit) and points at atomic object-cache stores for callers needing a hard limit. Addresses audit finding L5 as documented best-effort, per owner decision.
- **Legacy `hxwp_nonce` verification now warns.** `hp_validate_request()` still accepts the legacy nonce action for old clients, but every legacy-verified request emits a `_doing_it_wrong()` deprecation notice pointing at `hyperpress_nonce`, and fires a `hyperpress/nonce/legacy_verified` action for custom logging. Current-action verification warns nothing; the notice is filterable off via `hyperpress/nonce/legacy_deprecated_notice`. Addresses audit finding L8 (removal now has a migration path).
- **`include-vals` htmx extension documented as evaluating attribute content.** The upstream extension uses `eval()` on the `include-vals` attribute by design; the extension's description on the options page now carries an explicit caution (never user-influenced attribute values, keep disabled on untrusted sites). The extension remains opt-in and off by default. Addresses audit finding L7 as an upstream-design accepted risk, documented.
- **Log filename hash upgraded, based on the WooCommerce 8.9+ algorithm** (`Logging\FileV2\File::generate_hash`, hashing step): the filename suffix is now `hash_hmac('md5', $file_id, AUTH_SALT)` over the file id (`source-Y-m-d`) instead of `wp_hash($source)` over the bare source. The unguessable URL suffix now rotates with each daily file. Storage stays in the shared stack log directory `uploads/hyperpress-logs/` (also used by HyperFields, by design), with rotation, `.htaccess`/`index.html` protection, and append semantics unchanged. Fallback key literal covers writes before WordPress loads salts; no current caller runs that early. Same change as HyperFields 1.5.5 (the Log classes are kept in lockstep). Addresses audit finding M2 residual risk.

## [1.5.4] - 2026-08-11

### Fixed
- **Zero-config subsystem initialization in early-load environments (Bedrock, WP-CLI).** Same hardening as HyperFields 1.5.4 and HyperBlocks 1.5.4. `bootstrap.php`'s scheduling of `Bootstrap::init()` at `after_setup_theme` silently no-op'd when the file ran before `add_action()` existed, leaving Config uninitialized while classes autoloaded. Two windows: HTTP (`wp-config` requires `vendor/autoload` before `application.php` defines `ABSPATH`, so the guard returned before the callback was defined) and WP-CLI (`ABSPATH` pre-defined, but `add_action` absent so the scheduling line was skipped). Fix: the scheduler runs above the `ABSPATH` guard. With `add_action` it uses the normal path (`has_action(...) === false`, not `!has_action` which is always-true for a priority-0 callback). Without `add_action` it writes the registration into `$GLOBALS['wp_filter']` in the preinitialized-hooks format that `WP_Hook::build_preinitialized_hooks` converts on load (WP 4.7+). The `admin_notices` closure is `function_exists`-guarded. As a side effect the runtime now also comes up under WP-CLI, where the scheduling previously never landed.
- **`!has_action` priority-0 double-registration bug** (now `=== false`).
- **Unguarded `add_action` in the autoloader-fallback branch** (now `function_exists`-guarded with an `error_log` fallback).

### Changed
- **G1: election-guard ordering.** `define(__NAMESPACE__ . '\\LOADED')` now runs AFTER `Config::markInitialized()` inside `Bootstrap::init()`. A mid-init abort can no longer leave LOADED claimed with Config uninitialized (which would make any later guard a silent no-op). The `DOING_CRON` / `DOING_AJAX` / `REST_REQUEST` / `XMLRPC_REQUEST` / `WP_CLI` early-return behavior is unchanged; it still runs after `markInitialized()` and the helper/deprecated loads.
- **Contract freeze (documented).** The bootstrap callback name (`hyperpress_bootstrap_init`) and the `init()` target (`\HyperPress\Bootstrap::init`) are a cross-version contract: a stale copy winning Composer's `autoload.files` race calls into whatever class wins the SPL election, so renaming the method in a future major fatals every request.

### Internal
- The unreachable `is_array()` false branch in the `wp_filter` preinit write now `error_log`s, so a future regression of that shape cannot fail silent.

> Note: unlike HyperFields and HyperBlocks, HyperPress-Core takes no Layer 2 (`ensureInitialized`) or Layer 3 (register-or-run) guards. Nothing reaches its bootstrap before `after_setup_theme`, and all of its hooks (`init`, `template_redirect`, `wp_head`, `rest_api_init`, enqueues) fire at or after that point. `Config::isInitialized()` is also a false positive under the background/API early-return contexts, so a blanket guard would mislead.

## [1.5.3] - 2026-08-09

### Added
- **Settings link on the plugins screen.** When the admin options page is enabled, a Settings link is added to the plugin's action row on `wp-admin/plugins.php`. In plugin mode the link targets this plugin's own row; in library mode a host plugin declares its row through the new `hyperpress/admin/action_links_basename` filter (documented in `docs/developer-configuration.md`).

### Security
- **Endpoint request validation hardened.** Requests that fail nonce verification are now treated as unauthenticated for the remainder of the request and cannot inherit a privileged session, and endpoint parameters are sanitized more strictly before rendering runs.
- **Renderer input handling tightened.** Recursive array processing now sanitizes keys at every nesting depth and drops empty keys, and the request superglobal is restored after signal decoding so request state cannot leak across reads.

### Changed
- Template path resolution hoisted for clearer, faster realpath handling in `Render`.
- Test coverage expanded for nonce handling and recursive sanitization (`RenderNonceTest`, `RenderSanitizationTest`).

## [1.5.2] - 2026-08-04

### Changed
- **Dependency refresh: pull in `estebanforge/hyperblocks` 1.5.0.** HyperBlocks 1.5.0 makes fluent blocks WordPress 7.1 iframed-editor ready (apiVersion 3), adds the `hyperblocks/blocks/api_version` filter, introduces the `block.json` ownership marker (`"hyperblocks": true`), and makes JSON-block registration actually work (it had been a silent no-op). The lockfile is updated so consumers resolving HyperPress-Core 1.5.2 get the set.
- **Demo JSON blocks updated to the new ownership convention.** The bundled `hyperblocks/content-card`, `hyperblocks/hero-banner`, and `hyperblocks/quote-block` `block.json` files now declare `apiVersion: 3` and `"hyperblocks": true`. With HyperBlocks 1.5.0's marker-gated discovery these demo blocks now register and render for the first time (their `render.php` was already in place); previously they were discovered but never registered.

### Removed
- `HyperPress\Blocks\Registry::discoverJsonBlocksForEditor()` facade proxy. The underlying `HyperBlocks\Registry::discoverJsonBlocksForEditor()` was removed in HyperBlocks 1.5.0 (zero callers, wrong abstraction; JSON blocks surface through WordPress core). The facade proxy had zero callers of its own and is dropped to keep the facade in sync with the library's public API.

## [1.5.1] - 2026-08-03

### Changed
- Dependencies updated.

## [1.5.0] - 2026-07-30

### Changed
- **WordPress 6.5+ / PHP 8.2+ stack modernization.** HyperPress-Core joins the Hyper stack's move to a WordPress 6.5+ minimum (see HyperFields 1.5.0). HyperPress-Core was already PHP `>=8.2`; no own code changes for the floor, but the minor bump marks the alignment and the refreshed dependency set.
- **Dependency refresh: pull in `estebanforge/hyperfields` 1.5.0 and `estebanforge/hyperblocks` 1.4.0.** HyperFields 1.5.0 adds backend-aware transient + OPcache invalidation on save and the WP 6.5+ floor; HyperBlocks 1.4.0 raises its PHP floor to 8.2. The lockfile is updated so consumers resolving HyperPress-Core 1.5.0 get the modernized set.
- `src/Config.php` `VERSION` resynced to `1.5.0` to match `composer.json` (had been left at `1.4.3` during the manual bump; caught by the `EndpointTest`, `MainTest`, and `ThemeTest` version assertions).

## [1.4.3] - 2026-07-24

### Changed
- `docs/installation.md`: documented the Jetpack Autoloader direct-require gate (consumers vendoring HyperPress-Core must directly require `automattic/jetpack-autoloader`; transitive presence leaves Jetpack inert).
- README: added a Jetpack Autoloader note for consumers.

## [1.4.2] - 2026-07-23

### Changed
- **Dependency refresh: pull in `estebanforge/hyperblocks` 1.3.3.** No HyperPress-Core code changes; the lockfile and vendored HyperBlocks are updated so the dynamic-block `useBlockProps()` editor-preview fix (1.3.3) ships to consumers that resolve HyperPress-Core. Released as a patch per the refresh-downstream policy: the committed dependency set changed, so the tag must move.

## [1.4.1] - 2026-07-23

### Fixed
- **Library-mode `HYPERPRESS_PLUGIN_URL` no longer resolves to a broken URL when HyperPress-Core is vendored outside `WP_PLUGIN_DIR`.** Previously library mode defined `HYPERPRESS_PLUGIN_URL` as `''` ("not applicable"), which silently disabled all frontend asset enqueue (htmx/alpine/datastar and the editor bundle). When HyperPress is loaded as a library — including the common case of being vendored into a consumer plugin's bundled `vendor/` tree — `bootstrap.php` now resolves the URL against web-accessible WordPress content roots via HyperFields' shared resolver (`hyperfields_resolve_content_url()`), so frontend assets enqueue correctly. Returns `''` only when the library genuinely sits outside every web-accessible root (e.g. a Bedrock application's root composer vendor outside `src/web`), in which case asset enqueue bails cleanly instead of emitting a 404ing URL. Note: HyperPress-Core has always resolved this constant from its own base directory; the related fix in HyperFields 1.4.1 (which previously defined HYPERPRESS_PLUGIN_URL by copying a broken HyperFields URL) now lets this library's own definition win unobstructed.

### Added
- `tests/Unit/AssetUrlResolverTest.php` — locks in that the shared resolver (HyperFields' `LibraryBootstrap::resolveContentUrl()`, which HyperPress delegates to) handles the nested plugin-vendor case and the Bedrock-root-vendor (non-web-accessible) empty case.
- `WP_PLUGIN_URL`/`WP_CONTENT_URL` constants in the test bootstrap so the resolver's production code path is exercised.

## [1.4.0] - 2026-07-16

### Changed
- **`scripts/version-bump.sh` gained non-interactive flag support.** Previously the script prompted interactively only; it now resolves the target version from flags first, falling back to the interactive prompt only when called with no arguments:
  - `--patch` / `--minor` / `--major` — compute the next version from the current `composer.json` version via a shared `bump_version()` helper (e.g. `1.3.5` + `--minor` → `1.4.0`).
  - `--version X.Y.Z` — explicit target, validated against `^[0-9]+\.[0-9]+\.[0-9]+$` and rejected if identical to the current version.
  - `-h` / `--help` prints usage; unknown arguments exit `2`.
  - Emits a final `RESULT: <cur> -> <new>` line for machine-parseable output.
  - No flags = unchanged interactive behavior (backwards compatible).
- No library, API, or runtime changes.

## [1.3.2] - 2026-07-07

### Security
- Pinned `estebanforge/hyperblocks` to the 1.2.0 release, which closes a critical auto-discovery bug that broke virtually every standard WordPress/ACF theme the moment HyperPress-Core loaded. HyperBlocks' `Registry::discoverAndLoadFluentBlocks()` globbed every registered block path and `require_once`d every `.hb.php`/`.php` match on `init`, executing WP-standard `render.php` files (co-located under `/blocks/<slug>/` and expecting a render context with `$block`, `$attributes`, `$content` in scope) out of context — echoing markup before `<!DOCTYPE html>`, hitting an undefined `$block`, and producing a cascade of warnings plus a fully broken page. HyperPress-Core is a direct vector for this because its block integration registers the active theme's `/blocks` directories as discovery paths, so any theme following the de-facto `/blocks/<slug>/{block.json,init.php,render.php}` layout was broken on install. The fix is upstream (see [hyperblocks 1.2.0]): candidate files are now parsed via `get_file_data()` (first 8 KB, never executed) and only files declaring a non-empty `HyperBlocks Block:` header are loaded; a new `hyperblocks/blocks/auto_discover_theme_blocks` filter adds defense-in-depth.

### Changed
- The HyperPress-Core consumer file `hyperblocks/fluent-demos/fluent-demos.hb.php` now carries the required `HyperBlocks Block:` docblock header so it survives the new header-gated auto-discovery intact. The header is namespace-agnostic (this file uses the `HyperPress\Blocks\Registry` proxy namespace, not `HyperBlocks\`), which is why the upstream fix deliberately rejected a `str_contains('HyperBlocks')` content-sniff — it would have missed exactly files like this one.
- `composer.json` — bumped `estebanforge/hyperblocks` to `^1` (resolving to 1.2.0) and package version to `1.3.2`.

### Upgrade notes
- This is a drop-in security fix. No HyperPress-Core API changes; consumers using `hp_register_block_path()` / `Config::registerBlockPath()` or the bundled demo blocks require no action.
- If you ship your own fluent block definition files loaded through HyperPress-Core's block integration (files that call `Block::make(...)` and `Registry::getInstance()->registerFluentBlock(...)` and rely on auto-discovery), add a `HyperBlocks Block: <title>` line to the file's top docblock, matching the upstream convention. Files you load via your own `require_once` are unaffected.

[hyperblocks 1.2.0]: ../HyperBlocks/CHANGELOG.md#120---2026-07-07

## [1.3.1] - 2026-07-07

### Changed
- Updated hyperfields and hyperblocks dependencies.

## [1.3.0] - 2026-07-03

### Changed
- Updated hyperfields and hyperblocks dependencies.

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
