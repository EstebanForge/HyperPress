# HyperPress-Core

HyperPress-Core is the runtime Composer library behind HyperPress.

It contains:
- endpoint routing and template rendering (`/wp-html/v1/`)
- assets/runtime integration for HTMX, Alpine AJAX, and Datastar
- admin options and compatibility layers
- block integration and orchestration with HyperBlocks and HyperFields

## Package role

This package is a library, not a WordPress.org plugin entrypoint.

The WordPress plugin adapter lives in `src/app/plugins/api-for-htmx/` and loads this library from Composer.

## Installation

```bash
composer require estebanforge/hyperpress-core
```

HyperPress-Core self-initializes (zero-config). Its `bootstrap.php` is a Composer `autoload.files` entry that schedules `Bootstrap::init()` at `after_setup_theme` (priority 0), and chains into the bundled HyperFields and HyperBlocks bootstraps so the whole stack comes up together. This works across every WordPress load order, including the Bedrock and WP-CLI early-load windows where `add_action()` is not yet available (the bootstrap writes the registration into `$GLOBALS['wp_filter']` in WordPress' preinitialized-hooks format). You do not need to call `init()` yourself.

If your host plugin uses a classmap-only autoloader that skips Composer `autoload.files`, require the bootstrap chain explicitly on `plugins_loaded` — see [Installation — Vendoring inside a host plugin](docs/installation.md#vendoring-inside-a-host-plugin). For the full bootstrap guide (explicit override, Bedrock dual-copy sites, asset hard-floor) see [`docs/library-bootstrap.md`](docs/library-bootstrap.md).

## Dependencies

- PHP >= 8.1
- `estebanforge/hyperfields`
- `estebanforge/hyperblocks`
- `starfederation/datastar-php`

## Development

Run tests with Pest v4:

```bash
composer run test
composer run test:unit
composer run test:integration
composer run test:feature
```

Coverage:

```bash
composer run test:coverage
composer run test:summary
composer run test:clover
```

## Docs

Technical docs for runtime behavior live in `docs/`.

## License

GPL-2.0-or-later
