# HyperBlocks

A powerful, modular block library for WordPress with a fluent API and deep integration with HyperFields.

## Features

- **Fluent API**: Create blocks with an intuitive, chainable API
- **Field Integration**: Seamless integration with HyperFields for field management
- **Template Rendering**: Secure, isolated PHP template rendering
- **Auto-Discovery**: Automatic block discovery from configured paths
- **JSON Block Support**: Full support for standard `block.json` blocks
- **REST API**: Built-in REST API for block field discovery and preview rendering
- **Backward Compatible**: Facade layer ensures smooth migration from HyperPress

## Installation

```bash
composer require estebanforge/hyperblocks
```

## Quick Start

### Creating a Block

```php
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;

// Define a block
$block = Block::make('Hero Banner')
    ->setName('my-theme/hero-banner')
    ->setIcon('star-filled')
    ->addFields([
        Field::make('text', 'heading', 'Heading')
            ->setDefault('Welcome'),
        Field::make('textarea', 'subheading', 'Subheading')
            ->setPlaceholder('Enter subheading'),
        Field::make('image', 'background', 'Background Image'),
    ])
    ->setRenderTemplate('file:blocks/hero-banner.hb.php');

// Register the block
use HyperBlocks\Registry;
Registry::getInstance()->registerFluentBlock($block);
```

### Helper Functions

```php
// Create a block using helper
hyperblocks_register_block(
    hyperblocks_block('Hero Banner')
        ->setName('my-theme/hero')
        ->addFields([
            hyperblocks_field('text', 'title', 'Title'),
        ])
);
```

## Configuration

### Register Block Discovery Paths

```php
use HyperBlocks\Config;

// Register custom block paths
Config::registerBlockPath(get_stylesheet_directory() . '/blocks');
Config::registerBlockPath(plugin_dir_path(__FILE__) . 'custom-blocks');
```

### Configure Template Extensions

```php
use HyperBlocks\Config;

// Set custom template extensions
Config::set('template_extensions', '.hb.php,.php,.html');
```

### Enable Debug Mode

```php
use HyperBlocks\Config;

// Enable debug mode for development
Config::set('debug', true);
```

## Template Files

### Example Template (blocks/hero-banner.hb.php)

```php
<?php
/**
 * Hero Banner Block Template
 *
 * @var string $heading The heading text.
 * @var string $subheading The subheading text.
 * @var int $background The background image ID.
 */

$imageUrl = wp_get_attachment_image_url($background, 'full');
?>

<section class="hero-banner" style="background-image: url(<?php echo esc_url($imageUrl); ?>);">
    <div class="hero-content">
        <h1 class="hero-heading"><?php echo esc_html($heading); ?></h1>
        <?php if (!empty($subheading)): ?>
            <p class="hero-subheading"><?php echo esc_html($subheading); ?></p>
        <?php endif; ?>
    </div>
</section>
```

## Field Groups

### Reusable Field Groups

```php
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\Block\Field;

// Define a field group
$contentGroup = FieldGroup::make('Content Fields', 'content')
    ->addFields([
        Field::make('text', 'title', 'Title')
            ->setRequired(true),
        Field::make('textarea', 'description', 'Description'),
    ]);

// Register the group
Registry::getInstance()->registerFieldGroup($contentGroup);

// Use the group in a block
$block = Block::make('Card')
    ->setName('my-theme/card')
    ->addFieldGroup('content')
    ->addFields([
        Field::make('image', 'image', 'Image'),
    ]);
```

## REST API

HyperBlocks provides REST API endpoints for block management:

### Get Block Fields

```
GET /wp-json/hyperblocks/v1/block-fields?name=my-theme/hero-banner
```

### Render Preview

```
POST /wp-json/hyperblocks/v1/render-preview

{
  "blockName": "my-theme/hero-banner",
  "attributes": {
    "heading": "Welcome",
    "background": 123
  }
}
```

## WordPress Integration

### Bootstrap HyperBlocks

```php
// In your plugin or theme
add_action('plugins_loaded', function() {
    \HyperBlocks\WordPress\Bootstrap::init();
});
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `block_paths` | `array` | `[]` | Paths to scan for blocks |
| `template_extensions` | `string` | `.hb.php,.php` | Template file extensions |
| `auto_discovery` | `bool` | `true` | Enable auto-discovery |
| `debug` | `bool` | `false` | Enable debug mode |
| `cache_blocks` | `bool` | `true` | Cache rendered blocks |
| `rest_namespace` | `string` | `hyperblocks/v1` | REST API namespace |

## Migration from HyperPress

If you're migrating from `HyperPress\Blocks\*`, the API is nearly identical:

```php
// Old (HyperPress)
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Field;

// New (HyperBlocks)
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
```

All existing code continues to work through the backward compatibility facade.

## Requirements

- PHP 8.1+
- WordPress 5.0+
- HyperFields ^1.0

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

GPL-2.0-or-later

## Links

- [Documentation](https://github.com/EstebanForge/HyperBlocks/blob/main/docs/)
- [Issues](https://github.com/EstebanForge/HyperBlocks/issues)
- [HyperFields](https://github.com/EstebanForge/HyperFields)
