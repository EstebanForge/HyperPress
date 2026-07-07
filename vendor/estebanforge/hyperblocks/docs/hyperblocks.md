# HyperBlocks — API Reference

HyperBlocks is a PHP-first Gutenberg block library. Blocks, fields, and templates are defined entirely in PHP. No JavaScript configuration is required for server-rendered blocks.

**Package**: `estebanforge/hyperblocks`
**Requires**: PHP 8.2+, WordPress latest, `estebanforge/hyperfields` ^1.0 (bootstrapped automatically)

---

## Core concepts

- **Block** — a Gutenberg block defined via a fluent PHP builder.
- **Field** — a typed attribute that the editor stores and the template receives.
- **FieldGroup** — a named, reusable set of fields that can be shared across blocks.
- **Registry** — the singleton that holds all block and field-group definitions.
- **Renderer** — executes `.hb.php` templates with attributes extracted as local variables.
- **HyperFields integration** — all field sanitization, validation, and Gutenberg attribute mapping is delegated to `estebanforge/hyperfields`.

---

## Block

### `Block::make(string $title): Block`

Creates a new block builder. The default name is derived automatically as `hyperblocks/<sanitize_title($title)>`. Always call `setName()` to use a proper namespace.

```php
use HyperBlocks\Block\Block;

$block = Block::make('Hero Banner');
// default name: hyperblocks/hero-banner
```

### `->setName(string $name): self`

Override the block name. Must follow the WordPress `namespace/slug` format.

```php
$block->setName('my-theme/hero-banner');
```

### `->setIcon(string $slug): self`

Set the block icon using a [Dashicon](https://developer.wordpress.org/resource/dashicons/) slug.

```php
$block->setIcon('cover-image');
```

### `->addFields(Field[] $fields): self`

Append one or more `Field` objects. Chainable; call multiple times to build incrementally.

```php
use HyperBlocks\Block\Field;

$block->addFields([
    Field::make('text',     'heading',    'Heading'),
    Field::make('textarea', 'subheading', 'Subheading'),
]);
```

### `->addFieldGroup(string $groupId): self`

Attach a pre-registered `FieldGroup` by its ID. Fields from the group are merged at registration time; block fields take precedence over group fields when names collide.

```php
$block->addFieldGroup('common-settings');
```

### `->setRenderTemplate(string $template): self`

Set the template used to render the block on the frontend.

Two forms accepted:

- `file:relative/path.hb.php` — a PHP file resolved against registered block paths, `WP_CONTENT_DIR`, or the active theme.
- Inline PHP string — executed directly (useful for simple blocks; prefer file templates in production).

```php
// File template (preferred)
$block->setRenderTemplate('file:blocks/hero-banner.hb.php');

// Inline string
$block->setRenderTemplate('<h1><?php echo esc_html($heading); ?></h1>');
```

### `->setRenderTemplateFile(string $path): self`

Shorthand for `setRenderTemplate('file:' . $path)`.

```php
$block->setRenderTemplateFile('blocks/hero-banner.hb.php');
```

Template path rules:
- Must be relative (no leading `/`).
- Must not contain `..` (path traversal is rejected).
- File must exist inside `WP_CONTENT_DIR`, the active theme directory, `HYPERBLOCKS_PATH`, or a directory registered with `Config::registerBlockPath()` or `Config::registerTemplatePath()` (the validation allowlist is the union of both).
- Extension must match `Config::get('template_extensions', '.hb.php,.php')`.

### `->getFieldAdapters(): array`

Returns `['fieldName' => HyperFields\BlockFieldAdapter]` for every field directly on the block (not field groups). Rarely needed outside internal use.

### `->toArray(): array`

Serializes the block definition to an array. Keys: `name`, `title`, `icon`, `fields`, `field_groups`, `render_template`.

---

## Field

`HyperBlocks\Block\Field` is a thin wrapper around `HyperFields\Field`. It adds block-specific helpers (`toBlockAttribute()`, `getAdapter()`) and delegates all config, sanitization, and validation to HyperFields.

### `Field::make(string $type, string $name, string $label): Field`

Static constructor. Throws `\InvalidArgumentException` for unsupported types.

```php
$field = Field::make('select', 'layout', 'Layout');
```

### Supported types

| Type | Gutenberg attribute type | Notes |
|---|---|---|
| `text` | `string` | Single-line text |
| `textarea` | `string` | Multi-line text |
| `email` | `string` | Email address |
| `url` | `string` | URL |
| `number` | `number` | Integer or float |
| `color` | `string` | Color value |
| `date` | `string` | Date (ISO format) |
| `time` | `string` | Time |
| `datetime` | `string` | Date + time |
| `image` | `number` | Attachment ID |
| `file` | `string` | File URL or attachment ID |
| `select` | `string` | Single-choice dropdown |
| `multiselect` | `string` | Multiple choices |
| `checkbox` | `boolean` | Boolean toggle |
| `radio` | `string` | Radio buttons |
| `rich_text` | `string` | Rich text / WYSIWYG |
| `hidden` | `string` | Hidden value |
| `html` | `string` | Raw HTML |
| `map` | `string` | Map embed |
| `oembed` | `string` | oEmbed URL |
| `separator` | `string` | Visual divider (no stored value) |
| `heading` | `string` | Visual heading (no stored value) |
| `media_gallery` | `string` | Multiple media items |
| `repeater` | `string` | Repeatable row set |

### Configuration methods

| Method | Description |
|---|---|
| `->setDefault(mixed $value)` | Default value used in the editor and as the sanitization fallback. |
| `->setPlaceholder(string $text)` | Placeholder shown in the editor input. |
| `->setRequired(bool $required = true)` | Mark field as required. |
| `->setHelp(string $text)` | Help text shown below the editor input. |
| `->setOptions(array $options)` | Key → label pairs for `select`, `multiselect`, `radio`. |
| `->setValidation(array $rules)` | Validation rules array passed to HyperFields. |

### Lower-level methods

| Method | Description |
|---|---|
| `->getHyperField()` | Returns the underlying `HyperFields\Field` instance. |
| `->getAdapter()` | Returns a `HyperFields\BlockFieldAdapter` for this field. |
| `->toBlockAttribute()` | Returns `['type' => '...', 'default' => ...]` for `register_block_type`. |
| `->sanitizeValue(mixed $value)` | Strips `<script>` tags then delegates to `HyperFields\Field::sanitizeValue()`. |
| `->validateValue(mixed $value)` | Delegates to `HyperFields\Field::validateValue()`. |

### Magic properties

Properties `type`, `name`, `label`, `default`, `placeholder`, `required`, `help` are readable and writable via PHP magic `__get`/`__set`. `type`, `name`, `label` are immutable after construction.

```php
echo $field->type;       // 'select'
echo $field->name;       // 'layout'
$field->default = 'boxed'; // same as ->setDefault('boxed')
```

---

## FieldGroup

### `FieldGroup::make(string $name, string $id): FieldGroup`

Create a named, reusable group of fields.

```php
use HyperBlocks\Block\FieldGroup;

$group = FieldGroup::make('Layout Settings', 'layout-settings');
```

- `$name` — human-readable label.
- `$id` — machine ID used to attach the group to blocks via `Block::addFieldGroup($id)`.

### `->addFields(Field[] $fields): self`

Append fields to the group.

```php
$group->addFields([
    Field::make('select', 'alignment', 'Alignment')
        ->setOptions(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
        ->setDefault('center'),
    Field::make('checkbox', 'show_border', 'Show Border')
        ->setDefault(false),
]);
```

### `->toArray(): array`

Serializes to `['id' => ..., 'name' => ..., 'fields' => [...]]`.

**Field precedence**: when a block has its own field with the same name as a field-group field, the block's version wins.

---

## Registry

Singleton. Holds all registered blocks and field groups.

```php
use HyperBlocks\Registry;

$registry = Registry::getInstance();
```

### Block methods

| Method | Description |
|---|---|
| `registerFluentBlock(Block $block): void` | Register a block. |
| `getFluentBlock(string $name): ?Block` | Retrieve by name, or `null`. |
| `getFluentBlocks(): Block[]` | All registered blocks, keyed by name. |
| `hasFluentBlock(string $name): bool` | Check if a block is registered. |

### Field group methods

| Method | Description |
|---|---|
| `registerFieldGroup(FieldGroup $group): void` | Register a group. |
| `getFieldGroup(string $id): ?FieldGroup` | Retrieve by ID, or `null`. |
| `getFieldGroups(): FieldGroup[]` | All registered groups. |

### Attribute & field helpers

| Method | Description |
|---|---|
| `generateBlockAttributes(Block $block): array` | Returns the `attributes` array for `register_block_type`, merging block fields and attached groups. |
| `getMergedFields(Block $block): Field[]` | Returns all fields (block + groups), keyed by name. Block fields take precedence. |
| `findJsonBlockPath(string $blockName): ?string` | Finds the directory of a `block.json` block by name. |

### Auto-discovery and the `HyperBlocks Block:` header

`Registry::discoverAndLoadFluentBlocks()` globs each registered block path for `.hb.php` / `.php` files **one directory level beneath the base** and `require_once`s the matches — but only those that declare a `HyperBlocks Block:` docblock header. This protects against the de-facto WP/ACF `/blocks/<slug>/{block.json,init.php,render.php}` layout, whose `render.php` files expect to be included by WordPress's block renderer with `$block` in scope: auto-loading them at `init` executes them out of context, echoing markup before `<!DOCTYPE html>` and tripping warnings. The header makes HyperBlocks definition files explicit, the same convention WordPress uses for plugins, themes, and dropins.

```php
<?php
/**
 * HyperBlocks Block: Hero Banner
 */

use HyperBlocks\Block\Block;
use HyperBlocks\Registry;

Registry::getInstance()->registerFluentBlock(
    Block::make('Hero Banner')->setName('my-theme/hero-banner') /* ... */
);
```

`get_file_data()` reads only the first 8 KB of each candidate file (never executing it) and checks for a non-empty `HyperBlocks Block:` value. Files lacking the header are skipped. Files pointed at directly via the `hyperblocks/blocks/register_fluent_blocks` filter bypass the check — naming a file explicitly is consumer consent.

### Testing

```php
Registry::reset(); // clears all registered blocks and groups — testing only
```

---

## Config

Static configuration store. Initialized once from the database and WordPress filters; readable anywhere.

```php
use HyperBlocks\Config;

Config::get('auto_discovery', true);           // read with fallback
Config::set('debug', true);                    // set at runtime
Config::registerBlockPath('/abs/path/to/dir');                      // discovery + validation (default)
Config::registerBlockPath('/abs/path/to/templates', ['discover' => false]); // validation only, never scanned
Config::registerTemplatePath('/abs/path/to/templates');            // one-liner equivalent of the above
Config::getBlockPaths();                       // discovery paths (also valid for templates)
Config::getTemplatePaths();                    // validation-only paths
Config::getTemplateValidationPaths();          // deduplicated union used by the validators
Config::getTemplateExtensions();               // ['.hb.php', '.php']
Config::isDebug(): bool
Config::isCacheEnabled(): bool
Config::getRestNamespace(): string             // 'hyperblocks/v1'
Config::getEditorScriptHandle(): string        // 'hyperblocks-editor'
```

**Discovery vs. template paths.** A registered path can serve two independent purposes: being scanned for block definitions (discovery) and being on the allowlist that resolves `Block::setRenderTemplateFile()` / `Renderer` templates (validation). They are split because a directory of render templates is not safe to `require_once` as block definitions; auto-discovering it fatals when a template expects a render context.

- `registerBlockPath($path)` (no options, default) registers for **both** discovery and validation. Backwards-compatible with the original single-purpose behavior.
- `registerBlockPath($path, ['discover' => false])` registers for **validation only**; `Registry::discoverAndLoadFluentBlocks()` never globs it.
- `registerTemplatePath($path)` is the one-liner equivalent of the above.
- `Config::getBlockPaths()` returns discovery paths; `Config::getTemplatePaths()` returns validation-only paths; `Config::getTemplateValidationPaths()` returns the deduplicated union used by both validators.

Paths are normalized on registration (trailing slashes stripped) so `/foo/bar` and `/foo/bar/` collapse to one entry.

### Default keys

| Key | Default | Description |
|---|---|---|
| `block_paths` | `[]` | Directories scanned for block definition files and used for template validation. |
| `template_paths` | `[]` | Template-validation-only directories; never scanned for block definitions. |
| `template_extensions` | `.hb.php,.php` | Comma-separated; first extension is the default for new files. |
| `auto_discovery` | `true` | Automatically scan block paths on `init`. |
| `debug` | `false` | Log errors via `error_log`. |
| `cache_blocks` | `true` | Cache rendered block output. |
| `rest_namespace` | `hyperblocks/v1` | REST API base namespace. |
| `editor_script_handle` | `hyperblocks-editor` | WordPress script handle used for the editor JS bundle. |

### WordPress filters

- `hyperblocks/config/defaults` — filter the default config array before it is applied.
- `hyperblocks/config/override` — highest-priority override; applied after the database values.

---

## Renderer

`HyperBlocks\Renderer` executes PHP templates. You rarely instantiate it directly — it is called internally during `renderBlock` and `renderPreview`. It is exposed for custom rendering scenarios.

```php
$renderer = new \HyperBlocks\Renderer();
$html = $renderer->render($block->render_template, $attributes);
```

### Template variable injection

All entries in `$attributes` are extracted as local variables via `extract()` before the template executes:

```php
// Block has fields: heading (text), bg_image (image), show_cta (checkbox)
// Template receives:
$heading   // string
$bg_image  // int (attachment ID)
$show_cta  // bool
```

### Custom components

Two pseudo-components are available inside `.hb.php` templates. They are replaced during rendering before the HTML is returned.

**`<RichText>`** — renders an attribute value inside a configurable HTML tag.

```html
<RichText attribute="heading" tag="h1" placeholder="Enter heading" />
<RichText attribute="body" tag="p" style="color: #333;" />
```

Attributes:
- `attribute` (required) — the block attribute name.
- `tag` — the wrapping HTML tag. Default: `div`.
- `placeholder` — shown when the attribute is empty.
- `style` — inline style on the wrapper element.

**`<InnerBlocks>`** — inserts a WordPress inner-blocks placeholder so nested blocks work.

```html
<InnerBlocks />
```

### Error handling

- In `WP_DEBUG` mode: rendering errors are returned as `<div class="hyperblocks-error">` inline elements.
- In production: a generic `<div class="hyperblocks-error">Block rendering failed</div>` is returned. No stack traces are leaked.

---

## WordPress\Bootstrap

`HyperBlocks\WordPress\Bootstrap` wires HyperBlocks into WordPress. It is called automatically from `bootstrap.php` when the `after_setup_theme` action fires. You do not call it directly.

### Hooks registered

| Hook | Priority | Action |
|---|---|---|
| `plugins_loaded` | 5 | Load config from database, apply filters. |
| `init` | 5 | Register default block paths (theme `blocks/` directories), gated by the `hyperblocks/blocks/auto_discover_theme_blocks` filter (default `true`). Files within them are auto-discovered only if they carry the `HyperBlocks Block:` header. |
| `init` | 10 | Discover and register all fluent and `block.json` blocks. |
| `rest_api_init` | 10 | Register REST routes. |
| `enqueue_block_editor_assets` | 10 | Enqueue editor CSS if `assets/css/editor.css` exists. |

### Block discovery filters

Use these to register additional paths or individual block files without modifying your theme or plugin's `Config` directly:

```php
// Disable auto-registration of the active theme's /blocks directories entirely.
// Default is true (back-compat). Return false if your theme uses /blocks for
// WP-native/ACF blocks and you prefer an opt-out over the header-based filter.
add_filter('hyperblocks/blocks/auto_discover_theme_blocks', '__return_false');

// Add an entire directory to scan for block.json blocks
add_filter('hyperblocks/blocks/register_json_paths', function (array $paths): array {
    $paths[] = get_stylesheet_directory() . '/blocks';
    return $paths;
});

// Add individual block.json block directories
add_filter('hyperblocks/blocks/register_json_blocks', function (array $blocks): array {
    $blocks[] = get_stylesheet_directory() . '/blocks/my-special-block';
    return $blocks;
});

// Add a directory to scan for fluent-block PHP files (HyperBlocks Block header required)
add_filter('hyperblocks/blocks/register_fluent_paths', function (array $paths): array {
    $paths[] = get_stylesheet_directory() . '/blocks';
    return $paths;
});

// Add individual fluent-block PHP files (header check BYPASSED — explicit consent)
add_filter('hyperblocks/blocks/register_fluent_blocks', function (array $files): array {
    $files[] = get_stylesheet_directory() . '/blocks/my-block.hb.php';
    return $files;
});
```

### Editor registration

Fluent blocks are **dynamic**: they are server-rendered through the `render_callback` that `Bootstrap::registerSingleBlock()` wires into `register_block_type()`. To make the editor aware of them (so they appear in the inserter and parse when present in saved post content), HyperBlocks ships a small vanilla-JS file, `assets/js/editor.js`, registered under the `editor_script_handle` (`hyperblocks-editor` by default).

Registration is **register-only, not enqueue**: it happens as a side-effect of `init` block registration (once at least one fluent block exists), and `Bootstrap::registerEditorScript()` calls `wp_register_script()` rather than `wp_enqueue_script()`. This is deliberate — `init` fires on every request, including the public front end, so enqueueing there would leak the Gutenberg bundle (`wp-blocks`, `wp-element`, `wp-components`) onto every page. Instead, the handle is registered with WordPress and core's own `wp_enqueue_registered_block_scripts_and_styles()` enqueues it in the editor context only, driven by the `editor_script` argument that `registerSingleBlock()` passes to `register_block_type()`.

As part of registration, HyperBlocks also injects each block's `{ name, title, icon }` as `window.hyperBlocksConfig` via `wp_add_inline_script(..., 'before')`, attached to the same handle.

`editor.js` then:

- Iterates `window.hyperBlocksConfig`.
- Skips any block already known to the editor via `wp.blocks.getBlockType(name)` (idempotent across multiple includes).
- Calls `wp.blocks.registerBlockType(name, { title, icon, edit: () => null, save: () => null })`.

`edit()` and `save()` deliberately return `null`: the editor shows the server-rendered markup, and `save()` returning `null` marks the block as dynamic so WordPress does not store static HTML in `post_content`. The script only makes blocks known and parsable client-side; it adds no interactive editor UI.

**URL resolution**: the script URL is resolved from `HYPERBLOCKS_PLUGIN_URL` (defined in `bootstrap.php`, correct when HyperBlocks is vendored inside a consumer plugin's `vendor/` tree), falling back to `plugins_url()` when the constant is empty.

---

## REST API

Base namespace: `hyperblocks/v1`

### `GET /wp-json/hyperblocks/v1/block-fields`

Returns field definitions for a registered block (fluent or `block.json`).

**Query parameter**: `name` (required) — fully qualified block name (e.g. `my-theme/hero-banner`).

**Auth**: public (no authentication required).

**Response** — JSON array of field objects:

```json
[
  { "name": "heading",    "label": "Heading",    "type": "text",   "default": "Welcome" },
  { "name": "bg_image",   "label": "Background", "type": "image",  "default": 0 },
  { "name": "show_cta",   "label": "Show CTA",   "type": "checkbox", "default": false }
]
```

**404** when the block is not registered.

### `POST /wp-json/hyperblocks/v1/render-preview`

Server-side renders a block with the supplied attributes. Attributes are sanitized and validated through HyperFields before rendering.

**Auth**: requires `edit_posts` capability.

**Request body**:

```json
{
  "blockName": "my-theme/hero-banner",
  "attributes": {
    "heading": "Hello World",
    "bg_image": 42,
    "show_cta": true
  }
}
```

**Success response**:

```json
{ "success": true, "html": "<section class=\"hb-hero-banner\">...</section>" }
```

**Error response**:

```json
{ "success": false, "error": "No render template defined for block: my-theme/hero-banner" }
```

---

## Helper functions

All helpers are defined in `src/helpers.php` and available globally after bootstrap.

### Factory helpers

```php
hyperblocks_block(string $title): Block
hyperblocks_field(string $type, string $name, string $label): Field
hyperblocks_field_group(string $name, string $id): FieldGroup
```

### Registration helpers

```php
hyperblocks_register_block(Block $block): void
hyperblocks_register_field_group(FieldGroup $group): void
hyperblocks_register_path(string $path): void
hyperblocks_register_template_path(string $path): void
```

### Query helpers

```php
hyperblocks_registry(): Registry
hyperblocks_has_block(string $blockName): bool
hyperblocks_get_block(string $blockName): ?Block
```

### Config helper

```php
hyperblocks_config(string $key, mixed $default = null): mixed
```

### Render helper

```php
hyperblocks_render(string $template, array $attributes = []): string
```

---

## Bootstrap constants

Set by `bootstrap.php` after `after_setup_theme` (priority 0) runs the version-resolution logic.

| Constant | Description |
|---|---|
| `HYPERBLOCKS_VERSION` | Version string read from `composer.json`. |
| `HYPERBLOCKS_PATH` | Absolute path to the HyperBlocks root (trailing slash). Same as `HYPERBLOCKS_ABSPATH`. |
| `HYPERBLOCKS_PLUGIN_FILE` | Absolute path to `bootstrap.php`. |
| `HYPERBLOCKS_PLUGIN_URL` | Public URL to the HyperBlocks root (trailing slash). |
| `HYPERBLOCKS_BOOTSTRAP_LOADED` | Defined when `bootstrap.php` is first included. Prevents double-include. |
| `HYPERBLOCKS_INSTANCE_LOADED` | Defined when initialization logic runs. Ensures single initialization even across multiple vendored copies. |

---

## HyperFields integration

HyperBlocks integrates HyperFields at three layers:

**1 — Field definitions**

`HyperBlocks\Block\Field` wraps `HyperFields\Field`. All config, sanitization, and validation is handled by HyperFields. You get the full HyperFields field API through the wrapper.

**2 — Block attribute mapping**

`HyperFields\BlockFieldAdapter::toBlockAttribute()` translates HyperFields types to Gutenberg attribute types (`string`, `number`, `boolean`). This mapping is used by `Registry::generateBlockAttributes()` when calling `register_block_type`.

**3 — Render-time sanitization**

On every `renderBlock` call (frontend render) and every `renderPreview` REST request, incoming attributes pass through `BlockFieldAdapter::sanitizeForBlock()` and `validateForBlock()`. Values that fail validation are replaced with the field's default before the template executes.

**Bootstrap relationship**

When HyperBlocks runs standalone (no standalone HyperFields plugin active), `bootstrap.php` triggers HyperFields initialization from the vendored copy at `vendor/estebanforge/hyperfields/bootstrap.php`. When both are active simultaneously, HyperFields' own guards (`HYPERFIELDS_BOOTSTRAP_LOADED`, `HYPERFIELDS_INSTANCE_LOADED`) prevent double-initialization. The highest-version candidate always wins.

---

## Security notes

- Template paths are validated at **both** definition time (`Block::validateTemplatePath`) and render time (`Renderer::validateTemplatePath`). Path traversal (`..`) and absolute paths outside allowed directories are rejected with `\InvalidArgumentException`.
- `<script>` tags in incoming attribute values are stripped before HyperFields sanitization runs.
- The `render-preview` endpoint requires `edit_posts`; `block-fields` is public but returns only metadata, no stored values.
- All template output must be escaped. Always use `esc_html()`, `esc_url()`, `esc_attr()`, or `wp_kses_post()` in `.hb.php` templates.

---

## Version management

1. Update `version` in `composer.json`.
2. Run `composer run version-bump` — updates fallback version literals in `bootstrap.php`.
3. Update `CHANGELOG.md`.
