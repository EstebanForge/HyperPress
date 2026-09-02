<?php

declare(strict_types=1);

namespace HyperPress\Admin;

use HyperFields\HyperFields;
use HyperPress\Config;
use HyperPress\Libraries\HTMXLib;
use HyperPress\Main;

// Exit if accessed directly.
if (!defined('ABSPATH') && !defined('HYPERPRESS_TESTING_MODE')) {
    return;
}

/**
 * New Options Class using Hyper Fields System.
 * Replaces wp-settings dependency with our Hyper fields system.
 *
 * @since 2025-07-21
 */
class Options
{
    private string $option_name = 'hyperpress_options';
    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
        add_action('init', $this->initOptionsPage(...));
        add_action('admin_enqueue_scripts', $this->enqueueOptionsPageAssets(...));
        add_filter('plugin_action_links', $this->pluginActionLinks(...), 10, 2);
    }

    /**
     * Enqueue the options page bundle (auto-submit on library/htmx-version
     * change, copy-to-clipboard). Built by wp-scripts into assets/js; the
     * .asset.php sibling carries the dependency list and content hash.
     *
     * @param string $hook Current admin page hook suffix.
     * @return void
     */
    public function enqueueOptionsPageAssets(string $hook): void
    {
        if (!str_contains($hook, 'hyperpress-options')) {
            return;
        }

        // Library mode has no web-reachable URL; its settings page is hidden
        // anyway, so there is nothing to serve the bundle from.
        if (Config::$pluginUrl === '' || Config::$abspath === '') {
            return;
        }

        $asset_file = Config::$abspath . 'assets/js/admin-options.asset.php';
        $asset = file_exists($asset_file)
            ? include $asset_file
            : ['dependencies' => [], 'version' => Config::VERSION];

        wp_enqueue_script(
            'hyperpress-admin-options',
            Config::$pluginUrl . 'assets/js/admin-options.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }

    /**
     * Determine if the admin options page should be registered.
     *
     * Returns true in plugin mode (current behavior preserved) and false
     * in library mode unless the `hyperpress/admin/show_menu` filter returns
     * a truthy value.
     *
     * @since 1.2.0
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        // Must be called after the procedural helpers load. The boot
        // sequence guarantees this (helpers loaded inside
        // Bootstrap::init() before any Options instantiation). If this
        // fails it indicates a boot-order bug.
        if (hp_is_library_mode()) {
            return (bool) apply_filters('hyperpress/admin/show_menu', false);
        }

        return true;
    }

    /**
     * Migrate legacy hmapi_options to hyperpress_options if needed. Runs only once.
     */
    private function maybeMigrateLegacyOptions(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $old_option = get_option('hmapi_options', null);
        $new_option = get_option($this->option_name, null);

        if ($old_option !== null && (empty($new_option) || !is_array($new_option))) {
            update_option($this->option_name, $old_option, false);
            delete_option('hmapi_options');
        }
    }

    public function initOptionsPage(): void
    {
        // Library mode gate, evaluated at `init` time so library consumers
        // can register the `hyperpress/admin/show_menu` filter up to the
        // last moment (i.e. on `plugins_loaded`, `after_setup_theme`, or
        // any `init` priority strictly less than default 10).
        //
        // This callback now runs on every context (REST, WP-CLI, cron):
        // Bootstrap boots Main unconditionally so the options-page metadata
        // feeds the Abilities layer. The menu itself only renders where
        // admin_menu fires. Legacy option migration still runs only where
        // is_admin() + manage_options hold.
        if (!self::isEnabled()) {
            return;
        }

        $this->maybeMigrateLegacyOptions();

        $options = HyperFields::getOptions($this->option_name, []);

        $all_sections = array_merge(
            $this->buildGeneralTabConfig(),
            $this->buildHTMXTabConfig(),
            $this->buildAlpineTabConfig(),
            $this->buildDatastarTabConfig(),
            $this->buildAboutTabConfig()
        );

        // PHP-side tab conditionality: filter by visible_if
        $sections = [];
        foreach ($all_sections as $section) {
            if (!isset($section['visible_if'])) {
                $sections[] = $section;
                continue;
            }
            $field = $section['visible_if']['field'] ?? null;
            $value = $section['visible_if']['value'] ?? null;
            if ($field && isset($options[$field]) && $options[$field] === $value) {
                $sections[] = $section;
            }
        }

        HyperFields::registerOptionsPage([
            'title' => 'HyperPress Options',
            'slug' => 'hyperpress-options',
            'menu_title' => 'HyperPress',
            'parent_slug' => 'options-general.php',
            'capability' => 'manage_options',
            'option_name' => $this->option_name,
            'sections' => $sections,
            'footer_content' => $this->getFooterContent(),
        ]);

        // L3: run imported option values through the same Field sanitizers
        // the options page uses. The Data Tools import validates against a
        // self-attested schema in the upload; this filter is the field-level
        // backstop for HyperPress's own option group.
        add_filter('hyperfields/import/sanitize_fields', function (array $fields, string $optionName) use ($all_sections): array {
            if ($optionName !== $this->option_name) {
                return $fields;
            }

            foreach ($all_sections as $section) {
                foreach ($section['fields'] ?? [] as $field) {
                    if (!is_array($field)) {
                        continue;
                    }
                    $type = $field['type'] ?? null;
                    $name = $field['name'] ?? null;
                    if (!is_string($type) || !is_string($name) || $name === '') {
                        continue;
                    }
                    try {
                        $fields[$name] = \HyperFields\Field::make($type, $name, is_string($field['label'] ?? null) ? $field['label'] : $name);
                    } catch (\InvalidArgumentException $e) {
                        // Unknown field type in a section config: leave that
                        // key unsanitized rather than breaking the import.
                    }
                }
            }

            return $fields;
        }, 10, 2);
    }

    private function buildGeneralTabConfig(): array
    {
        return [
            [
                'id' => 'general_settings',
                'title' => __('General Settings', 'api-for-htmx'),
                'description' => __('Configure the general settings for the HyperPress plugin.', 'api-for-htmx'),
                'fields' => [
                    [
                        'type' => 'html',
                        'name' => 'api_endpoint',
                        'label' => '',
                        'html_content' => $this->renderApiEndpointHtml(),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'active_library',
                        'label' => __('Active Library', 'api-for-htmx'),
                        'options' => [
                            'datastar' => 'Datastar',
                            'htmx' => 'HTMX',
                            'alpine-ajax' => 'Alpine Ajax',
                        ],
                        'default' => 'datastar',
                        'help' => __('Select the primary hypermedia library to use.', 'api-for-htmx'),
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'load_from_cdn',
                        'label' => __('Load from CDN', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Load libraries from a CDN instead of the local copies.', 'api-for-htmx'),
                    ],
                ],
            ],
        ];
    }

    private function buildHTMXTabConfig(): array
    {
        // The field list is version-scoped: the 2.x and 4.x lines accept
        // different companions and different extensions. Changing the version
        // select re-submits the form (admin-options.js), so this page renders
        // again with the matching fields.
        $options = $this->main->getOptions();
        $htmx_version = (($options['htmx_version'] ?? '2') === '4') ? '4' : '2';

        $fields = [
            [
                'type' => 'select',
                'name' => 'htmx_version',
                'label' => __('HTMX Version', 'api-for-htmx'),
                'options' => [
                    // '2' first on purpose: the select sanitizer falls back
                    // to the first key on invalid input, and that fallback
                    // must never silently upgrade a site to the 4.x line.
                    '2' => 'htmx 2.x (legacy, stable)',
                    '4' => 'htmx 4.x (recommended, new default)',
                ],
                // Dynamic default: a legacy site (resolved '2') must show
                // and save '2' even if the stored row lacks the key — a
                // static '4' default here would silently upgrade it on any
                // options save.
                'default' => $htmx_version,
                'help' => __('htmx 4.x is the default for new installs; existing sites stay on 2.x until switched. Saving reloads this page with the matching options.', 'api-for-htmx'),
            ],
            [
                'type' => 'checkbox',
                'name' => 'set_htmx_hxboost',
                'label' => __('Enable hx-boost on body', 'api-for-htmx'),
                'default' => false,
                'help' => __('Automatically add hx-boost to the <body> tag for progressive enhancement.', 'api-for-htmx'),
            ],
            [
                'type' => 'checkbox',
                'name' => 'load_htmx_backend',
                'label' => __('Load HTMX in WP Admin', 'api-for-htmx'),
                'default' => false,
                'help' => __('Enable HTMX functionality within the WordPress admin area.', 'api-for-htmx'),
            ],
            [
                'type' => 'separator',
                'name' => 'htmx_companion_separator',
            ],
        ];

        if ($htmx_version === '4') {
            $fields[] = [
                'type' => 'checkbox',
                'name' => 'load_hxlive',
                'label' => __('Load hx-live with HTMX', 'api-for-htmx'),
                'default' => true,
                'help' => __('hx-live is the official htmx 4 scripting companion (reactive bindings, hx-on helpers). Included by default; disable only if your theme ships its own scripting solution.', 'api-for-htmx'),
            ];
            $fields[] = [
                'type' => 'html',
                'name' => 'htmx4_migration_notice',
                'html_content' => '<p>' . esc_html__('Moving an existing site from htmx 2.x? Enable the htmx-2-compat extension below first: it restores 2.x inheritance, event names, and error handling while you migrate templates. Audit your theme with: npx htmx.org@4.0.0 upgrade-check -- ./wp-content/themes/your-theme', 'api-for-htmx') . '</p>',
            ];
            $available_extensions = HTMXLib::getExtensions($this->main, '4');
        } else {
            $fields[] = [
                'type' => 'checkbox',
                'name' => 'load_hyperscript',
                'label' => __('Load Hyperscript with HTMX', 'api-for-htmx'),
                'default' => true,
                'help' => __('Automatically load Hyperscript when HTMX is active.', 'api-for-htmx'),
            ];
            $fields[] = [
                'type' => 'checkbox',
                'name' => 'load_alpinejs_with_htmx',
                'label' => __('Load Alpine.js with HTMX', 'api-for-htmx'),
                'default' => false,
                'help' => __('Load Alpine.js alongside HTMX for enhanced interactivity.', 'api-for-htmx'),
            ];
            $available_extensions = HTMXLib::getExtensions($this->main, '2');
        }

        $fields[] = [
            'type' => 'html',
            'name' => 'htmx_ext_heading',
            'html_content' => '<h2 style="margin-top:1.5em">' . esc_html__('HTMX Extensions', 'api-for-htmx') . '</h2><p>' . esc_html__('Enable specific HTMX extensions for enhanced functionality. On htmx 4 they auto-register once loaded.', 'api-for-htmx') . '</p>',
        ];

        foreach ($available_extensions as $extension_key => $extension_details) {
            $fields[] = [
                'type' => 'checkbox',
                'name' => 'load_extension_' . str_replace('-', '_', $extension_key),
                'label' => esc_html($extension_details['label']),
                'default' => false,
                'help' => esc_html($extension_details['description']),
            ];
        }

        return [
            [
                'id' => 'htmx_settings',
                'title' => __('HTMX Settings', 'api-for-htmx'),
                'visible_if' => ['field' => 'active_library', 'value' => 'htmx'],
                'description' => __('Configure HTMX-specific settings and features.', 'api-for-htmx'),
                'fields' => $fields,
            ],
        ];
    }

    private function buildAlpineTabConfig(): array
    {
        return [
            [
                'id' => 'alpine_settings',
                'title' => __('Alpine Ajax Settings', 'api-for-htmx'),
                'visible_if' => ['field' => 'active_library', 'value' => 'alpine-ajax'],
                'description' => __('Alpine.js automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_alpinejs_backend',
                        'label' => __('Load Alpine Ajax in WP Admin', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Enable Alpine Ajax functionality within the WordPress admin area.', 'api-for-htmx'),
                    ],
                ],
            ],
        ];
    }

    private function buildDatastarTabConfig(): array
    {
        return [
            [
                'id' => 'datastar_settings',
                'title' => __('Datastar Settings', 'api-for-htmx'),
                'visible_if' => ['field' => 'active_library', 'value' => 'datastar'],
                'description' => __('Datastar automatically loads when selected as the active library. Configure backend loading below.', 'api-for-htmx'),
                'fields' => [
                    [
                        'type' => 'checkbox',
                        'name' => 'load_datastar_backend',
                        'label' => __('Load Datastar in WP Admin', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Enable Datastar functionality within the WordPress admin area.', 'api-for-htmx'),
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'datastar_csp',
                        'label' => __('Content Security Policy (CSP) mode', 'api-for-htmx'),
                        'default' => false,
                        'help' => __('Requires Datastar 1.0.3+. Adds a per-request nonce to the <html> tag, to every WordPress-enqueued script, and sends a strict script-src Content-Security-Policy header, so the browser blocks injected scripts instead of running them. Scripts printed as raw <script> tags outside wp_enqueue_script will be blocked — test your site after enabling. Full-page caches will reuse the nonce across requests; supply a stable one with the hyperpress/datastar/csp_nonce filter.', 'api-for-htmx'),
                    ],
                ],
            ],
        ];
    }

    private function buildAboutTabConfig(): array
    {
        return [
            [
                'id' => 'about',
                'title' => __('About', 'api-for-htmx'),
                'description' => '',
                'fields' => [
                    [
                        'type' => 'html',
                        'name' => 'about_content',
                        'label' => '',
                        'html_content' => $this->getAboutHtml(),
                    ],
                    [
                        'type' => 'html',
                        'name' => 'system_info',
                        'label' => '',
                        'html_content' => $this->getSystemInfoHtml(),
                    ],
                ],
            ],
        ];
    }

    private function getAboutHtml(): string
    {
        return '<div class="hyperpress-about-content">'
            . '<p>' . __('Designed for developers, HyperPress brings the power and simplicity of hypermedia to your WordPress projects. It seamlessly integrates popular libraries like HTMX, Alpine AJAX, and Datastar, empowering you to create rich, dynamic user interfaces without the complexity of traditional JavaScript frameworks.', 'api-for-htmx') . '</p>'
            . '<p>' . __('Adds a new endpoint /wp-html/v1/ from which you can load any hypermedia template partial.', 'api-for-htmx') . '</p>'
            . '<p>' . __('At its core, hypermedia is an approach that empowers you to build modern, dynamic applications by extending the capabilities of HTML. Libraries like HTMX, Alpine AJAX, and Datastar allow you to harness advanced browser technologies—such as AJAX, WebSockets, and Server-Sent Events, simply by adding special attributes to your HTML, minimizing or eliminating the need for a complex JavaScript layer.', 'api-for-htmx') . '</p>'
            . '<p>' . __('Plugin repository and documentation:', 'api-for-htmx') . ' <a href="https://github.com/EstebanForge/HyperPress" target="_blank">https://github.com/EstebanForge/HyperPress</a></p>'
            . '</div>';
    }

    private function getSystemInfoHtml(): string
    {
        $system_info_table = $this->renderSystemInfo($this->getSystemInformation());

        return '<hr style="margin: 1rem 0;"><div class="hyperpress-system-info-section">
            <p>' . __('General information about your WordPress installation and this plugin status:', 'api-for-htmx') . '</p>
            ' . $system_info_table . '
        </div>';
    }

    private function renderSystemInfo(array $system_info): string
    {
        $html = '<div class="hyperpress-system-info"><table class="widefat">';
        $html .= '<thead><tr><th>' . __('Setting', 'api-for-htmx') . '</th><th>' . __('Value', 'api-for-htmx') . '</th></tr></thead><tbody>';

        foreach ($system_info as $key => $value) {
            $html .= sprintf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                esc_html($key),
                esc_html($value)
            );
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function getSystemInformation(): array
    {
        global $wp_version;

        $options = HyperFields::getOptions($this->option_name, []);
        $plugin_version = Config::VERSION;
        $php_version = PHP_VERSION;
        $wp_ver = $wp_version ?? get_bloginfo('version');

        // Datastar SDK: try to read installed.json produced by composer in vendor/composer
        $datastar_version = 'v1.0.0-RC.3'; // fallback (keep existing default)
        if (Config::$abspath !== '') {
            $installed_json = rtrim(Config::$abspath, '/') . '/vendor/composer/installed.json';
            if (file_exists($installed_json)) {
                $installed = json_decode(file_get_contents($installed_json), true);
                // installed.json can be an object with 'packages' or an array of packages depending on composer version
                $packages = $installed['packages'] ?? $installed;
                if (is_array($packages)) {
                    foreach ($packages as $pkg) {
                        if (($pkg['name'] ?? '') === 'starfederation/datastar-php') {
                            $datastar_version = $pkg['version'] ?? $datastar_version;
                            break;
                        }
                    }
                }
            }
        }

        $info = [
            __('WordPress Version', 'api-for-htmx') => $wp_ver,
            __('PHP Version', 'api-for-htmx') => $php_version,
            __('Plugin Version', 'api-for-htmx') => $plugin_version,
            __('Active Library', 'api-for-htmx') => ucfirst($options['active_library'] ?? 'datastar'),
            __('Datastar SDK', 'api-for-htmx') => $datastar_version,
        ];

        return apply_filters('hyperpress/about/system_info', $info);
    }

    private function getFooterContent(): string
    {
        $plugin_version = Config::VERSION;

        return '<span>' . __('Active Instance: Plugin v', 'api-for-htmx') . esc_html($plugin_version) . '</span><br />'
            . __('Proudly brought to you by', 'api-for-htmx')
            . ' <a href="https://actitud.xyz" target="_blank" rel="noopener noreferrer">Actitud Studio</a>.';
    }

    /**
     * Add a Settings link to the plugin's row on plugins.php.
     *
     * Gated by the same `isEnabled()` flag as the options page so the link
     * only appears when the destination page exists. In plugin mode the
     * link targets this plugin's own row automatically. In library mode the
     * library has no row of its own, so a host plugin must declare its
     * basename via the `hyperpress/admin/action_links_basename` filter.
     *
     * @since 1.5.3
     *
     * @param array  $links       Existing action links.
     * @param string $plugin_file Plugin basename for the current row.
     * @return array
     */
    public function pluginActionLinks(array $links, string $plugin_file): array
    {
        if (!self::isEnabled()) {
            return $links;
        }

        $target = apply_filters('hyperpress/admin/action_links_basename', Config::$basename);
        if ($target === '' || $plugin_file !== $target) {
            return $links;
        }

        $links['settings'] = '<a href="' . esc_url(admin_url('options-general.php?page=hyperpress-options')) . '">' . esc_html__('Settings', 'api-for-htmx') . '</a>';

        return $links;
    }

    private function renderApiEndpointHtml(): string
    {
        ob_start();
        $api_url = hp_get_endpoint_url();
        ?>
<div class="hyperpress-api-endpoint-box">
    <h2><?php echo esc_html__('HyperPress API Endpoint', 'api-for-htmx'); ?>
    </h2>
    <div style="display:flex;align-items:center;gap:8px;max-width:100%;">
        <input type="text" readonly
            value="<?php echo esc_attr($api_url); ?>"
            id="hyperpress-api-endpoint"
            aria-label="<?php echo esc_attr__('API Endpoint', 'api-for-htmx'); ?>" />
        <button type="button" class="button"
            id="hyperpress-api-endpoint-copy"><?php echo esc_html__('Copy', 'api-for-htmx'); ?></button>
    </div>
    <p><?php echo esc_html__('Use this base URL to make requests to the HyperPress API endpoints from your frontend code.', 'api-for-htmx'); ?>
    </p>
    <script>
        // Vanilla JS for Copy button (LOC principle)
        (function() {
            var btn = document.getElementById('hyperpress-api-endpoint-copy');
            var input = document.getElementById('hyperpress-api-endpoint');
            if (btn && input) {
                btn.addEventListener('click', function() {
                    input.select();
                    input.setSelectionRange(0, 99999);
                    try {
                        document.execCommand('copy');
                        btn.textContent =
                            '<?php echo esc_js(__('Copied!', 'api-for-htmx')); ?>';
                        setTimeout(function() {
                            btn.textContent =
                                '<?php echo esc_js(__('Copy', 'api-for-htmx')); ?>';
                        }, 1200);
                    } catch (e) {
                        btn.textContent =
                            '<?php echo esc_js(__('Error', 'api-for-htmx')); ?>';
                    }
                });
            }
        })();
    </script>
</div>
<?php
                return ob_get_clean();
    }
}
?>
