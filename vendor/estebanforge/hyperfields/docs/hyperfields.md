# HyperFields (API and Field Types)

**Note on Helper Functions:**
This documentation uses the `hf_` prefix for helper functions (e.g., `hf_get_field()`), which are the canonical names for the HyperFields plugin. For backward compatibility, `hp_` prefixed aliases (e.g., `hp_get_field()`) are also available and function identically.

## API Reference

See below for all available methods, usage patterns, and value operations for HyperFields.

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

## Field Types Reference

This section documents the available HyperFields field types, how to declare them with the factory helpers, how values are saved/retrieved, and any special sanitization or rendering notes.

Notes and assumptions:
- All examples use the `hf_field()` factory (alias of `hf_field()` in this codebase).
- `hf_update_field()` will run `Field::sanitizeValue()` when a `type` is provided in the `$args`.
- When rendering values in templates always escape output according to the value shape (use `esc_html()`, `esc_url()`, `wp_kses_post()` as appropriate).

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

### Media

Reference to an attachment. By default the field returns an attachment ID; configuration may allow returning an array with metadata.

Declaration:

```php
$field = HyperFields::makeField('media', 'hero_image', 'Hero image');
$field->setReturn('id'); // or 'array'
```

Save / retrieve:

```php
hf_update_field('hero_image', 456, 123, [ 'type' => 'media' ]); // saves attachment ID
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

If you'd like, I can:
- Normalize other docs in the repo that reference `.hp.php` to match `.hb.php` where appropriate.
- Generate small example templates under `docs/examples/` demonstrating admin registration and front-end rendering for each field type.
