# HyperBlocks Examples

This directory contains example snippets demonstrating HyperBlocks usage. These files are NOT auto-activated and are provided for learning and reference purposes.

## 📁 What You'll Find

- Fluent API block examples (no JS build)
- WordPress-standard `block.json` examples (no custom JS required)
- Render template patterns (inline and file-based using `file:` prefix)
- Reusable Field Groups on blocks

## 🚀 Fluent API: Minimal Block Example

```php
use HyperPress\Blocks\Block;
use HyperPress\Blocks\FieldGroup;
use HyperPress\Blocks\Registry;
use HyperPress\Fields\HyperFields; // for field definitions

add_action('init', function () {
    // Define a field group (reusable)
    $group = new FieldGroup('cardFields');
    $group->addField(
        HyperFields::makeField('text', 'title', 'Title')->setDefault('Hello')
    );
    $group->addField(
        HyperFields::makeField('textarea', 'body', 'Body')
    );

    // Register the group
    Registry::getInstance()->registerFieldGroup($group);

    // Create a block
    $block = new Block('hyper/card');
    $block->setTitle('Hyper Card')
          ->setIcon('id')
          // Inline template (PHP allowed)
          ->setRenderTemplate('<div class="card"><RichText attribute="title" tag="h3" /><div class="content">{{ body }}</div></div>')
          ->addField( HyperFields::makeField('text', 'title', 'Title') )
          ->addField( HyperFields::makeField('textarea', 'body', 'Body') )
          ->addFieldGroup('cardFields');

    // Register with the registry (auto-registers on init)
    Registry::getInstance()->registerFluentBlock($block);
});
```

## 🗂️ Fluent API: File Template Example

```php
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Registry;
use HyperPress\Fields\HyperFields;

add_action('init', function () {
    $block = new Block('hyper/hero');
    $block->setTitle('Hyper Hero')
          ->setIcon('slides')
          // Use a file template; prefix is required
          ->setRenderTemplateFile('hyperblocks/hero/render')
          ->addField( HyperFields::makeField('text', 'title', 'Title')->setDefault('Welcome') )
          ->addField( HyperFields::makeField('text', 'subtitle', 'Subtitle') );

    Registry::getInstance()->registerFluentBlock($block);
});
```

Example `render.php` (relative to plugin root `HYPERPRESS_ABSPATH`):

```php
<section class="hero">
  <h1><?php echo esc_html($title ?? ''); ?></h1>
  <?php if (!empty($subtitle)) : ?>
    <p class="subtitle"><?php echo esc_html($subtitle); ?></p>
  <?php endif; ?>
</section>
```

Notes:
- File paths must be prefixed with `file:` and resolve under `WP_CONTENT_DIR` or `HYPERPRESS_ABSPATH`.
- Relative paths are resolved against `HYPERPRESS_ABSPATH`. See `HyperPress\Blocks\Renderer::validateTemplatePath()`.

## 🔤 Using RichText and InnerBlocks in Templates

Inline or file templates support lightweight component tags parsed at render time:

```html
<div class="article">
  <RichText attribute="title" tag="h2" style="margin:0" />
  <InnerBlocks />
</div>
```

- `<RichText attribute="..." tag="hN" style="..." />` outputs sanitized attribute content.
- `<InnerBlocks />` becomes `<!-- wp:innerblocks /-->` for WordPress to render child content.

## 📦 block.json Example (No Custom JS)

Directory structure under `hyperblocks/cta/`:

```
hyperblocks/
└── cta/
    ├── block.json
    └── render.php
```

`block.json`:

```json
{
  "name": "hyper/cta",
  "title": "Hyper CTA",
  "icon": "megaphone",
  "category": "widgets",
  "attributes": {
    "text": { "type": "string", "default": "Click me" },
    "url": { "type": "string", "default": "#" }
  },
  "supports": { "html": false }
}
```

`render.php`:

```php
<a class="cta" href="<?php echo esc_url($url ?? '#'); ?>">
  <?php echo esc_html($text ?? ''); ?>
</a>
```

Auto-discovery:
- Place block folders under `hyperblocks/` with a `block.json`.
- `HyperPress\Blocks\Registry` will auto-register and SSR using `render.php`.

## 👥 Reusable Field Groups on Blocks

```php
use HyperPress\Blocks\FieldGroup;
use HyperPress\Blocks\Block;
use HyperPress\Blocks\Registry;
use HyperPress\Fields\HyperFields;

add_action('init', function () {
    $media = new FieldGroup('media');
    $media->addField( HyperFields::makeField('text', 'caption', 'Caption') );

    Registry::getInstance()->registerFieldGroup($media);

    $block = new Block('hyper/image-card');
    $block->setTitle('Image Card')
          ->addFieldGroup('media')
          ->addField( HyperFields::makeField('text', 'title', 'Title') )
          ->setRenderTemplate('<figure><figcaption>{{ caption }}</figcaption></figure>');

    Registry::getInstance()->registerFluentBlock($block);
});
```

## 🔧 How to Use These Examples

- Copy snippets into your theme/plugin and adjust namespaces/paths.
- For file templates, ensure the path exists under your plugin (or content) directory and prefix with `file:`.
- Blocks are registered on `init` via the `Registry` singleton.

## ⚠️ Important Notes

- Examples do not auto-activate.
- Follow WordPress coding standards and escape output.
- Fluent API attributes are generated and sanitized using HyperFields adapters at render time.
- JSON blocks are registered via `block.json` and rendered by `render.php` automatically.

## 📚 More Documentation

- See `docs/hyperblocks.md` for an overview, auto-discovery, and technical details.
- See `docs/hyperfields.md` for field definitions and sanitization.
