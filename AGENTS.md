# HyperPress (`api-for-htmx`) — Plugin Adapter

**Package**: `estebanforge/hyperpress` (WordPress.org plugin)
**Repository**: https://github.com/EstebanForge/HyperPress

## What this is

HyperPress is a **thin WordPress plugin adapter** published on WordPress.org. It bundles three Composer libraries and delegates all runtime behavior to them:

- **HyperPress-Core** (`estebanforge/hyperpress-core`) — the runtime: REST routing (`/wp-html/v1/`), rendering, assets, admin, block wiring.
- **HyperFields** (`estebanforge/hyperfields`) — custom fields, options pages, export/import.
- **HyperBlocks** (`estebanforge/hyperblocks`) — PHP-first Gutenberg blocks.

The plugin itself contains **no runtime classes**. Its job is narrow: load the Composer autoloader, wire the adapter-level hooks the plugin owns (the HyperFields Data Tools page, activation/deactivation, the About system-info rows), and let the libraries self-initialize. Runtime architecture (Router, Render, Assets, Blocks, Libraries, Main, Config) lives in HyperPress-Core — see that library's `AGENTS.md`.

## Why `vendor/` is committed

WordPress.org users install a zip and cannot run Composer. So `vendor/` is **committed to this repository**, runtime dependencies only — the same pattern WP Rocket uses. Dev dependencies (phpunit, pest, php-cs-fixer) are not tracked: they are present locally after `composer install` but excluded from commits.

The plugin loads the bundled libraries **unprefixed**, exactly as Composer dictates. Duplicate-load safety comes from each library's first-to-boot `LOADED` guard (and `function_exists` on the global helpers), not from namespace prefixing. If another plugin bundles a different version of the same library, the first to boot wins — acceptable for this plugin, which is the primary distribution. Namespace prefixing remains an optional escape hatch for third-party developers, documented in the libraries.

Release build:

```bash
composer production   # composer install --no-dev --optimize-autoloader
```

WordPress.org publication is via SVN (`scripts/deploy.sh`), separate from this git repository.

## Files

```
api-for-htmx.php   # Entrypoint: header, version, show-menu opt-in, loads bootstrap.php
bootstrap.php      # Loads Composer autoloader; wires Data Tools page, activation, system-info
uninstall.php      # Cleans up DB options on uninstall
composer.json      # wordpress-plugin type; requires hyperpress-core (+ transitive HF/HB)
tests/Unit/        # Pest: adapter contract + library integration smoke tests
vendor/            # Committed runtime dependencies (WP.org distribution)
```

## Initialization model

Each bundled library self-initializes: its `bootstrap.php` is a Composer `autoload.files` entry that schedules its `init()` at `after_setup_theme` behind a first-to-boot, namespace-scoped `LOADED` guard. The plugin registers **no** multi-instance election machinery (removed from the libraries). Two plugins shipping HyperPress will not double-init or fatal — the first to boot wins.

`bootstrap.php` checks Core presence via `class_exists(\HyperPress\Bootstrap::class)` and uses `::class` references throughout (prefix-safe hygiene, should a consumer ever prefix the libraries).

For the full duplicate-load behavior and the optional Mozart prefix path for guaranteed isolation, see HyperFields `docs/library-bootstrap.md`.

## Helper functions

Global, bare-callable, one prefix per library (matching how WordPress itself exposes helpers):

- `hp_*` — HyperPress (`hp_get_endpoint_url()`, `hp_get_option()`, …)
- `hf_*` — HyperFields (`hf_field()`, `hf_get_field()`, …)
- `hb_*` — HyperBlocks (`hb_block()`, `hb_field()`, …)

All are `function_exists`-guarded for first-to-boot safety.

## Development

```bash
composer install                # runtime + dev deps locally
composer run test               # Pest suite (auto-installs if needed)
composer run cs:fix             # php-cs-fixer
composer dump-autoload --optimize
```

Tests are Pest v4 with Brain Monkey (dev) for WordPress hook/function mocking. Coverage is layered: structural contracts, autoload/Config-identity smoke tests, and adapter hook-wiring assertions (Brain Monkey confirms the right filters/actions are registered). Brain Monkey's `apply_filters` does not execute registered closures, so closure-internal behavior is tested by extracting the pure logic (system_info insertion) into functions under direct unit test.

## Library mode

When HyperPress-Core is consumed as a Composer library (no `api-for-htmx.php` active), the Settings page is hidden by default. Opt in:

```php
add_filter('hyperpress/admin/show_menu', '__return_true');
```

## Abilities API + MCP (3.6.1+)

The bundled libraries register their capabilities as WordPress Abilities (core 6.9+; silent no-op below). Nothing is exposed by default: sites opt in per package through kill-switch, REST-exposure, and MCP-public filters documented in each library's AGENTS.md. `examples/mcp-server-mu-plugin.php` is the documented opt-in path for MCP: copy to `wp-content/mu-plugins/`, requires the official [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin. `examples/` is svn-ignored and never ships in the wp.org zip. Requires at least 6.9 is declared in `readme.txt` and the plugin header because of this feature set; older cores run everything except the abilities module.

## Release Protocol (`api-for-htmx` Plugin)

Follow this sequence when releasing a new version of the HyperPress plugin:

1. **Prerequisite**: Ensure underlying libraries (`HyperFields`, `HyperBlocks`, `HyperPress-Core`) are already released, tagged, pushed, and visible on Packagist.
2. **Update dependency requirement**:
   In `composer.json`, ensure `"estebanforge/hyperpress-core": "*"` is set.
3. **Update vendored libraries & lockfile**:
   ```bash
   composer update estebanforge/hyperpress-core estebanforge/hyperfields estebanforge/hyperblocks
   ```
4. **Run test suite**:
   ```bash
   composer test
   ```
5. **Bump plugin version**:
   ```bash
   bash scripts/version-bump.sh --patch   # or --minor / --major / --version X.Y.Z
   ```
   Synchronizes `composer.json`, `api-for-htmx.php`, `README.txt`, and `SECURITY.md`.
6. **Update `CHANGELOG.md`**:
   Add `## [X.Y.Z] - YYYY-MM-DD` section detailing changes and updated vendored package versions.
7. **Synchronize lock file & strip dev dependencies**:
   ```bash
   composer update --lock
   composer run production   # runs @php composer install --no-dev --optimize-autoloader
   ```
8. **Sanity Check**:
   Bring up the Docker dev environment and verify site and admin operation:
   ```bash
   ./devenv start
   ```
   Verify:
   - Site loads via HTTPS (restart webserver container if needed: `docker restart hypermedia_local_webserver_1`)
   - Login to WP Admin (`esteban` / `cuevas`)
   - Navigate to **Settings > HyperPress** (`options-general.php?page=hyperpress-options`)
   - Verify UI rendering, `.hp-*` / `.hf-*` CSS classes, modify a setting, submit form, verify settings update and persist in DB (`./wp option get hyperpress_options --format=json`).
9. **Commit, Tag & Push**:
   ```bash
   git add CHANGELOG.md README.txt SECURITY.md api-for-htmx.php composer.json composer.lock vendor/
   # Commit via Conventional Commits:
   chore(release): X.Y.Z
   git push origin main
   git tag -a X.Y.Z -m "Release X.Y.Z"
   git push origin X.Y.Z
   ```

