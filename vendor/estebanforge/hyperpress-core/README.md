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

If your project loads Composer autoload (`vendor/autoload.php`), HyperPress-Core bootstrap is loaded automatically via Composer `autoload.files`.

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
