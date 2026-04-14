# HyperFields React Integration - Developer Guide

## Overview

HyperFields now supports **optional React rendering** for fields that benefit from enhanced interactivity. This is implemented as a **PHP-first API** with an internal React bridge - developers don't need to know React to use modern UI components.

## Key Features

✅ **PHP-only API** - No React knowledge required
✅ **Progressive Enhancement** - HTML works, React is opt-in
✅ **Zero Breaking Changes** - Existing code continues working
✅ **Modern UI Components** - Uses WordPress' @wordpress/components
✅ **Easy Migration** - Switch fields to React with one line change

## Quick Start

### Basic Field (HTML - unchanged)
```php
use HyperFields\OptionsPage;
use HyperFields\Field;

$page = OptionsPage::make('My Settings', 'my-settings')
    ->addSection('general', 'General', 'General settings')
    ->addField(
        Field::make('text', 'site_title', 'Site Title')
            ->setDefault('My Website')
    );

$page->register();
```

### React-Enhanced Field (NEW)
```php
use HyperFields\OptionsPage;
use HyperFields\Field\ReactField;

$page = OptionsPage::make('My Settings', 'my-settings')
    ->addSection('general', 'General', 'General settings')
    ->addField(
        ReactField::make('text', 'site_title', 'Site Title')
            ->setDefault('My Website')
    )
    ->addField(
        ReactField::make('image', 'logo', 'Site Logo')
            ->setHelp('Upload a logo (React-enhanced)')
    );

$page->register();
```

**That's it!** Just change `Field::make()` to `ReactField::make()` and the field automatically uses React rendering.

## Supported Field Types

| Type | Component | Notes |
|------|-----------|-------|
| `text` | TextField | Standard text input |
| `textarea` | TextareaField | Multi-line text |
| `number` | NumberField | Number input with min/max |
| `email` | EmailField | Email validation |
| `url` | UrlField | URL input |
| `color` | ColorPicker | WordPress color picker |
| `image` | MediaUpload | Media library integration |
| `checkbox` | CheckboxControl | Toggle checkbox |
| `select` | SelectControl | Dropdown select |

## Advanced Usage

### Custom React Props
```php
ReactField::make('image', 'hero_image', 'Hero Image')
    ->setReactProp('maxWidth', 1200)
    ->setReactProp('maxHeight', 600)
    ->setReactProp('buttonLabel', 'Upload Hero Image');
```

### Custom Component
```php
ReactField::make('custom', 'my_field', 'My Custom Field')
    ->setReactComponent('MyCustomComponent');
    // Component must be registered in React app
```

### Conditional React Usage
```php
$field = ReactField::make('text', 'username', 'Username');
if ($useReact) {
    $field->setUseReact(true);
} else {
    $field->setUseReact(false); // Falls back to HTML
}
```

## Building React Assets

### First-Time Setup
```bash
cd /path/to/HyperFields
npm install
```

### Build Commands
```bash
# Production build (minified)
npm run build

# Development build (with source maps)
npm run build:dev

# Watch mode for development
npm run watch

# Clean build artifacts
npm run clean
```

## Architecture

### PHP Side
1. **ReactField** extends Field with React-specific properties
2. **OptionsPage** detects ReactField instances
3. Enqueues wp-element, wp-components
4. Passes field data via wp_localize_script

### JavaScript Side
1. **index.jsx** - Entry point, initializes React app
2. **ReactFieldsApp** - Main app component
3. **Components** - Individual field components
4. Updates hidden inputs for form submission

### Data Flow
```
PHP Field Definition → wp_localize_script → window.hyperfieldsReactData
                                                          ↓
                                                    React App
                                                          ↓
                                              Component renders
                                                          ↓
                                    User updates value → onChange
                                                          ↓
                              Updates hidden input → form submission
```

## Troubleshooting

### React fields not rendering
- Check browser console for errors
- Verify build assets exist: `assets/js/dist/react-fields.js`
- Confirm wp-element and wp-components are enqueued
- Check that `window.hyperfieldsReactData` exists

### Build errors
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Clear build artifacts
npm run clean
npm run build
```

### Styling issues
- Ensure both hyperfields-admin.css and react-fields.css are enqueued
- Check for CSS conflicts with theme/plugins
- Verify CSS variables are defined

## Migration Guide

### Phase 1: Try React on new fields
```php
// New fields get React by default
ReactField::make('image', 'avatar', 'User Avatar');
```

### Phase 2: Gradual migration
```php
// Migrate complex fields first (images, colors, repeaters)
ReactField::make('repeater', 'slides', 'Slides');
ReactField::make('color', 'brand_color', 'Brand Color');
```

### Phase 3: Evaluate and adjust
- Keep HTML for simple fields (text, checkbox)
- Use React for complex fields (media, repeaters, color pickers)
- Mix and match as needed

## Contributing

### Adding a new field component

1. Create component file:
```jsx
// assets/js/src/components/MyField.jsx
export default function MyField({ label, value, onChange, ...props }) {
    return (
        <div className="hyperpress-field-myfield">
            <label>{label}</label>
            <input value={value} onChange={(e) => onChange(e.target.value)} />
        </div>
    );
}
```

2. Register in ReactFieldsApp:
```jsx
import MyField from './components/MyField';

const fieldComponents = {
    // ...existing components
    myfield: MyField,
};
```

3. Add mapping in ReactField::getReactComponent() if needed

## Support

- **Issues**: https://github.com/EstebanForge/HyperFields/issues
- **Docs**: https://github.com/EstebanForge/HyperFields/wiki
- **Discussions**: https://github.com/EstebanForge/HyperFields/discussions

## License

GPL-2.0-or-later
