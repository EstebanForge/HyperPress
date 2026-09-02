<?php

declare(strict_types=1);

/**
 * Shared block operations.
 *
 * Single implementation behind the REST surface (hyperblocks/v1) and the
 * Abilities surface (hyperblocks/*): field lookups, preview rendering, and
 * the block inventory. REST and ability callbacks must stay behaviorally
 * identical, so both call into this class instead of holding their own copy
 * of the logic.
 *
 * @since 1.7.0
 */

namespace HyperBlocks;

use HyperFields\BlockFieldAdapter;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Block field lookup, preview rendering, and inventory operations.
 */
final class BlockOperations
{
    /**
     * Field definitions for a block (fluent or JSON).
     *
     * @param string $blockName Block name (namespace/slug).
     * @return array|null Field definition arrays, or null when the block is unknown.
     */
    public static function getFields(string $blockName): ?array
    {
        $registry = Registry::getInstance();

        $block = $registry->getFluentBlock($blockName);

        if ($block) {
            // Merge field group fields with block fields, deduped by name:
            // block fields take precedence over field group fields (same
            // rule as preview(), so field lookups and renders agree when
            // names collide).
            $mergedFields = [];
            foreach ($block->field_groups as $groupId) {
                $fieldGroup = $registry->getFieldGroup($groupId);
                if ($fieldGroup) {
                    foreach ($fieldGroup->fields as $field) {
                        $mergedFields[$field->name] ??= $field;
                    }
                }
            }
            foreach ($block->fields as $field) {
                $mergedFields[$field->name] = $field;
            }

            return array_map(static fn ($field) => $field->toArray(), array_values($mergedFields));
        }

        return self::getJsonBlockFields($blockName);
    }

    /**
     * Render a server-side preview of a block with the given attributes.
     *
     * @param string $blockName  Block name (namespace/slug).
     * @param array  $attributes Incoming attributes; sanitized before rendering.
     * @return array{status: string, html: string, error: string, rest_status: int}
     *                status: ok | no_template | not_found | error. rest_status
     *                carries the HTTP status the REST layer should map to; the
     *                Abilities layer ignores it.
     */
    public static function preview(string $blockName, array $attributes): array
    {
        $registry = Registry::getInstance();
        $block = $registry->getFluentBlock($blockName);

        if ($block) {
            if (empty($block->render_template)) {
                return self::result('no_template', 'No render template defined for block: ' . $blockName, 400);
            }

            try {
                // Sanitize and validate incoming attributes against HyperFields
                $mergedFields = [];
                foreach ($block->fields as $f) {
                    $mergedFields[$f->name] = $f;
                }
                foreach ($block->field_groups as $groupId) {
                    $group = $registry->getFieldGroup($groupId);
                    if ($group) {
                        foreach ($group->fields as $gf) {
                            if (!isset($mergedFields[$gf->name])) {
                                $mergedFields[$gf->name] = $gf;
                            }
                        }
                    }
                }

                foreach ($mergedFields as $name => $field) {
                    $adapter = BlockFieldAdapter::fromField($field->getHyperField(), $attributes);
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

                // Use the renderer to generate preview HTML
                $renderer = new Renderer();
                $html = $renderer->render($block->render_template, $attributes);

                return ['status' => 'ok', 'html' => $html, 'error' => '', 'rest_status' => 200];
            } catch (\Throwable $e) {
                return self::result('error', 'Rendering failed: ' . $e->getMessage(), 500);
            }
        }

        // If not a fluent block, try JSON block
        $blockPath = $registry->findJsonBlockPath($blockName);
        if (!$blockPath) {
            return self::result('not_found', 'Block not found: ' . $blockName, 404);
        }

        $blockJsonFile = $blockPath . '/block.json';
        if (!file_exists($blockJsonFile)) {
            return self::result('not_found', 'Block not found: ' . $blockName, 404);
        }

        $metadata = json_decode(file_get_contents($blockJsonFile), true);
        if (!$metadata) {
            return self::result('not_found', 'Block not found: ' . $blockName, 404);
        }

        // Check if there's a render.php file
        $renderFile = $blockPath . '/render.php';
        if (!file_exists($renderFile)) {
            return self::result('error', 'No render.php file found for JSON block: ' . $blockName, 200);
        }

        $attributes = self::sanitizeJsonBlockAttributes($attributes, $metadata['attributes'] ?? []);

        try {
            $renderer = new Renderer();
            $html = $renderer->render('file:' . $renderFile, $attributes);

            return ['status' => 'ok', 'html' => $html, 'error' => '', 'rest_status' => 200];
        } catch (\Throwable $e) {
            return self::result('error', 'Rendering failed: ' . $e->getMessage(), 200);
        }
    }

    /**
     * Inventory of every registered block, fluent and JSON.
     *
     * @return array<int, array{name: string, title: string, source: string, has_render_template: bool}>
     */
    public static function listBlocks(): array
    {
        $entries = [];

        foreach (Registry::getInstance()->getFluentBlocks() as $block) {
            $entries[] = [
                'name'                => $block->name,
                'title'               => $block->title,
                'source'              => 'fluent',
                'has_render_template' => $block->render_template !== '',
            ];
        }

        foreach (Registry::getInstance()->getJsonBlocks() as $name => $path) {
            $metadata = json_decode((string) file_get_contents($path . '/block.json'), true);
            $entries[] = [
                'name'                => $name,
                'title'               => is_array($metadata) ? (string) ($metadata['title'] ?? $name) : $name,
                'source'              => 'json',
                'has_render_template' => file_exists($path . '/render.php'),
            ];
        }

        usort($entries, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $entries;
    }

    /**
     * Field definitions for a JSON block, derived from its block.json
     * attributes.
     *
     * @param string $blockName The name of the block.
     * @return array|null Array of field definitions or null when unknown.
     */
    private static function getJsonBlockFields(string $blockName): ?array
    {
        // Find the block.json file for this block
        $blockPath = Registry::getInstance()->findJsonBlockPath($blockName);
        if (!$blockPath) {
            return null;
        }

        $blockJsonFile = $blockPath . '/block.json';
        if (!file_exists($blockJsonFile)) {
            return null;
        }

        $metadata = json_decode((string) file_get_contents($blockJsonFile), true);
        if (!$metadata || !isset($metadata['attributes'])) {
            return null;
        }

        // Convert block.json attributes to field definitions
        $fields = [];
        foreach ($metadata['attributes'] as $attrName => $attrConfig) {
            $fields[] = [
                'name'    => $attrName,
                'label'   => self::generateFieldLabel($attrName),
                'type'    => self::mapAttributeTypeToFieldType($attrConfig['type'] ?? 'string'),
                'default' => $attrConfig['default'] ?? '',
            ];
        }

        return $fields;
    }

    /**
     * Normalize a non-ok result shape.
     *
     * @param string $status One of no_template | not_found | error.
     * @param string $error  Human-readable error message.
     * @param int    $restStatus HTTP status the REST layer should map to.
     * @return array{status: string, html: string, error: string, rest_status: int}
     */
    private static function result(string $status, string $error, int $restStatus): array
    {
        return ['status' => $status, 'html' => '', 'error' => $error, 'rest_status' => $restStatus];
    }

    /**
     * Sanitize JSON block attributes by their declared block.json types.
     *
     * @param array $attributes        Incoming attributes from the request.
     * @param array $declaredAttributes block.json attribute type declarations.
     * @return array Sanitized attributes.
     */
    public static function sanitizeJsonBlockAttributes(array $attributes, array $declaredAttributes): array
    {
        $sanitized = [];

        foreach ($attributes as $name => $value) {
            $declaration = $declaredAttributes[$name] ?? [];
            $type = $declaration['type'] ?? 'string';
            $source = $declaration['source'] ?? '';

            $sanitized[$name] = match ($type) {
                'integer', 'number' => is_numeric($value) ? $value + 0 : 0,
                'boolean' => (bool) $value,
                'string' => $source === 'html'
                    ? wp_kses_post((string) $value)
                    : sanitize_text_field((string) $value),
                default => self::sanitizeNestedValue($value),
            };
        }

        return $sanitized;
    }

    /**
     * Recursively sanitize a nested array or scalar value.
     *
     * @param mixed $value The value to sanitize.
     * @return mixed Sanitized value.
     */
    private static function sanitizeNestedValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeNestedValue'], $value);
        }

        return sanitize_text_field((string) $value);
    }

    /**
     * Generate a human-readable label from a field name.
     *
     * @param string $fieldName The field name.
     * @return string The formatted label.
     */
    private static function generateFieldLabel(string $fieldName): string
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $fieldName));
    }

    /**
     * Map block.json attribute types to field types.
     *
     * @param string $attributeType The attribute type from block.json.
     * @return string The corresponding field type.
     */
    private static function mapAttributeTypeToFieldType(string $attributeType): string
    {
        switch ($attributeType) {
            case 'string':
                return 'text';
            case 'boolean':
                return 'checkbox';
            case 'number':
            case 'integer':
                return 'number';
            case 'object':
                return 'object';
            case 'array':
                return 'array';
            default:
                return 'text';
        }
    }
}
