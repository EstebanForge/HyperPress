<?php

declare(strict_types=1);

/**
 * Registry for blocks and field groups.
 */

namespace HyperBlocks;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperFields\BlockFieldAdapter;
use HyperBlocks\Config;

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
        // Set the plugin path
        if (defined('HYPERBLOCKS_PATH')) {
            $this->pluginPath = HYPERBLOCKS_PATH;
        } else {
            // Fallback: assume this file is in /src/Registry.php
            $this->pluginPath = dirname(__DIR__);
        }
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
     * Get all registered fluent blocks.
     *
     * @return Block[]
     */
    public function getFluentBlocks(): array
    {
        return $this->fluentBlocks;
    }

    /**
     * Check if a fluent block is registered.
     *
     * @param string $blockName The name of the block.
     * @return bool
     */
    public function hasFluentBlock(string $blockName): bool
    {
        return isset($this->fluentBlocks[$blockName]);
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
     * Get all registered field groups.
     *
     * @return FieldGroup[]
     */
    public function getFieldGroups(): array
    {
        return $this->fieldGroups;
    }

    /**
     * Generate block attributes based on block fields.
     *
     * @param Block $block The block instance.
     * @return array
     */
    public function generateBlockAttributes(Block $block): array
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
                        $adapter = BlockFieldAdapter::fromField($field->getHyperField());
                        $attributes[$field->name] = $adapter->toBlockAttribute();
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Get merged fields from block and field groups.
     *
     * @param Block $block The block instance.
     * @return Field[]
     */
    public function getMergedFields(Block $block): array
    {
        $mergedFields = [];

        // Add block fields
        foreach ($block->fields as $field) {
            $mergedFields[$field->name] = $field;
        }

        // Add field group fields (block fields take precedence)
        foreach ($block->field_groups as $groupId) {
            $group = $this->getFieldGroup($groupId);
            if ($group) {
                foreach ($group->fields as $field) {
                    if (!isset($mergedFields[$field->name])) {
                        $mergedFields[$field->name] = $field;
                    }
                }
            }
        }

        return $mergedFields;
    }

    /**
     * Discover and register blocks from `block.json` files.
     *
     * Scans configured directories for subdirectories containing `block.json` files
     * and registers them.
     *
     * @return array Array of discovered block paths
     */
    public function discoverAndRegisterJsonBlocks(): array
    {
        $jsonBlocks = [];

        // Get scan paths from configuration
        $scanPaths = Config::get('block_paths', []);

        // Add default plugin path if set
        if (defined('HYPERBLOCKS_PATH')) {
            $pluginBlocksPath = HYPERBLOCKS_PATH . '/blocks';
            if (is_dir($pluginBlocksPath)) {
                $scanPaths[] = $pluginBlocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperblocks/blocks/register_json_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        // Allow 3rd party devs to add individual block directories
        $additionalBlocks = apply_filters('hyperblocks/blocks/register_json_blocks', []);

        // Collect all JSON blocks
        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $blockDirectories = glob($basePath . '/*', GLOB_ONLYDIR);

            if ($blockDirectories === false) {
                continue;
            }

            foreach ($blockDirectories as $blockDirectory) {
                $blockName = basename($blockDirectory);

                // Skip directories starting with an underscore.
                if (str_starts_with($blockName, '_')) {
                    continue;
                }

                $blockJsonFile = $blockDirectory . '/block.json';
                if (file_exists($blockJsonFile)) {
                    $jsonBlocks[] = $blockDirectory;
                }
            }
        }

        // Add individual blocks provided via filter
        foreach ($additionalBlocks as $blockPath) {
            if (is_dir($blockPath) && file_exists($blockPath . '/block.json')) {
                $jsonBlocks[] = $blockPath;
            }
        }

        // Register JSON blocks
        foreach ($jsonBlocks as $blockPath) {
            $this->registerJsonBlockFromPath($blockPath);
        }

        return $jsonBlocks;
    }

    /**
     * Discover and load fluent block definition files.
     *
     * @return array Array of loaded file paths
     */
    public function discoverAndLoadFluentBlocks(): array
    {
        $loadedFiles = [];

        // Get scan paths from configuration
        $scanPaths = Config::get('block_paths', []);

        // Add default plugin path if set
        if (defined('HYPERBLOCKS_PATH')) {
            $pluginBlocksPath = HYPERBLOCKS_PATH . '/blocks';
            if (is_dir($pluginBlocksPath)) {
                $scanPaths[] = $pluginBlocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperblocks/blocks/register_fluent_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        // Allow 3rd party devs to add individual fluent block files
        $additionalFiles = apply_filters('hyperblocks/blocks/register_fluent_blocks', []);

        // Load fluent blocks from discovered paths
        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            // Find all files matching allowed extensions
            $fluentBlockFiles = [];
            $exts = array_map('trim', explode(',', Config::get('template_extensions', '.hb.php,.php')));
            foreach ($exts as $ext) {
                $files = glob($basePath . '/**/*' . $ext);
                if ($files !== false) {
                    $fluentBlockFiles = array_merge($fluentBlockFiles, $files);
                }
            }

            foreach ($fluentBlockFiles as $file) {
                // Skip files in directories starting with underscore
                if (str_contains($file, '/_')) {
                    continue;
                }
                require_once $file;
                $loadedFiles[] = $file;
            }
        }

        // Load individual fluent block files provided via filter
        $exts = array_map('trim', explode(',', Config::get('template_extensions', '.hb.php,.php')));
        foreach ($additionalFiles as $file) {
            foreach ($exts as $ext) {
                if (file_exists($file) && str_ends_with($file, $ext)) {
                    require_once $file;
                    $loadedFiles[] = $file;
                    break;
                }
            }
        }

        return $loadedFiles;
    }

    /**
     * Discover JSON blocks for editor registration.
     *
     * @return array Array of JSON block configurations for editor registration.
     */
    public function discoverJsonBlocksForEditor(): array
    {
        $jsonBlocks = [];

        // Get scan paths from configuration
        $scanPaths = Config::get('block_paths', []);

        // Add default plugin path if set
        if (defined('HYPERBLOCKS_PATH')) {
            $pluginBlocksPath = HYPERBLOCKS_PATH . '/blocks';
            if (is_dir($pluginBlocksPath)) {
                $scanPaths[] = $pluginBlocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperblocks/blocks/register_json_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $blockDirectories = glob($basePath . '/*', GLOB_ONLYDIR);

            if ($blockDirectories === false) {
                continue;
            }

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
     * Register a JSON block using our unified system.
     *
     * @param string $blockPath Path to the block directory.
     * @return bool Whether registration was successful
     */
    private function registerJsonBlockFromPath(string $blockPath): bool
    {
        $blockJsonFile = $blockPath . '/block.json';

        // Check if block.json exists
        if (!file_exists($blockJsonFile)) {
            return false;
        }

        $metadata = json_decode(file_get_contents($blockJsonFile), true);

        if (!$metadata || !isset($metadata['name'])) {
            return false;
        }

        return true;
    }

    /**
     * Find the path to a JSON block directory.
     *
     * @param string $blockName The name of the block.
     * @return string|null The path to the block directory or null if not found.
     */
    public function findJsonBlockPath(string $blockName): ?string
    {
        $scanPaths = [];

        // Get scan paths from configuration
        $scanPaths = Config::get('block_paths', []);

        // Add default plugin path if set
        if (defined('HYPERBLOCKS_PATH')) {
            $pluginBlocksPath = HYPERBLOCKS_PATH . '/blocks';
            if (is_dir($pluginBlocksPath)) {
                $scanPaths[] = $pluginBlocksPath;
            }
        }

        // Allow 3rd party devs to add their paths via filter
        $additionalPaths = apply_filters('hyperblocks/blocks/register_json_paths', []);
        $scanPaths = array_merge($scanPaths, $additionalPaths);

        foreach ($scanPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $blockDirectories = glob($basePath . '/*', GLOB_ONLYDIR);
            if ($blockDirectories === false) {
                continue;
            }

            foreach ($blockDirectories as $directory) {
                $blockJsonFile = $directory . '/block.json';
                if (file_exists($blockJsonFile)) {
                    $metadata = json_decode(file_get_contents($blockJsonFile), true);
                    if (isset($metadata['name']) && $metadata['name'] === $blockName) {
                        return $directory;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Reset the registry (useful for testing).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->fluentBlocks = [];
        $this->fieldGroups = [];
    }
}
