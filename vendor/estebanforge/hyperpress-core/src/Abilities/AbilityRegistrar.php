<?php

declare(strict_types=1);

/**
 * Abilities API registrar for HyperPress.
 *
 * Exposes read-only HyperPress platform information as WordPress Abilities
 * (core 6.9+) so external systems and AI agents can discover the site's
 * hypermedia surface: which library is active, which hypermedia templates
 * the /wp-html/v1 endpoint serves, and which extensions are enabled.
 *
 * Posture (see docs/ABILITIES-API-ADOPTION-PLAN.md): register everything,
 * expose nothing by default. show_in_rest stays false and the MCP public
 * flag is never set unless a site opts in through the dedicated filters.
 *
 * @since 3.6.0
 */

namespace HyperPress\Abilities;

use HyperPress\Config;
use HyperPress\OptionsResolver;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * Registers HyperPress read-only abilities on the Abilities API.
 *
 * The payload builders (configPayload, extensionStatusPayload,
 * endpointsPayload) are kept free of WordPress-callable dependencies where
 * possible so unit tests can target them without a WP_Ability class.
 */
final class AbilityRegistrar
{
    /**
     * Ability namespace. Mirrors the plugin slug per the Abilities API
     * naming convention.
     */
    public const NAMESPACE_SLUG = 'hyperpress';

    /**
     * Ability category shared by all HyperPress abilities.
     */
    public const CATEGORY = 'hyperpress';

    /**
     * Wire the registrar onto the Abilities API init hooks.
     *
     * Runs from Bootstrap::init() before the background/API early-return
     * guard: the /wp-abilities/v1 controller serves abilities during REST
     * requests, so registering only after that guard would hide every
     * ability from exactly the consumers this module exists for.
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
        if (!apply_filters('hyperpress/abilities/enabled', true)) {
            return;
        }

        add_action('wp_abilities_api_categories_init', [self::class, 'registerCategories']);
        add_action('wp_abilities_api_init', [self::class, 'registerAbilities']);

        // Note: LogObserver is NOT wired here on purpose. It is a generic
        // WP_DEBUG logger for every ability on the site and must survive the
        // kill switch; Bootstrap wires it independently.
    }

    /**
     * Register the HyperPress ability category.
     *
     * @return void
     */
    public static function registerCategories(): void
    {
        wp_register_ability_category(
            self::CATEGORY,
            [
                'label'       => __('HyperPress', 'api-for-htmx'),
                'description' => __('HyperPress platform information: active hypermedia library, served hypermedia templates, and extension status.', 'api-for-htmx'),
            ]
        );
    }

    /**
     * Register the HyperPress abilities.
     *
     * @return void
     */
    public static function registerAbilities(): void
    {
        wp_register_ability(
            self::NAMESPACE_SLUG . '/get-config',
            self::abilityArgs(
                [
                    'label'               => __('Get HyperPress Config', 'api-for-htmx'),
                    'description'         => __('Returns the active hypermedia library (htmx or Datastar), the htmx major version, the hypermedia endpoint slug, template directory and extensions, and the HyperPress version. An agent needs the active library to generate correct markup (hx-* attributes versus data-* signals).', 'api-for-htmx'),
                    'category'            => self::CATEGORY,
                    'output_schema'       => [
                        'type'       => 'object',
                        'properties' => [
                            'active_library'      => [
                                'type'        => 'string',
                                'enum'        => ['htmx', 'datastar'],
                                'description' => __('Active hypermedia library.', 'api-for-htmx'),
                            ],
                            'htmx_version'        => [
                                'type'        => 'string',
                                'enum'        => ['2', '4'],
                                'description' => __('htmx major version when active_library is htmx.', 'api-for-htmx'),
                            ],
                            'endpoint'            => [
                                'type'        => 'string',
                                'description' => __('Hypermedia endpoint slug, relative to the site URL (for example wp-html/v1).', 'api-for-htmx'),
                            ],
                            'endpoint_url'        => [
                                'type'        => 'string',
                                'format'      => 'uri',
                                'description' => __('Absolute hypermedia endpoint base URL.', 'api-for-htmx'),
                            ],
                            'template_dir'        => [
                                'type'        => 'string',
                                'description' => __('Theme subdirectory holding hypermedia templates.', 'api-for-htmx'),
                            ],
                            'template_extensions' => [
                                'type'        => 'array',
                                'items'       => ['type' => 'string'],
                                'description' => __('File extensions served as hypermedia templates.', 'api-for-htmx'),
                            ],
                            'version'             => [
                                'type'        => 'string',
                                'description' => __('HyperPress-Core version.', 'api-for-htmx'),
                            ],
                        ],
                        'required'   => ['active_library', 'endpoint', 'version'],
                    ],
                    'execute_callback'    => [self::class, 'executeGetConfig'],
                    'permission_callback' => [self::class, 'currentUserCanManageOptions'],
                ]
            )
        );

        wp_register_ability(
            self::NAMESPACE_SLUG . '/list-endpoints',
            self::abilityArgs(
                [
                    'label'               => __('List Hypermedia Endpoints', 'api-for-htmx'),
                    'description'         => __('Lists the hypermedia templates this site serves under the /wp-html/v1/ endpoint: the theme hypermedia directory, the legacy directory, and any namespaced template paths registered via the hyperpress/render/register_template_path filter. Each entry carries the template name to call and its ready-to-use endpoint URL.', 'api-for-htmx'),
                    'category'            => self::CATEGORY,
                    'output_schema'       => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name'      => [
                                    'type'        => 'string',
                                    'description' => __('Template name to append to the endpoint URL (or namespace:name for registered paths).', 'api-for-htmx'),
                                ],
                                'url'       => [
                                    'type'        => 'string',
                                    'format'      => 'uri',
                                    'description' => __('Absolute endpoint URL for this template.', 'api-for-htmx'),
                                ],
                                'source'    => [
                                    'type'        => 'string',
                                    'enum'        => ['theme', 'legacy', 'namespace'],
                                    'description' => __('Where the template was discovered.', 'api-for-htmx'),
                                ],
                                'file'      => [
                                    'type'        => 'string',
                                    'description' => __('Template file name including its extension.', 'api-for-htmx'),
                                ],
                                'namespace' => [
                                    'type'        => 'string',
                                    'description' => __('Registered namespace, present only for namespaced templates.', 'api-for-htmx'),
                                ],
                            ],
                            'required'   => ['name', 'url', 'source'],
                        ],
                    ],
                    'execute_callback'    => [self::class, 'executeListEndpoints'],
                    'permission_callback' => [self::class, 'currentUserCanEditPosts'],
                ]
            )
        );

        wp_register_ability(
            self::NAMESPACE_SLUG . '/get-extension-status',
            self::abilityArgs(
                [
                    'label'               => __('Get Extension Status', 'api-for-htmx'),
                    'description'         => __('Returns the enabled/disabled state of every HyperPress hypermedia extension that has a stored option (load_extension_* keys). Extensions absent from the map default to disabled.', 'api-for-htmx'),
                    'category'            => self::CATEGORY,
                    'output_schema'       => [
                        'type'                 => 'object',
                        'additionalProperties' => [
                            'type'        => 'boolean',
                            'description' => __('Whether the extension is enabled.', 'api-for-htmx'),
                        ],
                    ],
                    'execute_callback'    => [self::class, 'executeGetExtensionStatus'],
                    'permission_callback' => [self::class, 'currentUserCanEditPosts'],
                ]
            )
        );
    }

    /**
     * Complete ability args: caller args plus the mandatory explicit meta.
     *
     * Exposure is opt-in per site through two filters; both default to
     * hidden because registered-but-hidden is the safe default for a
     * library shipped to third-party sites.
     *
     * @param array $args Caller-provided args (label, description, category,
     *                    output_schema, execute_callback, permission_callback).
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

        $meta['show_in_rest'] = (bool) apply_filters('hyperpress/abilities/expose_rest', false);

        if ((bool) apply_filters('hyperpress/abilities/mcp_public', false)) {
            $meta['mcp'] = ['public' => true];
        }

        $args['meta'] = $meta;

        return $args;
    }

    /**
     * Execute callback: hyperpress/get-config.
     *
     * @param mixed $input Unused; the ability takes no input.
     * @return array
     */
    public static function executeGetConfig($input = null): array
    {
        return self::configPayload() + [
            'endpoint_url' => hp_get_endpoint_url(),
        ];
    }

    /**
     * Execute callback: hyperpress/list-endpoints.
     *
     * @param mixed $input Unused; the ability takes no input.
     * @return array
     */
    public static function executeListEndpoints($input = null): array
    {
        return self::endpointsPayload();
    }

    /**
     * Execute callback: hyperpress/get-extension-status.
     *
     * @param mixed $input Unused; the ability takes no input.
     * @return array
     */
    public static function executeGetExtensionStatus($input = null): array
    {
        return self::extensionStatusPayload();
    }

    /**
     * Permission callback: site-level configuration.
     *
     * @param mixed $input Unused.
     * @return bool
     */
    public static function currentUserCanManageOptions($input = null): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Permission callback: content-level reads.
     *
     * @param mixed $input Unused.
     * @return bool
     */
    public static function currentUserCanEditPosts($input = null): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Canonical HyperPress configuration payload.
     *
     * @return array
     */
    public static function configPayload(): array
    {
        $options = OptionsResolver::resolve();

        return [
            'active_library'      => (string) ($options['active_library'] ?? 'datastar'),
            'htmx_version'        => (string) ($options['htmx_version'] ?? '4'),
            'endpoint'            => Config::ENDPOINT . '/' . Config::ENDPOINT_VERSION,
            'template_dir'        => Config::TEMPLATE_DIR,
            'template_extensions' => array_values(array_filter(explode(',', Config::TEMPLATE_EXT))),
            'version'             => Config::VERSION,
        ];
    }

    /**
     * Extension status payload from the resolved options.
     *
     * @return array<string, bool>
     */
    public static function extensionStatusPayload(): array
    {
        return self::extensionStatusFromOptions(OptionsResolver::resolve());
    }

    /**
     * Filter load_extension_* keys out of an options array.
     *
     * Pure function, separated from extensionStatusPayload() so unit tests
     * can cover it without stored options.
     *
     * @param array $options Resolved HyperPress options.
     * @return array<string, bool> Map of load_extension_* key to enabled state.
     */
    public static function extensionStatusFromOptions(array $options): array
    {
        $status = [];
        foreach ($options as $key => $value) {
            if (is_string($key) && strpos($key, 'load_extension_') === 0) {
                $status[$key] = (bool) $value;
            }
        }

        ksort($status);

        return $status;
    }

    /**
     * Hypermedia template inventory payload.
     *
     * Mirrors Render::getTemplateFile() discovery: the active theme's
     * hypermedia directory, the legacy directory, and namespaced paths
     * registered via the hyperpress/render/register_template_path filter.
     *
     * @param string|null $theme_dir Theme root override (tests). Defaults to
     *                               the active theme directory.
     * @return array<int, array<string, string>>
     */
    public static function endpointsPayload(?string $theme_dir = null): array
    {
        $entries = [];

        $theme_dir ??= self::themeDir();
        $default_paths = [
            $theme_dir . '/' . Config::TEMPLATE_DIR . '/',
            $theme_dir . '/' . Config::LEGACY_TEMPLATE_DIR . '/',
        ];

        // Shared with Render::getTemplateFile(), which passes flat path
        // strings: keep that exact payload shape so existing subscribers of
        // this public filter cannot fatal when this ability runs.
        $default_paths = apply_filters('hyperpress/render/get_template_file/templates_path', $default_paths);

        foreach ((array) $default_paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $dir = trailingslashit($path);
            $source = str_ends_with(rtrim($dir, '/'), '/' . Config::LEGACY_TEMPLATE_DIR) ? 'legacy' : 'theme';

            foreach (self::scanTemplateDir($dir) as $entry) {
                $entry['source'] = $source;
                $entry['url'] = hp_get_endpoint_url($entry['name']);
                $entries[] = $entry;
            }
        }

        $namespaced_paths = apply_filters('hyperpress/render/register_template_path', []);
        foreach ((array) $namespaced_paths as $namespace => $dir) {
            if (!is_string($namespace) || $namespace === '' || !is_string($dir) || $dir === '') {
                continue;
            }

            foreach (self::scanTemplateDir(trailingslashit($dir)) as $entry) {
                $entry['source'] = 'namespace';
                $entry['namespace'] = $namespace;
                $entry['url'] = hp_get_endpoint_url($namespace . ':' . $entry['name']);
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Active (child) theme directory, mirroring Render::getThemePath().
     *
     * @return string Directory without trailing slash.
     */
    private static function themeDir(): string
    {
        $theme_path = trailingslashit(get_template_directory());

        if (is_child_theme()) {
            $theme_path = trailingslashit(get_stylesheet_directory());
        }

        return rtrim($theme_path, '/\\');
    }

    /**
     * Recursively scan a template directory for hypermedia template files.
     *
     * @param string $base_dir Directory with trailing slash.
     * @return array<int, array{name: string, file: string}> Template names
     *                                                      (extension stripped) and file names, sorted by name.
     */
    private static function scanTemplateDir(string $base_dir): array
    {
        if (!is_dir($base_dir)) {
            return [];
        }

        $extensions = array_merge(
            array_filter(explode(',', Config::TEMPLATE_EXT)),
            array_filter(explode(',', Config::LEGACY_TEMPLATE_EXT))
        );

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base_dir, \FilesystemIterator::SKIP_DOTS)
        );

        $entries = [];
        foreach ($iterator as $file_info) {
            if (!$file_info->isFile()) {
                continue;
            }

            $file_name = $file_info->getFilename();
            $extension = self::matchingExtension($file_name, $extensions);
            if ($extension === null) {
                continue;
            }

            $relative = substr($file_info->getPathname(), strlen($base_dir));

            $entries[] = [
                'name' => str_replace('\\', '/', substr($relative, 0, -strlen($extension))),
                'file' => str_replace('\\', '/', $relative),
            ];
        }

        usort($entries, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $entries;
    }

    /**
     * Return the template extension a file name ends with, if any.
     *
     * @param string   $file_name  File name.
     * @param string[] $extensions Extensions including leading dots.
     * @return string|null
     */
    private static function matchingExtension(string $file_name, array $extensions): ?string
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($file_name, $extension)) {
                return $extension;
            }
        }

        return null;
    }
}
