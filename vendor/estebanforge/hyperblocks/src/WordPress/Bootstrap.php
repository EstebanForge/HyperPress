<?php

declare(strict_types=1);

/**
 * WordPress Bootstrap for HyperBlocks.
 *
 * This file handles WordPress-specific initialization and integration.
 */

namespace HyperBlocks\WordPress;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\RestApi;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Bootstrap class for WordPress integration.
 */
class Bootstrap
{
    /**
     * Initialize HyperBlocks in WordPress.
     *
     * @return void
     */
    public static function init(): void
    {
        // Cross-copy election guard (Carbon Fields pattern). The first copy
        // of HyperBlocks to reach init() claims a namespace-scoped constant
        // and wins; every later copy bails before bootstrapping, so two
        // plugins shipping HyperBlocks do not double-init (re-register hooks,
        // conflict on Config state) and do not fatal. Built with
        // __NAMESPACE__ so it is prefix-safe: unprefixed copies share
        // HyperBlocks\WordPress\LOADED and elect one winner, while a
        // namespace-prefixed copy lives under a different namespace
        // (different constant) and boots independently. First-to-boot wins,
        // not newest; prefix if you need version determinism across divergent
        // copies.
        if (defined(__NAMESPACE__ . '\\LOADED')) {
            return;
        }

        define(__NAMESPACE__ . '\\LOADED', __DIR__);

        if (Config::isInitialized()) {
            return;
        }

        // Runtime identity (prefix-safe). Mirrors HyperFields\Config: these
        // hold per-copy paths/URL so a prefixed copy
        // (ConsumerX\...\HyperBlocks\Config) is fully isolated from any other
        // consumer's copy. Replaces the former global HYPERBLOCKS_* constants.
        $base_dir = trailingslashit(dirname(__DIR__, 2));
        $plugin_file = $base_dir . 'bootstrap.php';
        $plugin_url = self::resolvePluginUrl($base_dir);

        Config::markInitialized();
        Config::$abspath = $base_dir;
        Config::$pluginFile = $plugin_file;
        Config::$pluginUrl = $plugin_url;

        // Load the procedural helper API. Loaded here (after ABSPATH is
        // available) rather than via Composer autoload.files, so the direct-
        // access guard in helpers.php does not bail when a consumer's
        // autoloader runs before WordPress loads. Mirrors HyperFields.
        $helpers = dirname(__DIR__) . '/helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }

        // Initialize configuration
        add_action('plugins_loaded', [self::class, 'initializeConfig'], 5);

        // Register blocks
        add_action('init', [self::class, 'registerBlocks'], 10);

        // Register REST API
        add_action('rest_api_init', [self::class, 'registerRestApi'], 10);

        // Enqueue editor assets
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditorAssets'], 10);

        // Register default block paths
        add_action('init', [self::class, 'registerDefaultPaths'], 5);
    }

    /**
     * Resolve the library root URL against the web-accessible WordPress
     * content roots. Delegates to the canonical HyperFields resolver
     * (HyperBlocks requires HyperFields) so a stack shipping both libraries
     * runs one resolver. Returns '' when the directory is not under any
     * web-accessible root (e.g. a Bedrock root composer vendor outside the
     * document root); callers that need the assets must then bail.
     *
     * @param string $base_dir Library root (with trailing slash).
     * @return string Trailing-slashed URL, or '' when not resolvable.
     */
    private static function resolvePluginUrl(string $base_dir): string
    {
        if (!class_exists(\HyperFields\LibraryBootstrap::class)) {
            return '';
        }

        $resolved = \HyperFields\LibraryBootstrap::resolveContentUrl(rtrim($base_dir, '/\\'));

        return $resolved !== '' ? trailingslashit($resolved) : '';
    }

    /**
     * Initialize configuration.
     *
     * @return void
     */
    public static function initializeConfig(): void
    {
        // Set default block path from the library root (Config::$abspath).
        if (Config::$abspath !== '' && is_dir(Config::$abspath . 'blocks')) {
            Config::registerBlockPath(Config::$abspath . 'blocks');
        }
    }

    /**
     * Register default block paths.
     *
     * Auto-registers the active theme's /blocks directories as discovery
     * paths. On by default for back-compat; gated by the
     * `hyperblocks/blocks/auto_discover_theme_blocks` filter so a consumer
     * whose theme uses /blocks for WP-native/ACF blocks (and who prefers an
     * explicit opt-out over the header-based file filter) can disable
     * auto-registration entirely with __return_false. The library's own
     * bundled blocks (Config::$abspath/blocks) are registered separately in
     * initializeConfig() and are NOT affected by this filter.
     *
     * @return void
     */
    public static function registerDefaultPaths(): void
    {
        // Defense-in-depth alongside the HyperBlocks Block header: a consumer
        // can opt out of theme /blocks auto-registration entirely. Default
        // true preserves the historical behavior.
        if (!apply_filters('hyperblocks/blocks/auto_discover_theme_blocks', true)) {
            return;
        }

        // Register theme blocks directory if it exists
        if (is_child_theme()) {
            $childBlocks = get_stylesheet_directory() . '/blocks';
            if (is_dir($childBlocks)) {
                Config::registerBlockPath($childBlocks);
            }
        }

        $parentBlocks = get_template_directory() . '/blocks';
        if (is_dir($parentBlocks)) {
            Config::registerBlockPath($parentBlocks);
        }
    }

    /**
     * Register blocks with WordPress.
     *
     * @return void
     */
    public static function registerBlocks(): void
    {
        $registry = Registry::getInstance();

        // Discover and load fluent blocks
        if (Config::get('auto_discovery', true)) {
            $registry->discoverAndLoadFluentBlocks();
        }

        // Discover JSON blocks
        $registry->discoverAndRegisterJsonBlocks();

        // Register all fluent blocks with WordPress
        self::registerFluentBlocksWithWordPress();
    }

    /**
     * Register fluent blocks with WordPress.
     *
     * @return void
     */
    private static function registerFluentBlocksWithWordPress(): void
    {
        $registry = Registry::getInstance();
        $blocks = $registry->getFluentBlocks();

        if (empty($blocks)) {
            return;
        }

        // Register editor script so core can enqueue it in the editor via
        // the block type's `editor_script` handle (contextual, editor-only).
        self::registerEditorScript();

        foreach ($blocks as $block) {
            self::registerSingleBlock($block);
        }
    }

    /**
     * Register a single block with WordPress.
     *
     * @param \HyperBlocks\Block\Block $block The block to register.
     * @return void
     */
    public static function registerSingleBlock(\HyperBlocks\Block\Block $block): void
    {
        $registry = Registry::getInstance();
        $attributes = $registry->generateBlockAttributes($block);

        // Build the WP block args. Optional metadata (category/description/
        // keywords/style) is included only when set, so existing fluent blocks
        // with defaults behave exactly as before.
        $args = [
            'api_version'     => 2,
            'title'           => $block->title,
            'icon'            => $block->icon,
            'attributes'      => $attributes,
            'render_callback' => [self::class, 'renderBlock'],
            'editor_script'   => Config::getEditorScriptHandle(),
        ];

        if ($block->category !== null) {
            $args['category'] = $block->category;
        }
        if ($block->description !== null) {
            $args['description'] = $block->description;
        }
        if ($block->keywords !== []) {
            $args['keywords'] = $block->keywords;
        }
        if ($block->style !== null) {
            $args['style'] = $block->style;
        }

        register_block_type($block->name, $args);
    }

    /**
     * Render callback for blocks.
     *
     * @param array      $attributes The block attributes.
     * @param string     $content    The block content.
     * @param \WP_Block  $block      The block instance.
     * @return string The rendered HTML.
     */
    public static function renderBlock(array $attributes, string $content = '', ?\WP_Block $block = null): string
    {
        if (!$block) {
            return '<div class="hyperblocks-error">Block instance not provided</div>';
        }

        $registry = Registry::getInstance();
        $blockDef = $registry->getFluentBlock($block->name);

        if (!$blockDef) {
            return '<div class="hyperblocks-error">Block configuration not found</div>';
        }

        if (empty($blockDef->render_template)) {
            return '<div class="hyperblocks-error">No render template defined for block: ' . esc_html($block->name) . '</div>';
        }

        // Sanitize and validate attributes
        $attributes = self::sanitizeAttributes($blockDef, $attributes);

        // Render
        $renderer = new \HyperBlocks\Renderer();

        return $renderer->render($blockDef->render_template, $attributes);
    }

    /**
     * Sanitize and validate block attributes.
     *
     * @param \HyperBlocks\Block\Block $blockDef    The block definition.
     * @param array                    $attributes The incoming attributes.
     * @return array The sanitized attributes.
     */
    private static function sanitizeAttributes(\HyperBlocks\Block\Block $blockDef, array $attributes): array
    {
        try {
            $registry = Registry::getInstance();
            $mergedFields = $registry->getMergedFields($blockDef);

            foreach ($mergedFields as $name => $field) {
                $adapter = $field->getAdapter();
                $incoming = $attributes[$name] ?? null;

                if ($incoming === null) {
                    $attributes[$name] = $field->getHyperField()->getDefault();
                    continue;
                }

                $sanitized = $adapter->sanitizeForBlock($incoming);
                if (!$adapter->validateForBlock($sanitized)) {
                    $attributes[$name] = $field->getHyperField()->getDefault();
                } else {
                    $attributes[$name] = $sanitized;
                }
            }
        } catch (\Throwable $e) {
            // Fail soft: keep original attributes if sanitization fails unexpectedly
            if (Config::isDebug()) {
                error_log('HyperBlocks: Sanitization error - ' . $e->getMessage());
            }
        }

        return $attributes;
    }

    /**
     * Register REST API endpoints.
     *
     * @return void
     */
    public static function registerRestApi(): void
    {
        $restApi = new RestApi();
        $restApi->init();
    }

    /**
     * Register the editor script that makes fluent blocks known to the
     * Gutenberg client so they appear in the inserter and parse in saved post
     * content.
     *
     * Only registers the handle (does not enqueue): this runs on `init`, which
     * fires on every request including the public front end. Core enqueues the
     * handle in the editor context only, via the `editor_script` argument passed
     * to `register_block_type()` in `registerSingleBlock()`.
     *
     * @return void
     */
    private static function registerEditorScript(): void
    {
        $scriptHandle = Config::getEditorScriptHandle();

        $scriptPath = Config::$abspath !== ''
            ? Config::$abspath . 'assets/js/editor.js'
            : null;

        if (!$scriptPath || !file_exists($scriptPath)) {
            return;
        }

        // Resolve the editor asset URL from Config::$pluginUrl (computed at
        // init from the web-accessible content roots). The fallback
        // re-resolves the asset path so this is correct even if a consumer
        // overrode the URL to ''. Both bail (return '') when the library
        // sits outside every content root, e.g. a Bedrock root composer
        // vendor, because no URL can serve a file outside the web document
        // root. Enqueuing a 404ing URL here would silently make every fluent
        // block inserter-invisible.
        $scriptUrl = '';
        if (Config::$pluginUrl !== '') {
            $scriptUrl = Config::$pluginUrl . 'assets/js/editor.js';
        } elseif (class_exists(\HyperFields\LibraryBootstrap::class)) {
            $scriptUrl = \HyperFields\LibraryBootstrap::resolveContentUrl($scriptPath);
        }

        if ($scriptUrl === '') {
            if (function_exists('error_log')) {
                error_log(sprintf(
                    'HyperBlocks: editor script %s is not reachable from any web-accessible WordPress content root. '
                    . 'Fluent blocks will render on the front end but will not appear in the block inserter. '
                    . 'HyperBlocks is loaded from %s; move it under a plugin/theme/vendor directory inside wp-content (e.g. via the consumer plugin bundled vendor) so the assets can be served.',
                    $scriptPath,
                    Config::$pluginFile !== '' ? Config::$pluginFile : $scriptPath
                ));
            }

            return;
        }

        // Register only. Core enqueues this in the editor via the block type's
        // `editor_script` handle, so it never reaches the front end.
        wp_register_script(
            $scriptHandle,
            $scriptUrl,
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-dom-ready', 'wp-block-editor', 'wp-server-side-render'],
            (string) filemtime($scriptPath),
            true
        );

        // Seed the block list the editor script reads on load. Attached to the
        // registered handle; prints in the editor when core enqueues it.
        $registry = Registry::getInstance();
        $blocks = $registry->getFluentBlocks();

        $blockConfigs = [];
        foreach ($blocks as $block) {
            $blockConfigs[] = [
                'name'  => $block->name,
                'title' => $block->title,
                'icon'  => $block->icon,
            ];
        }

        wp_add_inline_script(
            $scriptHandle,
            'window.hyperBlocksConfig = ' . wp_json_encode($blockConfigs) . ';',
            'before'
        );
    }

    /**
     * Enqueue editor assets.
     *
     * @return void
     */
    public static function enqueueEditorAssets(): void
    {
        // Enqueue editor styles if they exist
        $stylePath = Config::$abspath !== ''
            ? Config::$abspath . 'assets/css/editor.css'
            : null;

        if ($stylePath && file_exists($stylePath)) {
            $styleUrl = Config::$pluginUrl !== ''
                ? Config::$pluginUrl . 'assets/css/editor.css'
                : (class_exists(\HyperFields\LibraryBootstrap::class)
                    ? \HyperFields\LibraryBootstrap::resolveContentUrl($stylePath)
                    : '');

            if ($styleUrl !== '') {
                wp_enqueue_style(
                    'hyperblocks-editor',
                    $styleUrl,
                    [],
                    filemtime($stylePath)
                );
            }
        }
    }

    /**
     * Get the block configuration for the editor.
     *
     * @return array Array of block configurations.
     */
    public static function getEditorBlockConfigs(): array
    {
        $registry = Registry::getInstance();
        $blocks = $registry->getFluentBlocks();

        $configs = [];
        foreach ($blocks as $block) {
            $configs[] = [
                'name'  => $block->name,
                'title' => $block->title,
                'icon'  => $block->icon,
            ];
        }

        return $configs;
    }
}
