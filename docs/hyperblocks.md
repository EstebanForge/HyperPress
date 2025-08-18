# HyperBlocks

PHP-first Gutenberg blocks for WordPress.

## Overview

HyperBlocks enables two complementary ways to build blocks:
- Fluent PHP API (no JavaScript build)
- WordPress-standard `block.json` (no custom JS required)

Both approaches share the same editor UI and server-side rendering engine.

## Features
- Auto-discovery of blocks in `/hyperblocks/` directories
- Secure PHP template execution (SSR)
- Reusable field groups
- REST endpoints for dynamic block fields and live previews
- Works alongside existing WordPress blocks

## Block Fields in Inspector

HyperFields can render block settings in the block Inspector.

- Editor script wraps `editor.BlockEdit` to render InspectorControls
- REST endpoint provides field definitions per block name

Endpoint:
```
/hyperblocks/v1/block-fields?name=<blockName>
```

Editor integration script: `assets/js/hyperblocks-editor.js`

## REST API

Two endpoints back the editor integration and server-side previews:

- GET `/hyperblocks/v1/block-fields?name=<blockName>`
  - Returns field definitions for the requested block name.
  - Permission: open (`__return_true`).
  - Sources fields from Fluent PHP blocks first, then falls back to JSON blocks.

- POST `/hyperblocks/v1/render-preview`
  - Body: `{ "blockName": string, "attributes": object }`
  - Permission: `current_user_can('edit_posts')`.
  - For Fluent blocks: merges block fields and attached field groups; sanitizes incoming attributes via HyperFields `BlockFieldAdapter` and defaults missing/invalid values.
  - For JSON blocks: discovers the block directory and renders `render.php` with provided attributes. Returns `{ success: true, html }` or `{ success: false, error }`.

### Discovery paths for JSON blocks

- Scans `HYPERPRESS_ABSPATH . '/hyperblocks'`.
- Additional paths can be provided via filter: `hyperpress/blocks/register_json_paths`.

### Notes on sanitization and security

- Attributes are sanitized against HyperFields definitions before rendering.
- `render-preview` requires edit capability; avoid exposing it to unauthenticated users.

## Getting Started
- Create a folder `hyperblocks/your-block/`
- EITHER add a `block.json` + PHP render template
- OR register via the Fluent PHP API (WIP docs)
- Define fields for the block via HyperFields

## Full Guide

### Why HyperBlocks?
- No JavaScript Required: Build complex blocks using only PHP
- Unified Editor: Both approaches use the same React editor component
- WordPress Standards: Full compatibility with WordPress block API
- Developer Choice: Pick the approach that fits your workflow
- Auto-Discovery: Automatic block registration from `hyperblocks/` directory

### Two Development Approaches

#### 1. Fluent API
Pure PHP, rapid development - Single file approach

```php
<?php
// hyperblocks/hero-banner.hb.php - Complete implementation in one file
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Field;

$heroBlock = Block::make('Hero Banner')
    ->setIcon('format-image')
    ->addFields([
        Field::make('text', 'headline', 'Headline')
            ->setPlaceholder('Enter your headline')
            ->setRequired(true),
        Field::make('textarea', 'subtitle', 'Subtitle')
            ->setPlaceholder('Enter subtitle text'),
        Field::make('color', 'background_color', 'Background Color')
            ->setDefault('#ffffff'),
        Field::make('color', 'text_color', 'Text Color')
            ->setDefault('#333333'),
        Field::make('url', 'button_url', 'Button URL'),
        Field::make('text', 'button_text', 'Button Text')
            ->setDefault('Learn More')
    ])
    ->setRenderTemplate('
        <div class="hero-banner" style="background-color: {{background_color}}; color: {{text_color}}; padding: 2rem; text-align: center;">
            <RichText attribute="headline" tag="h1" style="font-size: 3rem; margin-bottom: 1rem;" placeholder="Enter headline..." />
            <RichText attribute="subtitle" tag="p" style="font-size: 1.5rem; margin-bottom: 2rem; opacity: 0.8;" placeholder="Enter subtitle..." />
            <a href="{{button_url}}" class="hero-button" style="background: {{text_color}}; color: {{background_color}}; padding: 1rem 2rem; text-decoration: none; border-radius: 4px;">{{button_text}}</a>
        </div>
    ');

// Register the block (auto-discovery will handle this)
return $heroBlock;
```

#### 2. block.json Approach (WordPress Standard)
Multiple files, WordPress conventions

File Structure:
```
hyperblocks/my-hero-block/
├── block.json      (configuration)
├── render.php      (template)
└── editor.js       (React editor - optional)
```

block.json:
```json
{
    "name": "my-plugin/hero-banner",
    "title": "Hero Banner",
    "description": "A customizable hero banner block",
    "category": "layout",
    "icon": "format-image",
    "attributes": {
        "headline": {
            "type": "string",
            "default": "Welcome to Our Site"
        },
        "subtitle": {
            "type": "string",
            "default": "Amazing things happen here"
        },
        "backgroundColor": {
            "type": "string",
            "default": "#ffffff"
        },
        "textColor": {
            "type": "string",
            "default": "#333333"
        },
        "buttonUrl": {
            "type": "string",
            "default": "#"
        },
        "buttonText": {
            "type": "string",
            "default": "Learn More"
        }
    },
    "supports": {
        "align": ["wide", "full"],
        "html": false
    },
    "render": "file:./render.php"
}
```

render.php:
```php
<?php
$headline = $attributes['headline'] ?? 'Welcome to Our Site';
$subtitle = $attributes['subtitle'] ?? 'Amazing things happen here';
$backgroundColor = $attributes['backgroundColor'] ?? '#ffffff';
$textColor = $attributes['textColor'] ?? '#333333';
$buttonUrl = $attributes['buttonUrl'] ?? '#';
$buttonText = $attributes['buttonText'] ?? 'Learn More';
?>

<div class="hero-banner" style="background-color: <?php echo esc_attr($backgroundColor); ?>; color: <?php echo esc_attr($textColor); ?>; padding: 2rem; text-align: center;">
    <h1><?php echo esc_html($headline); ?></h1>
    <p><?php echo esc_html($subtitle); ?></p>
    <a href="<?php echo esc_url($buttonUrl); ?>" class="hero-button">
        <?php echo esc_html($buttonText); ?>
    </a>
</div>
```

### Side-by-Side Comparison

| Aspect                   | Fluent API       | block.json          |
| ------------------------ | ---------------- | ------------------- |
| Files Required           | 1 file per block | 2-3 files per block |
| Development Speed        | Very fast        | Moderate            |
| JavaScript Knowledge     | Not required     | Not required        |
| WordPress Standards      | Custom approach  | Official standard   |
| Customization Level      | Template-based   | Full control        |
| Reusability              | Field groups     | Component libraries |

### Auto-Discovery & Registration

Automatic block discovery works from:
- `wp-content/themes/your-theme/hyperblocks/`
- `wp-content/plugins/your-plugin/hyperblocks/`

Supported formats:
- `.hb.php` files for Fluent API blocks
- `block.json` files for WordPress standard blocks
- Ignored files: Prefix directory/filenames with `_` to disable

Example directory structure:
```
hyperblocks/
├── hero-banner.hb.php          # Fluent API
├── content-card.hb.php         # Fluent API
├── quote-block/
│   ├── block.json              # WordPress standard
│   ├── render.php
│   └── editor.js
├── _draft-block/               # Disabled (ignored)
│   └── draft.hb.php
└── _deprecated/                # Disabled directory
    └── old-block.hb.php
```

### Manual Block Registration

For developers who need to register blocks from outside the auto-discovery directories (e.g., from a separate plugin or a theme), HyperPress provides filters to manually add blocks.

#### Registering a Fluent API Block

Use the `hyperpress/blocks/register_fluent_blocks` filter to add the absolute path to your `.hb.php` file.

```php
add_filter('hyperpress/blocks/register_fluent_blocks', function ($blocks) {
    // Path to the block file in your plugin/theme
    $blocks[] = MY_PLUGIN_PATH . 'path/to/my-custom-fluent-block.hb.php';
    return $blocks;
});
```

#### Registering a `block.json` Block

Use the `hyperpress/blocks/register_json_blocks` filter to add the absolute path to your block's directory.

```php
add_filter('hyperpress/blocks/register_json_blocks', function ($blocks) {
    // Path to the block directory in your plugin/theme
    $blocks[] = MY_PLUGIN_PATH . 'path/to/my-custom-json-block/';
    return $blocks;
});
```

### When to Choose Which?

Choose Fluent API when:
- Rapid prototyping is priority
- PHP-only team (no JavaScript developers)
- Quick iterations needed
- Plugin/theme bundling for distribution
- Template-heavy blocks (lots of HTML/CSS)

Choose block.json when:
- WordPress standards compliance is required
- Schema validation needed
- Standard WordPress tooling preferred
- Enterprise development with strict standards

### Technical Details

Both approaches use:
- Same Rendering Engine: `HyperPress\Blocks\Renderer`
- Same REST API: `/hyperblocks/v1/block-fields`, `/hyperblocks/v1/render-preview`
- Same Custom Components: `<RichText>`, `<InnerBlocks>`, `<MediaUpload>`
- Same Field Types: `text`, `textarea`, `color`, `url`, `media`, `repeater`, `tabs` (field organization)
- `association`, `map`, `date`, `datetime`, `time`
- Same React Editor: Unified generic editor component

### Demo Blocks Included

The plugin includes three identical demo blocks implemented in both approaches:

1. Hero Banner - Landing page headers with headline, subtitle, and CTA
2. Content Card - Product cards with image, title, and content
3. Quote Block - Testimonials with quote, author, and styling

To test:
1. Go to Posts → Add New
2. Search for blocks: "Hero Banner", "Hero Banner (JSON)", etc.
3. Compare identical functionality between approaches

## Notes
- Use plugin constants (e.g., `HYPERPRESS_PLUGIN_URL`, `HYPERPRESS_VERSION`) when enqueueing any assets.
- Capability checks and nonces apply when persisting block data via HyperFields.
