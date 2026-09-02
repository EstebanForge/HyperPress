<?php

declare(strict_types=1);

/**
 * Abilities API registrar for HyperBlocks.
 *
 * Mirrors the tested REST surface (hyperblocks/v1) as WordPress Abilities
 * (core 6.9+) so agents and external tools can discover the block library
 * and render server-side previews. REST and ability callbacks both call
 * BlockOperations: one implementation, two surfaces.
 *
 * Posture (see the HyperPress adoption plan): register everything, expose
 * nothing by default. show_in_rest stays false and the MCP public flag is
 * never set unless a site opts in through the dedicated filters.
 *
 * @since 1.7.0
 */

namespace HyperBlocks\Abilities;

use HyperBlocks\BlockOperations;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Registers HyperBlocks read/preview abilities on the Abilities API.
 */
final class AbilityRegistrar
{
    /**
     * Ability namespace. Mirrors the plugin slug per the Abilities API
     * naming convention.
     */
    public const NAMESPACE_SLUG = 'hyperblocks';

    /**
     * Ability category shared by all HyperBlocks abilities.
     */
    public const CATEGORY = 'hyperblocks';

    /**
     * Wire the registrar onto the Abilities API init hooks.
     *
     * @return void
     */
    public static function init(): void
    {
        // WP < 6.9: the whole module is a silent no-op. The libraries keep
        // their usual minimum WP version; only this feature needs 6.9.
        if (!class_exists(\WP_Ability::class)) {
            return;
        }

        // Kill switch: lets a site disable registration entirely,
        // independent of REST/MCP exposure.
        if (!apply_filters('hyperblocks/abilities/enabled', true)) {
            return;
        }

        add_action('wp_abilities_api_categories_init', [self::class, 'registerCategories']);
        add_action('wp_abilities_api_init', [self::class, 'registerAbilities']);
    }

    /**
     * Register the HyperBlocks ability category.
     *
     * @return void
     */
    public static function registerCategories(): void
    {
        wp_register_ability_category(
            self::CATEGORY,
            [
                'label'       => __('HyperBlocks', 'hyperblocks'),
                'description' => __('HyperBlocks block library: block inventory, field definitions, and server-side preview rendering.', 'hyperblocks'),
            ]
        );
    }

    /**
     * Register the HyperBlocks abilities.
     *
     * @return void
     */
    public static function registerAbilities(): void
    {
        wp_register_ability(
            self::NAMESPACE_SLUG . '/list-blocks',
            self::abilityArgs(
                [
                    'label'               => __('List Blocks', 'hyperblocks'),
                    'description'         => __('Lists every registered HyperBlocks block, fluent and JSON, with its title and whether it has a render template. Pair with hyperblocks/get-block-fields and hyperblocks/render-preview to inspect and preview a block.', 'hyperblocks'),
                    'category'            => self::CATEGORY,
                    'output_schema'       => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name'                => [
                                    'type'        => 'string',
                                    'description' => __('Block name (namespace/slug).', 'hyperblocks'),
                                ],
                                'title'               => [
                                    'type'        => 'string',
                                    'description' => __('Human-readable block title.', 'hyperblocks'),
                                ],
                                'source'              => [
                                    'type'        => 'string',
                                    'enum'        => ['fluent', 'json'],
                                    'description' => __('How the block is defined: fluent PHP API or block.json.', 'hyperblocks'),
                                ],
                                'has_render_template' => [
                                    'type'        => 'boolean',
                                    'description' => __('Whether the block can be preview-rendered.', 'hyperblocks'),
                                ],
                            ],
                            'required'   => ['name', 'title', 'source', 'has_render_template'],
                        ],
                    ],
                    'execute_callback'    => [self::class, 'executeListBlocks'],
                    'permission_callback' => [self::class, 'currentUserCanEditPosts'],
                ]
            )
        );

        wp_register_ability(
            self::NAMESPACE_SLUG . '/get-block-fields',
            self::abilityArgs(
                [
                    'label'               => __('Get Block Fields', 'hyperblocks'),
                    'description'         => __('Returns the field definitions for one HyperBlocks block (fluent or JSON), matching the hyperblocks/v1 REST /block-fields response. Use hyperblocks/list-blocks first to discover block names.', 'hyperblocks'),
                    'category'            => self::CATEGORY,
                    'input_schema'        => [
                        'type'                 => 'object',
                        'properties'           => [
                            'name' => [
                                'type'        => 'string',
                                'minLength'   => 1,
                                'description' => __('Block name (namespace/slug).', 'hyperblocks'),
                            ],
                        ],
                        'required'             => ['name'],
                        'additionalProperties' => false,
                    ],
                    'output_schema'       => [
                        'type'        => 'array',
                        'items'       => [
                            'type' => 'object',
                        ],
                        'description' => __('Field definition objects.', 'hyperblocks'),
                    ],
                    'execute_callback'    => [self::class, 'executeGetBlockFields'],
                    'permission_callback' => [self::class, 'currentUserCanEditPosts'],
                ]
            )
        );

        wp_register_ability(
            self::NAMESPACE_SLUG . '/render-preview',
            self::abilityArgs(
                [
                    'label'               => __('Render Block Preview', 'hyperblocks'),
                    'description'         => __('Server-side renders one HyperBlocks block with the given attributes and returns the HTML, mirroring the hyperblocks/v1 REST /render-preview endpoint. Attributes are sanitized and validated against the block field definitions before rendering; invalid values fall back to field defaults.', 'hyperblocks'),
                    'category'            => self::CATEGORY,
                    'input_schema'        => [
                        'type'                 => 'object',
                        'properties'           => [
                            'blockName'  => [
                                'type'        => 'string',
                                'minLength'   => 1,
                                'description' => __('Block name (namespace/slug).', 'hyperblocks'),
                            ],
                            'attributes' => [
                                'type'        => 'object',
                                'description' => __('Block attributes keyed by field name.', 'hyperblocks'),
                            ],
                        ],
                        'required'             => ['blockName', 'attributes'],
                        'additionalProperties' => false,
                    ],
                    'output_schema'       => [
                        'type'       => 'object',
                        'properties' => [
                            'success' => [
                                'type'        => 'boolean',
                                'description' => __('Whether the preview rendered.', 'hyperblocks'),
                            ],
                            'html'    => [
                                'type'        => 'string',
                                'description' => __('Rendered preview HTML when success is true.', 'hyperblocks'),
                            ],
                            'error'   => [
                                'type'        => 'string',
                                'description' => __('Error message when success is false.', 'hyperblocks'),
                            ],
                        ],
                        'required'   => ['success'],
                    ],
                    // Renders HTML but writes nothing: additive-only and
                    // repeatable, so idempotent despite not being readonly.
                    'meta'                => [
                        'annotations' => [
                            'readonly'    => false,
                            'destructive' => false,
                            'idempotent'  => true,
                        ],
                    ],
                    'execute_callback'    => [self::class, 'executeRenderPreview'],
                    'permission_callback' => [self::class, 'currentUserCanEditPosts'],
                ]
            )
        );
    }

    /**
     * Complete ability args: caller args plus the mandatory explicit meta.
     *
     * Deliberately package-local (not shared with HyperPress-Core):
     * HyperBlocks cannot depend on HyperPress-Core without a circular
     * dependency, and each package owns its exposure filters.
     *
     * @param array $args Caller-provided args (label, description, category,
     *                    schemas, callbacks, optional meta annotations).
     * @return array
     */
    public static function abilityArgs(array $args): array
    {
        $meta = $args['meta'] ?? [];

        // Annotations are always explicit: destructive defaults to true in
        // the API, which would map unannotated abilities to DELETE on REST.
        $meta['annotations'] = array_merge(
            [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ],
            $meta['annotations'] ?? []
        );

        $meta['show_in_rest'] = (bool) apply_filters('hyperblocks/abilities/expose_rest', false);

        if ((bool) apply_filters('hyperblocks/abilities/mcp_public', false)) {
            $meta['mcp'] = ['public' => true];
        }

        $args['meta'] = $meta;

        return $args;
    }

    /**
     * Execute callback: hyperblocks/list-blocks.
     *
     * @param mixed $input Unused; the ability takes no input.
     * @return array
     */
    public static function executeListBlocks($input = null): array
    {
        return BlockOperations::listBlocks();
    }

    /**
     * Execute callback: hyperblocks/get-block-fields.
     *
     * @param mixed $input {name: string}.
     * @return array|WP_Error Field definitions, or error when unknown.
     */
    public static function executeGetBlockFields($input = null)
    {
        $name = is_array($input) ? (string) ($input['name'] ?? '') : '';

        $fields = BlockOperations::getFields($name);

        if ($fields === null) {
            return new \WP_Error(
                'hyperblocks_block_not_found',
                sprintf(__('Block not found: %s', 'hyperblocks'), $name)
            );
        }

        return $fields;
    }

    /**
     * Execute callback: hyperblocks/render-preview.
     *
     * @param mixed $input {blockName: string, attributes: object}.
     * @return array|WP_Error {success, html?, error?}, or error when unknown.
     */
    public static function executeRenderPreview($input = null)
    {
        $blockName = is_array($input) ? (string) ($input['blockName'] ?? '') : '';
        $attributes = is_array($input) && is_array($input['attributes'] ?? null) ? $input['attributes'] : [];

        $result = BlockOperations::preview($blockName, $attributes);

        if ($result['status'] === 'not_found') {
            return new \WP_Error(
                'hyperblocks_block_not_found',
                sprintf(__('Block not found: %s', 'hyperblocks'), $blockName)
            );
        }

        if ($result['status'] === 'ok') {
            return [
                'success' => true,
                'html'    => $result['html'],
            ];
        }

        return [
            'success' => false,
            'error'   => $result['error'],
        ];
    }

    /**
     * Permission callback: content-level reads, matching the hyperblocks/v1
     * REST gates.
     *
     * @param mixed $input Unused.
     * @return bool
     */
    public static function currentUserCanEditPosts($input = null): bool
    {
        return current_user_can('edit_posts');
    }
}
