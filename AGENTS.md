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

Tests are Pest v4 with Brain Monkey (dev) for WordPress hook/function mocking. Coverage is layered: structural contracts, autoload/Config-identity smoke tests, and adapter hook-wiring assertions (Brain Monkey confirms the right filters/actions are registered). Brain Monkey's `apply_filters` does not execute registered closures, so closure-internal behavior is tested by extracting the pure logic (system_info insertion, the review-milestone predicates) into functions under direct unit test. The nonce-gated `admin_init` dismissal handler is covered by a wiring assertion only.

## Library mode

When HyperPress-Core is consumed as a Composer library (no `api-for-htmx.php` active), the Settings page is hidden by default. Opt in:

```php
add_filter('hyperpress/admin/show_menu', '__return_true');
```
