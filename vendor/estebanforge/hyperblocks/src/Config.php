<?php

declare(strict_types=1);

/**
 * Configuration management for HyperBlocks.
 */

namespace HyperBlocks;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
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
        // Block discovery paths. Scanned for block definitions AND used for
        // template validation. Populated by registerBlockPath().
        'block_paths' => [],

        // Template-only paths. Used ONLY for template validation
        // (setRenderTemplateFile / Renderer). Never scanned for block
        // definitions. Populated by registerTemplatePath() or
        // registerBlockPath($path, ['discover' => false]).
        'template_paths' => [],

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
     * By default the path is both scanned for block definitions
     * (discovery) and added to the template-validation allowlist. Pass
     * ['discover' => false] to register a path for template validation
     * only, so it is never globbed by Registry::discoverAndLoadFluentBlocks().
     * This avoids fatals when a directory holds render templates that are
     * not safe to execute as top-level block definitions.
     *
     * A discovery-disabled registration is equivalent to
     * registerTemplatePath() and is stored in the same set.
     *
     * @param string $path    The path to add.
     * @param array  $options {
     *     Optional. 'discover' (bool, default true): when false, the path is
     *     registered for template validation only and excluded from
     *     auto-discovery.
     * }
     * @return void
     */
    public static function registerBlockPath(string $path, array $options = []): void
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

        $discover = $options['discover'] ?? true;
        $key = $discover ? 'block_paths' : 'template_paths';

        // Normalize: strip trailing slashes so /foo/bar and /foo/bar/ dedup
        // as the same path (array_unique is string-only and would otherwise
        // keep both, inflating the allowlist).
        $path = rtrim($path, '/\\');

        // Add to the target path array if not already present
        $paths = self::$config[$key] ?? [];
        if (!in_array($path, $paths, true)) {
            $paths[] = $path;
            self::$config[$key] = $paths;
        }
    }

    /**
     * Register a template-only path.
     *
     * The path is added to the template-validation allowlist but is never
     * scanned for block definitions. Use this when a directory holds render
     * templates that must resolve via Block::setRenderTemplateFile() but
     * must not be auto-executed as block definitions on init.
     *
     * Equivalent to registerBlockPath($path, ['discover' => false]).
     *
     * @param string $path The path to add.
     * @return void
     */
    public static function registerTemplatePath(string $path): void
    {
        self::registerBlockPath($path, ['discover' => false]);
    }

    /**
     * Get all registered block discovery paths.
     *
     * These paths are scanned for block definitions and also used for
     * template validation.
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
     * Get all registered template-only paths.
     *
     * These paths are used for template validation only and are never
     * scanned for block definitions.
     *
     * @return array Array of paths.
     */
    public static function getTemplatePaths(): array
    {
        if (!self::$loaded) {
            self::init();
        }

        return self::$config['template_paths'] ?? [];
    }

    /**
     * Get every path allowed for template validation.
     *
     * Returns the union of discovery paths (block_paths) and template-only
     * paths (template_paths). Block::validateTemplatePath() and
     * Renderer::validateTemplatePath() resolve templates against this set.
     *
     * @return array Array of paths (deduplicated).
     */
    public static function getTemplateValidationPaths(): array
    {
        if (!self::$loaded) {
            self::init();
        }

        $paths = array_merge(
            self::$config['block_paths'] ?? [],
            self::$config['template_paths'] ?? []
        );

        return array_values(array_unique($paths));
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
}
