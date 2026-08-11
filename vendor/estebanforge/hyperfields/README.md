# HyperFields

HyperFields is a Composer library for WordPress custom fields.

It provides:
- options pages
- post/user/term field containers
- field validation/sanitization
- conditional logic
- JSON export/import for options with typed-node schema validation
- JSON export/import for pages/CPT content
- pluggable transfer-module orchestration
- transfer audit logging with a built-in admin logs screen

## Installation

```bash
composer require estebanforge/hyperfields
```

Load your project Composer autoloader, then call the library bootstrap:

```php
require_once __DIR__ . '/vendor/autoload.php';

if (class_exists('\HyperFields\LibraryBootstrap')) {
    \HyperFields\LibraryBootstrap::init([
        'plugin_file' => __FILE__,
        'plugin_url'  => plugin_dir_url(__FILE__) . 'vendor/estebanforge/hyperfields/',
    ]);
}
```

HyperFields self-initializes (zero-config). Its `bootstrap.php` is a Composer
`autoload.files` entry that schedules `LibraryBootstrap::init()` at
`after_setup_theme` (priority 0). This works across every WordPress load
order, including the Bedrock and WP-CLI early-load windows where `add_action()`
is not yet available: there the bootstrap writes the registration straight
into `$GLOBALS['wp_filter']` in WordPress' preinitialized-hooks format, which
core converts into a real hook when `plugin.php` loads
(`WP_Hook::build_preinitialized_hooks`, since WP 4.7). You do not need to
call `init()` yourself.

Calling `LibraryBootstrap::init()` explicitly is still supported as an
optional deterministic override (for example, to pin a specific `plugin_file`
or `plugin_url`). It is idempotent and safe under the cross-copy election guard.
See [`docs/library-bootstrap.md`](docs/library-bootstrap.md) for the full
guide, arguments, the Bedrock dual-copy note, and the explicit-override
contract.

## Basic usage

```php
use HyperFields\Field;
use HyperFields\OptionsPage;

$page = OptionsPage::make('My Settings', 'my-settings');

$page->addField(
    Field::make('text', 'site_title', 'Site Title')
        ->setDefault('My Site')
        ->setRequired()
);

$page->register();
```

## Helper functions

Procedural helpers are available with `hf_` prefix (for example: `hf_field`, `hf_get_field`, `hf_update_field`, `hf_option_page`).

## Schema validation for JSON imports

JSON exports now include embedded type schemas alongside each value. When importing, HyperFields validates that values match their declared schemas, preventing malformed data or injection attacks.

See [`docs/transfer-export-import.md`](docs/transfer-export-import.md) for:
- Typed-node envelope format
- SchemaValidator API
- Building schema maps for exports
- Import validation flow
- Extending with custom format validators
- Transfer audit logging, retention controls, and logs UI hooks

## Requirements

- PHP 8.1+

## Testing

HyperFields uses Pest v4.

```bash
composer run test
composer run test:unit
composer run test:integration
composer run test:coverage
composer run test:xdebug
```

## License

GPL-2.0-or-later
