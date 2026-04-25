# Changelog

## [1.2.2] - 2026-04-25

### Fixed
- **Multiselect Form Submission** - Added hidden select element for proper form data submission
  - Added `hf-multiselect-hidden` class to hidden select element in multiselect template
  - Updated JavaScript to prioritize finding hidden select by class before falling back to name-based search
  - Ensures proper value submission when multiselect field is used in forms

### Changed
- Improved multiselect field JavaScript selector logic for more robust element detection
- Enhanced multiselect template with dedicated hidden select for form handling

## [1.2.0] - 2026-04-12

### Added
- **React Integration** - Modern UI components for options pages with PHP-only API
  - `ReactField` class extends `Field` with React rendering capabilities
  - Automatic React asset loading when `ReactField` instances are detected
  - Supports 10 field types: text, textarea, number, email, url, color, image, checkbox, select
  - Uses WordPress `@wordpress/components` for consistent admin UI
  - Media library integration for image fields with live preview
  - WordPress color picker with alpha channel support
  - Progressive enhancement - HTML works, React is opt-in
  - Zero breaking changes - existing code continues to work unchanged

- **ReactField API**:
  - `ReactField::make()` - Create React-enhanced fields
  - `setReactProp()` - Pass custom props to React components
  - `setReactComponent()` - Override default React component
  - `setUseReact()` - Enable/disable React per field
  - `getReactComponent()` - Get component name for field type
  - `getReactProps()` - Get all props for React component

- **Build System**:
  - Webpack 5 configuration for React asset compilation
  - Babel preset for JSX transformation
  - Build commands: `build`, `build:dev`, `watch`, `clean`
  - Optimized production builds with source maps
  - WordPress externals (wp-element, wp-components, wp-block-editor)

- **React Components** (`assets/js/src/components/`):
  - `TextField.jsx` - Modern text input with validation
  - `TextareaField.jsx` - Multi-line text with rows config
  - `NumberField.jsx` - Number input with min/max/step
  - `EmailField.jsx` - Email validation
  - `UrlField.jsx` - URL input validation
  - `ColorField.jsx` - WordPress color picker
  - `ImageField.jsx` - Media library integration with thumbnail preview
  - `CheckboxField.jsx` - Toggle checkbox
  - `SelectField.jsx` - Dropdown select

- **Enhanced CSS**:
  - Modern WooCommerce-inspired design system
  - CSS variables for theming (--hf-color-primary, etc.)
  - Card layout with better spacing and typography
  - Improved tabs and field rows
  - Responsive design with mobile-first approach
  - Dark mode support via `@media (prefers-color-scheme: dark)`
  - React-specific styles in `assets/css/react-fields.css`

- **OptionsPage Integration**:
  - Auto-detection of `ReactField` instances
  - Automatic enqueuing of wp-element, wp-components, wp-block-editor
  - Data bridge via `wp_localize_script()` to `window.hyperfieldsReactData`
  - React root container (`#hyperpress-react-root`) for component mounting
  - Hidden input synchronization for form submission

- **Developer Experience**:
  - One-line change from `Field::make()` to `ReactField::make()`
  - No React knowledge required
  - PHP-only API maintained
  - Optional custom React props per field

- **Documentation**:
  - `REACT_EXAMPLES.md` - Complete developer guide
  - `IMPLEMENTATION_SUMMARY.md` - Implementation details
  - `VERSION_BUMP_GUIDE.md` - Version management guide
  - `examples/react-test.php` - Working test file

- **Build Automation**:
  - `composer build-assets` script for standalone asset building
  - `composer production` now automatically builds React assets
  - Graceful fallback when npm is not available
  - Cross-platform support (Linux, macOS, Windows)

- **Version Management**:
  - Updated `scripts/version-bump.sh` to handle all version locations
  - Now updates: composer.json, package.json, bootstrap.php, src/OptionsPage.php
  - Synchronizes fallback versions across all files

### Changed
- **CSS Refresh** - Modernized `assets/css/hyperfields-admin.css` with:
  - CSS variable-based theming system
  - Card layout replacing dated field styling
  - Better spacing, typography, and visual hierarchy
  - Improved tabs with active state styling
  - Responsive design improvements
  - Dark mode compatibility

- **OptionsPage Enhancements**:
  - Added `reactFieldsToRender` property for React field tracking
  - `getReactFields()` method to collect React fields from sections
  - `hasReactFields()` method to check for React presence
  - `enqueueReactAssets()` method for React dependency loading
  - Modified `renderPage()` to include React root container

- **Build System**:
  - Added `package.json` with React dependencies
  - Added `webpack.config.js` for asset compilation
  - Updated `composer.json` scripts to integrate React builds

- **Composer Scripts**:
  - Added `build-assets` script
  - Updated `production` script to run asset builds automatically

### Fixed
- **ImageField Component**:
  - Added null check for `wp.media` object
  - Improved error handling for media attachment loading
  - Fallback to number input when MediaUpload unavailable

- **Build System**:
  - Fixed webpack externals configuration for WordPress dependencies
  - Added `@wordpress/block-editor` to dependencies
  - Resolved module resolution issues

### Performance
- React assets are minified in production builds
- Source maps generated for development debugging
- Lazy loading of React components only when needed

### Developer Experience
- Zero breaking changes - existing fields work unchanged
- Opt-in React rendering - choose which fields benefit from modern UI
- Mixed rendering - use React for complex fields, HTML for simple ones
- Full backward compatibility maintained

## [1.1.9] - 2026-04-01

### Added
- Transfer audit logging runtime with dedicated classes:
  - `Transfer\AuditLogger` hook subscriber for options/content/manager transfer flows
  - `Transfer\AuditLogStorage` dedicated DB storage (`{wp_prefix}hyperfields_transfer_logs`) with lazy schema setup and pruning
  - `Transfer\AuditContext` request-scoped manager-depth guard to avoid nested duplicate logs
- Built-in transfer logs admin screen via `Admin\TransferLogsUI`, integrated into `ExportImportUI` with a contextual `View transfer logs` link.
- `ExportImportUI` extensibility upgrades:
  - `renderConfigured(ExportImportPageConfig $config)` entrypoint
  - custom `exporter`, `previewer`, and `importer` callables
  - optional `exportFormExtras` HTML injection point in export form
  - option group labels support in `render(..., array $optionGroups = [])`
- Transfer Manager envelope customization via `Transfer\SchemaConfig` and `Manager::withSchema(...)`.
- Automatic transfer-audit hook initialization in `LibraryBootstrap::init()`.
- Typed-node schema validation for option import/export via `Validation\SchemaValidator`.
- `ContentExportImport` API for post/content JSON export/import/diff flows.
- `Transfer\Manager` pluggable module registry for export/import/diff orchestration.
- New facade and helper methods for content transfer:
  - `HyperFields::exportPosts()`, `snapshotPosts()`, `importPosts()`, `diffPosts()`, `makeTransferManager()`
  - `hf_export_posts()`, `hf_snapshot_posts()`, `hf_import_posts()`, `hf_diff_posts()`
- Strategy support expansion (`__strategy`) for transfer payload behavior control.
- Expanded transfer/bootstrap docs:
  - `docs/transfer-and-content-export-import.md`
  - `docs/library-bootstrap.md`

### Changed
- Documentation refreshed for transfer logging, Transfer Manager schema configuration, and `ExportImportUI` extension points.
- `ExportImport` updated to align with transfer-manager, typed-node, and schema-aware flows.
- `ExportImportUI` overhauled with richer export selection/filter UX and improved diff/import experience.
- Export options filter layout and admin styling/scripts refactored (`assets/js/hyperfields-admin.js`, `assets/css/hyperfields-admin.css`).
- Core docs updated with content transfer and extensible manager guidance.
- Composer/library metadata and README refreshed for library-first usage.
- Packaging cleanup for library distribution:
  - adjusted bootstrap/composer metadata
  - stopped shipping committed Composer vendor autoload artifacts
- Repository ignore rules updated for generated/vendor files.
- Minimum supported PHP version raised to `8.2`.

### Fixed
- Data Tools UI regressions in export/import rendering and request handling.
- Post-overhaul code quality and test alignment fixes in `ExportImportUI`.
- Release metadata/version bump consistency updates.

## [1.1.0] - 2026-03-28

### Added
- `ExportImport` class: export WordPress option groups to JSON and import with prefix filtering, whitelist enforcement, automatic backup transients, and additive merge
- `ExportImportUI` class: admin submenu page for visual Export / Import with jsondiffpatch diff preview
- `ExportImportUI::registerPage()` — single-call public API for third-party plugins to register the Data Tools page; hooks asset enqueueing to `admin_enqueue_scripts` automatically
- `ExportImportUI::enqueuePageAssets()` — public method to enqueue HyperFields admin CSS + jsondiffpatch diff assets
- `HyperFields::registerDataToolsPage()` — facade entry point for `ExportImportUI::registerPage()`
- `hf_register_data_tools_page()` helper function — procedural wrapper for registering the Data Tools page
- `hf_export_options()` helper function — procedural wrapper for `ExportImport::exportOptions()`
- `hf_import_options()` helper function — procedural wrapper for `ExportImport::importOptions()`
- Export/Import UI styled with HyperFields admin CSS classes (`hyperpress-options-wrap`, `hyperpress-fields-group`, `hyperpress-field-wrapper`, etc.) for visual consistency with existing options pages
- Full i18n coverage: all user-visible strings in `ExportImportUI` wrapped with `__()` using `hyperfields` text domain
- `OptionsPage::addTab()` and `OptionsPage::addSectionToTab()` for explicit tab-to-section composition
- Section metadata support in `OptionsSection` (`slug`, `as_link`, `allow_html_description`) for section-link navigation and HTML-safe section descriptions
- WPSettings compatibility mapping for `menu_icon` and `menu_position` to options page menu metadata
- WPSettings compatibility support for `visible` callbacks to conditionally skip field registration/rendering
- WPSettings compatibility implementation for `code-editor` / `code_editor` using WordPress code editor assets and initialization (`wp_enqueue_code_editor`)
- WPSettings compatibility type mapping for `wp-editor` / `wp_editor` to HyperFields `rich_text`
- WPSettings compatibility type mapping for `media` / `video` to HyperFields `image`
- WPSettings compatibility support for tab/section `option_level` nesting with option-path read/write handling
- WPSettings compatibility support for `validate` and callable `sanitize` option args in the options-page save pipeline
- WPSettings compatibility support for `wp_settings_option_type_map` custom type bridge into HyperFields custom fields
- Field template/render arg parity extended for `input_type`, `attributes`, `rows`, `cols`, `editor_settings`, and inline `error` output
- Field template arg parity via `Field::toArray()` for `input_class`, `label_class`, and `help_is_html`

### Fixed
- `TypeError` in prefix filter arrow functions when option arrays have integer keys — keys now explicitly cast to string before `strpos`
- `importOptions()` returned `success: true` when whitelist or prefix filtering blocked every incoming entry — now returns `success: false` with a descriptive message
- `restoreBackup()` did not delete the backup transient when `update_option` returned `false` because the stored value was identical to the current value — unchanged-value case now correctly detected and transient cleaned up
- XSS via `</script>` injection in diff preview data island — `wp_json_encode` now uses `JSON_HEX_TAG | JSON_HEX_AMP` flags
- File upload handler did not check `$_FILES['import_file']['error'] !== UPLOAD_ERR_OK` before calling `is_uploaded_file`, allowing error-state uploads to proceed
- Non-array option values silently coerced to `[]` during export — they are now skipped entirely, matching the additive-import contract
- `wp_json_encode` returning `false` on unencodable data caused `exportOptions` to return an empty string — fallback is now `'{}'`
- Asset enqueueing (`wp_enqueue_style` / `wp_enqueue_script`) was called inside an `ob_start()` output buffer in `render()`, which fires too late for WordPress header output — moved to `admin_enqueue_scripts` hook
- Options-page registration and sanitization for compatibility tabs with multiple sections now process the active renderable section set rather than assuming section ID equals tab ID
- Options section slug generation now falls back safely when WordPress `sanitize_title()` is unavailable (test/library context)
- Field templates (`input`, `checkbox`, `radio`, `multiselect`, `custom`) now correctly honor `input_class` / `label_class` and support HTML help rendering with `help_is_html`
- Custom field fallback markup now uses `name_attr` to preserve correct options-array field naming
- Compatibility field save flow now supports nested `option_path` values and inline per-field validation feedback without breaking standard options-page behavior

### Changed
- Updated `docs/hyperfields.md` with the authoritative field type matrix (core field types + compatibility aliases) and current compatibility parity behavior details

## [1.0.3] - 2026-01-08

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
