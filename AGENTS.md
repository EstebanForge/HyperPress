## Project Overview

HyperPress (`api-for-htmx`) is a thin WordPress plugin adapter. It bootstraps Composer libraries and delegates runtime behavior to `HyperPress-Core`.

**Architecture**: The adapter plugin depends on `estebanforge/hyperpress-core`, which in turn depends on `estebanforge/hyperfields` and `estebanforge/hyperblocks`.

## Common Development Commands

### Environment Management (Docker)
```bash
./setup              # First-time interactive setup with dependency checking
./start              # Start Docker environment with SELinux/permission handling
./stop               # Stop environment
./wp <command>       # WP-CLI integration (e.g., ./wp plugin list)
docker-compose logs -f  # View container logs
```

### Plugin Development

#### HyperPress (api-for-htmx)
```bash
cd src/app/plugins/api-for-htmx
npm run update-all       # Download all hypermedia libraries
npm run build           # Build JS assets with WordPress scripts
npm run update-htmx     # Update specific library
composer update          # Update Composer dependencies (including HyperFields)
```

#### HyperFields (dependency)
```bash
cd src/app/plugins/HyperFields
# HyperFields development (decoupled plugin/library)
# This is a separate plugin that HyperPress depends on via Composer
```

### Code Quality & Testing
```bash
composer run cs:fix      # Auto-fix coding standards with php-cs-fixer
composer run production  # Prepare for production (cs-fix + optimized autoload)
composer run test:setup  # Setup SQLite test database
composer dump-autoload --optimize  # Regenerate optimized autoloader
```

## Architecture & Key Components

### Core Systems

**HyperFields**: Decoupled custom field system available as a separate plugin/library (located at `src/app/plugins/HyperFields/` in development, or via Composer package `estebanforge/hyperfields`). HyperPress requires this via Composer. Provides field types for posts, terms, users, and options pages with sanitization and validation. HyperPress uses `HyperFields\HyperFields` for options pages, `HyperFields\BlockFieldAdapter` for block integration, and `HyperFields\Field` for field definitions.

**HyperBlocks**: PHP-based Gutenberg block creation system in `src/Blocks/`. Allows creating dynamic blocks using PHP templates instead of React, with support for RichText and InnerBlocks.

**Hypermedia Integration**: HTMX, Alpine AJAX, Datastar support via custom REST endpoint `/wp-html/v1/`. Templates are served from theme's `hypermedia/` directory or custom registered paths.

### Directory Structure
- `src/` - Core PHP classes (PSR-4 autoloaded as `HyperPress\`)
  - `Admin/` - Admin interface, options pages, activation
  - `Assets.php` - Asset management and enqueueing
  - `Blocks/` - HyperBlocks system for PHP-based Gutenberg blocks
  - `Libraries/` - Hypermedia library integrations
  - `Main.php` - Plugin coordinator class
- `hyperblocks/` - Example blocks (both fluent API and block.json approaches)
- `hypermedia/` - Template examples for hypermedia endpoints
- `assets/` - Compiled JS/CSS and library files
- `tests/` - PHPUnit tests (unit and integration)

**Note**: HyperFields functionality is now provided by the separate HyperFields plugin/library via Composer dependency (`estebanforge/hyperfields`).

### Key Development Patterns

#### Working with HyperFields
HyperFields is now a decoupled plugin/library that HyperPress requires via Composer (`estebanforge/hyperfields`). HyperPress does NOT use `HyperFields\TemplateLoader` (which is internal to HyperFields for rendering field UI). Instead, HyperPress uses:
- `HyperFields\HyperFields::registerOptionsPage()` and `HyperFields\HyperFields::getOptions()` for admin options
- `HyperFields\BlockFieldAdapter` for Gutenberg block field integration
- `HyperFields\Field` as a field definition wrapper

Fields are defined in the HyperFields plugin and can be used independently or through HyperPress integration.

#### Creating HyperBlocks
Blocks can be created using either:
1. **Fluent API**: Programmatic approach using `HyperBlock` class
2. **block.json**: Standard WordPress approach with PHP render templates

#### Hypermedia Endpoints
Templates are served from:
- Theme's `hypermedia/` directory (no namespace needed)
- Custom registered paths using `hyperpress/render/register_template_path` filter

### Library Management
Hypermedia libraries are managed via npm scripts and copied to `assets/libs/`. Use npm commands for updates, not manual downloads.

### Configuration
The plugin can be configured either through admin interface or programmatically using filters. When used as a Composer library, it automatically runs in library mode without admin interface.

## Important Notes

- This plugin serves as both a standalone plugin and a Composer library
- **Dependencies**: HyperPress requires HyperFields via Composer (`estebanforge/hyperfields`)
- All assets can be overridden via filters for custom CDN/local paths
- **Autoloader**: Uses optimized Composer autoloader (no vendor-prefixing)
- **Template Extensions**: `.hp.php`, `.hm.php`, `.hb.php` for hypermedia templates (legacy: `.htmx.php`)
- The plugin provides extensive filters for customization without modifying core files
- When used as a Composer library, admin interface is automatically hidden (library mode)

## Project Overview

**HyperPress** is a WordPress plugin that integrates hypermedia libraries (HTMX, Alpine AJAX, Datastar) with WordPress, enabling modern interactive web development using primarily PHP instead of JavaScript frameworks. It provides:

- **Custom REST API**: `/wp-html/v1/` endpoint for serving hypermedia template partials
- **HyperBlocks**: PHP-based Gutenberg block system (no React required)
- **HyperFields Integration**: Uses the decoupled HyperFields plugin/library for custom fields, options pages, and block metadata

**Key Philosophy**: Eliminate JavaScript build complexity while maintaining modern UI/UX. Build dynamic, interactive experiences with PHP and hypermedia attributes instead of complex frontend frameworks.

## Development Commands

### Testing
```bash
composer run test              # Run all tests with coverage (pcov)
composer run test:unit         # Unit tests only
composer run test:integration  # Integration tests only
composer run test:feature      # Feature tests only
composer run test:coverage     # Full coverage report (HTML + text)
composer run test:fast         # Quick coverage check
composer run test:summary      # Coverage summary only
composer run test:clover       # Generate clover.xml for CI
```

Run individual test file:
```bash
php -d pcov.enabled=1 vendor/bin/phpunit tests/Unit/SpecificTest.php
```

### Code Quality
```bash
composer run cs:fix            # Auto-fix code style with PHP-CS-Fixer
composer run production        # Prepare for production (cs:fix + optimized autoload)
composer dump-autoload --optimize  # Regenerate optimized autoloader
```

Standards: PSR-12, PER-CS, PHP 8.2+ migration rules

### Asset Management
```bash
npm run update-all             # Download/update all hypermedia libraries
npm run update-htmx            # Update HTMX only
npm run update-htmx-extensions # Update HTMX extensions
npm run update-hyperscript     # Update Hyperscript
npm run update-alpinejs        # Update Alpine.js
npm run update-alpine-ajax     # Update Alpine AJAX
npm run update-datastar        # Update Datastar
npm run build                  # Build JS assets (editor, admin, field scripts)
```

Libraries are downloaded via PHP script (`.ci/update-libraries.php`) and copied to `assets/lib/`. Never manually download libraries.

### Deployment
```bash
./deploy.sh                    # Deploy to WordPress.org (requires SVN credentials)
./deploy-readme-only.sh        # Update README only on WordPress.org
```

## Architecture

### Core Components

**Main.php** (`HyperPress\Main`): Central coordinator that initializes all components via dependency injection:
- `Router`: Handles `/wp-html/v1/` REST endpoints
- `Render`: Template loading and processing
- `Assets`: Script/style enqueueing with library detection
- `Config`: Meta tag and configuration management
- `Compatibility`: Plugin conflict handling
- `Theme`: Theme integration support
- `Options`: Admin settings (uses HyperFields)

**Bootstrap System** (`bootstrap.php`): Multi-instance version resolution. Supports both plugin and library modes. Ensures only the latest version loads when multiple instances exist (e.g., theme dependency + plugin).

**Block System** (`src/Blocks/`):
- `Registry`: Auto-discovers and registers blocks
- `Block`: Fluent API for programmatic block creation
- `Renderer`: Server-side rendering with RichText/InnerBlocks support
- `RestApi`: Block preview endpoint for editor
- `Field`, `FieldGroup`: Block metadata fields (uses HyperFields)

**Library Integration** (`src/Libraries/`):
- `HTMXLib`, `AlpineAjaxLib`, `DatastarLib`: Library-specific integrations
- Each provides enqueue methods, version detection, and configuration

### Directory Structure

```
src/                      # PSR-4: HyperPress\
  Admin/                  # Activation, Options, Migration
  Blocks/                 # HyperBlocks system
  Libraries/              # HTMX, Alpine, Datastar integrations
  Assets.php              # Asset management
  Config.php              # Meta tags, configuration
  Render.php              # Template rendering
  Router.php              # REST endpoint routing
  Theme.php               # Theme support
  Main.php                # Plugin coordinator

hyperblocks/              # Example blocks
  fluent-demos/           # Fluent API examples
  content-card/           # block.json example
  hero-banner/            # block.json example
  quote-block/            # block.json example

hypermedia/               # Example templates
  *.hp.php                # HyperPress templates
  noswap/                 # No-swap response examples

tests/
  Unit/                   # Unit tests (Brain\Monkey)
  Integration/            # Integration tests
  Feature/                # Feature tests
  bootstrap.php           # Test bootstrap with WordPress mocks

docs/                     # Documentation
assets/                   # Compiled JS/CSS, library files
.ci/                      # CI scripts, version bumping
```

### Key Development Patterns

#### HyperBlocks: Two Approaches

**1. Fluent API** (Programmatic):
```php
use HyperPress\Blocks\Block;

Block::make('my-namespace/my-block')
    ->title('My Block')
    ->icon('admin-comments')
    ->category('widgets')
    ->keywords(['custom', 'dynamic'])
    ->template(__DIR__ . '/template.php')
    ->supports(['align' => true])
    ->register();
```

**2. block.json** (WordPress Standard):
- Create `block.json` in block directory
- Define render callback pointing to PHP template
- Use `BlockFieldAdapter` for inspector fields

Both approaches support HyperFields for block metadata via `BlockFieldAdapter`.

#### Hypermedia Templates

Templates use extensions: `.hp.php`, `.hm.php`, `.hb.php` (legacy: `.htmx.php`)

**Template Locations**:
1. Theme's `hypermedia/` directory (no namespace)
2. Custom paths via `hyperpress/render/register_template_path` filter

**Rendering**:
```php
// In template
use HyperPress\Render;

// From theme hypermedia/ directory
Render::load('my-template', ['data' => $value]);

// No-swap responses (status codes only)
use function HyperPress\hypermedia_no_swap;
hypermedia_no_swap(204);
```

**REST Endpoint**:
```
GET /wp-json/wp-html/v1/{template-name}
```

#### Working with HyperFields

HyperPress depends on `estebanforge/hyperfields` package. It uses:
- `HyperFields\HyperFields::registerOptionsPage()` - Admin options
- `HyperFields\HyperFields::getOptions()` - Retrieve options
- `HyperFields\BlockFieldAdapter` - Block inspector fields
- `HyperFields\Field` - Field definitions

**NOT used**: `HyperFields\TemplateLoader` (internal to HyperFields)

See HyperFields documentation for field types, validation, sanitization.

### Library Mode vs Plugin Mode

**Plugin Mode**: Standalone WordPress plugin with admin UI
**Library Mode**: Composer dependency in another project, admin UI hidden

Detection is automatic based on whether plugin file exists or loaded via Composer.

### Version Resolution

When multiple HyperPress instances exist (e.g., plugin + theme dependency), `bootstrap.php` registers all candidates and loads only the latest version via `hyperpress_select_and_load_latest()` at `after_setup_theme` priority 0.

## Important Patterns

### Asset Enqueueing
Assets use `Assets` class with automatic library detection. Frontend libraries can be:
- Loaded from plugin assets
- Overridden via filter for CDN
- Conditionally loaded based on page content scan

### Security
- All templates: `Render::sanitize_request_vars()` sanitizes query parameters
- Custom sanitization: `hyperpress/render/custom_sanitize` filter
- Capability checks: Use `hyperpress/render/capability` filter
- CSRF protection: Use WordPress nonces in forms

### Filters for Customization

```php
// Register custom template paths
add_filter('hyperpress/render/register_template_path', function($paths) {
    $paths['my-namespace'] = '/path/to/templates';
    return $paths;
});

// Override library URLs (CDN)
add_filter('hyperpress/assets/htmx_url', fn() => 'https://cdn.example.com/htmx.js');

// Customize library versions
add_filter('hyperpress/assets/htmx_version', fn() => '2.0.0');

// Disable admin interface (library mode)
add_filter('hyperpress/admin/show_menu', '__return_false');
```

See `docs/developer-configuration.md` for comprehensive filter reference.

## Testing Notes

- Tests use Brain\Monkey for WordPress function mocking
- SQLite integration for database tests (see `.ci/setup-sqlite-dropin.php`)
- Coverage requires pcov extension (faster than xdebug)
- Test database setup: `composer run test:setup`
- Bootstrap files: `tests/bootstrap.php` (full), `tests/bootstrap-minimal.php` (unit only)

## Documentation

Full documentation in `docs/`:
- `docs/index.md` - Documentation index
- `docs/hyperblocks.md` - Block development guide
- `docs/how-to-use.md` - Hypermedia templates guide
- `docs/helper-functions.md` - PHP helper functions
- `docs/datastar-helpers.md` - Datastar SSE helpers
- `docs/developer-configuration.md` - Filters and customization
- `docs/security.md` - Security practices

Example code:
- `hyperblocks/` - Block examples (both approaches)
- `hypermedia/` - Template examples for each library

## Common Tasks

**Add new hypermedia template**:
1. Create `hypermedia/my-template.hp.php` in theme
2. Use `Render::load()` or access via `/wp-json/wp-html/v1/my-template`

**Create new HyperBlock**:
1. Fluent: Add to `hyperblocks/fluent-demos/`, use `Block::make()`
2. Standard: Create directory with `block.json` and render callback

**Update library version**:
1. Run `npm run update-{library}`
2. Test compatibility
3. Update version in `composer.json` if tracking

**Add block field**:
Use `BlockFieldAdapter` with HyperFields field types (see HyperFields docs)

**Debug template rendering**:
Check `src/Render.php::load()` and `src/Router.php::handle_request()`
