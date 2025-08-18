<?php

namespace HyperPress\Fields;

class TemplateLoader
{
    private static string $template_dir;
    private static array $template_cache = [];
    private static array $rendered_field_types = [];

    public static function init(): void
    {
        self::$template_dir = __DIR__ . '/templates/';
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueAssets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueueAssets']);
        // Enqueue type-specific assets after fields render (footer), so checks see rendered types
        add_action('admin_print_footer_scripts', [__CLASS__, 'enqueue_late_assets']);
        add_action('wp_print_footer_scripts', [__CLASS__, 'enqueue_late_assets']);
    }

    public static function render_field(array $field_data, mixed $value = null): void
    {
        $type = $field_data['type'] ?? 'text';
        $name = $field_data['name'] ?? '';
        self::$rendered_field_types[$type] = true;

        // Set the value in field data
        $field_data['value'] = $value;

        // Store TabsField instances in global variable for template access
        if ($type === 'tabs' && isset($field_data['instance'])) {
            if (!isset($GLOBALS['hyperpress_tabs_instances'])) {
                $GLOBALS['hyperpress_tabs_instances'] = [];
            }
            $GLOBALS['hyperpress_tabs_instances'][$name] = $field_data['instance'];
        }

        // Get the appropriate template file
        $template_file = self::get_template_file($type);

        if (!$template_file) {
            // Fallback to basic input template
            $template_file = self::$template_dir . 'field-input.php';
        }

        // Allow template override via filter
        $template_file = apply_filters('hyperpress_field_template', $template_file, $type, $field_data);

        if (file_exists($template_file)) {

            include $template_file;

        } else {
            // Last resort fallback
            self::render_fallback($field_data);
        }
    }

    private static function get_template_file(string $type): ?string
    {
        // Check cache first
        if (isset(self::$template_cache[$type])) {
            return self::$template_cache[$type];
        }

        $template_file = self::$template_dir . 'field-' . $type . '.php';

        if (file_exists($template_file)) {
            self::$template_cache[$type] = $template_file;

            return $template_file;
        }

        // Check for type-specific templates in theme
        $theme_template = get_template_directory() . '/hyperpress/fields/field-' . $type . '.php';
        if (file_exists($theme_template)) {
            self::$template_cache[$type] = $theme_template;

            return $theme_template;
        }

        // Check child theme
        if (is_child_theme()) {
            $child_template = get_stylesheet_directory() . '/hyperpress/fields/field-' . $type . '.php';
            if (file_exists($child_template)) {
                self::$template_cache[$type] = $child_template;

                return $child_template;
            }
        }

        self::$template_cache[$type] = null;

        return null;
    }

    private static function render_fallback(array $field_data): void
    {
        $type = $field_data['type'] ?? 'text';
        $name = $field_data['name'] ?? '';
        $label = $field_data['label'] ?? '';
        $value = $field_data['value'] ?? '';
        $placeholder = $field_data['placeholder'] ?? '';
        $required = $field_data['required'] ?? false;
        $help = $field_data['help'] ?? '';
        ?>

        <div class="hyperpress-field-wrapper">
            <label for="<?php echo esc_attr($name); ?>" class="hyperpress-field-label">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?><span class="required">*</span><?php endif; ?>
            </label>

            <div class="hyperpress-field-input">
                <input type="<?php echo esc_attr($type); ?>"
                    id="<?php echo esc_attr($name); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    class="regular-text">

                <?php if ($help): ?>
                    <p class="description"><?php echo esc_html($help); ?></p>
                <?php endif; ?>
            </div>
        </div>
<?php
    }

    public static function enqueueAssets(): void
    {
        // Always enqueue admin.css for HyperFields admin pages
        if (is_admin()) {
            wp_enqueue_style(
                'hyperpress-admin',
                HYPERPRESS_PLUGIN_URL . 'assets/css/admin.css',
                [],
                HYPERPRESS_VERSION
            );
            // In admin, enqueue base JS for HyperFields; CSS is covered by admin.css
            wp_enqueue_script(
                'hyperpress-conditional-fields',
                HYPERPRESS_PLUGIN_URL . 'assets/js/conditional-fields.js',
                [],
                HYPERPRESS_VERSION,
                true
            );

            wp_localize_script('hyperpress-conditional-fields', 'hyperpressFields', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hyperpress_fields_nonce'),
                'l10n' => [
                    'selectImage' => __('Select Image', 'api-for-htmx'),
                    'selectFile' => __('Select File', 'api-for-htmx'),
                    'remove' => __('Remove', 'api-for-htmx'),
                    'addImages' => __('Add Images', 'api-for-htmx'),
                    'clearGallery' => __('Clear Gallery', 'api-for-htmx'),
                    'searchAddress' => __('Search for an address...', 'api-for-htmx'),
                ],
            ]);

            return; // Done for admin
        }

        // Frontend: only enqueue when fields have been rendered on the page
        if (empty(self::$rendered_field_types)) {
            return;
        }

        wp_enqueue_script(
            'hyperpress-conditional-fields',
            HYPERPRESS_PLUGIN_URL . 'assets/js/conditional-fields.js',
            [],
            HYPERPRESS_VERSION,
            true
        );

        wp_localize_script('hyperpress-conditional-fields', 'hyperpressFields', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hyperpress_fields_nonce'),
            'l10n' => [
                'selectImage' => __('Select Image', 'api-for-htmx'),
                'selectFile' => __('Select File', 'api-for-htmx'),
                'remove' => __('Remove', 'api-for-htmx'),
                'addImages' => __('Add Images', 'api-for-htmx'),
                'clearGallery' => __('Clear Gallery', 'api-for-htmx'),
                'searchAddress' => __('Search for an address...', 'api-for-htmx'),
            ],
        ]);
    }

    public static function enqueue_late_assets(): void
    {
        // Enqueue heavy/type-specific assets after fields have rendered (works for admin and frontend)
        if (isset(self::$rendered_field_types['map'])) {
            wp_enqueue_style('hyperpress-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
            wp_enqueue_script('hyperpress-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
            wp_enqueue_script('hyperpress-map-field', HYPERPRESS_PLUGIN_URL . 'assets/js/map-field.js', ['hyperpress-leaflet'], HYPERPRESS_VERSION, true);
        }
    }

    public static function get_supported_field_types(): array
    {
        $types = [
            'text',
            'textarea',
            'number',
            'email',
            'url',
            'color',
            'date',
            'datetime',
            'time',
            'image',
            'file',
            'select',
            'multiselect',
            'checkbox',
            'radio',
            'radio_image',
            'rich_text',
            'hidden',
            'html',
            'map',
            'oembed',
            'separator',
            'header_scripts',
            'footer_scripts',
            'set',
            'sidebar',
            'association',
            'tabs',
            'custom',
        ];

        return apply_filters('hyperpress_supported_field_types', $types);
    }
}
