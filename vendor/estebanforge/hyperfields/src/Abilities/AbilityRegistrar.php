<?php

declare(strict_types=1);

/**
 * Abilities API registrar for HyperFields.
 *
 * Exposes registered options pages as WordPress Abilities (core 6.9+):
 * page/field discovery with JSON Schema per field, field reads, and a
 * single-field write that runs through the exact sanitize/validate/pre_save
 * pipeline the Settings-API save uses.
 *
 * Permissions are per page and resolved at execution time from the page's
 * own capability (OptionsPage::getCapability()), so a page registered with
 * a lesser capability never leaks through a blanket gate. Unknown pages
 * fail closed.
 *
 * Posture: register everything, expose nothing. show_in_rest stays false
 * and the MCP public flag is never set unless a site opts in through the
 * dedicated filters.
 *
 * @since 1.7.0
 */

namespace HyperFields\Abilities;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERFIELDS_TESTING_MODE')) {
    return;
}

/**
 * Registers HyperFields options-page abilities on the Abilities API.
 */
final class AbilityRegistrar
{
    /**
     * Ability namespace. Mirrors the plugin slug per the Abilities API
     * naming convention.
     */
    public const NAMESPACE_SLUG = 'hyperfields';

    /**
     * Ability category shared by all HyperFields abilities.
     */
    public const CATEGORY = 'hyperfields';

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
        if (!apply_filters('hyperfields/abilities/enabled', true)) {
            return;
        }

        add_action('wp_abilities_api_categories_init', [self::class, 'registerCategories']);
        add_action('wp_abilities_api_init', [self::class, 'registerAbilities']);
    }

    /**
     * Register the HyperFields ability category.
     *
     * @return void
     */
    public static function registerCategories(): void
    {
        wp_register_ability_category(
            self::CATEGORY,
            [
                'label'       => __('HyperFields', 'hyperfields'),
                'description' => __('HyperFields options pages: page/field discovery with JSON Schema, field reads, and single-field writes through each page\'s own sanitization pipeline.', 'hyperfields'),
            ]
        );
    }

    /**
     * Register the HyperFields abilities.
     *
     * @return void
     */
    public static function registerAbilities(): void
    {
        wp_register_ability(
            self::NAMESPACE_SLUG . '/list-option-pages',
            self::abilityArgs(
                [
                    'label'               => __('List Option Pages', 'hyperfields'),
                    'description'         => __('Lists every registered HyperFields options page with its slug, option group, capability, and field inventory (name, label, type, JSON Schema). Use the page slug with hyperfields/get-option and hyperfields/update-option to read or write individual fields. The inventory reflects the sections registered on this request: pages using conditional sections only expose fields whose conditions match the currently saved values, so re-call this after changing a field that other fields depend on.', 'hyperfields'),
                    'category'            => self::CATEGORY,
                    'output_schema'       => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'page'         => [
                                    'type'        => 'string',
                                    'description' => __('Page slug used by the other hyperfields abilities.', 'hyperfields'),
                                ],
                                'title'        => [
                                    'type'        => 'string',
                                    'description' => __('Admin page title.', 'hyperfields'),
                                ],
                                'option_group' => [
                                    'type'        => 'string',
                                    'description' => __('Option name storing the page values.', 'hyperfields'),
                                ],
                                'capability'   => [
                                    'type'        => 'string',
                                    'description' => __('Capability required to read or write this page.', 'hyperfields'),
                                ],
                                'fields'       => [
                                    'type'        => 'array',
                                    'items'       => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'name'   => ['type' => 'string'],
                                            'label'  => ['type' => 'string'],
                                            'type'   => ['type' => 'string'],
                                            'schema' => [
                                                'description' => __('JSON Schema for the stored value; null when the type has no derivable schema.', 'hyperfields'),
                                            ],
                                        ],
                                        'required'   => ['name', 'label', 'type'],
                                    ],
                                    'description' => __('Field inventory of the page.', 'hyperfields'),
                                ],
                            ],
                            'required'   => ['page', 'title', 'option_group', 'capability', 'fields'],
                        ],
                    ],
                    'execute_callback'    => [self::class, 'executeListOptionPages'],
                    'permission_callback' => [self::class, 'currentUserCanManageOptions'],
                ]
            )
        );

        wp_register_ability(
            self::NAMESPACE_SLUG . '/get-option',
            self::abilityArgs(
                [
                    'label'               => __('Get Option Field', 'hyperfields'),
                    'description'         => __('Reads one field value from a registered HyperFields options page. The page must be registered (see hyperfields/list-option-pages); the stored value is returned, falling back to the field default when nothing is saved yet.', 'hyperfields'),
                    'category'            => self::CATEGORY,
                    'input_schema'        => [
                        'type'                 => 'object',
                        'properties'           => [
                            'page'  => [
                                'type'        => 'string',
                                'minLength'   => 1,
                                'description' => __('Page slug from hyperfields/list-option-pages.', 'hyperfields'),
                            ],
                            'field' => [
                                'type'        => 'string',
                                'minLength'   => 1,
                                'description' => __('Field name (as stored, prefix included).', 'hyperfields'),
                            ],
                        ],
                        'required'             => ['page', 'field'],
                        'additionalProperties' => false,
                    ],
                    'output_schema'       => [
                        'type'       => 'object',
                        'properties' => [
                            'value' => [
                                'description' => __('The stored value, or the field default when unset.', 'hyperfields'),
                            ],
                        ],
                        'required'   => ['value'],
                    ],
                    'execute_callback'    => [self::class, 'executeGetOption'],
                    'permission_callback' => [self::class, 'currentUserCanForPage'],
                ]
            )
        );

        wp_register_ability(
            self::NAMESPACE_SLUG . '/update-option',
            self::abilityArgs(
                [
                    'label'               => __('Update Option Field', 'hyperfields'),
                    'description'         => __('Writes one field value on a registered HyperFields options page. The value is coerced through the field\'s own sanitization pipeline (wps_sanitize / Field::sanitizeValue / wps_validate / pre_save filter) before storage: malformed input is coerced to the field\'s fallback (for example 0 for number fields) rather than rejected, exactly like a form save of that single field. Repeat writes with the same value have no additional effect. The field inventory is request-scoped: after changing a field that other fields depend on, re-call hyperfields/list-option-pages.', 'hyperfields'),
                    'category'            => self::CATEGORY,
                    'input_schema'        => [
                        'type'                 => 'object',
                        'properties'           => [
                            'page'  => [
                                'type'        => 'string',
                                'minLength'   => 1,
                                'description' => __('Page slug from hyperfields/list-option-pages.', 'hyperfields'),
                            ],
                            'field' => [
                                'type'        => 'string',
                                'minLength'   => 1,
                                'description' => __('Field name (as stored, prefix included).', 'hyperfields'),
                            ],
                            'value' => [
                                'description' => __('New value; must match the field\'s JSON Schema (see hyperfields/list-option-pages).', 'hyperfields'),
                            ],
                        ],
                        'required'             => ['page', 'field', 'value'],
                        'additionalProperties' => false,
                    ],
                    'output_schema'       => [
                        'type'       => 'object',
                        'properties' => [
                            'success'       => [
                                'type'        => 'boolean',
                                'description' => __('Whether the value was written.', 'hyperfields'),
                            ],
                            'field_default' => [
                                'description' => __('The field default, for reference when building future writes.', 'hyperfields'),
                            ],
                        ],
                        'required'   => ['success'],
                    ],
                    // Writing the same value twice has no additional effect.
                    // Not readonly: it persists. Not destructive: it only ever
                    // sets one known field.
                    'meta'                => [
                        'annotations' => [
                            'readonly'    => false,
                            'destructive' => false,
                            'idempotent'  => true,
                        ],
                    ],
                    'execute_callback'    => [self::class, 'executeUpdateOption'],
                    'permission_callback' => [self::class, 'currentUserCanForPage'],
                ]
            )
        );
    }

    /**
     * Complete ability args: caller args plus the mandatory explicit meta.
     *
     * Deliberately package-local: HyperFields cannot depend on HyperPress-
     * Core (circular), and each package owns its exposure filters.
     *
     * @param array $args Caller-provided args.
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

        $meta['show_in_rest'] = (bool) apply_filters('hyperfields/abilities/expose_rest', false);

        if ((bool) apply_filters('hyperfields/abilities/mcp_public', false)) {
            $meta['mcp'] = ['public' => true];
        }

        $args['meta'] = $meta;

        return $args;
    }

    /**
     * Execute callback: hyperfields/list-option-pages.
     *
     * @param mixed $input Unused; the ability takes no input.
     * @return array<int, array<string, mixed>>
     */
    public static function executeListOptionPages($input = null): array
    {
        $pages = [];

        foreach (\HyperFields\OptionsPage::getRegisteredPages() as $page) {
            $fields = [];
            foreach ($page->allFields() as $field) {
                $entry = [
                    'name'  => $field->getName(),
                    'label' => $field->getLabel(),
                    'type'  => $field->getType(),
                ];

                $schema = $field->toJsonSchema();
                if ($schema !== null) {
                    $entry['schema'] = $schema;
                }

                $fields[] = $entry;
            }

            $pages[] = [
                'page'         => $page->getMenuSlug(),
                'title'        => $page->getPageTitle(),
                'option_group' => $page->getOptionName(),
                'capability'   => $page->getCapability(),
                'fields'       => $fields,
            ];
        }

        usort($pages, static fn (array $a, array $b): int => strcmp($a['page'], $b['page']));

        return $pages;
    }

    /**
     * Execute callback: hyperfields/get-option.
     *
     * @param mixed $input {page: string, field: string}.
     * @return array|WP_Error {value}, or error when page/field unknown.
     */
    public static function executeGetOption($input = null)
    {
        $page = self::resolvePage(is_array($input) ? (string) ($input['page'] ?? '') : '');
        $field = $page !== null ? $page->findField(is_array($input) ? (string) ($input['field'] ?? '') : '') : null;

        if ($page === null || $field === null) {
            return self::notFoundError($page === null ? 'page' : 'field', $input);
        }

        $values = get_option($page->getOptionName(), []);
        $value = is_array($values) && array_key_exists($field->getName(), $values)
            ? $values[$field->getName()]
            : $field->getDefault();

        return ['value' => $value];
    }

    /**
     * Execute callback: hyperfields/update-option.
     *
     * @param mixed $input {page: string, field: string, value: mixed}.
     * @return array|WP_Error {success, field_default?}, or error.
     */
    public static function executeUpdateOption($input = null)
    {
        $page = self::resolvePage(is_array($input) ? (string) ($input['page'] ?? '') : '');
        $fieldName = is_array($input) ? (string) ($input['field'] ?? '') : '';
        $field = $page !== null ? $page->findField($fieldName) : null;

        if ($page === null || $field === null) {
            return self::notFoundError($page === null ? 'page' : 'field', $input);
        }

        $value = is_array($input) ? ($input['value'] ?? null) : null;

        $written = $page->setFieldValue($fieldName, $value);

        if (!$written) {
            return new \WP_Error(
                'hyperfields_validation_failed',
                sprintf(__('The value for field "%s" failed validation or no change was written.', 'hyperfields'), $fieldName)
            );
        }

        return [
            'success'       => true,
            'field_default' => $field->getDefault(),
        ];
    }

    /**
     * Permission callback: site-level topology listing.
     *
     * @param mixed $input Unused.
     * @return bool
     */
    public static function currentUserCanManageOptions($input = null): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Permission callback: per-page capability, resolved from the input at
     * execution time. Unknown pages fail closed.
     *
     * @param mixed $input {page: string}.
     * @return bool
     */
    public static function currentUserCanForPage($input = null): bool
    {
        $page = self::resolvePage(is_array($input) ? (string) ($input['page'] ?? '') : '');

        if ($page === null) {
            return false;
        }

        return current_user_can($page->getCapability());
    }

    /**
     * Resolve a registered page by menu slug.
     *
     * @param string $slug Menu slug.
     * @return \HyperFields\OptionsPage|null
     */
    private static function resolvePage(string $slug): ?\HyperFields\OptionsPage
    {
        if ($slug === '') {
            return null;
        }

        return \HyperFields\OptionsPage::getRegisteredPages()[$slug] ?? null;
    }

    /**
     * Consistent not-found error. Same code and message for unknown pages
     * and unknown fields: nothing about the site's surface leaks to a
     * caller that cannot resolve it.
     *
     * @param string $kind  'page' or 'field'.
     * @param mixed  $input Raw ability input.
     * @return WP_Error
     */
    private static function notFoundError(string $kind, $input): \WP_Error
    {
        $identifier = '';
        if (is_array($input)) {
            $identifier = (string) ($input['field'] ?? $input['page'] ?? '');
        }

        return new \WP_Error(
            'hyperfields_not_found',
            sprintf(__('Unknown %1$s: %2$s', 'hyperfields'), $kind, $identifier)
        );
    }
}
