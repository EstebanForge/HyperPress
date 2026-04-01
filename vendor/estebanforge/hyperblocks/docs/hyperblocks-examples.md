# HyperBlocks Examples

Practical, copy-ready patterns for building blocks with HyperBlocks. All examples use the fluent PHP API and `.hb.php` templates.

---

## Where to register blocks

Register blocks on the `init` action or, if you need to ensure HyperFields is initialized first, on `after_setup_theme` (after priority 0). The safest hook is `init`.

```php
add_action('init', function (): void {
    // register blocks here
});
```

If you use the auto-discovery system, place your block definition files inside any directory registered with `Config::registerBlockPath()`. HyperBlocks scans those directories on `init` and includes every `.hb.php` file it finds.

---

## Example 1 — Simple text block

The minimal case: a block with one text field and a file template.

**Block definition** (`my-plugin/blocks/announcement.php`):

```php
<?php
if (!defined('ABSPATH')) exit;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

Registry::getInstance()->registerFluentBlock(
    Block::make('Announcement')
        ->setName('my-theme/announcement')
        ->setIcon('megaphone')
        ->addFields([
            Field::make('text', 'message', 'Message')
                ->setDefault('Important notice')
                ->setRequired(true),
            Field::make('select', 'type', 'Type')
                ->setOptions([
                    'info'    => 'Info',
                    'warning' => 'Warning',
                    'error'   => 'Error',
                ])
                ->setDefault('info'),
        ])
        ->setRenderTemplateFile('blocks/announcement.hb.php')
);
```

**Template** (`my-theme/blocks/announcement.hb.php`):

```php
<?php
/**
 * Announcement Block Template.
 *
 * @var string $message The announcement text.
 * @var string $type    Type: info | warning | error.
 */
?>
<div class="hb-announcement hb-announcement--<?php echo esc_attr($type); ?>">
    <p><?php echo esc_html($message); ?></p>
</div>
```

---

## Example 2 — Hero banner with image and CTA

A full hero section showing image fields, color fields, and conditional output.

**Block definition**:

```php
<?php
if (!defined('ABSPATH')) exit;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

Registry::getInstance()->registerFluentBlock(
    Block::make('Hero Banner')
        ->setName('my-theme/hero-banner')
        ->setIcon('cover-image')
        ->addFields([
            Field::make('text', 'heading', 'Heading')
                ->setDefault('Welcome')
                ->setRequired(true),
            Field::make('textarea', 'subheading', 'Subheading')
                ->setPlaceholder('Optional supporting text'),
            Field::make('image', 'background_image', 'Background Image')
                ->setHelp('Recommended size: 1920×1080px'),
            Field::make('color', 'overlay_color', 'Overlay Color')
                ->setDefault('rgba(0,0,0,0.45)')
                ->setHelp('Overlay placed over the background image'),
            Field::make('text', 'cta_text', 'Button Label')
                ->setDefault('Learn More'),
            Field::make('url', 'cta_url', 'Button URL')
                ->setPlaceholder('https://'),
        ])
        ->setRenderTemplateFile('blocks/hero-banner.hb.php')
);
```

**Template** (`blocks/hero-banner.hb.php`):

```php
<?php
/**
 * Hero Banner Block Template.
 *
 * @var string $heading          Main heading text.
 * @var string $subheading       Supporting text.
 * @var int    $background_image Attachment ID.
 * @var string $overlay_color    CSS color value.
 * @var string $cta_text         Button label.
 * @var string $cta_url          Button URL.
 */

$bg_url      = $background_image ? wp_get_attachment_image_url($background_image, 'full') : '';
$show_cta    = !empty($cta_text) && !empty($cta_url);
$bg_style    = $bg_url ? 'background-image:url(' . esc_url($bg_url) . ');' : '';
$overlay_css = !empty($overlay_color) ? 'background-color:' . esc_attr($overlay_color) . ';' : '';
?>
<section class="hb-hero" style="<?php echo $bg_style; ?>">
    <?php if ($overlay_css): ?>
        <div class="hb-hero__overlay" style="<?php echo $overlay_css; ?>"></div>
    <?php endif; ?>
    <div class="hb-hero__content">
        <h1 class="hb-hero__heading"><?php echo esc_html($heading); ?></h1>
        <?php if (!empty($subheading)): ?>
            <p class="hb-hero__sub"><?php echo esc_html($subheading); ?></p>
        <?php endif; ?>
        <?php if ($show_cta): ?>
            <a href="<?php echo esc_url($cta_url); ?>" class="hb-hero__cta">
                <?php echo esc_html($cta_text); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
```

---

## Example 3 — Card block with select and checkbox

Demonstrates option lists and boolean fields.

**Block definition**:

```php
<?php
if (!defined('ABSPATH')) exit;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

Registry::getInstance()->registerFluentBlock(
    Block::make('Feature Card')
        ->setName('my-theme/feature-card')
        ->setIcon('media-text')
        ->addFields([
            Field::make('text', 'title', 'Title')
                ->setRequired(true),
            Field::make('textarea', 'body', 'Body text'),
            Field::make('image', 'thumbnail', 'Thumbnail'),
            Field::make('select', 'size', 'Card Size')
                ->setOptions(['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'])
                ->setDefault('md'),
            Field::make('checkbox', 'elevated', 'Add drop shadow')
                ->setDefault(false),
            Field::make('color', 'accent', 'Accent Color')
                ->setDefault('#4a90e2'),
            Field::make('url', 'link', 'Card Link URL')
                ->setPlaceholder('Optional — makes the whole card clickable'),
        ])
        ->setRenderTemplateFile('blocks/feature-card.hb.php')
);
```

**Template** (`blocks/feature-card.hb.php`):

```php
<?php
/**
 * Feature Card Block Template.
 *
 * @var string $title     Card title.
 * @var string $body      Body text.
 * @var int    $thumbnail Attachment ID.
 * @var string $size      sm | md | lg.
 * @var bool   $elevated  Drop shadow toggle.
 * @var string $accent    CSS color.
 * @var string $link      Optional URL.
 */

$classes    = 'hb-card hb-card--' . esc_attr($size) . ($elevated ? ' hb-card--elevated' : '');
$thumb_url  = $thumbnail ? wp_get_attachment_image_url($thumbnail, 'medium') : '';
$accent_css = !empty($accent) ? '--card-accent:' . esc_attr($accent) . ';' : '';

$open_tag  = $link ? '<a href="' . esc_url($link) . '" class="' . $classes . '" style="' . $accent_css . '">'
                   : '<div class="' . $classes . '" style="' . $accent_css . '">';
$close_tag = $link ? '</a>' : '</div>';
echo $open_tag;
?>
    <?php if ($thumb_url): ?>
        <div class="hb-card__thumb">
            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>">
        </div>
    <?php endif; ?>
    <div class="hb-card__body">
        <h3 class="hb-card__title"><?php echo esc_html($title); ?></h3>
        <?php if (!empty($body)): ?>
            <p class="hb-card__text"><?php echo esc_html($body); ?></p>
        <?php endif; ?>
    </div>
<?php echo $close_tag; ?>
```

---

## Example 4 — Reusable field groups

Share a common set of fields across multiple blocks.

**Field group registration** (call once, before blocks are registered):

```php
<?php
if (!defined('ABSPATH')) exit;

use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\Registry;

// Shared layout controls used by many blocks
Registry::getInstance()->registerFieldGroup(
    FieldGroup::make('Layout Controls', 'layout-controls')
        ->addFields([
            Field::make('select', 'alignment', 'Text Alignment')
                ->setOptions(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                ->setDefault('left'),
            Field::make('select', 'spacing', 'Vertical Spacing')
                ->setOptions(['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'])
                ->setDefault('md'),
            Field::make('color', 'background', 'Background Color')
                ->setDefault(''),
            Field::make('checkbox', 'full_width', 'Full Width')
                ->setDefault(false),
        ])
);
```

**Attaching to blocks**:

```php
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

$registry = Registry::getInstance();

// Block A — uses layout group + its own fields
$registry->registerFluentBlock(
    Block::make('Content Section')
        ->setName('my-theme/content-section')
        ->addFields([
            Field::make('rich_text', 'content', 'Content'),
        ])
        ->addFieldGroup('layout-controls')
        ->setRenderTemplateFile('blocks/content-section.hb.php')
);

// Block B — also uses layout group
$registry->registerFluentBlock(
    Block::make('Media Block')
        ->setName('my-theme/media-block')
        ->addFields([
            Field::make('image', 'media', 'Image'),
            Field::make('text', 'caption', 'Caption'),
        ])
        ->addFieldGroup('layout-controls')
        ->setRenderTemplateFile('blocks/media-block.hb.php')
);
```

**Template** (`blocks/content-section.hb.php`):

```php
<?php
/**
 * Content Section Block Template.
 *
 * @var string $content    Rich text content.
 * @var string $alignment  left | center | right.
 * @var string $spacing    none | sm | md | lg.
 * @var string $background Background color.
 * @var bool   $full_width Full width toggle.
 */

$classes = 'hb-section hb-section--' . esc_attr($alignment) . ' hb-section--spacing-' . esc_attr($spacing);
if ($full_width) $classes .= ' hb-section--full';
$style = !empty($background) ? 'background-color:' . esc_attr($background) . ';' : '';
?>
<section class="<?php echo esc_attr($classes); ?>" style="<?php echo $style; ?>">
    <div class="hb-section__inner">
        <?php echo wp_kses_post($content); ?>
    </div>
</section>
```

---

## Example 5 — Block that overrides a group field

Block fields always take precedence over field-group fields with the same name.

```php
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

// The 'layout-controls' group defines alignment with default 'left'.
// This block overrides alignment with default 'center'.
Registry::getInstance()->registerFluentBlock(
    Block::make('Centered Banner')
        ->setName('my-theme/centered-banner')
        ->addFields([
            Field::make('text', 'heading', 'Heading'),
            // Override group's alignment field — this definition wins
            Field::make('select', 'alignment', 'Text Alignment')
                ->setOptions(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                ->setDefault('center'), // force center as default for this block
        ])
        ->addFieldGroup('layout-controls')
        ->setRenderTemplateFile('blocks/centered-banner.hb.php')
);
```

---

## Example 6 — Using `<RichText>` and `<InnerBlocks>` components

HyperBlocks supports two pseudo-components inside `.hb.php` templates that are replaced during rendering.

**Block definition**:

```php
Registry::getInstance()->registerFluentBlock(
    Block::make('Rich Content Card')
        ->setName('my-theme/rich-content-card')
        ->addFields([
            Field::make('text', 'card_title', 'Card Title'),
            Field::make('rich_text', 'intro', 'Introduction'),
        ])
        ->setRenderTemplateFile('blocks/rich-content-card.hb.php')
);
```

**Template** (`blocks/rich-content-card.hb.php`):

```php
<?php
/**
 * Rich Content Card Template.
 *
 * @var string $card_title The card title.
 * @var string $intro      Rich text introduction.
 */
?>
<div class="hb-rich-card">
    <!-- RichText renders the attribute inside the given tag -->
    <RichText attribute="card_title" tag="h2" placeholder="Card title" />
    <RichText attribute="intro" tag="div" />

    <!-- InnerBlocks allows editors to nest other Gutenberg blocks -->
    <div class="hb-rich-card__nested">
        <InnerBlocks />
    </div>
</div>
```

The `<RichText>` component supports `tag`, `placeholder`, and `style` attributes. `<InnerBlocks />` is replaced with the standard WordPress `<!-- wp:innerblocks /-->` comment.

---

## Example 7 — Registering block discovery paths

Instead of registering each block individually, point HyperBlocks at a directory and let it discover all `.hb.php` block files automatically.

```php
// In your plugin's main file or functions.php
add_action('init', function (): void {
    \HyperBlocks\Config::registerBlockPath(get_stylesheet_directory() . '/blocks');
}, 4); // before HyperBlocks scans at priority 5
```

With `auto_discovery` enabled (default), HyperBlocks includes every `.hb.php` file inside that directory on `init`. Each file is responsible for defining and registering its own block.

Via WordPress filter (preferred when inside a plugin):

```php
add_filter('hyperblocks/blocks/register_fluent_paths', function (array $paths): array {
    $paths[] = plugin_dir_path(__FILE__) . 'blocks';
    return $paths;
});
```

---

## Example 8 — Inline string templates

For very simple blocks, skip the file and pass a PHP string directly. Useful for prototyping; file templates are preferred in production.

```php
Registry::getInstance()->registerFluentBlock(
    Block::make('Divider')
        ->setName('my-theme/divider')
        ->addFields([
            Field::make('select', 'style', 'Style')
                ->setOptions(['solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted'])
                ->setDefault('solid'),
            Field::make('color', 'color', 'Color')->setDefault('#cccccc'),
        ])
        ->setRenderTemplate('<?php ?><hr class="hb-divider hb-divider--<?php echo esc_attr($style); ?>" style="border-color:<?php echo esc_attr($color); ?>;">')
);
```

---

## Example 9 — block.json approach

HyperBlocks auto-discovers and registers standard `block.json` blocks from configured directories. No PHP definition needed for JSON blocks.

**Directory layout**:

```
my-plugin/blocks/
  my-block/
    block.json
    render.php
    editor.js      (optional)
    editor.css     (optional)
```

**`block.json`**:

```json
{
  "name": "my-plugin/my-block",
  "title": "My Block",
  "icon": "star-filled",
  "attributes": {
    "heading": { "type": "string", "default": "Hello" },
    "show_cta": { "type": "boolean", "default": false }
  },
  "apiVersion": 2
}
```

**`render.php`**:

```php
<?php
/**
 * @var array  $attributes Block attributes.
 * @var string $content    Inner block content.
 */
$heading  = esc_html($attributes['heading'] ?? '');
$show_cta = (bool) ($attributes['show_cta'] ?? false);
?>
<div class="my-block">
    <h2><?php echo $heading; ?></h2>
    <?php if ($show_cta): ?>
        <a href="#" class="my-block__cta">Call to action</a>
    <?php endif; ?>
</div>
```

Register the containing directory:

```php
add_filter('hyperblocks/blocks/register_json_paths', function (array $paths): array {
    $paths[] = plugin_dir_path(__FILE__) . 'blocks';
    return $paths;
});
```

---

## Example 10 — Procedural helpers

The `hyperblocks_*` helpers are aliases for the class API — use whichever style you prefer. They are useful in theme `functions.php` where you want to avoid `use` declarations.

```php
add_action('init', function (): void {
    hyperblocks_register_field_group(
        hyperblocks_field_group('CTA Controls', 'cta-controls')
            ->addFields([
                hyperblocks_field('text', 'cta_label', 'Button Label')->setDefault('Read more'),
                hyperblocks_field('url',  'cta_url',   'Button URL'),
                hyperblocks_field('checkbox', 'cta_new_tab', 'Open in new tab')->setDefault(false),
            ])
    );

    hyperblocks_register_block(
        hyperblocks_block('Blog Post Teaser')
            ->setName('my-theme/post-teaser')
            ->setIcon('format-aside')
            ->addFields([
                hyperblocks_field('text', 'eyebrow', 'Eyebrow text'),
                hyperblocks_field('text', 'title', 'Title')->setRequired(true),
                hyperblocks_field('textarea', 'excerpt', 'Excerpt'),
                hyperblocks_field('image', 'featured_image', 'Featured Image'),
            ])
            ->addFieldGroup('cta-controls')
            ->setRenderTemplateFile('blocks/post-teaser.hb.php')
    );
});
```

**Template** (`blocks/post-teaser.hb.php`):

```php
<?php
/**
 * Blog Post Teaser Block Template.
 *
 * @var string $eyebrow        Small label above the title.
 * @var string $title          Post title.
 * @var string $excerpt        Short text excerpt.
 * @var int    $featured_image Attachment ID.
 * @var string $cta_label      Button label.
 * @var string $cta_url        Button URL.
 * @var bool   $cta_new_tab    Open link in new tab.
 */

$img_url    = $featured_image ? wp_get_attachment_image_url($featured_image, 'medium_large') : '';
$target     = $cta_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
?>
<article class="hb-teaser">
    <?php if ($img_url): ?>
        <div class="hb-teaser__image">
            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($title); ?>">
        </div>
    <?php endif; ?>
    <div class="hb-teaser__body">
        <?php if (!empty($eyebrow)): ?>
            <span class="hb-teaser__eyebrow"><?php echo esc_html($eyebrow); ?></span>
        <?php endif; ?>
        <h2 class="hb-teaser__title"><?php echo esc_html($title); ?></h2>
        <?php if (!empty($excerpt)): ?>
            <p class="hb-teaser__excerpt"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>
        <?php if (!empty($cta_label) && !empty($cta_url)): ?>
            <a href="<?php echo esc_url($cta_url); ?>" class="hb-teaser__cta"<?php echo $target; ?>>
                <?php echo esc_html($cta_label); ?>
            </a>
        <?php endif; ?>
    </div>
</article>
```

---

## Example 11 — Rendering a block manually (outside Gutenberg)

Use `hyperblocks_render()` to output a block template anywhere in PHP — useful for widget areas, shortcodes, or custom page builders.

```php
$html = hyperblocks_render('file:blocks/hero-banner.hb.php', [
    'heading'          => 'Welcome',
    'subheading'       => 'Built with HyperBlocks',
    'background_image' => 0,
    'overlay_color'    => 'rgba(0,0,0,0.4)',
    'cta_text'         => 'Get started',
    'cta_url'          => '/start',
]);

echo $html;
```

---

## Developer tips

- Always `esc_html()` text output, `esc_url()` URLs, `esc_attr()` attribute values, and `wp_kses_post()` for rich text.
- For `image` fields, always check that the attachment ID is non-zero before calling `wp_get_attachment_image_url()`.
- For `url` fields, always check non-empty before building links.
- Use `setDefault()` on every field — it is the fallback value when an attribute is absent and is used to define the Gutenberg block attribute schema.
- Use `setHelp()` generously — it surfaces in the block editor sidebar and reduces support requests.
- Keep templates stateless. They receive `$attributes` only — do not use `get_the_ID()` or globals without explicit justification.
- For large option sets on `select` or `radio` fields, build the options array in PHP first to keep the Field definition readable.
- Prefer `->setRenderTemplateFile()` over inline string templates for anything beyond trivial blocks. File templates are easier to maintain and can be overridden by child themes.
