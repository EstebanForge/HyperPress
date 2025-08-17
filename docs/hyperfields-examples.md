# HyperFields Examples

This directory contains **example files** demonstrating HyperFields usage. These files are **NOT auto-activated** and are provided for learning and reference purposes.

## 📁 Files Overview

### 🧰 **helper-functions-examples.php**
Examples for the helper functions:
- `hp_get_field()` to retrieve values
- `hp_save_field()` to store values (alias of `hp_update_field()`)
- `hp_delete_field()` to remove values

Covers contexts: options, post meta, user meta, and term meta. Shows how to pass `type` to leverage `Field::sanitize_value()`.

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

## 📚 Full Documentation

See the main plugin README.md for complete HyperFields documentation, including:
- API reference
- Field types
- Conditional logic
- Options pages
- Integration examples

## 🚧 Optional: Compact Input for Options Pages

To avoid hitting PHP's `max_input_vars` on complex options pages, HyperFields can compact all option inputs into a single POST variable.

- Disabled by default.
- Enable via constants (e.g. in `wp-config.php` or early plugin code):

```php
define('HMAPI_COMPACT_INPUT', true);
define('HMAPI_COMPACT_INPUT_KEY', 'hmapi_compact_input'); // optional, default shown
```

When enabled, HyperFields will:
- Render a hidden compact input on options pages.
- Serialize the active tab's fields into JSON under the `HMAPI_COMPACT_INPUT_KEY`.
- Remove original field `name` attributes before submit to drastically reduce POST vars.
- Expand and sanitize the compacted input server-side in `OptionsPage::sanitize_options()`.

No changes are needed to your field definitions. Existing helpers like `hp_get_field()` and `hp_save_field()` continue to work as before.
