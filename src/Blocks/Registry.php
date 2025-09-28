<?php

declare(strict_types=1);

/**
 * Registry for blocks and field groups.
 */

namespace HyperPress\Blocks;

use HyperFields\BlockFieldAdapter;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Singleton class to manage block and field group registrations.
 */
final class Registry
{
    /**
     * The single instance of the class.
     *
     * @var Registry|null
     */
    private static ?Registry $instance = null;

    /**
     * Registered fluent blocks.
     *
     * @var Block[]
     */
    private array $fluentBlocks = [];

    /**
     * Registered field groups.
     *
     * @var FieldGroup[]
     */
    private array $fieldGroups = [];

    /**
     * The root path of the plugin.
     *
     * @var string
     */
    private string $pluginPath;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Assuming this file is in /src/Blocks/, we need to go up two levels.
        $this->pluginPath = dirname(__DIR__, 2);
    }

    /**
     * Initialize the registry and hook into WordPress.
     */
    public function init(): void
    {
        add_action('init', $this->registerAll(...));
    }

    /**
     * Get the single instance of the Registry.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register a fluent block.
     *
     * @param Block $block The block to register.
     * @return void
     */
    public function registerFluentBlock(Block $block): void
    {
        // Use the block's name as the key for easy lookup.
        $this->fluentBlocks[$block->name] = $block;
    }

    /**
     * Get a fluent block definition by its name.
     *
     * @param string $blockName The name of the block.
     * @return Block|null
     */
    public function getFluentBlock(string $blockName): ?Block
    {
        return $this->fluentBlocks[$blockName] ?? null;
    }

    /**
     * Register a field group.
     *
     * @param FieldGroup $group The field group to register.
     * @return void
     */
    public function registerFieldGroup(FieldGroup $group): void
    {
        $this->fieldGroups[$group->id] = $group;
    }

    /**
     * Get a field group definition by its ID.
     *
     * @param string $groupId The ID of the field group.
     * @return FieldGroup|null
     */
    public function getFieldGroup(string $groupId): ?FieldGroup
    {
        return $this->fieldGroups[$groupId] ?? null;
    }

    /**
     * Discover and register blocks from `block.json` files.
     *
     * Scans a conventional directory (`/blocks`) for subdirectories
     * containing `block.json` files and registers them.
     *
     * @return void
     */
    private function discoverAndRegisterJsonBlocks(): void
    {
        // Define base paths to scan for JSON blocks
        $scanPaths = [];
        $jsonBlocks = [];

        // Always scan our plugin's hyperblocks directory
        if (defined('HYPERPRESS_ABSPATH')) {
            $pluginHyperblocksPath = HYPERPRESS_ABSPATH . '/hyperblocks';
            if (is_dir($pluginHyperblocksPath)) {
                $scanPaths[] = $pluginHyperblocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperpress/blocks/register_json_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        // Allow 3rd party devs to add individual block directories
        $additionalBlocks = apply_filters('hyperpress/blocks/register_json_blocks', []);

        // Collect all JSON blocks
        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $blockDirectories = glob($basePath . '/*', GLOB_ONLYDIR);

            foreach ($blockDirectories as $blockDirectory) {
                $blockName = basename($blockDirectory);

                // Skip directories starting with an underscore.
                if (str_starts_with($blockName, '_')) {
                    continue;
                }

                $blockJsonFile = $blockDirectory . '/block.json';
                if (file_exists($blockJsonFile)) {
                    $jsonBlocks[] = $blockDirectory;
                } else {
                }
            }
        }

        // Add individual blocks provided via filter
        foreach ($additionalBlocks as $blockPath) {
            if (is_dir($blockPath) && file_exists($blockPath . '/block.json')) {
                $jsonBlocks[] = $blockPath;
            }
        }

        // Register JSON blocks using our unified system (like fluent blocks)
        foreach ($jsonBlocks as $blockPath) {
            $this->registerJsonBlockFromPath($blockPath);
        }
    }

    private function discoverAndLoadFluentBlocks(): void
    {
        // Define base paths to scan for fluent blocks
        $scanPaths = [];

        // Always scan our plugin's hyperblocks directory
        if (defined('HYPERPRESS_ABSPATH')) {
            $pluginHyperblocksPath = HYPERPRESS_ABSPATH . '/hyperblocks';
            if (is_dir($pluginHyperblocksPath)) {
                $scanPaths[] = $pluginHyperblocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperpress/blocks/register_fluent_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        // Allow 3rd party devs to add individual fluent block files
        $additionalFiles = apply_filters('hyperpress/blocks/register_fluent_blocks', []);

        // Load fluent blocks from discovered paths
        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            // Find all files matching allowed extensions
            $fluentBlockFiles = [];
            if (defined('HYPERPRESS_TEMPLATE_EXT')) {
                $exts = array_map('trim', explode(',', HYPERPRESS_TEMPLATE_EXT));
                foreach ($exts as $ext) {
                    $files = glob($basePath . '/**/*' . $ext);
                    if ($files !== false) {
                        $fluentBlockFiles = array_merge($fluentBlockFiles, $files);
                    }
                }
            }

            foreach ($fluentBlockFiles as $file) {
                // Skip files in directories starting with underscore
                if (str_contains($file, '/_')) {
                    continue;
                }
                require_once $file;
            }
        }

        // Load individual fluent block files provided via filter
        if (defined('HYPERPRESS_TEMPLATE_EXT')) {
            $exts = array_map('trim', explode(',', HYPERPRESS_TEMPLATE_EXT));
            foreach ($additionalFiles as $file) {
                foreach ($exts as $ext) {
                    if (file_exists($file) && str_ends_with($file, $ext)) {
                        require_once $file;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Register all blocks and field groups with WordPress.
     *
     * @return void
     */
    public function registerAll(): void
    {
        // 1. Discover and load fluent block definition files.
        $this->discoverAndLoadFluentBlocks();

        // 2. Discover JSON blocks for editor registration.
        $jsonBlocks = $this->discoverJsonBlocksForEditor();

        // 3. Enqueue editor script for all blocks (fluent and JSON).
        if (!empty($this->fluentBlocks) || !empty($jsonBlocks)) {
            $this->enqueueEditorScript();
        }

        // 4. Discover and register `block.json` based blocks.
        $this->discoverAndRegisterJsonBlocks();

        // 5. Register Fluent API blocks.
        $this->registerFluentApiBlocks();
    }

    /**
     * Register blocks created with the Fluent API.
     *
     * @return void
     */
    private function registerFluentApiBlocks(): void
    {
        foreach ($this->fluentBlocks as $block) {
            // Generate dynamic attributes based on block fields
            $attributes = $this->generateBlockAttributes($block);

            register_block_type(
                $block->name,
                [
                    'api_version'     => 2,
                    'attributes'      => $attributes,
                    'render_callback' => [$this, 'renderBlock'],
                    'editor_script'   => 'hyperblocks-editor',
                ]
            );
        }
    }

    /**
     * Generate block attributes based on block fields.
     *
     * @param Block $block The block instance.
     * @return array
     */
    private function generateBlockAttributes(Block $block): array
    {
        $attributes = [];

        // Add attributes from block fields using HyperFields adapter
        foreach ($block->fields as $field) {
            $adapter = BlockFieldAdapter::fromField($field->getHyperField());
            $attributes[$field->name] = $adapter->toBlockAttribute();
        }

        // Add attributes from attached field groups (only if not already defined by block)
        foreach ($block->field_groups as $groupId) {
            $group = $this->getFieldGroup($groupId);
            if ($group) {
                foreach ($group->fields as $field) {
                    if (!array_key_exists($field->name, $attributes)) {
                        $adapter = \HyperFields\BlockFieldAdapter::fromField($field->getHyperField());
                        $attributes[$field->name] = $adapter->toBlockAttribute();
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Convert field type to block attribute type.
     *
     * @param string $fieldType The field type.
     * @return string
     */
    private function getAttributeType(string $fieldType): string
    {
        switch ($fieldType) {
            case 'text':
            case 'textarea':
            case 'url':
            case 'color':
                return 'string';
            case 'image':
                return 'object';
            default:
                return 'string';
        }
    }

    /**
     * Enqueue the editor script for all blocks (fluent and JSON).
     *
     * @return void
     */
    private function enqueueEditorScript(): void
    {
        $assetFile = $this->pluginPath . '/assets/js/build/editor.asset.php';

        if (!file_exists($assetFile)) {
            return;
        }

        $assetData = require $assetFile;

        wp_enqueue_script(
            'hyperblocks-editor',
            $this->getAssetUrl('assets/js/build/editor.js'),
            $assetData['dependencies'],
            $assetData['version'],
            true
        );

        // Collect all blocks to register in the editor
        $blockRegistrations = [];

        // Add fluent blocks
        foreach ($this->fluentBlocks as $block) {
            $blockRegistrations[] = [
                'name' => $block->name,
                'title' => $block->title,
                'icon' => $block->icon,
            ];
        }

        // Add JSON blocks discovered via block.json
        $jsonBlocks = $this->discoverJsonBlocksForEditor();

        foreach ($jsonBlocks as $block) {
            $blockRegistrations[] = $block;
        }

        wp_add_inline_script(
            'hyperblocks-editor',
            'window.hyperBlocksConfig = ' . wp_json_encode($blockRegistrations) . ';',
            'before'
        );
    }

    /**
     * Discover JSON blocks for editor registration.
     *
     * @return array Array of JSON block configurations for editor registration.
     */
    private function discoverJsonBlocksForEditor(): array
    {
        $jsonBlocks = [];
        $scanPaths = [];

        // Always scan our plugin's hyperblocks directory
        if (defined('HYPERPRESS_ABSPATH')) {
            $pluginHyperblocksPath = HYPERPRESS_ABSPATH . '/hyperblocks';
            if (is_dir($pluginHyperblocksPath)) {
                $scanPaths[] = $pluginHyperblocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperpress/blocks/register_json_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $blockDirectories = glob($basePath . '/*', GLOB_ONLYDIR);

            foreach ($blockDirectories as $blockDirectory) {
                $blockName = basename($blockDirectory);

                // Skip directories starting with an underscore
                if (str_starts_with($blockName, '_')) {
                    continue;
                }

                $blockJsonFile = $blockDirectory . '/block.json';
                if (file_exists($blockJsonFile)) {
                    $metadata = json_decode(file_get_contents($blockJsonFile), true);
                    if ($metadata && isset($metadata['name'])) {
                        $jsonBlocks[] = [
                            'name' => $metadata['name'],
                            'title' => $metadata['title'] ?? $metadata['name'],
                            'icon' => $metadata['icon'] ?? 'block-default',
                        ];
                    }
                }
            }
        }

        return $jsonBlocks;
    }

    /**
     * Register a JSON block using our unified system (like fluent blocks).
     *
     * @param string $blockPath Path to the block directory.
     * @return void
     */
    private function registerJsonBlockFromPath(string $blockPath): void
    {
        $blockJsonFile = $blockPath . '/block.json';

        // Check if block.json exists
        if (!file_exists($blockJsonFile)) {

            return;
        }

        $metadata = json_decode(file_get_contents($blockJsonFile), true);

        if (!$metadata || !isset($metadata['name'])) {
            return;
        }

        // Log the block name being registered
        $blockName = $metadata['name'];

        // Extract attributes from block.json
        $attributes = [];
        if (isset($metadata['attributes']) && is_array($metadata['attributes'])) {
            foreach ($metadata['attributes'] as $attrName => $attrConfig) {
                $attributes[$attrName] = [
                    'type' => $attrConfig['type'] ?? 'string',
                    'default' => $attrConfig['default'] ?? '',
                ];
            }
        }

        // Register the block using our unified system
        $block_args = [
            'api_version' => 2,
            'attributes' => $attributes,
            'render_callback' => [$this, 'renderJsonBlock'],
            'editor_script' => 'hyperblocks-editor',
        ];

        // Add metadata from block.json
        if (isset($metadata['title'])) {
            $block_args['title'] = $metadata['title'];
        }
        if (isset($metadata['description'])) {
            $block_args['description'] = $metadata['description'];
        }
        if (isset($metadata['category'])) {
            $block_args['category'] = $metadata['category'];
        }
        if (isset($metadata['icon'])) {
            $block_args['icon'] = $metadata['icon'];
        }
        if (isset($metadata['keywords']) && is_array($metadata['keywords'])) {
            $block_args['keywords'] = $metadata['keywords'];
        }
        if (isset($metadata['supports']) && is_array($metadata['supports'])) {
            $block_args['supports'] = $metadata['supports'];
        }

        try {
            $result = register_block_type($blockName, $block_args);
        } catch (\Exception $e) {
        }
    }

    /**
     * Render callback for JSON blocks.
     *
     * @param array $attributes The block attributes.
     * @param string $content The block content.
     * @param \WP_Block $block The block instance.
     * @return string
     */
    public function renderJsonBlock(array $attributes, string $content = '', \WP_Block $block = null): string
    {
        if (!$block) {
            return '<div class="hyperblocks-error">Block instance not provided</div>';
        }

        // Get the block directory path
        $blockName = $block->name;
        $blockPath = null;

        // Find the block directory by scanning known paths
        $scanPaths = [];
        if (defined('HYPERPRESS_ABSPATH')) {
            $pluginHyperblocksPath = HYPERPRESS_ABSPATH . '/hyperblocks';
            if (is_dir($pluginHyperblocksPath)) {
                $scanPaths[] = $pluginHyperblocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperpress/blocks/register_json_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $blockDirectories = glob($basePath . '/*', GLOB_ONLYDIR);
            foreach ($blockDirectories as $directory) {
                $blockJsonFile = $directory . '/block.json';
                if (file_exists($blockJsonFile)) {
                    $metadata = json_decode(file_get_contents($blockJsonFile), true);
                    if (isset($metadata['name']) && $metadata['name'] === $blockName) {
                        $blockPath = $directory;
                        break 2;
                    }
                }
            }
        }

        if (!$blockPath) {
            return '<div class="hyperblocks-error">Block path not found: ' . esc_html($blockName) . '</div>';
        }

        $renderFile = $blockPath . '/render.php';
        if (!file_exists($renderFile)) {
            return '<div class="hyperblocks-error">Render file not found for block: ' . esc_html($blockName) . '</div>';
        }

        // Use the renderer to process the template
        $renderer = new Renderer();

        return $renderer->render('file:' . $renderFile, $attributes);
    }

    /**
     * Get the URL for plugin assets.
     *
     * @param string $assetPath The relative path to the asset.
     * @return string
     */
    private function getAssetUrl(string $assetPath): string
    {
        if (defined('HYPERPRESS_PLUGIN_URL') && !empty(HYPERPRESS_PLUGIN_URL)) {
            return HYPERPRESS_PLUGIN_URL . $assetPath;
        }

        // Fallback for library mode - construct URL from plugin path
        // Support both legacy 'api-for-htmx.php' and current 'hyperpress.php'
        $basePath = function_exists('trailingslashit') ? trailingslashit($this->pluginPath) : rtrim($this->pluginPath, '/\\') . '/';
        $entryCandidates = [
            $basePath . 'hyperpress.php',
            $basePath . 'api-for-htmx.php',
        ];
        $entryFile = null;
        foreach ($entryCandidates as $candidate) {
            if (file_exists($candidate)) {
                $entryFile = $candidate;
                break;
            }
        }
        // If no entry file found, use bootstrap.php so plugins_url can resolve the base
        $entryFile = $entryFile ?: ($basePath . 'bootstrap.php');

        $pluginUrl = plugins_url('', $entryFile);
        $pluginUrl = rtrim($pluginUrl, '/');

        return $pluginUrl . '/' . ltrim($assetPath, '/');
    }

    /**
     * Render callback for fluent blocks using the rendering engine.
     *
     * @param array $attributes The block attributes.
     * @param string $content The block content (for InnerBlocks).
     * @param \WP_Block $block The block instance.
     * @return string
     */
    public function renderBlock(array $attributes, string $content = '', \WP_Block $block = null): string
    {
        if (!$block) {
            return '<div class="hyperblocks-error">Block instance not provided</div>';
        }

        // Get the fluent block configuration
        $fluentBlock = $this->getFluentBlock($block->name);

        if (!$fluentBlock) {
            return '<div class="hyperblocks-error">Block configuration not found</div>';
        }

        // Check if block has a render template
        if (empty($fluentBlock->render_template)) {
            return '<div class="hyperblocks-error">No render template defined for block: ' . esc_html($block->name) . '</div>';
        }

        // Sanitize and validate attributes via HyperFields before rendering
        try {
            // Build a merged map of field name => HyperPress\Blocks\Field (block fields take precedence)
            $mergedFields = [];
            foreach ($fluentBlock->fields as $f) {
                $mergedFields[$f->name] = $f;
            }
            foreach ($fluentBlock->field_groups as $groupId) {
                $group = $this->getFieldGroup($groupId);
                if ($group) {
                    foreach ($group->fields as $gf) {
                        if (!isset($mergedFields[$gf->name])) {
                            $mergedFields[$gf->name] = $gf;
                        }
                    }
                }
            }

            // Sanitize/validate incoming attributes; apply defaults when missing/invalid
            foreach ($mergedFields as $name => $field) {
                $adapter = BlockFieldAdapter::fromField($field->getHyperField(), $attributes);
                $incoming = $attributes[$name] ?? null;

                if ($incoming === null) {
                    // Apply default when missing
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
        }

        // Use the renderer to process the template
        $renderer = new Renderer();

        return $renderer->render($fluentBlock->render_template, $attributes);
    }
}
