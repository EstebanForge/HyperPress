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
     * Initialize configuration.
     *
     * @return void
     */
    public static function initializeConfig(): void
    {
        // Set default block path if HYPERBLOCKS_PATH is defined
        if (defined('HYPERBLOCKS_PATH') && is_dir(HYPERBLOCKS_PATH . '/blocks')) {
            Config::registerBlockPath(HYPERBLOCKS_PATH . '/blocks');
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
     * bundled blocks (HYPERBLOCKS_PATH/blocks) are registered separately in
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

        $scriptPath = defined('HYPERBLOCKS_PATH')
            ? HYPERBLOCKS_PATH . '/assets/js/editor.js'
            : null;

        if (!$scriptPath || !file_exists($scriptPath)) {
            return;
        }

        // Resolve the editor asset URL against the active web-accessible
        // content roots. HYPERBLOCKS_PLUGIN_URL is now computed the same way
        // in bootstrap.php; the fallback re-resolves the asset path so this
        // path is correct even if a consumer overrode the constant to ''.
        // Both bail (return '') when the library sits outside every content
        // root — e.g. a Bedrock root composer vendor — because no URL can
        // serve a file outside the web document root. Enqueuing a 404ing URL
        // here would silently make every fluent block inserter-invisible.
        $scriptUrl = '';
        if (defined('HYPERBLOCKS_PLUGIN_URL') && HYPERBLOCKS_PLUGIN_URL !== '') {
            $scriptUrl = HYPERBLOCKS_PLUGIN_URL . 'assets/js/editor.js';
        } elseif (function_exists('hyperblocks_resolve_content_url')) {
            $scriptUrl = hyperblocks_resolve_content_url($scriptPath);
        }

        if ($scriptUrl === '') {
            if (function_exists('error_log')) {
                error_log(sprintf(
                    'HyperBlocks: editor script %s is not reachable from any web-accessible WordPress content root. '
                    . 'Fluent blocks will render on the front end but will not appear in the block inserter. '
                    . 'HyperBlocks is loaded from %s; move it under a plugin/theme/vendor directory inside wp-content (e.g. via the consumer plugin bundled vendor) so the assets can be served.',
                    $scriptPath,
                    defined('HYPERBLOCKS_INSTANCE_LOADED_PATH') ? HYPERBLOCKS_INSTANCE_LOADED_PATH : $scriptPath
                ));
            }

            return;
        }

        // Register only. Core enqueues this in the editor via the block type's
        // `editor_script` handle, so it never reaches the front end.
        wp_register_script(
            $scriptHandle,
            $scriptUrl,
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-dom-ready'],
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
        $stylePath = defined('HYPERBLOCKS_PATH')
            ? HYPERBLOCKS_PATH . '/assets/css/editor.css'
            : null;

        if ($stylePath && file_exists($stylePath)) {
            $styleUrl = defined('HYPERBLOCKS_PLUGIN_URL') && HYPERBLOCKS_PLUGIN_URL !== ''
                ? HYPERBLOCKS_PLUGIN_URL . 'assets/css/editor.css'
                : (function_exists('hyperblocks_resolve_content_url')
                    ? hyperblocks_resolve_content_url($stylePath)
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
