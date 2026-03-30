<?php

declare(strict_types=1);

namespace HyperFields;

class OptionsPage
{
    private string $page_title;
    private string $menu_title;
    private string $capability;
    private string $menu_slug;
    private string $parent_slug;
    private string $icon_url;
    private ?int $position;
    /**
     * @var array<string, array{title: string, sections: array<int, string>}>
     */
    private array $tabs = [];
    private array $sections = [];
    private array $fields = [];
    private string $option_name = 'hyperpress_options';
    private array $option_values = [];
    private array $default_values = [];
    private ?string $footer_content = null;
    private string $prefix = '';
    /**
     * @var array<string, string>
     */
    private array $compatibility_field_errors = [];

    public static function make(string $page_title, string $menu_slug, string $prefix = ''): self
    {
        return new self($page_title, $menu_slug, $prefix);
    }

    private function __construct(string $page_title, string $menu_slug, string $prefix = '')
    {
        $this->page_title = $page_title;
        $this->menu_title = $page_title;
        $this->menu_slug = $menu_slug;
        $this->capability = 'manage_options';
        $this->parent_slug = 'options-general.php';
        $this->icon_url = '';
        $this->position = null;
        $this->prefix = $prefix;
    }

    public function setMenuTitle(string $menu_title): self
    {
        $this->menu_title = $menu_title;

        return $this;
    }

    public function setCapability(string $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    public function setParentSlug(string $parent_slug): self
    {
        $this->parent_slug = $parent_slug;

        return $this;
    }

    public function setIconUrl(string $icon_url): self
    {
        $this->icon_url = $icon_url;

        return $this;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function setOptionName(string $option_name): self
    {
        $this->option_name = $option_name;

        return $this;
    }

    public function setFooterContent(string $footer_content): self
    {
        $this->footer_content = $footer_content;

        return $this;
    }

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getOptionName(): string
    {
        return $this->option_name;
    }

    public function addTab(string $id, string $title): self
    {
        if (!isset($this->tabs[$id])) {
            $this->tabs[$id] = [
                'title' => $title,
                'sections' => [],
            ];
        }

        return $this;
    }

    public function addSection(string $id, string $title, string $description = ''): OptionsSection
    {
        $section = new OptionsSection($id, $title, $description);
        $this->sections[$id] = $section;
        $this->addTab($id, $title);
        $this->attachSectionToTab($id, $id);

        return $section;
    }

    public function addSectionToTab(
        string $tab_id,
        string $id,
        string $title,
        string $description = '',
        array $args = []
    ): OptionsSection {
        if (!isset($this->tabs[$tab_id])) {
            $this->addTab($tab_id, $tab_id);
        }

        $section = new OptionsSection($id, $title, $description, $args);
        $this->sections[$id] = $section;
        $this->attachSectionToTab($tab_id, $id);

        return $section;
    }

    public function addSectionObject(OptionsSection $section): self
    {
        $this->sections[$section->getId()] = $section;
        $this->addTab($section->getId(), $section->getTitle());
        $this->attachSectionToTab($section->getId(), $section->getId());

        // Collect default values from the fields in this section
        foreach ($section->getFields() as $field) {
            $this->default_values[$field->getName()] = $field->getDefault();
        }

        return $this;
    }

    private function attachSectionToTab(string $tab_id, string $section_id): void
    {
        if (!isset($this->tabs[$tab_id])) {
            return;
        }

        if (!in_array($section_id, $this->tabs[$tab_id]['sections'], true)) {
            $this->tabs[$tab_id]['sections'][] = $section_id;
        }
    }

    public function addField(Field $field): self
    {
        if ($this->prefix !== '' && strpos($field->getName(), $this->prefix) !== 0) {
            $field->setName($this->prefix . $field->getName());
        }
        $this->fields[$field->getName()] = $field;

        return $this;
    }

    public function register(): void
    {
        $this->loadOptions();

        // Check if we're currently in the admin_menu hook execution
        // If called during admin_menu, register directly; otherwise hook into admin_menu
        if (doing_filter('admin_menu')) {
            $this->addMenuPage();
        } else {
            add_action('admin_menu', $this->addMenuPage(...));
        }

        add_action('admin_init', $this->registerSettings(...));
        add_action('admin_enqueue_scripts', $this->enqueueAssets(...));
    }

    private function loadOptions(): void
    {
        $saved_options = get_option($this->option_name, []);
        $this->option_values = array_merge($this->default_values, $saved_options);
    }

    public function addMenuPage(): void
    {
        if ($this->parent_slug === 'menu') {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'renderPage'],
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
                [$this, 'renderPage'],
                $this->position
            );
        }
    }

    public function registerSettings(): void
    {
        // Register a single settings group and option for all sections/tabs.
        register_setting($this->option_name, $this->option_name, [
            'sanitize_callback' => [$this, 'sanitizeOptions'],
        ]);

        // Register fields for all sections/tabs, but only register settings fields for the active tab
        $active_tab = $this->getActiveTab();
        $active_sections = $this->getRenderableSectionIds($active_tab);

        foreach ($this->sections as $section_id => $section) {
            add_settings_section($section_id, '', '__return_false', $this->option_name);

            // Set option values for all fields in all sections
            foreach ($section->getFields() as $field) {
                $field->setOptionValues($this->option_values, $this->option_name);
            }

            // Only register settings fields for sections currently rendered in the active tab.
            if (in_array($section_id, $active_sections, true)) {
                foreach ($section->getFields() as $field) {
                    add_settings_field($field->getName(), '', [$field, 'render'], $this->option_name, $section_id, $field->getArgs());
                }
            }
        }
    }

    public function renderPage(): void
    {
        $active_tab = $this->getActiveTab();
        ?>
        <div class="wrap hyperpress hyperpress-options-wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            <?php $this->renderTabs(); ?>
            <form method="post" action="options.php">
                <input type="hidden" name="hyperpress_active_tab" value="<?php echo esc_attr($active_tab); ?>" />
                <input type="hidden" name="hyperpress_active_section" value="<?php echo esc_attr($this->getActiveSection($active_tab)); ?>" />
                <?php
                        settings_fields($this->option_name);
        if (defined('HYPERPRESS_COMPACT_INPUT') && HYPERPRESS_COMPACT_INPUT === true) {
            // Placeholder for the compacted JSON payload the JS will populate
            $key = defined('HYPERPRESS_COMPACT_INPUT_KEY') ? HYPERPRESS_COMPACT_INPUT_KEY : 'hyperpress_compact_input';
            if (!is_string($key)) {
                $key = 'hyperpress_compact_input';
            }
            echo '<input type="hidden" name="' . esc_attr((string) $key) . '" value="" />';
            // Dummy field under the option array to ensure the Settings API processes this option
            echo '<input type="hidden" data-hp-keep-name="1" name="' . esc_attr((string) $this->option_name) . '[_compact]" value="1" />';
        }
        $this->renderSectionMenu($active_tab);
        $renderable_sections = $this->getRenderableSectionIds($active_tab);
        foreach ($renderable_sections as $section_id) {
            if (!isset($this->sections[$section_id])) {
                continue;
            }
            $section = $this->sections[$section_id];

            if ($section->getTitle()) {
                echo '<h2>' . esc_html($section->getTitle()) . '</h2>';
            }
            if ($section->getDescription()) {
                if ($section->allowsHtmlDescription()) {
                    echo '<p>' . wp_kses_post($section->getDescription()) . '</p>';
                } else {
                    echo '<p>' . esc_html($section->getDescription()) . '</p>';
                }
            }

            echo '<div class="hyperpress-fields-group">';
            do_settings_fields($this->option_name, $section_id);
            echo '</div>';
        }
        submit_button(
            esc_html__('Save Changes', 'api-for-htmx'),
            'primary'
        );
        ?>
            </form>
            <?php if ($this->footer_content): ?>
                <div class="hyperpress-options-footer">
                    <?php echo wp_kses_post($this->footer_content); ?>
                </div>
            <?php endif; ?>
        </div>
<?php
    }

    public function sanitizeOptions(?array $input): array
    {
        // When compact input is enabled, reconstruct $input from the single compacted POST variable
        if (defined('HYPERPRESS_COMPACT_INPUT') && HYPERPRESS_COMPACT_INPUT === true) {
            $compact_key = defined('HYPERPRESS_COMPACT_INPUT_KEY') ? HYPERPRESS_COMPACT_INPUT_KEY : 'hyperpress_compact_input';
            if (isset($_POST[$compact_key])) {
                $raw = wp_unslash($_POST[$compact_key]);
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded)) {
                    if (isset($decoded[$this->option_name]) && is_array($decoded[$this->option_name])) {
                        $input = $decoded[$this->option_name];
                    }
                }
            }
        }
        // Use the already loaded options to preserve values from other tabs
        $output = $this->option_values;
        $this->compatibility_field_errors = [];

        // Only process fields from the active tab
        $active_tab = $this->getActiveTab();
        $active_sections = $this->getRenderableSectionIds($active_tab);

        foreach ($active_sections as $section_id) {
            if (!isset($this->sections[$section_id])) {
                continue;
            }
            foreach ($this->sections[$section_id]->getFields() as $field) {
                $field_name = $field->getName();
                $field_args = $field->getArgs();
                $option_path = isset($field_args['option_path']) && is_string($field_args['option_path']) && $field_args['option_path'] !== ''
                    ? $field_args['option_path']
                    : null;

                $input_exists = false;
                $raw_value = null;
                if ($option_path !== null) {
                    [$input_exists, $raw_value] = $this->getValueByPath($input, $option_path);
                } elseif (is_array($input) && array_key_exists($field_name, $input)) {
                    $input_exists = true;
                    $raw_value = $input[$field_name];
                }

                if (!$input_exists && $field->getType() === 'checkbox') {
                    $raw_value = $field_args['checkbox_unchecked_value'] ?? '0';
                    $input_exists = true;
                }

                if (!$input_exists) {
                    continue;
                }

                $sanitized = $raw_value;
                if (isset($field_args['wps_sanitize']) && is_callable($field_args['wps_sanitize'])) {
                    $sanitized = call_user_func($field_args['wps_sanitize'], $sanitized);
                } else {
                    $sanitized = $field->sanitizeValue($sanitized);
                }

                $validation_error = $this->validateCompatibilityField($field, $sanitized);
                if ($validation_error !== null) {
                    $this->compatibility_field_errors[$field_name] = $validation_error;
                    if (function_exists('add_settings_error')) {
                        add_settings_error($this->option_name, $field_name, $validation_error, 'error');
                    }

                    continue;
                }

                if ($option_path !== null) {
                    $output = $this->setValueByPath($output, $option_path, $sanitized);
                } else {
                    $output[$field_name] = $sanitized;
                }
            }
        }

        return $output;
    }

    private function validateCompatibilityField(Field $field, mixed $value): ?string
    {
        $args = $field->getArgs();

        if (isset($args['wps_validate'])) {
            $validation = $args['wps_validate'];
            if (is_callable($validation)) {
                $valid = (bool) call_user_func($validation, $value);
                if (!$valid) {
                    return isset($args['wps_validate_feedback']) && is_string($args['wps_validate_feedback']) && $args['wps_validate_feedback'] !== ''
                        ? $args['wps_validate_feedback']
                        : __('Validation failed for this field.', 'hyperfields');
                }
            } elseif (is_array($validation)) {
                foreach ($validation as $rule) {
                    if (!is_array($rule) || !isset($rule['callback']) || !is_callable($rule['callback'])) {
                        continue;
                    }
                    $valid = (bool) call_user_func($rule['callback'], $value);
                    if (!$valid) {
                        if (isset($rule['feedback']) && is_string($rule['feedback']) && $rule['feedback'] !== '') {
                            return $rule['feedback'];
                        }

                        return __('Validation failed for this field.', 'hyperfields');
                    }
                }
            }
        }

        if (!$field->validateValue($value)) {
            return __('Validation failed for this field.', 'hyperfields');
        }

        return null;
    }

    /**
     * @return array{0: bool, 1: mixed}
     */
    private function getValueByPath(?array $source, string $path): array
    {
        if (!is_array($source) || $path === '') {
            return [false, null];
        }

        $segments = array_values(array_filter(explode('.', $path), static fn ($segment): bool => $segment !== ''));
        if ($segments === []) {
            return [false, null];
        }

        $cursor = $source;
        foreach ($segments as $index => $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return [false, null];
            }
            $value = $cursor[$segment];
            if ($index === count($segments) - 1) {
                return [true, $value];
            }
            $cursor = $value;
        }

        return [false, null];
    }

    private function setValueByPath(array $target, string $path, mixed $value): array
    {
        $segments = array_values(array_filter(explode('.', $path), static fn ($segment): bool => $segment !== ''));
        if ($segments === []) {
            return $target;
        }

        $cursor = &$target;
        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $cursor[$segment] = $value;
                break;
            }

            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }

        return $target;
    }

    private function getActiveTab(): string
    {
        // On POST (save), check for hidden field
        if (!empty($_POST['hyperpress_active_tab']) && isset($this->tabs[$_POST['hyperpress_active_tab']])) {
            return $_POST['hyperpress_active_tab'];
        }
        // On GET (view), check query param
        $tab = $_GET['tab'] ?? null;
        if ($tab && isset($this->tabs[$tab])) {
            return $tab;
        }
        $tab_keys = array_keys($this->tabs);

        return $tab_keys[0] ?? 'main';
    }

    private function renderTabs(): void
    {
        if (empty($this->tabs)) {
            return;
        }

        $active_tab = $this->getActiveTab();
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->tabs as $tab_id => $tab) {
            $class = ($active_tab === $tab_id) ? 'nav-tab-active' : '';
            $url_base = $this->parent_slug === 'options-general.php' ? 'options-general.php' : 'admin.php';
            $url = add_query_arg(['page' => $this->menu_slug, 'tab' => $tab_id], admin_url($url_base));
            echo '<a href="' . esc_url($url) . '" class="nav-tab ' . esc_attr($class) . '">' . esc_html($tab['title']) . '</a>';
        }
        echo '</h2>';
    }

    /**
     * @return array<int, string>
     */
    private function getRenderableSectionIds(string $tab_id): array
    {
        $sections = $this->tabs[$tab_id]['sections'] ?? [];
        if ($sections === []) {
            return [];
        }

        $linked = [];
        $non_linked = [];
        foreach ($sections as $section_id) {
            if (!isset($this->sections[$section_id])) {
                continue;
            }

            if ($this->sections[$section_id]->isLinkSection()) {
                $linked[] = $section_id;
            } else {
                $non_linked[] = $section_id;
            }
        }

        if ($linked === []) {
            return $sections;
        }

        $active_section_slug = $this->getActiveSection($tab_id);
        if ($active_section_slug !== '') {
            foreach ($linked as $section_id) {
                if ($this->sections[$section_id]->getSlug() === $active_section_slug) {
                    return [$section_id];
                }
            }
        }

        if (count($linked) === count($sections)) {
            return [$linked[0]];
        }

        return $non_linked;
    }

    private function getActiveSection(string $tab_id): string
    {
        if (!empty($_POST['hyperpress_active_section']) && is_string($_POST['hyperpress_active_section'])) {
            return $_POST['hyperpress_active_section'];
        }

        if (!empty($_GET['section']) && is_string($_GET['section'])) {
            return $_GET['section'];
        }

        $linked = [];
        foreach ($this->tabs[$tab_id]['sections'] ?? [] as $section_id) {
            if (!isset($this->sections[$section_id])) {
                continue;
            }
            if ($this->sections[$section_id]->isLinkSection()) {
                $linked[] = $section_id;
            }
        }

        if ($linked !== [] && count($linked) === count($this->tabs[$tab_id]['sections'] ?? [])) {
            return $this->sections[$linked[0]]->getSlug();
        }

        return '';
    }

    private function renderSectionMenu(string $tab_id): void
    {
        $linked = [];
        foreach ($this->tabs[$tab_id]['sections'] ?? [] as $section_id) {
            if (!isset($this->sections[$section_id])) {
                continue;
            }
            if ($this->sections[$section_id]->isLinkSection()) {
                $linked[] = $this->sections[$section_id];
            }
        }

        if ($linked === []) {
            return;
        }

        $active_section = $this->getActiveSection($tab_id);
        echo '<ul class="subsubsub" style="display: block; width: 100%; margin-bottom: 15px;">';
        foreach ($linked as $section) {
            $current = $section->getSlug() === $active_section ? 'current' : '';
            $url_base = $this->parent_slug === 'options-general.php' ? 'options-general.php' : 'admin.php';
            $url = add_query_arg(
                ['page' => $this->menu_slug, 'tab' => $tab_id, 'section' => $section->getSlug()],
                admin_url($url_base)
            );
            echo '<li><a href="' . esc_url($url) . '" class="' . esc_attr($current) . '">' . esc_html($section->getTitle()) . '</a> | </li>';
        }
        echo '</ul>';
    }

    public function enqueueAssets(string $hook_suffix): void
    {
        if (
            $hook_suffix !== 'settings_page_' . $this->menu_slug
            && $hook_suffix !== $this->parent_slug . '_page_' . $this->menu_slug
        ) {
            return;
        }

        TemplateLoader::enqueueAssets();

        // Require a valid plugin URL; skip in library mode where URL is unavailable
        if (!defined('HYPERPRESS_PLUGIN_URL') || empty(HYPERPRESS_PLUGIN_URL)) {
            return;
        }

        // Enqueue admin options JS for HyperFields options pages
        wp_enqueue_script(
            'hyperpress-admin-options',
            defined('HYPERPRESS_PLUGIN_URL') ? HYPERPRESS_PLUGIN_URL . 'assets/js/admin-options.js' : '',
            ['jquery'],
            defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.0.7',
            true
        );

        wp_localize_script('hyperpress-admin-options', 'hyperpressOptions', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hyperpress_options'),
            'compactInput' => defined('HYPERPRESS_COMPACT_INPUT') ? (bool) HYPERPRESS_COMPACT_INPUT : false,
            'compactInputKey' => defined('HYPERPRESS_COMPACT_INPUT_KEY') ? HYPERPRESS_COMPACT_INPUT_KEY : 'hyperpress_compact_input',
            'optionName' => $this->option_name,
            'activeTab' => $this->getActiveTab(),
        ]);
    }
}
