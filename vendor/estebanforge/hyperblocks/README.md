# HyperBlocks

HyperBlocks is a Composer library for PHP-first Gutenberg block development.

It provides:
- fluent API block definitions
- block.json-compatible registration flow
- reusable field groups
- REST endpoints for block field discovery and preview
- HyperFields integration

## Installation

```bash
composer require estebanforge/hyperblocks
```

Load your project Composer autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

HyperBlocks bootstrap is registered via Composer `autoload.files`.

## Quick start

```php
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

$block = Block::make('Hero Banner')
    ->setName('my-theme/hero-banner')
    ->addFields([
        Field::make('text', 'heading', 'Heading')->setDefault('Welcome'),
        Field::make('textarea', 'subheading', 'Subheading'),
    ])
    ->setRenderTemplate('file:blocks/hero-banner.hb.php');

Registry::getInstance()->registerFluentBlock($block);
```

## Requirements

- PHP 8.1+
- `estebanforge/hyperfields` ^1.0

## Testing

HyperBlocks uses Pest v4.

```bash
composer run test
composer run test:unit
composer run test:integration
composer run test:coverage
```

## License

GPL-2.0-or-later

## Links

- Issues: https://github.com/EstebanForge/HyperBlocks/issues
- Source: https://github.com/EstebanForge/HyperBlocks
