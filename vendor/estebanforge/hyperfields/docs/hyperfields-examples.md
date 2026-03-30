# HyperFields Examples

**Note on Helper Functions:**
This documentation uses the `hf_` prefix for helper functions (e.g., `hf_get_field()`), which are the canonical names for the HyperFields plugin. For backward compatibility, `hp_` prefixed aliases (e.g., `hp_get_field()`) are also available and function identically.

This directory contains **example files** demonstrating HyperFields usage. These files are **NOT auto-activated** and are provided for learning and reference purposes.

## 📁 Files Overview

### 🧰 **helper-functions-examples.php**
Examples for the helper functions:
- `hf_get_field()` to retrieve values
- `hf_save_field()` to store values (alias of `hf_update_field()`)
- `hf_delete_field()` to remove values

Covers contexts: options, post meta, user meta, and term meta. Shows how to pass `type` to leverage `Field::sanitizeValue()`.

**Usage:** Include the file and hook any of the provided functions (e.g., `hyperfields_helper_examples_options`) via `add_action('init', '...')`.

### 🚀 **simple-example.php**
Basic HyperFields metabox example showing:
- Simple post metabox creation
- Basic field types (text, textarea, checkbox)
- Post type targeting

**Usage:** Copy the function to your theme/plugin and activate it.

### 🎯 **targeting-examples.php**
Comprehensive targeting examples showing:
- Post targeting by ID, slug, type
- User targeting by ID, role
- Term targeting by ID, slug, taxonomy
- Complex combinations and conditional logic

**Usage:** Copy specific targeting patterns you need.

### 📦 **metabox-examples.php**
Complete metabox examples demonstrating:
- Post meta containers
- Term meta containers
- User meta containers
- Different field types and configurations

**Usage:** Copy entire container configurations.

### 📖 **targeting-quick-reference.php**
Quick reference guide showing:
- All targeting method syntax
- Practical examples
- Combination patterns
- Complete documentation in comments

**Usage:** Reference guide for targeting syntax.

## 🔧 How to Use These Examples

### Option 1: Copy Functions
```php
// Copy any function from the examples to your theme/plugin
function my_custom_metabox() {
    $container = HyperFields::makePostMeta('my_meta', 'My Fields')
        ->where('post')
        ->setContext('normal');

    $container->addField(
        HyperFields::makeField('text', 'my_field', 'My Field')
    );
}

// Activate it
add_action('init', 'my_custom_metabox');
```

### Option 2: Include and Activate
```php
// In your theme/plugin
require_once 'path/to/hyperfields/simple-example.php';

// Uncomment the add_action lines in the example files
// OR manually activate:
add_action('init', 'hyperfields_simple_example');
```

### Option 3: Use as Reference
- Read through the examples to understand patterns
- Copy specific targeting syntax you need
- Adapt the field configurations for your use case

## ⚠️ Important Notes

- **These files are examples only** - they don't auto-activate
- **Always copy to your own code** - don't modify these files directly
- **Test thoroughly** - modify IDs and targeting to match your content
- **Follow WordPress coding standards** when implementing

## 🎯 Targeting Quick Reference

```php
// Post targeting
->where('post_type')              // All posts of type
->wherePostId(123)                // Specific post ID
->wherePostSlug('homepage')       // Specific post slug
->wherePostIds([1, 2, 3])         // Multiple post IDs
->wherePostSlugs(['home', 'about']) // Multiple post slugs

// User targeting
->where('administrator')          // User role
->whereUserId(123)                // Specific user ID
->whereUserIds([1, 2, 3])         // Multiple user IDs

// Term targeting
->where('category')               // Taxonomy
->whereTermId(123)                // Specific term ID
->whereTermSlug('featured')       // Specific term slug
->whereTermIds([1, 2, 3])         // Multiple term IDs
->whereTermSlugs(['featured', 'trending']) // Multiple term slugs
```

## 📤 Export / Import

HyperFields ships a built-in Export / Import system for WordPress option groups. It provides a visual admin page with jsondiffpatch diff preview and a programmatic API for headless use.

### Register a Data Tools page

The recommended one-call integration for third-party plugins. Must be called inside `admin_menu`:

```php
add_action('admin_menu', function () {
    HyperFields\HyperFields::registerDataToolsPage(
        parentSlug: 'my-plugin',           // Parent menu slug
        pageSlug:   'my-plugin-data-tools', // Unique page slug
        options: [
            'my_plugin_options' => 'My Plugin Settings',
        ],
        allowedImportOptions: ['my_plugin_options'], // Whitelist for import
        prefix:     'myp_',                // Only export/import keys starting with 'myp_'
        title:      'Data Tools',
    );
});
```

Or with the procedural helper:

```php
add_action('admin_menu', function () {
    hf_register_data_tools_page(
        parentSlug: 'my-plugin',
        pageSlug:   'my-plugin-data-tools',
        options:    ['my_plugin_options' => 'My Plugin Settings'],
    );
});
```

### Programmatic API (no UI)

```php
// Export one or more option groups to JSON
$json = hf_export_options(['my_plugin_options'], 'myp_');

// Import from JSON — restrict to your own options
$result = hf_import_options($json, ['my_plugin_options'], 'myp_');

if ($result['success']) {
    // Import succeeded; backup transient key available
    $backup_key = $result['backup_keys']['my_plugin_options'] ?? null;
} else {
    echo $result['message']; // human-readable error
}

// Restore from transient backup if something went wrong
HyperFields\ExportImport::restoreBackup(
    $result['backup_keys']['my_plugin_options'],
    'my_plugin_options'
);
```

### Behaviour notes

- Export skips option groups whose value is not an array.
- When a `$prefix` is set, only keys starting with that prefix are included in export and import.
- Import is **additive**: keys not present in the JSON payload are preserved in the existing option.
- If the whitelist (`$allowedImportOptions`) or prefix filter removes all entries, `importOptions` returns `['success' => false, ...]`.
- Before overwriting, `importOptions` stores a 1-hour transient backup; the key is returned in `backup_keys`.
- `restoreBackup` deletes the transient after a successful restore (including when the value was already identical).

## 🚧 Optional: Compact Input for Options Pages

To avoid hitting PHP's `max_input_vars` on complex options pages, HyperFields can compact all option inputs into a single POST variable.

- Disabled by default.
- Enable via constants (e.g. in `wp-config.php` or early plugin code):

```php
define('HYPERPRESS_COMPACT_INPUT', true);
define('HYPERPRESS_COMPACT_INPUT_KEY', 'hyperpress_compact_input'); // optional, default shown
```

When enabled, HyperFields will:
- Render a hidden compact input on options pages.
- Serialize the active tab's fields into JSON under the `HYPERPRESS_COMPACT_INPUT_KEY`.
- Remove original field `name` attributes before submit to drastically reduce POST vars.
- Expand and sanitize the compacted input server-side in `OptionsPage::sanitize_options()`.

No changes are needed to your field definitions. Existing helpers like `hf_get_field()` and `hf_save_field()` continue to work as before.
