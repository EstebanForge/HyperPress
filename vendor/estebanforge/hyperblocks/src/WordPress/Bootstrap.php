<?php

declare(strict_types=1);

/**
 * WordPress Bootstrap for HyperBlocks
 *
 * This file handles WordPress-specific initialization and integration.
 *
 * @package HyperBlocks
 */

namespace HyperBlocks\WordPress;

use HyperBlocks\Config;
use HyperBlocks\Registry;
use HyperBlocks\RestApi;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
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
     * @return void
     */
    public static function registerDefaultPaths(): void
    {
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

        // Enqueue editor script
        self::enqueueEditorScript();

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

        register_block_type(
            $block->name,
            [
                'api_version'     => 2,
                'title'           => $block->title,
                'icon'            => $block->icon,
                'attributes'      => $attributes,
                'render_callback' => [self::class, 'renderBlock'],
                'editor_script'   => Config::getEditorScriptHandle(),
            ]
        );
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
     * Enqueue editor script.
     *
     * @return void
     */
    private static function enqueueEditorScript(): void
    {
        // This is a placeholder for editor script enqueuing
        // In a real implementation, this would enqueue the compiled JavaScript
        // that integrates with the Gutenberg editor

        $scriptHandle = Config::getEditorScriptHandle();

        // Only enqueue if the script file exists
        $scriptPath = defined('HYPERBLOCKS_PATH')
            ? HYPERBLOCKS_PATH . '/assets/js/editor.js'
            : null;

        if ($scriptPath && file_exists($scriptPath)) {
            $scriptUrl = plugins_url('/assets/js/editor.js', $scriptPath);
            wp_enqueue_script(
                $scriptHandle,
                $scriptUrl,
                ['wp-blocks', 'wp-element', 'wp-components'],
                filemtime($scriptPath),
                true
            );

            // Pass block configurations to the editor
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
            $styleUrl = plugins_url('/assets/css/editor.css', $stylePath);
            wp_enqueue_style(
                'hyperblocks-editor',
                $styleUrl,
                [],
                filemtime($stylePath)
            );
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
