# Changelog

## [1.0.3] - 2024-12-07

### Fixed
- Fixed test environment compatibility by allowing `HYPERFIELDS_TESTING_MODE` constant to bypass ABSPATH checks
- Updated `bootstrap.php` to conditionally return instead of exit when ABSPATH is not defined in test mode
- Updated `includes/helpers.php` to support test environment execution
- Updated `includes/backward-compatibility.php` to support test environment execution
- Fixed PHPUnit bootstrap configuration to properly initialize Brain\Monkey mocks

### Changed
- Test bootstrap now defines critical WordPress functions before autoloader to prevent conflicts
- Improved test infrastructure for downstream packages that depend on HyperFields via Composer

## [1.0.2] - 2024-12-07

### Added
- Complete custom field system for WordPress
- Post meta, term meta, and user meta container support
- Options pages with sections and tabs
- Conditional logic for dynamic field visibility
- Field types: text, textarea, email, URL, number, select, checkbox, radio, date, color, image, file, wysiwyg, code editor
- Repeater fields for creating dynamic field groups
- Template loader with automatic asset enqueuing
- Block field adapter for Gutenberg integration
- Backward compatibility layer for legacy class names
- PSR-4 autoloading with Composer
- Comprehensive field validation and sanitization
- Admin assets manager for styles and scripts
- Helper functions for field value retrieval

### Features
- **Metaboxes**: Add custom fields to posts, pages, and custom post types
- **Options Pages**: Create settings pages with organized sections and tabs
- **Conditional Logic**: Show/hide fields based on other field values
- **Flexible Containers**: Support for post meta, term meta, user meta, and options
- **Rich Field Types**: Extensive collection of field types for any use case
- **Developer-Friendly**: Clean API with extensive hooks and filters
- **Performance**: Optimized autoloading and asset management
- **Extensible**: Easy to extend with custom field types and containers

### Requirements
- PHP 8.1 or higher
- WordPress 5.0 or higher
- Composer for dependency management

