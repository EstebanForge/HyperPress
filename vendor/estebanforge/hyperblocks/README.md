# HyperBlocks

HyperBlocks is a Composer library for PHP-first Gutenberg block development.

It provides:
- Fluent API block definitions
- `block.json`-compatible registration flow
- Reusable field groups
- REST endpoints for block field discovery and server-side preview
- HyperFields integration (field sanitization, validation, block attributes)

## Requirements

- PHP 8.2+
- WordPress latest
- `estebanforge/hyperfields` ^1.0 (installed automatically)

## Installation

```bash
composer require estebanforge/hyperblocks
```

Load your project Composer autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

HyperBlocks bootstrap is registered via Composer `autoload.files`. HyperFields is bootstrapped automatically — no extra configuration needed.

## Quick start

```php
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

$block = Block::make('Hero Banner')
    ->setName('my-theme/hero-banner')
    ->setIcon('cover-image')
    ->addFields([
        Field::make('text', 'heading', 'Heading')->setDefault('Welcome'),
        Field::make('textarea', 'subheading', 'Subheading'),
        Field::make('image', 'background_image', 'Background Image'),
    ])
    ->setRenderTemplateFile('blocks/hero-banner.hb.php');

Registry::getInstance()->registerFluentBlock($block);
```

Or with the procedural helpers:

```php
hyperblocks_register_block(
    hyperblocks_block('Hero Banner')
        ->setName('my-theme/hero-banner')
        ->addFields([
            hyperblocks_field('text', 'heading', 'Heading')->setDefault('Welcome'),
        ])
        ->setRenderTemplateFile('blocks/hero-banner.hb.php')
);
```

## Field types

| Type | Notes |
|---|---|
| `text` | Single-line text |
| `textarea` | Multi-line text |
| `email` | Email address |
| `url` | URL |
| `number` | Numeric value |
| `color` | Color picker |
| `date` | Date |
| `time` | Time |
| `datetime` | Date + time |
| `image` | Attachment ID (integer) |
| `file` | File URL or ID |
| `select` | Single choice; use `setOptions(['key' => 'Label'])` |
| `multiselect` | Multiple choices |
| `checkbox` | Boolean |
| `radio` | Single choice with radio UI |
| `rich_text` | Rich text / WYSIWYG |
| `hidden` | Hidden value |
| `html` | Raw HTML |
| `map` | Map embed |
| `oembed` | oEmbed URL |
| `separator` | Visual separator (no value) |
| `heading` | Visual heading (no value) |
| `media_gallery` | Multiple media items |
| `repeater` | Repeatable field group |

## Templates

Template files use the `.hb.php` extension by default. All block attributes are extracted as PHP variables:

```php
// Template for a block with fields: heading (text), bg_image (image)
// $heading and $bg_image are available directly.

$image_url = wp_get_attachment_image_url($bg_image, 'full') ?: '';
?>
<section style="background-image: url('<?php echo esc_url($image_url); ?>');">
    <h1><?php echo esc_html($heading); ?></h1>
</section>
```

Two pseudo-components are available inside templates:

```html
<!-- RichText: renders the named attribute inside any HTML tag -->
<RichText attribute="heading" tag="h1" placeholder="Enter heading" />
<RichText attribute="body" tag="p" style="color: #333;" />

<!-- InnerBlocks: replaced with a WordPress inner-block placeholder -->
<InnerBlocks />
```

## Reusable field groups

```php
use HyperBlocks\Block\FieldGroup;

$group = FieldGroup::make('Layout Settings', 'layout-settings')
    ->addFields([
        Field::make('select', 'alignment', 'Alignment')
            ->setOptions(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
            ->setDefault('center'),
        Field::make('checkbox', 'show_border', 'Show Border')->setDefault(false),
    ]);

Registry::getInstance()->registerFieldGroup($group);

// Attach to a block — block fields take precedence over group fields on name collision
$block->addFieldGroup('layout-settings');
```

## Registering block discovery paths

HyperBlocks automatically scans the theme's `blocks/` directory. Add extra paths:

```php
use HyperBlocks\Config;

// Via static method
Config::registerBlockPath(get_stylesheet_directory() . '/my-blocks');

// Via WordPress filter
add_filter('hyperblocks/blocks/register_fluent_paths', function (array $paths): array {
    $paths[] = get_stylesheet_directory() . '/my-blocks';
    return $paths;
});
```

## REST API

| Endpoint | Method | Auth | Description |
|---|---|---|---|
| `/wp-json/hyperblocks/v1/block-fields?name=ns/slug` | GET | public | Returns field definitions for a block. |
| `/wp-json/hyperblocks/v1/render-preview` | POST | `edit_posts` | Server-side renders a block with supplied attributes. |

Preview request body:
```json
{ "blockName": "my-theme/hero-banner", "attributes": { "heading": "Hello" } }
```

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
- Detailed reference: [AGENTS.md](AGENTS.md)
