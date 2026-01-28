# Configuration Interface Design

## Overview

The configuration interface for HyperBlocks provides a centralized way to manage plugin settings, block discovery paths, and runtime behavior. This design follows the pattern established in HyperFields and HyperPress while providing block-specific configuration.

## Configuration Interface

### Core Configuration Class

```php
namespace HyperBlocks;

class Config
{
    /**
     * Default configuration values
     */
    private const DEFAULTS = [
        // Block discovery paths
        'block_paths' => [],

        // Template extensions
        'template_extensions' => '.hb.php,.php',

        // Auto-discovery enabled
        'auto_discovery' => true,

        // Debug mode
        'debug' => false,

        // Cache rendered blocks
        'cache_blocks' => true,

        // REST API namespace
        'rest_namespace' => 'hyperblocks/v1',

        // Editor script handle
        'editor_script_handle' => 'hyperblocks-editor',
    ];

    /**
     * Get a configuration value
     */
    public static function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value
     */
    public static function set(string $key, mixed $value): void;

    /**
     * Get all configuration
     */
    public static function all(): array;

    /**
     * Register a block discovery path
     */
    public static function registerBlockPath(string $path): void;

    /**
     * Get all registered block discovery paths
     */
    public static function getBlockPaths(): array;
}
```

## Configuration Sources (Priority Order)

1. **Programmatic Configuration** (Highest Priority)
   - `Config::set()` calls
   - Filters: `hyperblocks/config/override`

2. **Database Options** (Medium Priority)
   - Option key: `hyperblocks_options`
   - Can be set via options page or API

3. **Default Configuration** (Lowest Priority)
   - `Config::DEFAULTS` constant

## Configuration API

### Accessing Configuration

```php
use HyperBlocks\Config;

// Get a single value
$templateExtensions = Config::get('template_extensions');

// Get with default
$debug = Config::get('debug', false);

// Get all configuration
$allConfig = Config::all();
```

### Setting Configuration

```php
use HyperBlocks\Config;

// Set a value
Config::set('debug', true);

// Register a custom block path
Config::registerBlockPath(get_stylesheet_directory() . '/blocks');
Config::registerBlockPath(plugin_dir_path(__FILE__) . 'custom-blocks');
```

### Filter-Based Configuration

```php
// Override configuration via filter
add_filter('hyperblocks/config/override', function(array $config): array {
    $config['block_paths'][] = WP_CONTENT_DIR . '/shared-blocks';
    $config['template_extensions'] = '.hb.php,.php,.html';
    return $config;
});
```

## Configuration Options

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `block_paths` | `array` | `[]` | Paths to scan for block files |
| `template_extensions` | `string` | `.hb.php,.php` | Comma-separated list of template extensions |
| `auto_discovery` | `bool` | `true` | Whether to auto-discover blocks |
| `debug` | `bool` | `false` | Enable debug mode |
| `cache_blocks` | `bool` | `true` | Cache rendered blocks |
| `rest_namespace` | `string` | `hyperblocks/v1` | REST API namespace |
| `editor_script_handle` | `string` | `hyperblocks-editor` | Editor script handle |

## Block Discovery

### Auto-Discovery Mechanism

HyperBlocks discovers blocks from configured paths using two methods:

1. **Fluent Block Files**: Files matching `**/*.hb.php` (or other configured extensions)
2. **JSON Block Directories**: Directories containing `block.json`

```php
// Default discovery paths (automatically registered)
- {plugin_path}/blocks/
- {theme_path}/blocks/

// Custom paths registered via:
Config::registerBlockPath('/custom/path');
```

### Discovery Filters

```php
// Add custom block discovery paths
add_filter('hyperblocks/blocks/register_paths', function(array $paths): array {
    $paths[] = WP_CONTENT_DIR . '/shared-blocks';
    return $paths;
});

// Add individual block files
add_filter('hyperblocks/blocks/register_files', function(array $files): array {
    $files[] = '/path/to/custom-block.hb.php';
    return $files;
});
```

## Runtime Configuration

### Configuration Loading Sequence

1. Load default configuration
2. Apply `hyperblocks/config/defaults` filter
3. Load from database (if exists)
4. Apply programmatic overrides
5. Apply `hyperblocks/config/override` filter

### Configuration Persistence

```php
// Save configuration to database
add_action('init', function() {
    $config = [
        'debug' => true,
        'cache_blocks' => false,
    ];
    update_option('hyperblocks_options', $config);
});
```

## Debug Configuration

```php
// Enable debug mode
Config::set('debug', true);

// Debug information is available via:
// - WP Debug log
// - REST API endpoint: /wp-json/hyperblocks/v1/debug
// - Admin bar (when debug enabled)
```

## Validation

Configuration values are validated:

- `block_paths`: Must be valid directories
- `template_extensions`: Must be comma-separated list of extensions
- `rest_namespace`: Must be valid REST API namespace format
- `cache_blocks`: Must be boolean

Invalid values trigger deprecation warnings and fall back to defaults.
