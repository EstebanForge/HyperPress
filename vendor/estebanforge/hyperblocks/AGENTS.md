# HyperBlocks — Agent & Developer Reference

**Package**: `estebanforge/hyperblocks`
**Repository**: https://github.com/EstebanForge/HyperBlocks

## Overview

HyperBlocks is a PHP-first Gutenberg block library. Blocks and their fields are defined entirely in PHP using a fluent API. HyperFields (`estebanforge/hyperfields`) is a required dependency and is automatically bootstrapped by HyperBlocks.

Two block definition approaches are supported:

1. **Fluent API** — define blocks in PHP, register them with the Registry.
2. **block.json** — standard WordPress approach; HyperBlocks discovers and registers these automatically.

---

## Installation

```bash
composer require estebanforge/hyperblocks
```

Load the Composer autoloader from the host project:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

HyperBlocks' `bootstrap.php` is included via Composer `autoload.files`. It also bootstraps HyperFields automatically — no extra steps needed.

**Requirements**: PHP 8.2+, WordPress latest.

---

## Development Commands

```bash
composer run test            # Full test suite (Pest)
composer run test:unit       # Unit tests only
composer run test:coverage   # HTML coverage report
composer run cs:fix          # Auto-fix code style (php-cs-fixer)
composer run cs:check        # Dry-run style check
composer run version-bump    # Bump version in composer.json + bootstrap
```

---

## Architecture & Directory Structure

```
bootstrap.php               # Version resolution + HyperFields bootstrap
src/
  Block/
    Block.php               # Fluent block builder
    Field.php               # Field wrapper (delegates to HyperFields\Field)
    FieldGroup.php          # Reusable named field groups
  Config.php                # Static configuration store
  Registry.php              # Singleton: block + field-group registration
  Renderer.php              # PHP template executor + component parser
  RestApi.php               # REST endpoints (block-fields, render-preview)
  WordPress/
    Bootstrap.php           # WordPress hook wiring (init, rest_api_init, etc.)
  helpers.php               # Procedural API (hyperblocks_* functions)
examples/
  hero-banner-block.php     # Full fluent-API example
  field-groups-example.php  # Reusable field groups example
  blocks/                   # Matching .hb.php templates
tests/
  Unit/                     # PHPUnit/Pest unit tests
  mocks/wp-mocks.php        # WordPress function stubs for tests
```

---

## Key Classes

### `HyperBlocks\Block\Block`

Fluent builder for a single Gutenberg block.

```php
use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Registry;

$block = Block::make('Hero Banner')            // title; auto-name: hyperblocks/hero-banner
    ->setName('my-theme/hero-banner')          // override with explicit namespace/slug
    ->setIcon('cover-image')                   // dashicon slug
    ->addFields([
        Field::make('text', 'heading', 'Heading')->setDefault('Welcome'),
        Field::make('image', 'bg_image', 'Background'),
    ])
    ->addFieldGroup('common-settings')         // attach a registered FieldGroup by id
    ->setRenderTemplateFile('blocks/hero-banner.hb.php');  // file: prefix added automatically

Registry::getInstance()->registerFluentBlock($block);
```

**Methods**:

| Method | Description |
|---|---|
| `Block::make(string $title)` | Static constructor. Derives default name as `hyperblocks/<sanitize_title>`. |
| `->setName(string $name)` | Override block name (must be `namespace/slug`). |
| `->setIcon(string $slug)` | Dashicon slug (e.g. `star-filled`). |
| `->addFields(Field[] $fields)` | Append one or more fields. Chainable. |
| `->addFieldGroup(string $groupId)` | Attach a pre-registered FieldGroup. Chainable. |
| `->setRenderTemplate(string $template)` | Inline PHP string template or `file:relative/path.hb.php`. |
| `->setRenderTemplateFile(string $path)` | Shorthand for `setRenderTemplate('file:' . $path)`. |
| `->getFieldAdapters()` | Returns `['fieldName' => BlockFieldAdapter, ...]` for all block fields. |
| `->toArray()` | Serialize to array (name, title, icon, fields, field_groups, render_template). |

Template paths must be relative (no leading `/`, no `..`), within `WP_CONTENT_DIR`, the active theme, or a registered block path.

---

### `HyperBlocks\Block\Field`

Thin wrapper around `HyperFields\Field` scoped to block usage. All methods delegate to the underlying HyperFields field instance.

```php
use HyperBlocks\Block\Field;

$field = Field::make('select', 'layout', 'Layout')
    ->setOptions(['boxed' => 'Boxed', 'full' => 'Full Width'])
    ->setDefault('boxed')
    ->setRequired(true)
    ->setHelp('Controls the block width');
```

**Supported types** (`Field::FIELD_TYPES`):

`text`, `textarea`, `color`, `image`, `url`, `number`, `email`, `date`, `datetime`, `time`, `file`, `select`, `multiselect`, `checkbox`, `radio`, `rich_text`, `hidden`, `html`, `map`, `oembed`, `separator`, `heading`, `media_gallery`, `repeater`

**Methods**:

| Method | Description |
|---|---|
| `Field::make(string $type, string $name, string $label)` | Static constructor. Throws `InvalidArgumentException` for unknown types. |
| `->setDefault(mixed $value)` | Default attribute value (used in block editor and sanitization fallback). |
| `->setPlaceholder(string $text)` | Placeholder text shown in editor. |
| `->setRequired(bool $required = true)` | Mark field as required. |
| `->setHelp(string $text)` | Help/description text for the editor UI. |
| `->setOptions(array $options)` | Key-value pairs for `select`, `multiselect`, `radio`. |
| `->setValidation(array $rules)` | Validation rules array. |
| `->getHyperField()` | Returns the underlying `HyperFields\Field` instance. |
| `->getAdapter()` | Returns a `HyperFields\BlockFieldAdapter` for this field. |
| `->toBlockAttribute()` | Returns `['type' => '...', 'default' => ...]` for `register_block_type`. |
| `->sanitizeValue(mixed $value)` | Sanitize a value; strips `<script>` before delegating to HyperFields. |
| `->validateValue(mixed $value)` | Validate a value; delegates to HyperFields. |

Properties `type`, `name`, `label`, `default`, `placeholder`, `required`, `help` are accessible as read/write via magic `__get`/`__set`. `type`, `name`, `label` are immutable after construction.

---

### `HyperBlocks\Block\FieldGroup`

A named, reusable collection of fields that can be attached to multiple blocks.

```php
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\Registry;

$group = FieldGroup::make('Common Settings', 'common-settings')
    ->addFields([
        Field::make('select', 'alignment', 'Alignment')
            ->setOptions(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
            ->setDefault('center'),
        Field::make('checkbox', 'show_border', 'Show Border')->setDefault(false),
    ]);

Registry::getInstance()->registerFieldGroup($group);
```

Block fields take precedence over field-group fields when names collide.

---

### `HyperBlocks\Registry`

Singleton managing all block and field-group registrations.

```php
$registry = Registry::getInstance();

$registry->registerFluentBlock($block);
$registry->registerFieldGroup($group);
$registry->getFluentBlock('namespace/slug');     // Block|null
$registry->getFluentBlocks();                    // Block[]
$registry->hasFluentBlock('namespace/slug');     // bool
$registry->getFieldGroup('group-id');            // FieldGroup|null
$registry->generateBlockAttributes($block);      // ['fieldName' => ['type'=>'string','default'=>...], ...]
$registry->getMergedFields($block);              // Field[] from block + attached groups, block wins
Registry::reset();                               // testing only
```

---

### `HyperBlocks\Config`

Static configuration store. Initialized once; readable anywhere via `Config::get()`.

```php
use HyperBlocks\Config;

Config::registerBlockPath('/path/to/blocks');  // add a directory to scan for blocks
Config::get('auto_discovery', true);           // read a value
Config::set('debug', true);                    // set at runtime
```

**Default keys**:

| Key | Default | Description |
|---|---|---|
| `block_paths` | `[]` | Directories scanned for block definitions and templates. |
| `template_extensions` | `.hb.php,.php` | Comma-separated list; first extension is the default. |
| `auto_discovery` | `true` | Auto-scan block paths on `init`. |
| `debug` | `false` | Log errors via `error_log`. |
| `cache_blocks` | `true` | Cache rendered output. |
| `rest_namespace` | `hyperblocks/v1` | REST API namespace. |
| `editor_script_handle` | `hyperblocks-editor` | WP script handle for editor JS. |

**WordPress filters**:
- `hyperblocks/config/defaults` — filter default config array.
- `hyperblocks/config/override` — highest-priority config override.

---

### `HyperBlocks\Renderer`

Executes PHP block templates. Not instantiated directly in normal usage — called internally by `WordPress\Bootstrap::renderBlock()` and `RestApi::renderPreview()`.

```php
$renderer = new \HyperBlocks\Renderer();
$html = $renderer->render($block->render_template, $attributes);
```

**Template modes**:

- `file:relative/path.hb.php` — resolved against `WP_CONTENT_DIR`, theme dir, `HYPERBLOCKS_PATH`, and registered block paths.
- Inline PHP string — written to a temp file, executed, then cleaned up.

**Template variables**: all entries in `$attributes` are extracted as local variables via `extract()`. A template for a block with `heading` and `bg_image` fields will have `$heading` and `$bg_image` available directly.

**Custom components** available inside templates:

```html
<!-- RichText: renders attribute content inside any HTML tag -->
<RichText attribute="heading" tag="h1" placeholder="Enter heading" />
<RichText attribute="body" tag="p" style="color: #333;" />

<!-- InnerBlocks: replaced with WordPress inner-block placeholder -->
<InnerBlocks />
```

Errors in `WP_DEBUG` mode return an inline `<div class="hyperblocks-error">` — never on production.

---

### `HyperBlocks\WordPress\Bootstrap`

Called from `bootstrap.php` after WordPress loads. Hooks:

| Hook | Action |
|---|---|
| `plugins_loaded` (priority 5) | Load config from DB, apply filters. |
| `init` (priority 5) | Register default block paths (theme `/blocks` dirs). |
| `init` (priority 10) | Discover + register all blocks (fluent and JSON). |
| `rest_api_init` (priority 10) | Register REST routes. |
| `enqueue_block_editor_assets` | Enqueue editor CSS if present. |

**WordPress filters** for block discovery:
- `hyperblocks/blocks/register_json_paths` — add additional directories to scan for `block.json` blocks.
- `hyperblocks/blocks/register_json_blocks` — add individual block directory paths.
- `hyperblocks/blocks/register_fluent_paths` — add directories to scan for fluent-block PHP files.
- `hyperblocks/blocks/register_fluent_blocks` — add individual fluent-block file paths.

---

## REST API

Base: `GET|POST /wp-json/hyperblocks/v1/`

### `GET /block-fields?name=namespace/block-slug`

Returns field definitions for a registered block (fluent or JSON).

**Response**: JSON array of field definition objects.

```json
[
  { "name": "heading", "label": "Heading", "type": "text", "default": "Welcome" },
  { "name": "bg_image", "label": "Background Image", "type": "image", "default": "" }
]
```

**Permissions**: public (no authentication required).

### `POST /render-preview`

Server-side renders a block with provided attributes. Attributes are sanitized and validated through HyperFields before rendering.

**Request body**:
```json
{
  "blockName": "namespace/block-slug",
  "attributes": { "heading": "Hello", "bg_image": 42 }
}
```

**Response**:
```json
{ "success": true, "html": "<section class=\"hb-hero-banner\">...</section>" }
```

**Permissions**: requires `edit_posts` capability.

---

## Helpers (Procedural API)

All helper functions are defined in `src/helpers.php` and available globally after bootstrap.

```php
hyperblocks_block(string $title): Block
hyperblocks_field(string $type, string $name, string $label): Field
hyperblocks_field_group(string $name, string $id): FieldGroup
hyperblocks_register_block(Block $block): void
hyperblocks_register_field_group(FieldGroup $group): void
hyperblocks_registry(): Registry
hyperblocks_register_path(string $path): void
hyperblocks_config(string $key, mixed $default = null): mixed
hyperblocks_render(string $template, array $attributes = []): string
hyperblocks_has_block(string $blockName): bool
hyperblocks_get_block(string $blockName): ?Block
```

---

## Bootstrap & Constants

HyperBlocks uses a version-resolution bootstrap identical to HyperFields: each instance registers itself as a candidate; the highest version wins and initializes via `after_setup_theme` (priority 0).

**Constants defined after initialization**:

| Constant | Value |
|---|---|
| `HYPERBLOCKS_VERSION` | Version string from `composer.json`. |
| `HYPERBLOCKS_PATH` | Absolute path to the HyperBlocks root directory (trailing slash). |
| `HYPERBLOCKS_ABSPATH` | Same as `HYPERBLOCKS_PATH`. |
| `HYPERBLOCKS_PLUGIN_FILE` | Absolute path to `bootstrap.php`. |
| `HYPERBLOCKS_PLUGIN_URL` | URL to the HyperBlocks root (trailing slash). |
| `HYPERBLOCKS_BOOTSTRAP_LOADED` | Set when `bootstrap.php` is first included. |
| `HYPERBLOCKS_INSTANCE_LOADED` | Set when initialization logic runs (only once). |

HyperFields constants (`HYPERFIELDS_VERSION`, `HYPERFIELDS_ABSPATH`, etc.) are set by HyperFields' own bootstrap, which HyperBlocks triggers automatically when running standalone.

---

## HyperFields Integration

HyperBlocks integrates HyperFields at three levels:

1. **Field definitions** — `HyperBlocks\Block\Field` wraps `HyperFields\Field`. All field config, sanitization, and validation delegates to HyperFields.
2. **Block attributes** — `HyperFields\BlockFieldAdapter::toBlockAttribute()` maps HyperFields field types to Gutenberg attribute types (`string`, `number`, `boolean`).
3. **Sanitization pipeline** — on every `renderBlock` and `renderPreview` call, incoming attributes are run through `BlockFieldAdapter::sanitizeForBlock()` and `validateForBlock()` before the template executes. Invalid values fall back to the field's default.

When HyperBlocks is a Composer dependency (no standalone HyperFields plugin active), `bootstrap.php` triggers HyperFields initialization from the vendored copy. When both are active, HyperFields' own bootstrap guards prevent double-initialization.

---

## Version Management

1. Update `version` in `composer.json`.
2. Run `composer run version-bump` (updates `bootstrap.php` fallback literals).
3. Update `CHANGELOG.md`.

---

## Testing

```bash
# From the HyperBlocks root
composer run test
```

Tests use Pest v4 + Brain Monkey for WordPress function stubs. The test bootstrap:
- Defines `ABSPATH` and `HYPERBLOCKS_PATH`.
- Loads `vendor/autoload.php`.
- Loads `tests/mocks/wp-mocks.php` (WordPress function shims).
- Resets `Config` and `Registry` singletons.

Integration tests live in `tests/Integration/` (currently empty — add WP-loaded tests there).

---

## Important Notes

- PHP 8.2+ required (HyperFields sets the effective minimum).
- WordPress latest targeted.
- Do not call `Registry::reset()` outside tests.
- Do not call `Config::reset()` outside tests.
- Template paths are validated against an allowlist at both definition time (`Block::setRenderTemplate`) and render time (`Renderer::validateTemplatePath`). Path traversal (`..`) and absolute paths are rejected.
- `<script>` tags in field values are stripped before HyperFields sanitization.
- All block output must be escaped in templates (`esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`).
