# HyperFields — API Reference

**Note on Helper Functions:**
This documentation uses the `hf_` prefix for helper functions (e.g. `hf_get_field()`), which are the canonical names for the HyperFields plugin. For backward compatibility, `hp_` prefixed aliases (e.g. `hp_get_field()`) are also available and function identically.

## API Reference

Developer-focused API for saving and retrieving field values across posts, users, terms, and options, plus core helper factories for building admin UIs.

## Overview

- Centralized sanitization: values saved through HyperFields are sanitized via `Field::sanitizeValue()` when a type is provided.
- Field contexts supported: `post`, `user`, `term`, `option`.
- Helper factories available: `hf_option_page()`, `hf_field()`, `hf_tabs()`, `hf_repeater()`, `hf_section()`.
- Retrieval/update helpers: `hf_get_field()`, `hf_update_field()`, `hf_delete_field()`.

Source: `includes/helpers.php`

## Getting and Saving Values

Use the helpers to interact with various storage contexts.

```php
// Get from options (default group 'hyperpress_options')
$tagline = OptionField::forOption('site_tagline', 'text', 'site_tagline', 'Site Tagline')->getValue();

// Save to options (with type for sanitization)
OptionField::forOption('site_tagline', 'text', 'site_tagline', 'Site Tagline')->setValue('Hello World');

// Get post meta by ID
$title_override = PostField::forPost(123, 'text', 'custom_title', 'Custom Title')->getValue();

// Save user meta using user ID
UserField::forUser(45, 'checkbox', 'onboarding_done', 'Onboarding Done')->setValue('1');

// Delete a term meta value
TermField::forTerm(7, 'text', 'color', 'Color')->deleteValue();
```

Supported `$source` forms (auto-resolved):

- Post: numeric ID or `WP_Post`
- User: `"user_{ID}"` or `WP_User`
- Term: `"term_{ID}"` or `WP_Term`
- Options: `'option'|'options'` or `['type' => 'option', 'option_group' => '...']`
- `null`: falls back to current post if inside The Loop; otherwise options

See: `hf_resolve_field_context()` in `includes/helpers.php`.

## Sanitization

When you pass a `type` in the `$args`, `hf_update_field()` will sanitize via the HyperField model.

```php
hf_update_field('enable_feature', '1', 'options', [ 'type' => 'checkbox' ]);
```

Notes:
- Metabox field sanitization is centralized in `Field::sanitizeValue()` across Post/User/Term containers.
- Checkbox and Set fields are robust: hidden inputs ensure unchecked/empty states are posted; set fields drop the internal empty sentinel during sanitization.

## Helper Factories (for building UIs)

These factories return objects from the HyperFields system to compose admin pages/sections/fields.

```php
$opts = HyperFields::makeOptionPage('Site Settings', 'site-settings');
$field = HyperFields::makeField('text', 'site_tagline', 'Tagline');
$tabs  = HyperFields::makeTabs('settings_tabs', 'Settings');
$rep   = HyperFields::makeRepeater('social', 'Social Links');
$sec   = HyperFields::makeSection('general', 'General');
```

Refer to the HyperFields classes for the available methods on each object. Keep your implementation simple and PHP-first.

## Options Page Features

Recent HyperFields options work added the following capabilities:

- Explicit tab API in `OptionsPage`:
  - `addTab(string $id, string $title)`
  - `addSectionToTab(string $tabId, string $sectionId, string $title, string $description = '', array $args = [])`
- Section metadata in `OptionsSection`:
  - `slug` for section URL targeting (`?section=...`)
  - `as_link` to render section entries in a `subsubsub` menu
  - `allow_html_description` to render section descriptions through `wp_kses_post`
- Options page rendering/saving behavior now supports:
  - active tab + active section hidden fields
  - section menu rendering for linked sections
  - rendering and sanitizing only the currently active/renderable sections of a tab
- Additional field and options-page behavior:
  - `menu_icon` and `menu_position` map to options page menu metadata
  - `visible` callbacks can skip fields at registration/render time
  - alias support for `code-editor` / `code_editor` with WordPress code editor behavior
  - alias support for `wp-editor` / `wp_editor` mapped to HyperFields `rich_text`
  - alias support for `media` / `video` mapped to HyperFields `image`
  - `css.input_class` and `css.label_class` mapped to field args
  - `attributes` + `custom_attributes` merged into rendered input attributes
  - `validate` and `sanitize` callbacks are executed in the save pipeline
  - `rows` / `cols` and editor settings (`wpautop`, `teeny`, `tinymce`, `quicktags`, etc.) are passed through
  - tab/section `option_level` flags write and read nested option paths
  - legacy custom option classes can be bridged via a legacy option-type map filter
  - descriptions can be rendered as HTML when required
  - per-field validation feedback is attached and rendered inline

### Field Template Args (Newly Supported)

Field templates now support these args consistently:

- `input_class`: appended to rendered input controls
- `label_class`: appended to rendered labels
- `help_is_html`: renders help/description through `wp_kses_post` instead of escaping
- `input_type`: override text inputs with `email`, `url`, `number`, etc.
- `attributes`: additional safe HTML attributes rendered in field inputs
- `rows` / `cols`: textarea sizing controls
- `editor_settings`: rich-text editor settings passed into `wp_editor()`
- `error`: inline field validation message resolved from settings errors

These are exposed through `Field::toArray()` and are available to custom templates/renderers.


## Integration Examples

### Admin: Registering fields and saving values

```php
// Register an options page with fields
$options = HyperFields::makeOptionPage('Site Settings', 'site-settings')
    ->setMenuTitle('Site Settings')
    ->setParentSlug('options-general.php');
$general = $options->addSection('general', 'General Settings', 'Basic site configuration');
$general->addField(
    HyperFields::makeField('text', 'site_tagline', 'Site Tagline')->setDefault('')
);
$options->register();

// Register a post metabox field
add_action('add_meta_boxes', function() {
    add_meta_box('custom_title', 'Custom Title', function($post) {
        $field = HyperFields::makeField('text', 'custom_title', 'Custom Title');
        $value = PostField::for_post($post->ID, 'text', 'custom_title', 'Custom Title')->getValue();
        echo '<input type="text" name="custom_title" value="' . esc_attr($value) . '" />';
    }, 'post');
});
```

### Frontend: Rendering field values

```php
// Render an option field value
$tagline = hf_get_field('site_tagline', 'options', [ 'default' => '' ]);
echo esc_html($tagline);

// Render a post meta field value
$custom_title = hf_get_field('custom_title', get_the_ID(), [ 'default' => '' ]);
if ($custom_title) {
    echo '<h2>' . esc_html($custom_title) . '</h2>';
}

// Render a repeater field (social links)
$social = hf_get_field('social', 'options', [ 'default' => [] ]);
foreach ($social as $row) {
    echo '<a href="' . esc_url($row['url']) . '">' . esc_html($row['label']) . '</a> ';
}
```

HyperFields supports registering metaboxes for posts, users, and taxonomies. Use the value API to save and retrieve field values in these contexts.

### Example: Register a post metabox

```php
add_action('add_meta_boxes', function() {
    add_meta_box('custom_title', 'Custom Title', function($post) {
        $field = HyperFields::makeField('text', 'custom_title', 'Custom Title')
            ->setDefault('');
        $value = PostField::for_post($post->ID, 'text', 'custom_title', 'Custom Title')->getValue();
        echo '<input type="text" name="custom_title" value="' . esc_attr($value) . '" />';
    }, 'post');
});

// Save value
add_action('save_post', function($post_id) {
    if (isset($_POST['custom_title'])) {
        PostField::for_post($post_id, 'text', 'custom_title', 'Custom Title')->setValue($_POST['custom_title']);
    }
});
```

### Example: User meta field

```php
$field = HyperFields::makeField('checkbox', 'onboarding_done', 'Onboarding Done');
$value = UserField::forUser($user_id, 'checkbox', 'onboarding_done', 'Onboarding Done')->getValue();
```

### Example: Taxonomy meta field

```php
$field = HyperFields::makeField('color', 'category_color', 'Category Color');
$value = TermField::forTerm($term_id, 'color', 'category_color', 'Category Color')->getValue();
```

HyperFields provides a fluent API for building custom options pages in the WordPress admin. Use `HyperFields::makeOptionPage()` to create a page, add sections and fields, and register it.

### Example: Register a custom options page

```php
$options = HyperFields::makeOptionPage('Site Settings', 'site-settings')
    ->setMenuTitle('Site Settings')
    ->setParentSlug('options-general.php')
    ->setIconUrl('dashicons-admin-generic');

$general = $options->addSection('general', 'General Settings', 'Basic site configuration');
$general->addField(
    HyperFields::makeField('text', 'site_tagline', 'Site Tagline')
        ->setDefault('')
);

$options->register();
```

**Notes:**
- You can add multiple sections and fields to each options page.
- Use WordPress capabilities and nonces for security.
- Fields registered in options pages are stored in the options table and can be retrieved via the value API.

HyperFields supports conditional logic for field visibility and dynamic UI behavior. You can attach logic to any field using the `setConditionalLogic()` method.

### Example: Show field only if another field is set

```php
$field = HyperFields::makeField('number', 'items_per_page', 'Items Per Page')
    ->setDefault(10)
    ->setConditionalLogic([
        'relation' => 'AND',
        'conditions' => [[
            'field' => 'display_mode',
            'operator' => '=',
            'value' => 'advanced'
        ]]
    ]);
```

**Supported operators:** `=`, `!=`, `>`, `<`, `in`, `not_in` (see API for full list).

**Notes:**
- Conditional logic is evaluated server-side and/or client-side depending on your implementation.
- You can combine multiple conditions using `relation: 'AND'` or `relation: 'OR'`.
- Use conditional logic to build dynamic forms, show/hide fields, or enable advanced workflows.

- Prefer WordPress capabilities and nonces for admin operations.
- Keep forms accessible and semantic.
- Use `hf_get_field()` defaults to avoid undefined notices.
- For options pages, array notation is used where appropriate; compact POST is supported (see Options Compact Input).

## Export / Import

HyperFields includes a complete Export/Import system. It works at two levels: a programmatic API (`ExportImport` class + helper functions) and a ready-made admin UI (`ExportImportUI`) that matches the HyperFields admin look and feel.

### Registering a Data Tools admin page

The recommended approach for third-party developers. One call inside `admin_menu` wires up the submenu page, asset enqueueing, and rendering automatically.

**Via the facade:**

```php
use HyperFields\HyperFields;

add_action('admin_menu', function () {
    HyperFields::registerDataToolsPage(
        parentSlug:           'my-plugin',
        pageSlug:             'my-plugin-data-tools',
        options: [
            'my_plugin_options' => 'My Plugin Settings',
        ],
        allowedImportOptions: ['my_plugin_options'],
        prefix:               'myp_',
        title:                'Data Tools',
        capability:           'manage_options',
    );
});
```

**Via the helper function:**

```php
add_action('admin_menu', function () {
    hf_register_data_tools_page(
        parentSlug: 'my-plugin',
        pageSlug:   'my-plugin-data-tools',
        options:    ['my_plugin_options' => 'My Plugin Settings'],
        prefix:     'myp_',
        title:      'Data Tools',
    );
});
```

**Via the class directly:**

```php
use HyperFields\Admin\ExportImportUI;

add_action('admin_menu', function () {
    ExportImportUI::registerPage(
        parentSlug:           'my-plugin',
        pageSlug:             'my-plugin-data-tools',
        options:              ['my_plugin_options' => 'My Plugin Settings'],
        allowedImportOptions: ['my_plugin_options'],
        prefix:               'myp_',
        title:                'Data Tools',
        capability:           'manage_options',
    );
});
```

### Manual wiring (advanced)

For full control over menu placement and hooks:

```php
use HyperFields\Admin\ExportImportUI;

// Enqueue assets on the correct admin page
add_action('admin_enqueue_scripts', function (string $hook): void {
    if ($hook === 'my-plugin_page_my-plugin-data-tools') {
        ExportImportUI::enqueuePageAssets();
    }
});

// Register the menu and render
add_action('admin_menu', function (): void {
    add_submenu_page(
        'my-plugin',
        'Data Tools',
        'Data Tools',
        'manage_options',
        'my-plugin-data-tools',
        function (): void {
            echo ExportImportUI::render(
                options:              ['my_plugin_options' => 'My Plugin Settings'],
                allowedImportOptions: ['my_plugin_options'],
                prefix:               'myp_',
                title:                'Data Tools',
            );
        }
    );
});
```

The `admin_enqueue_scripts` hook fires before page output begins, which is the correct time to call `wp_enqueue_style` / `wp_enqueue_script`. Calling `enqueuePageAssets()` inside the render callback would be too late.

### Programmatic API (no UI)

Use these when you need to export/import from code, e.g. in a WP-CLI command or a migration script.

```php
// Export one or more option groups to a JSON string
$json = hf_export_options(['my_plugin_options'], 'myp_');
// or: HyperFields::exportOptions(['my_plugin_options'], 'myp_');

// Save the JSON somewhere or offer it for download
file_put_contents('/tmp/backup.json', $json);

// Import from a JSON string
$result = hf_import_options($json, ['my_plugin_options'], 'myp_');
// or: HyperFields::importOptions($json, ['my_plugin_options'], 'myp_');

if ($result['success']) {
    // Backup transient keys are available if data existed before import
    foreach ($result['backup_keys'] ?? [] as $optionName => $backupKey) {
        // Can call ExportImport::restoreBackup($backupKey, $optionName) to undo
    }
} else {
    error_log($result['message']);
}
```

### Restoring a backup

`importOptions()` automatically saves a transient backup of each option it overwrites (TTL: 1 hour). The backup key is returned in `$result['backup_keys']`.

```php
use HyperFields\ExportImport;

ExportImport::restoreBackup(
    $result['backup_keys']['my_plugin_options'],
    'my_plugin_options'
);
```

### `ExportImportUI` parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$parentSlug` | string | — | Parent menu slug (e.g. `'my-plugin'` or `'options-general.php'`). |
| `$pageSlug` | string | — | Unique slug for this page. |
| `$options` | array | `[]` | Map of WP option name → human-readable label. |
| `$allowedImportOptions` | array | `[]` | Whitelist of option names that may be written on import. Defaults to all keys in `$options`. |
| `$prefix` | string | `''` | Only export/import option-array keys starting with this prefix. |
| `$title` | string | `'Data Export / Import'` | Page heading and menu label. |
| `$capability` | string | `'manage_options'` | Required WordPress capability. |

### `ExportImport` API

| Method | Description |
|---|---|
| `exportOptions(array $optionNames, string $prefix = ''): string` | Serialize option groups to a JSON string. |
| `importOptions(string $json, array $allowed = [], string $prefix = ''): array` | Deserialize and write option groups. Returns `['success', 'message', 'backup_keys?']`. |
| `restoreBackup(string $backupKey, string $optionName): bool` | Restore an option from the transient backup created during import. |
| `snapshotOptions(array $optionNames, string $prefix = ''): array` | Read current DB values without encoding to JSON (used by the diff preview). |

Helper function aliases: `hf_export_options()`, `hf_import_options()`.
Facade aliases: `HyperFields::exportOptions()`, `HyperFields::importOptions()`.

## Field Types Reference

This section documents the available HyperFields field types, how to declare them with the factory helpers, how values are saved/retrieved, and any special sanitization or rendering notes.

Notes and assumptions:
- All examples use the `hf_field()` factory (alias of `hf_field()` in this codebase).
- `hf_update_field()` will run `Field::sanitizeValue()` when a `type` is provided in the `$args`.
- When rendering values in templates always escape output according to the value shape (use `esc_html()`, `esc_url()`, `wp_kses_post()` as appropriate).

### Authoritative Type Matrix

Core HyperFields field types accepted by `Field::make()`:

- `text`
- `textarea`
- `number`
- `email`
- `url`
- `color`
- `date`
- `datetime`
- `time`
- `image`
- `file`
- `select`
- `multiselect`
- `checkbox`
- `radio`
- `radio_image`
- `rich_text`
- `hidden`
- `html`
- `map`
- `oembed`
- `separator`
- `header_scripts`
- `footer_scripts`
- `set`
- `sidebar`
- `association`
- `tabs`
- `custom`
- `heading`
- `media_gallery`
- `repeater`

Accepted field aliases:

- `choices` -> `radio`
- `select-multiple` / `select_multiple` -> `multiselect`
- `wp-editor` / `wp_editor` -> `rich_text`
- `media` / `video` -> `image`
- `code-editor` / `code_editor` -> WordPress CodeMirror-backed custom field

### Text

Use for single-line strings.

Declaration (admin UI):

```php
$field = HyperFields::makeField('text', 'site_tagline', 'Site Tagline');
$field->setPlaceholder('Short tagline');
$field->setDefault('');
$field->setRequired(false);
```

Save / retrieve:

```php
hf_update_field('site_tagline', 'Hello world', 'options', [ 'type' => 'text' ]);
$tagline = hf_get_field('site_tagline', 'options', [ 'default' => '' ]);
echo esc_html($tagline);
```

Sanitization: trimmed string, HTML stripped unless explicitly allowed by field configuration.

### Textarea

Multi-line text; useful for summaries or HTML if you permit it.

Declaration:

```php
$field = HyperFields::makeField('textarea', 'bio', 'Author bio')
    ->setRows(4)
    ->setDefault('');
```

Save / retrieve:

```php
hf_update_field('bio', '<p>Bio here</p>', 'user_45', [ 'type' => 'textarea' ]);
$bio = hf_get_field('bio', 'user_45', [ 'default' => '' ]);
echo wp_kses_post($bio); // allow basic tags if your workflow permits
```

Sanitization: by default HTML is stripped; if the field is expected to contain safe HTML, document that and allow it at render time with `wp_kses_post()`.

### Number

Integers or floats. Accepts `min`, `max`, `step` in args.

Declaration:

```php
$field = HyperFields::makeField('number', 'priority', 'Priority');
$field->setDefault(10);
$field->setMin(0);
$field->setMax(100);
```

Save / retrieve:

```php
hf_update_field('priority', 20, 123, [ 'type' => 'number' ]);
$priority = (int) hf_get_field('priority', 123, [ 'default' => 0 ]);
```

Sanitization: coerced to numeric type; ensure client-side constraints if necessary.

### Checkbox

Boolean flag. When unchecked a hidden input may be used by the admin UI to ensure a value is posted.

Declaration:

```php
$field = HyperFields::makeField('checkbox', 'enable_feature', 'Enable feature');
$field->setDefault(false);
```

Save / retrieve:

```php
hf_update_field('enable_feature', '1', 'options', [ 'type' => 'checkbox' ]);
$enabled = (bool) hf_get_field('enable_feature', 'options', [ 'default' => false ]);
```

Sanitization: normalized to boolean-like values (0/1 or true/false).

### Select / Radio

Single-choice inputs. Provide `choices` as an associative array value => label.

Declaration:

```php
$field = HyperFields::makeField('select', 'color_scheme', 'Color Scheme');
$field->setChoices([ 'light' => 'Light', 'dark' => 'Dark' ]);
$field->setDefault('light');
```

Save / retrieve:

```php
hf_update_field('color_scheme', 'dark', 123, [ 'type' => 'select' ]);
$scheme = hf_get_field('color_scheme', 123, [ 'default' => 'light' ]);
echo esc_html($scheme);
```

Sanitization: value validated against provided choices when available.

### Color

Hex color values; sanitized via `esc_attr()` on render and validated on save.

Declaration:

```php
$field = HyperFields::makeField('color', 'accent_color', 'Accent Color');
$field->setDefault('#ff0000');
```

Save / retrieve:

```php
hf_update_field('accent_color', '#00aaFF', 'options', [ 'type' => 'color' ]);
$color = hf_get_field('accent_color', 'options', [ 'default' => '#000000' ]);
echo esc_attr($color);
```

Sanitization: hex validation; accept 3- or 6-digit hex values.

### URL

For link fields. Always escape with `esc_url()` when rendering.

Declaration:

```php
$field = HyperFields::makeField('url', 'button_url', 'Button URL');
$field->setDefault('#');
```

Save / retrieve:

```php
hf_update_field('button_url', 'https://example.com', 'options', [ 'type' => 'url' ]);
$url = hf_get_field('button_url', 'options', [ 'default' => '#' ]);
echo esc_url($url);
```

Sanitization: validated via `esc_url_raw()`/`esc_url()` semantics; schemes like `javascript:` are removed.

### Image (aliases: `media`, `video`)

Reference to an attachment ID. Native HyperFields type is `image`. `media` and `video` aliases are translated to `image`.

Declaration:

```php
$field = HyperFields::makeField('image', 'hero_image', 'Hero image');
```

Save / retrieve:

```php
hf_update_field('hero_image', 456, 123, [ 'type' => 'image' ]); // saves attachment ID
$attachment_id = hf_get_field('hero_image', 123, [ 'default' => 0 ]);
if ($attachment_id) {
    echo wp_get_attachment_image($attachment_id, 'large');
}
```

Sanitization: ensure the ID is numeric and the attachment exists before rendering.

### Repeater

Repeater fields store an ordered list of subfields. The value is typically an array of rows, each an associative array keyed by subfield name.

Declaration (example):

```php
$rep = HyperFields::makeRepeater('social', 'Social Links');
$rep->setFields([
    HyperFields::makeField('text', 'label', 'Label'),
    HyperFields::makeField('url', 'url', 'URL'),
    HyperFields::makeField('icon', 'icon', 'Icon')
]);
$rep->setMinRows(0);
```

Save / retrieve:

```php
$rows = [
    [ 'label' => 'Twitter', 'url' => 'https://twitter.com/example' ],
    [ 'label' => 'GitHub',  'url' => 'https://github.com/example' ],
];
hf_update_field('social', $rows, 'options', [ 'type' => 'repeater' ]);
$social = hf_get_field('social', 'options', [ 'default' => [] ]);
foreach ($social as $row) {
    echo '<a href="' . esc_url($row['url']) . '">' . esc_html($row['label']) . '</a>';
}
```

Sanitization: each subfield is sanitized according to its declared `type`.

### Tabs / Section (organization)

These are UI helpers to group fields; they do not change storage format. Use `hf_tabs()` and `hf_section()` to structure admin pages.

Example:

```php
$tabs = HyperFields::makeTabs('settings_tabs', 'Settings');
$tabs->addTab('general', 'General', [ HyperFields::makeField('text', 'site_tagline', 'Tagline') ]);
```

### Association / Map

Advanced types for linking objects (posts, terms, users) or storing key/value maps.

Declaration example (association):

```php
$field = HyperFields::makeField('association', 'related_posts', 'Related Posts');
$field->setPostType(['post','page']);
$field->setMultiple(true);
```

Save / retrieve:

```php
hf_update_field('related_posts', [12,45], 123, [ 'type' => 'association' ]);
$related = hf_get_field('related_posts', 123, [ 'default' => [] ]);
// $related is an array of post IDs by default
```

Map example (simple key/value storage):

```php
$field = HyperFields::makeField('map', 'social_handles', 'Social handles');
hf_update_field('social_handles', ['twitter' => '@me', 'github' => 'me'], 'options', [ 'type' => 'map' ]);
$handles = hf_get_field('social_handles', 'options', [ 'default' => [] ]);
```

### Date / Time / Datetime

Fields for date and time values. Values are saved in a consistent canonical format (ISO-ish) and can be converted server-side.

Declaration:

```php
$field = HyperFields::makeField('date', 'event_date', 'Event Date');
$field->setDefault('');
$time  = HyperFields::makeField('time', 'event_time', 'Event Time');
```

Save / retrieve:

```php
hf_update_field('event_date', '2025-09-01', 123, [ 'type' => 'date' ]);
$date = hf_get_field('event_date', 123, [ 'default' => '' ]);
echo esc_html($date);
```

Sanitization: validated to expected format; convert to DateTime objects where necessary in business logic.

## Developer tips

- Prefer explicit `type` when calling `hf_update_field()` so sanitization runs predictably.
- Use `hf_get_field(..., [ 'default' => ... ])` to avoid undefined values.
- When exposing user-supplied HTML, sanitize on output with `wp_kses_post()` and document the allowed tags.
- For media and association fields always check existence (attachment/post exists) before rendering links or images.
- For Export/Import: always pass `$allowedImportOptions` to restrict which options a site admin can overwrite. Never leave it empty on a shared/multisite install.
