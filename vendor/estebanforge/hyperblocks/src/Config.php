<?php

declare(strict_types=1);

/**
 * Configuration management for HyperBlocks.
 */

namespace HyperBlocks;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages configuration settings for HyperBlocks.
 */
class Config
{
    /**
     * Default configuration values.
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
     * Runtime configuration storage.
     *
     * @var array
     */
    private static array $config = [];

    /**
     * Whether configuration has been loaded.
     *
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Initialize configuration by loading from database and applying overrides.
     *
     * @return void
     */
    public static function init(): void
    {
        if (self::$loaded) {
            return;
        }

        // Load defaults
        self::$config = self::DEFAULTS;

        // Allow filtering of defaults
        self::$config = apply_filters('hyperblocks/config/defaults', self::$config);

        // Load from database
        $dbConfig = get_option('hyperblocks_options', []);
        if (is_array($dbConfig) && !empty($dbConfig)) {
            self::$config = array_merge(self::$config, $dbConfig);
        }

        // Apply override filter (highest priority)
        self::$config = apply_filters('hyperblocks/config/override', self::$config);

        self::$loaded = true;
    }

    /**
     * Get a configuration value.
     *
     * @param string $key     The configuration key.
     * @param mixed  $default The default value if not found.
     * @return mixed The configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::init();
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Set a configuration value at runtime.
     *
     * @param string $key   The configuration key.
     * @param mixed  $value The value to set.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$loaded) {
            self::init();
        }

        self::$config[$key] = $value;
    }

    /**
     * Get all configuration.
     *
     * @return array All configuration values.
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::init();
        }

        return self::$config;
    }

    /**
     * Register a block discovery path.
     *
     * @param string $path The path to add.
     * @return void
     */
    public static function registerBlockPath(string $path): void
    {
        if (!self::$loaded) {
            self::init();
        }

        if (!is_dir($path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HyperBlocks: Cannot register block path, directory not found: {$path}");
            }
            return;
        }

        // Add to block paths array if not already present
        $blockPaths = self::$config['block_paths'] ?? [];
        if (!in_array($path, $blockPaths, true)) {
            $blockPaths[] = $path;
            self::$config['block_paths'] = $blockPaths;
        }
    }

    /**
     * Get all registered block discovery paths.
     *
     * @return array Array of paths.
     */
    public static function getBlockPaths(): array
    {
        if (!self::$loaded) {
            self::init();
        }

        return self::$config['block_paths'] ?? [];
    }

    /**
     * Get template extensions.
     *
     * @return array Array of extensions.
     */
    public static function getTemplateExtensions(): array
    {
        if (!self::$loaded) {
            self::init();
        }

        $extensions = self::$config['template_extensions'] ?? '.hb.php,.php';
        return array_map('trim', explode(',', $extensions));
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public static function isDebug(): bool
    {
        return (bool) self::get('debug', false);
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    public static function isCacheEnabled(): bool
    {
        return (bool) self::get('cache_blocks', true);
    }

    /**
     * Get REST API namespace.
     *
     * @return string
     */
    public static function getRestNamespace(): string
    {
        return (string) self::get('rest_namespace', 'hyperblocks/v1');
    }

    /**
     * Get editor script handle.
     *
     * @return string
     */
    public static function getEditorScriptHandle(): string
    {
        return (string) self::get('editor_script_handle', 'hyperblocks-editor');
    }

    /**
     * Reset configuration to defaults (useful for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$config = self::DEFAULTS;
        self::$loaded = true;
    }

    /**
     * Set configuration array directly (useful for testing).
     *
     * @param array $config The configuration to set.
     * @return void
     */
    public static function setAll(array $config): void
    {
        self::$config = array_merge(self::DEFAULTS, $config);
        self::$loaded = true;
    }

    /**
     * Validate configuration values.
     *
     * @param array $config The configuration to validate.
     * @return bool True if valid.
     */
    public static function validate(array $config): bool
    {
        // Validate block_paths
        if (isset($config['block_paths'])) {
            if (!is_array($config['block_paths'])) {
                return false;
            }
            foreach ($config['block_paths'] as $path) {
                if (!is_string($path) || !is_dir($path)) {
                    return false;
                }
            }
        }

        // Validate template_extensions
        if (isset($config['template_extensions'])) {
            if (!is_string($config['template_extensions'])) {
                return false;
            }
            $extensions = array_map('trim', explode(',', $config['template_extensions']));
            foreach ($extensions as $ext) {
                if (!str_starts_with($ext, '.')) {
                    return false;
                }
            }
        }

        // Validate rest_namespace
        if (isset($config['rest_namespace'])) {
            if (!is_string($config['rest_namespace']) || !preg_match('/^[a-z0-9-]+\/v[0-9]+$/', $config['rest_namespace'])) {
                return false;
            }
        }

        // Validate boolean flags
        $booleanKeys = ['auto_discovery', 'debug', 'cache_blocks'];
        foreach ($booleanKeys as $key) {
            if (isset($config[$key]) && !is_bool($config[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save configuration to database.
     *
     * @param array $config The configuration to save.
     * @return bool True if saved successfully.
     */
    public static function save(array $config): bool
    {
        if (!self::validate($config)) {
            return false;
        }

        return update_option('hyperblocks_options', $config);
    }
}
