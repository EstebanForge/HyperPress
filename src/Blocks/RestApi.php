<?php

declare(strict_types=1);

/**
 * Handles REST API endpoint registration.
 */

namespace HyperPress\Blocks;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages the registration of REST API routes.
 */
class RestApi
{
    /**
     * The namespace for the REST API.
     *
     * @var string
     */
    private string $namespace = 'hyperblocks/v1';

    /**
     * Register hooks.
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register the REST API routes.
     */
    public function registerRoutes(): void
    {
        // Block fields endpoint
        register_rest_route(
            $this->namespace,
            '/block-fields',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getBlockFields'],
                'permission_callback' => '__return_true', // For now, open to all.
                'args'                => [
                    'name' => [
                        'required'          => true,
                        'validate_callback' => function ($param) {
                            return is_string($param);
                        },
                    ],
                ],
            ]
        );

        // Server-side preview endpoint
        register_rest_route(
            $this->namespace,
            '/render-preview',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'renderPreview'],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'args'                => [
                    'blockName' => [
                        'required'          => true,
                        'validate_callback' => function ($param) {
                            return is_string($param);
                        },
                    ],
                    'attributes' => [
                        'required'          => true,
                        'validate_callback' => function ($param) {
                            return is_array($param);
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Callback for the /block-fields endpoint.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function getBlockFields(\WP_REST_Request $request): \WP_REST_Response
    {
        $blockName = $request->get_param('name');

        $registry = Registry::getInstance();

        // First, try to find the block as a fluent block
        $block = $registry->getFluentBlock($blockName);

        if ($block) {
            // Handle fluent blocks
            $mergedFields = $block->fields;

            // If the block has attached field groups, merge their fields.
            foreach ($block->field_groups as $groupId) {
                $fieldGroup = $registry->getFieldGroup($groupId);
                if ($fieldGroup) {
                    // Merge field group fields with block fields.
                    // Block fields take precedence over field group fields.
                    $mergedFields = array_merge($fieldGroup->fields, $mergedFields);
                }
            }

            // Convert Field objects to arrays for JSON response.
            $fieldDefinitions = [];
            foreach ($mergedFields as $field) {
                $fieldDefinitions[] = $field->toArray();
            }

            return new \WP_REST_Response($fieldDefinitions);
        }

        // If not a fluent block, check if it's a JSON block
        $jsonBlockFields = $this->getJsonBlockFields($blockName);
        if ($jsonBlockFields !== null) {
            return new \WP_REST_Response($jsonBlockFields);
        }

        return new \WP_REST_Response(['error' => 'Block not found.'], 404);
    }

    /**
     * Get field definitions from a JSON block.
     *
     * @param string $blockName The name of the JSON block.
     * @return array|null Array of field definitions or null if not found.
     */
    private function getJsonBlockFields(string $blockName): ?array
    {
        // Find the block.json file for this block
        $blockPath = $this->findJsonBlockPath($blockName);
        if (!$blockPath) {
            return null;
        }

        $blockJsonFile = $blockPath . '/block.json';
        if (!file_exists($blockJsonFile)) {
            return null;
        }

        $metadata = json_decode(file_get_contents($blockJsonFile), true);
        if (!$metadata || !isset($metadata['attributes'])) {
            return null;
        }

        // Convert block.json attributes to field definitions
        $fields = [];
        foreach ($metadata['attributes'] as $attrName => $attrConfig) {
            $field = [
                'name' => $attrName,
                'label' => $this->generateFieldLabel($attrName),
                'type' => $this->mapAttributeTypeToFieldType($attrConfig['type'] ?? 'string'),
                'default' => $attrConfig['default'] ?? '',
            ];
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Callback for the /render-preview endpoint.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function renderPreview(\WP_REST_Request $request): \WP_REST_Response
    {
        $blockName = $request->get_param('blockName');
        $attributes = $request->get_param('attributes');

        $registry = Registry::getInstance();
        $block = $registry->getFluentBlock($blockName);

        // Try fluent block first
        if ($block) {
            // Check if block has a render template
            if (empty($block->render_template)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'error'   => 'No render template defined for block: ' . $blockName,
                ], 400);
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
                    $adapter = \HyperPress\Fields\BlockFieldAdapter::fromField($field->getHyperField(), $attributes);
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

                return new \WP_REST_Response([
                    'success' => true,
                    'html'    => $html,
                ]);
            } catch (\Exception $e) {
                return new \WP_REST_Response([
                    'success' => false,
                    'error'   => 'Rendering failed: ' . $e->getMessage(),
                ], 500);
            }
        }

        // If not a fluent block, try JSON block
        $jsonBlockPreview = $this->renderJsonBlockPreview($blockName, $attributes);
        if ($jsonBlockPreview !== null) {
            return new \WP_REST_Response($jsonBlockPreview);
        }

        return new \WP_REST_Response([
            'success' => false,
            'error'   => 'Block not found: ' . $blockName,
        ], 404);
    }

    /**
     * Render preview for JSON blocks.
     *
     * @param string $blockName The name of the JSON block.
     * @param array $attributes The block attributes.
     * @return array|null Array with success and html keys, or null if not found.
     */
    private function renderJsonBlockPreview(string $blockName, array $attributes): ?array
    {
        $blockPath = $this->findJsonBlockPath($blockName);
        if (!$blockPath) {
            return null;
        }

        $blockJsonFile = $blockPath . '/block.json';
        if (!file_exists($blockJsonFile)) {
            return null;
        }

        $metadata = json_decode(file_get_contents($blockJsonFile), true);
        if (!$metadata) {
            return null;
        }

        // Check if there's a render.php file
        $renderFile = $blockPath . '/render.php';
        if (!file_exists($renderFile)) {
            return [
                'success' => false,
                'error' => 'No render.php file found for JSON block: ' . $blockName,
            ];
        }

        try {
            $renderer = new Renderer();
            $html = $renderer->render('file:' . $renderFile, $attributes);

            return [
                'success' => true,
                'html' => $html,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Rendering failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find the path to a JSON block directory.
     *
     * @param string $blockName The name of the block.
     * @return string|null The path to the block directory or null if not found.
     */
    private function findJsonBlockPath(string $blockName): ?string
    {
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
     * Generate a human-readable label from a field name.
     *
     * @param string $fieldName The field name.
     * @return string The formatted label.
     */
    private function generateFieldLabel(string $fieldName): string
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
    private function mapAttributeTypeToFieldType(string $attributeType): string
    {
        switch ($attributeType) {
            case 'string':
                return 'text';
            case 'boolean':
                return 'checkbox';
            case 'number':
                return 'number';
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
