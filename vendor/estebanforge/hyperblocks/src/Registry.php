<?php

declare(strict_types=1);

/**
 * Registry for blocks and field groups.
 */

namespace HyperBlocks;

use HyperBlocks\Block\Block;
use HyperBlocks\Block\Field;
use HyperBlocks\Block\FieldGroup;
use HyperBlocks\WordPress\Bootstrap;
use HyperFields\BlockFieldAdapter;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Singleton class to manage block and field group registrations.
 */
final class Registry
{
    /**
     * WordPress-style file header marking a PHP file as a HyperBlocks
     * fluent-block definition. Auto-discovery only require_once's files that
     * declare this header, so render.php / init.php files co-located in a
     * theme's /blocks/<slug>/ directory (the de-facto WP/ACF block layout)
     * are never executed out of render context at init. See isFluentBlockFile().
     */
    public const FLUENT_BLOCK_HEADER = 'HyperBlocks Block';

    /**
     * Top-level key a block.json must declare (truthy) for HyperBlocks to own
     * it: register it, surface it, and resolve it through the REST API. The
     * JSON analog of FLUENT_BLOCK_HEADER: an explicit opt-in so discovery
     * never touches foreign (WP-native/ACF) block.json files co-located in a
     * registered path such as a theme's /blocks tree. WordPress core's
     * register_block_type_from_metadata() ignores unknown top-level keys, so
     * the marker is non-invasive.
     */
    public const JSON_BLOCK_MARKER = 'hyperblocks';

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
     * Resolved JSON block name -> directory map.
     *
     * Populated as owned blocks are registered during init discovery, so the
     * REST /block-fields and /render-preview lookups (findJsonBlockPath) are
     * an O(1) array hit instead of a fresh glob plus block.json read on every
     * request. Only positive results are cached (bounded by the block count);
     * misses fall through to the scan so varied bogus names cannot grow the
     * map.
     *
     * @var array<string, string>
     */
    private array $jsonBlockPathCache = [];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Set the plugin path from runtime identity (prefix-safe), falling
        // back to this file's parent when bootstrap has not initialized it.
        if (Config::$abspath !== '') {
            $this->pluginPath = Config::$abspath;
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
        // Layer 2 safety net: if a consumer reached Registry before
        // after_setup_theme fired, bring HyperBlocks up now. Recursion-safe
        // because init() runs Config::markInitialized() before any internal
        // getInstance() call (load-bearing invariant in Bootstrap::init).
        Bootstrap::ensureInitialized();
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

        // Add default library path if set (runtime identity, prefix-safe).
        if (Config::$abspath !== '') {
            $pluginBlocksPath = Config::$abspath . 'blocks';
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

        // Only discovery-enabled paths are scanned. Template-only paths
        // (template_paths, registered via registerTemplatePath() or
        // registerBlockPath($p, ['discover' => false])) are intentionally
        // excluded so render templates are never auto-executed as block
        // definitions.
        $scanPaths = Config::get('block_paths', []);

        // Add default library path if set (runtime identity, prefix-safe).
        if (Config::$abspath !== '') {
            $pluginBlocksPath = Config::$abspath . 'blocks';
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

            // Find all files matching allowed extensions, one directory
            // level beneath each base path. Native PHP glob() has no
            // globstar: the ** segment matches a single path component, so
            // this intentionally discovers ONLY files at basePath/<dir>/file
            // and never files directly in basePath or nested 2+ levels deep.
            // That bound is load-bearing: it keeps render templates stored at
            // the top of a registered path (e.g. the project's own
            // examples/blocks/*.hb.php) from being auto-executed as block
            // definitions. Pinning this behavior via tests; do not make it
            // recursive without a major-version bump and a migration guide.
            $fluentBlockFiles = [];
            $exts = array_map('trim', explode(',', Config::get('template_extensions', '.hb.php,.php')));
            foreach ($exts as $ext) {
                $files = glob($basePath . '/**/*' . $ext);
                if ($files !== false) {
                    $fluentBlockFiles = array_merge($fluentBlockFiles, $files);
                }
            }
            $fluentBlockFiles = array_unique($fluentBlockFiles);

            foreach ($fluentBlockFiles as $file) {
                // Skip files in directories starting with underscore
                if (str_contains($file, '/_')) {
                    continue;
                }

                // Only auto-load files that declare the HyperBlocks Block
                // header. WP-native render.php / init.php co-located in a
                // theme's /blocks/ tree never carry it; loading them at init
                // executes them out of render context (no $block in scope)
                // and dumps their output before <!DOCTYPE html>. See
                // isFluentBlockFile().
                if (!self::isFluentBlockFile($file)) {
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
     * Whether a PHP file declares itself a HyperBlocks fluent-block definition.
     *
     * Discovery require_once's PHP files found under registered block paths.
     * Standard WP/ACF themes co-locate render.php / init.php there — files
     * meant to be included by WP's block renderer with $block, $attributes,
     * and $content in scope. Auto-loading them at init executes them bare,
     * echoing markup before <!DOCTYPE html> and tripping "undefined $block"
     * warnings.
     *
     * The guard is a WordPress-style file header — the same convention used
     * for plugins, themes, and dropins. A file must declare a docblock with
     * a `HyperBlocks Block:` line to be treated as a fluent-block definition.
     * get_file_data() reads only the first 8KB, so large render templates are
     * never parsed past their header. WP-native render.php / init.php never
     * carry it and are skipped.
     *
     * Note: explicitly-filtered files (hyperblocks/blocks/register_fluent_blocks)
     * bypass this check — naming a file directly is explicit consumer consent.
     *
     * @param string $file Absolute path to the PHP file.
     * @return bool True when the HyperBlocks Block header is present and non-empty.
     */
    private static function isFluentBlockFile(string $file): bool
    {
        $headers = get_file_data($file, ['hyperblocks_block' => self::FLUENT_BLOCK_HEADER]);

        return ($headers['hyperblocks_block'] ?? '') !== '';
    }

    /**
     * Whether a parsed block.json declares HyperBlocks ownership.
     *
     * The single ownership predicate for JSON blocks. Every code path that
     * decides "this block.json is ours" (registration, REST field/preview
     * lookup, editor discovery) goes through here, so the marker is one gate
     * mirroring how isFluentBlockFile() gates fluent PHP files. A truthy
     * JSON_BLOCK_MARKER value opts in; absent or falsy opts out.
     *
     * @param array $metadata Parsed block.json contents.
     * @return bool
     */
    private static function isOwnedJsonBlock(array $metadata): bool
    {
        return !empty($metadata[self::JSON_BLOCK_MARKER]);
    }

    /**
     * Register a JSON block from its block.json, gated on the ownership marker.
     *
     * Only block.json files that declare the HyperBlocks marker
     * (JSON_BLOCK_MARKER) are registered, so discovery never sweeps up foreign
     * (WP-native/ACF) blocks co-located in a registered path. The block is
     * registered with WordPress from its metadata; core then handles inserter
     * surfacing, editor scripts, and front-end rendering declared in block.json.
     *
     * @param string $blockPath Path to the block directory.
     * @return bool True when the block was owned and registered.
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

        // Ownership gate: the JSON analog of the fluent HyperBlocks Block:
        // header. Without it, every co-located foreign block.json would be
        // registered. See isOwnedJsonBlock().
        if (!self::isOwnedJsonBlock($metadata)) {
            return false;
        }

        // Cache the name -> path mapping so REST lookups avoid rescanning the
        // block tree on every request. Recorded for every owned block, even
        // if core registration below fails, so the path stays resolvable.
        // See findJsonBlockPath().
        $this->jsonBlockPathCache[$metadata['name']] = $blockPath;

        // Fail soft: a malformed block.json must not take down the whole init
        // pass (every block registration on the request). Skip the offending
        // block and log when debug is on. Mirrors Bootstrap::sanitizeAttributes().
        try {
            return (bool) register_block_type_from_metadata($blockPath);
        } catch (\Throwable $e) {
            if (Config::isDebug()) {
                error_log('HyperBlocks: JSON block registration failed for ' . $blockPath . ' - ' . $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Find the path to a JSON block directory.
     *
     * @param string $blockName The name of the block.
     * @return string|null The path to the block directory or null if not found.
     */
    public function findJsonBlockPath(string $blockName): ?string
    {
        // O(1) lookup once init discovery has populated the name -> path map.
        // See registerJsonBlockFromPath(). Misses are cached up to a 100-entry bound
        // to prevent redundant disk rescans while capping memory growth.
        if (array_key_exists($blockName, $this->jsonBlockPathCache)) {
            return $this->jsonBlockPathCache[$blockName];
        }

        $scanPaths = [];

        // Get scan paths from configuration
        $scanPaths = Config::get('block_paths', []);

        // Add default library path if set (runtime identity, prefix-safe).
        if (Config::$abspath !== '') {
            $pluginBlocksPath = Config::$abspath . 'blocks';
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
                    // Only resolve owned blocks: the REST field/preview lookups
                    // must not match foreign block.json files in the same path.
                    if (isset($metadata['name']) && $metadata['name'] === $blockName && self::isOwnedJsonBlock($metadata)) {
                        $this->jsonBlockPathCache[$metadata['name']] = $directory;
                        return $directory;
                    }
                }
            }
        }

        if (count($this->jsonBlockPathCache) < 100) {
            $this->jsonBlockPathCache[$blockName] = null;
        }

        return null;
    }

    /**
     * Reset the registry (useful for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        $instance = self::getInstance();
        $instance->fluentBlocks = [];
        $instance->fieldGroups = [];
        $instance->jsonBlockPathCache = [];
    }
}
