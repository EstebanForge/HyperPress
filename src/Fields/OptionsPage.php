<?php

declare(strict_types=1);

namespace HMApi\Fields;

class OptionsPage
{
    private string $page_title;
    private string $menu_title;
    private string $capability;
    private string $menu_slug;
    private string $parent_slug;
    private string $icon_url;
    private ?int $position;
    private array $sections = [];
    private array $fields = [];
    private string $option_name = 'hmapi_options';
    private array $option_values = [];
    private array $default_values = [];
    private ?string $footer_content = null;

    public static function make(string $page_title, string $menu_slug): self
    {
        return new self($page_title, $menu_slug);
    }

    private function __construct(string $page_title, string $menu_slug)
    {
        $this->page_title = $page_title;
        $this->menu_title = $page_title;
        $this->menu_slug = $menu_slug;
        $this->capability = 'manage_options';
        $this->parent_slug = 'options-general.php';
        $this->icon_url = '';
        $this->position = null;
    }

    public function set_menu_title(string $menu_title): self
    {
        $this->menu_title = $menu_title;

        return $this;
    }

    public function set_capability(string $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    public function set_parent_slug(string $parent_slug): self
    {
        $this->parent_slug = $parent_slug;

        return $this;
    }

    public function set_icon_url(string $icon_url): self
    {
        $this->icon_url = $icon_url;

        return $this;
    }

    public function set_position(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function set_option_name(string $option_name): self
    {
        $this->option_name = $option_name;

        return $this;
    }

    public function set_footer_content(string $footer_content): self
    {
        $this->footer_content = $footer_content;

        return $this;
    }

    public function get_option_name(): string
    {
        return $this->option_name;
    }

    public function add_section(string $id, string $title, string $description = ''): OptionsSection
    {
        $section = new OptionsSection($id, $title, $description);
        $this->sections[$id] = $section;

        return $section;
    }

    public function add_section_object(OptionsSection $section): self
    {
        $this->sections[$section->get_id()] = $section;

        // Collect default values from the fields in this section
        foreach ($section->get_fields() as $field) {
            $this->default_values[$field->get_name()] = $field->get_default();
        }

        return $this;
    }

    public function add_field(Field $field): self
    {
        $this->fields[$field->get_name()] = $field;

        return $this;
    }

    public function register(): void
    {
        $this->load_options();
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    private function load_options(): void
    {
        $saved_options = get_option($this->option_name, []);
        $this->option_values = array_merge($this->default_values, $saved_options);
    }

    public function add_menu_page(): void
    {
        if ($this->parent_slug === 'menu') {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'render_page'],
                $this->icon_url,
                $this->position
            );
        } else {
            add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'render_page'],
                $this->position
            );
        }
    }

    public function register_settings(): void
    {
        // Register a single settings group and option for all sections/tabs.
        register_setting($this->option_name, $this->option_name, [
            'sanitize_callback' => [$this, 'sanitize_options'],
        ]);

        // Register fields for all sections/tabs, but only register settings fields for the active tab
        $active_tab = $this->get_active_tab();

        foreach ($this->sections as $section_id => $section) {
            add_settings_section($section_id, '', '__return_false', $this->option_name);

            // Set option values for all fields in all sections
            foreach ($section->get_fields() as $field) {
                $field->set_option_values($this->option_values, $this->option_name);
            }

            // Only register settings fields for the active tab
            if ($section_id === $active_tab) {
                foreach ($section->get_fields() as $field) {
                    add_settings_field($field->get_name(), '', [$field, 'render'], $this->option_name, $section_id, $field->get_args());
                }
            }
        }
    }

    public function render_page(): void
    {
        $active_tab = $this->get_active_tab();
        ?>
        <div class="wrap hyperpress hyperpress-options-wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            <?php $this->render_tabs(); ?>
            <form method="post" action="options.php">
                <input type="hidden" name="hmapi_active_tab" value="<?php echo esc_attr($active_tab); ?>" />
                <?php
                settings_fields($this->option_name);
                if (defined('HMAPI_COMPACT_INPUT') && HMAPI_COMPACT_INPUT === true) {
                    // Placeholder for the compacted JSON payload the JS will populate
                    echo '<input type="hidden" name="' . esc_attr(defined('HMAPI_COMPACT_INPUT_KEY') ? HMAPI_COMPACT_INPUT_KEY : 'hmapi_compact_input') . '" value="" />';
                    // Dummy field under the option array to ensure the Settings API processes this option
                    echo '<input type="hidden" data-hm-keep-name="1" name="' . esc_attr($this->option_name) . '[_compact]" value="1" />';
                }
                // Only render the active tab's section
                if (isset($this->sections[$active_tab])) {
                    $section = $this->sections[$active_tab];
                    // Render section title
                    if ($section->get_title()) {
                        echo '<h2>' . esc_html($section->get_title()) . '</h2>';
                    }
                    // Render section description
                    if ($section->get_description()) {
                        echo '<p>' . esc_html($section->get_description()) . '</p>';
                    }
                    // Render fields for this section
                    echo '<div class="hmapi-fields-group">';
                    do_settings_fields($this->option_name, $active_tab);
                    echo '</div>';
                }
                submit_button(
                    esc_html__('Save Changes', 'api-for-htmx'),
                    'primary'
                );
                ?>
            </form>
            <?php if ($this->footer_content): ?>
                <div class="hmapi-options-footer">
                    <?php echo wp_kses_post($this->footer_content); ?>
                </div>
            <?php endif; ?>
        </div>
<?php
    }

    public function sanitize_options(?array $input): array
    {
        \HMApi\Log::debug('sanitize_options called: input=' . print_r($input, true) . ' option_name=' . $this->option_name);

        // When compact input is enabled, reconstruct $input from the single compacted POST variable
        if (defined('HMAPI_COMPACT_INPUT') && HMAPI_COMPACT_INPUT === true) {
            $compact_key = defined('HMAPI_COMPACT_INPUT_KEY') ? HMAPI_COMPACT_INPUT_KEY : 'hmapi_compact_input';
            if (isset($_POST[$compact_key])) {
                $raw = wp_unslash($_POST[$compact_key]);
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded)) {
                    if (isset($decoded[$this->option_name]) && is_array($decoded[$this->option_name])) {
                        $input = $decoded[$this->option_name];
                        \HMApi\Log::debug('sanitize_options compact input expanded for ' . $this->option_name . ': ' . print_r($input, true));
                    }
                }
            }
        }
        // Use the already loaded options to preserve values from other tabs
        $output = $this->option_values;
        \HMApi\Log::debug('sanitize_options existing_options (from property): ' . print_r($output, true) . ' option_name=' . $this->option_name);

        // Only process fields from the active tab
        $active_tab = $this->get_active_tab();
        \HMApi\Log::debug('sanitize_options active_tab: ' . $active_tab);

        if (isset($this->sections[$active_tab])) {
            $section = $this->sections[$active_tab];
            // Only update fields for the current tab, preserve all others
            foreach ($section->get_fields() as $field) {
                $field_name = $field->get_name();
                if (isset($input[$field_name])) {
                    $output[$field_name] = $field->sanitize_value($input[$field_name]);
                } elseif ($field->get_type() === 'checkbox') {
                    // If checkbox not present, means unchecked
                    $output[$field_name] = '0';
                } else {
                    // For non-checkbox fields, preserve previous value (do not unset)
                    // No action needed
                }
            }
        }
        \HMApi\Log::debug('sanitize_options output: ' . print_r($output, true) . ' option_name=' . $this->option_name);
        return $output;
    }

    private function get_active_tab(): string
    {
        // On POST (save), check for hidden field
        if (!empty($_POST['hmapi_active_tab']) && isset($this->sections[$_POST['hmapi_active_tab']])) {
            return $_POST['hmapi_active_tab'];
        }
        // On GET (view), check query param
        $tab = $_GET['tab'] ?? null;
        if ($tab && isset($this->sections[$tab])) {
            return $tab;
        }
        // Default to the first available tab
        $section_keys = array_keys($this->sections);

        return $section_keys[0] ?? 'main';
    }

    private function render_tabs(): void
    {
        if (empty($this->sections)) {
            return;
        }

        $active_tab = $this->get_active_tab();
        echo '<h2 class="nav-tab-wrapper">';
        foreach (array_keys($this->sections) as $tab_id) {
            $class = ($active_tab === $tab_id) ? 'nav-tab-active' : '';
            $url_base = $this->parent_slug === 'options-general.php' ? 'options-general.php' : 'admin.php';
            $url = add_query_arg(['page' => $this->menu_slug, 'tab' => $tab_id], admin_url($url_base));
            echo '<a href="' . esc_url($url) . '" class="nav-tab ' . esc_attr($class) . '">' . esc_html($this->sections[$tab_id]->get_title()) . '</a>';
        }
        echo '</h2>';
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if (
            $hook_suffix !== 'settings_page_' . $this->menu_slug &&
            $hook_suffix !== $this->parent_slug . '_page_' . $this->menu_slug
        ) {
            return;
        }

        TemplateLoader::enqueue_assets();

        // Require a valid plugin URL; skip in library mode where URL is unavailable
        if (!defined('HMAPI_PLUGIN_URL') || empty(HMAPI_PLUGIN_URL)) {
            return;
        }

        // Enqueue admin options JS for HyperFields options pages
        wp_enqueue_script(
            'hmapi-admin-options',
            defined('HMAPI_PLUGIN_URL') ? HMAPI_PLUGIN_URL . 'assets/js/admin-options.js' : '',
            ['jquery'],
            defined('HMAPI_VERSION') ? HMAPI_VERSION : '2.0.7',
            true
        );
        wp_localize_script('hmapi-admin-options', 'hmapiOptions', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hmapi_options'),
            'compactInput' => defined('HMAPI_COMPACT_INPUT') ? (bool) HMAPI_COMPACT_INPUT : false,
            'compactInputKey' => defined('HMAPI_COMPACT_INPUT_KEY') ? HMAPI_COMPACT_INPUT_KEY : 'hmapi_compact_input',
            'optionName' => $this->option_name,
            'activeTab' => $this->get_active_tab(),
        ]);
    }
}
