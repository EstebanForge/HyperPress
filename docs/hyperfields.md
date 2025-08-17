# HyperFields (API and Field Types)

Developer-focused API for saving and retrieving field values across posts, users, terms, and options, plus core helper factories for building admin UIs.

## Overview

- Centralized sanitization: values saved through HyperFields are sanitized via `Field::sanitize_value()` when a type is provided.
- Field contexts supported: `post`, `user`, `term`, `option`.
- Helper factories available: `hp_create_option_page()`, `hp_create_field()`, `hp_create_tabs()`, `hp_create_repeater()`, `hp_create_section()`.
- Retrieval/update helpers: `hp_get_field()`, `hp_update_field()`, `hp_delete_field()`.

Source: `src/plugins/HyperPress/includes/helpers.php`

## Getting and Saving Values

Use the helpers to interact with various storage contexts.

```php
// Get from options (default group 'hmapi_options')
$tagline = hp_get_field('site_tagline', 'options', [
    'default' => ''
]);

// Save to options (with type for sanitization)
hp_update_field('site_tagline', 'Hello World', 'options', [
    'type' => 'text',            // Enables Field::sanitize_value()
    'option_group' => 'hmapi_options'
]);

// Get post meta by ID
$title_override = hp_get_field('custom_title', 123, [ 'default' => '' ]);

// Save user meta using "user_{ID}" shorthand
hp_update_field('onboarding_done', '1', 'user_45', [ 'type' => 'checkbox' ]);

// Delete a term meta value
hp_delete_field('color', 'term_7');
```

Supported `$source` forms (auto-resolved):

- Post: numeric ID or `WP_Post`
- User: `"user_{ID}"` or `WP_User`
- Term: `"term_{ID}"` or `WP_Term`
- Options: `'option'|'options'` or `['type' => 'option', 'option_group' => '...']`
- `null`: falls back to current post if inside The Loop; otherwise options

See: `hp_resolve_field_context()` in `includes/helpers.php`.

## Sanitization

When you pass a `type` in the `$args`, `hp_update_field()` will sanitize via the HyperField model.

```php
hp_update_field('enable_feature', '1', 'options', [ 'type' => 'checkbox' ]);
```

Notes:
- Metabox field sanitization is centralized in `Field::sanitize_value()` across Post/User/Term containers.
- Checkbox and Set fields are robust: hidden inputs ensure unchecked/empty states are posted; set fields drop the internal empty sentinel during sanitization.

## Helper Factories (for building UIs)

These factories return objects from the HyperFields system to compose admin pages/sections/fields.

```php
$opts = hp_option_page('Site Settings', 'site-settings');
$field = hp_field('text', 'site_tagline', 'Tagline');
$tabs  = hp_tabs('settings_tabs', 'Settings');
$rep   = hp_repeater('social', 'Social Links');
$sec   = hp_section('general', 'General');
```

Refer to the HyperFields classes for the available methods on each object. Keep your implementation simple and PHP-first.

## Tips

- Prefer WordPress capabilities and nonces for admin operations.
- Keep forms accessible and semantic.
- Use `hp_get_field()` defaults to avoid undefined notices.
- For options pages, array notation is used where appropriate; compact POST is supported (see Options Compact Input).
