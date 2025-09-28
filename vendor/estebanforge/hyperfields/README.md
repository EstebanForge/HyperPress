# HyperFields

A powerful custom field system for WordPress, providing metaboxes, options pages, and conditional logic.

## Installation

### As a Plugin

1. Download the plugin zip file
2. Upload to your WordPress plugins directory
3. Activate the plugin

### As a Composer Library

```bash
composer require estebanforge/hyperfields
```

Then include the bootstrap file in your project:

```php
require_once 'path/to/hyperfields/bootstrap.php';
```

## Usage

### Helper Functions

HyperFields provides convenient helper functions with the `hf_` prefix:

```php
// Create a field
$field = hf_field('text', 'my_field', 'My Field');

// Get field value
$value = hf_get_field('my_field', 'option', ['option_group' => 'my_options']);

// Update field value
hf_update_field('my_field', 'new value', 'option', ['option_group' => 'my_options']);

// Create an options page
$page = hf_option_page('My Settings', 'my-settings');
```

### Creating Fields

```php
use HyperFields\Field;
use HyperFields\OptionsPage;

// Create an options page
$page = OptionsPage::make('My Settings', 'my-settings');

// Add fields
$page->addField(
    Field::make('text', 'site_title', 'Site Title')
        ->setDefault('My Awesome Site')
        ->setRequired()
);

// Render the page
$page->render();
```

## Field Types

- text
- textarea
- number
- email
- url
- color
- date
- datetime
- time
- image
- file
- select
- multiselect
- checkbox
- radio
- radio_image
- rich_text
- hidden
- html
- map
- oembed
- separator
- header_scripts
- footer_scripts
- set
- sidebar
- association
- tabs
- custom
- heading
- media_gallery

## Features

- Conditional logic
- Validation
- Sanitization
- Multiple storage types (post meta, user meta, term meta, options)
- Custom field containers
- Repeater fields
- Tabbed interfaces
- Extensive hooks and filters

## Requirements

- PHP 8.1+
- WordPress 5.0+

## License

GPL-2.0-or-later
