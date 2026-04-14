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
    /**
     * @var array<int, array>
     */
    private array $reactFieldsToRender = [];

    /**
     * Make.
     *
     * @return self
     */
    public static function make(string $page_title, string $menu_slug, string $prefix = ''): self
    {
        return new self($page_title, $menu_slug, $prefix);
    }

    /**
     *   construct.
     */
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

    /**
     * SetMenuTitle.
     *
     * @return self
     */
    public function setMenuTitle(string $menu_title): self
    {
        $this->menu_title = $menu_title;

        return $this;
    }

    /**
     * SetCapability.
     *
     * @return self
     */
    public function setCapability(string $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    /**
     * SetParentSlug.
     *
     * @return self
     */
    public function setParentSlug(string $parent_slug): self
    {
        $this->parent_slug = $parent_slug;

        return $this;
    }

    /**
     * SetIconUrl.
     *
     * @return self
     */
    public function setIconUrl(string $icon_url): self
    {
        $this->icon_url = $icon_url;

        return $this;
    }

    /**
     * SetPosition.
     *
     * @return self
     */
    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * SetOptionName.
     *
     * @return self
     */
    public function setOptionName(string $option_name): self
    {
        $this->option_name = $option_name;

        return $this;
    }

    /**
     * SetFooterContent.
     *
     * @return self
     */
    public function setFooterContent(string $footer_content): self
    {
        $this->footer_content = $footer_content;

        return $this;
    }

    /**
     * SetPrefix.
     *
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * GetPrefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * GetOptionName.
     *
     * @return string
     */
    public function getOptionName(): string
    {
        return $this->option_name;
    }

    /**
     * AddTab.
     *
     * @return self
     */
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

    /**
     * AddSection.
     *
     * @return OptionsSection
     */
    public function addSection(string $id, string $title, string $description = ''): OptionsSection
    {
        $section = new OptionsSection($id, $title, $description);
        $this->sections[$id] = $section;
        $this->addTab($id, $title);
        $this->attachSectionToTab($id, $id);

        return $section;
    }

    /**
     * AddSectionToTab.
     */
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

    /**
     * AddSectionObject.
     *
     * @return self
     */
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

    /**
     * AttachSectionToTab.
     *
     * @return void
     */
    private function attachSectionToTab(string $tab_id, string $section_id): void
    {
        if (!isset($this->tabs[$tab_id])) {
            return;
        }

        if (!in_array($section_id, $this->tabs[$tab_id]['sections'], true)) {
            $this->tabs[$tab_id]['sections'][] = $section_id;
        }
    }

    /**
     * AddField.
     *
     * @return self
     */
    public function addField(Field $field): self
    {
        if ($this->prefix !== '' && strpos($field->getName(), $this->prefix) !== 0) {
            $field->setName($this->prefix . $field->getName());
        }
        $this->fields[$field->getName()] = $field;

        return $this;
    }

    /**
     * Register.
     *
     * @return void
     */
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

    /**
     * LoadOptions.
     *
     * @return void
     */
    private function loadOptions(): void
    {
        $saved_options = get_option($this->option_name, []);
        $this->option_values = array_merge($this->default_values, $saved_options);
    }

    /**
     * AddMenuPage.
     *
     * @return void
     */
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

    /**
     * RegisterSettings.
     *
     * @return void
     */
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

    /**
     * RenderPage.
     *
     * @return void
     */
    public function renderPage(): void
    {
        $active_tab = $this->getActiveTab();
        $react_fields = $this->getReactFields($active_tab);

        // If we have React fields, the enqueueAssets method will handle loading React
        if (!empty($react_fields)) {
            // Store React fields for later use in enqueueAssets
            $this->reactFieldsToRender = $react_fields;
        }
        ?>
        <div class="wrap hyperpress hyperpress-options-wrap" id="hyperpress-options-page">
            <div class="hyperpress-layout__header" data-hyperpress-sticky-header>
                <div class="hyperpress-layout__header-wrapper">
                    <h1 class="hyperpress-layout__header-heading"><?php echo esc_html($this->page_title); ?></h1>
                </div>
            </div>
            <?php $this->renderTabs(); ?>
            <form method="post" action="options.php" id="hyperpress-options-form">
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
        echo '<div class="wp-header-end hyperpress-notice-catcher" id="hyperpress-layout__notice-catcher"></div>';
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
        // React root container - React fields will be rendered here
        if (!empty($react_fields)) {
            echo '<div id="hyperpress-react-root" data-hyperpress-react></div>';
        }
        submit_button(
            esc_html__('Save Changes', 'api-for-htmx'),
            'primary',
            'submit',
            true,
            ['class' => 'button button-primary']
        );
        echo '</form>';

        if ($this->footer_content) {
            echo '<div class="hyperpress-options-footer">';
            echo wp_kses_post($this->footer_content);
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * SanitizeOptions.
     *
     * @return array
     */
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

    /**
     * ValidateCompatibilityField.
     *
     * @return ?string
     */
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

    /**
     * SetValueByPath.
     *
     * @return array
     */
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

    /**
     * GetActiveTab.
     *
     * @return string
     */
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

    /**
     * RenderTabs.
     *
     * @return void
     */
    private function renderTabs(): void
    {
        if (empty($this->tabs)) {
            return;
        }

        $active_tab = $this->getActiveTab();
        echo '<nav class="nav-tab-wrapper hyperpress-nav-tab-wrapper" aria-label="' . esc_attr__('Settings sections', 'api-for-htmx') . '">';
        foreach ($this->tabs as $tab_id => $tab) {
            $class = 'nav-tab hyperpress-nav-tab';
            if ($active_tab === $tab_id) {
                $class .= ' nav-tab-active';
            }
            $url_base = $this->parent_slug === 'options-general.php' ? 'options-general.php' : 'admin.php';
            $url = add_query_arg(['page' => $this->menu_slug, 'tab' => $tab_id], admin_url($url_base));
            $aria_current = $active_tab === $tab_id ? ' aria-current="page"' : '';
            echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '"' . $aria_current . '>' . esc_html($tab['title']) . '</a>';
        }
        echo '</nav>';
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

    /**
     * GetActiveSection.
     *
     * @return string
     */
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

    /**
     * RenderSectionMenu.
     *
     * @return void
     */
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
        echo '<ul class="subsubsub hyperpress-subsubsub">';
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

    /**
     * Get all ReactField instances for a specific tab.
     *
     * @param string $tab_id Tab identifier.
     * @return array<int, array> Array of React field configurations.
     */
    public function getReactFields(string $tab_id): array
    {
        $react_fields = [];
        $sections = $this->getRenderableSectionIds($tab_id);

        foreach ($sections as $section_id) {
            if (!isset($this->sections[$section_id])) {
                continue;
            }

            foreach ($this->sections[$section_id]->getFields() as $field) {
                // Check if this is a ReactField instance and React is enabled
                if ($field instanceof ReactField && $field->shouldUseReact()) {
                    $react_fields[] = [
                        'name' => $field->getName(),
                        'type' => $field->getType(),
                        'label' => $field->getLabel(),
                        'component' => $field->getReactComponent(),
                        'props' => $field->getReactProps(),
                        'value' => $this->option_values[$field->getName()] ?? $field->getDefault(),
                    ];
                }
            }
        }

        return $react_fields;
    }

    /**
     * Check if the current tab has any React fields.
     *
     * @param string $tab_id Tab identifier.
     * @return bool True if React fields are present.
     */
    public function hasReactFields(string $tab_id): bool
    {
        return !empty($this->getReactFields($tab_id));
    }

    /**
     * EnqueueAssets.
     *
     * @return void
     */
    public function enqueueAssets(string $hook_suffix): void
    {
        $is_exact_settings_hook = $hook_suffix === 'settings_page_' . $this->menu_slug;
        $is_exact_parent_hook = $hook_suffix === $this->parent_slug . '_page_' . $this->menu_slug;
        $is_slug_match_hook = strpos($hook_suffix, $this->menu_slug) !== false;

        if (!$is_exact_settings_hook && !$is_exact_parent_hook && !$is_slug_match_hook) {
            return;
        }

        TemplateLoader::enqueueAssets();

        $plugin_url = '';
        if (defined('HYPERFIELDS_PLUGIN_URL') && is_string(HYPERFIELDS_PLUGIN_URL) && HYPERFIELDS_PLUGIN_URL !== '') {
            $plugin_url = HYPERFIELDS_PLUGIN_URL;
        } elseif (function_exists('plugins_url')) {
            $resolved = plugins_url('', dirname(__DIR__) . '/bootstrap.php');
            if (is_string($resolved) && $resolved !== '') {
                $plugin_url = trailingslashit($resolved);
            }
        }

        if ($plugin_url === '') {
            return;
        }

        // Enqueue admin options JS for HyperFields options pages
        $admin_options_script_version = defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.0.7';
        $admin_options_script_path = dirname(__DIR__) . '/assets/js/hyperfields-admin.js';
        if (is_file($admin_options_script_path)) {
            $mtime = filemtime($admin_options_script_path);
            if ($mtime !== false) {
                $admin_options_script_version = (string) $mtime;
            }
        }

        wp_enqueue_script(
            'hyperpress-admin-options',
            $plugin_url . 'assets/js/hyperfields-admin.js',
            ['jquery'],
            $admin_options_script_version,
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

        // Hide notices before JS relocates them under the sticky header.
        // Keep selector list centralized to avoid JS/PHP drift.
        $notice_selectors = implode(', ', [
            '#wpbody-content > .notice',
            '#wpbody-content > .update-nag',
            '#wpbody-content > .updated',
            '#wpbody-content > .error',
            '.wrap > .notice',
            '.wrap > .update-nag',
            '.wrap > .updated',
            '.wrap > .error',
            '.wrap.hyperpress-options-wrap > .notice',
            '.wrap.hyperpress-options-wrap > .update-nag',
            '.wrap.hyperpress-options-wrap > .updated',
            '.wrap.hyperpress-options-wrap > .error',
            '.wrap > .notice:first-child',
            '.wrap > .update-nag:first-child',
            '.wrap > .updated:first-child',
            '.wrap > .error:first-child',
        ]);

        wp_add_inline_style('hyperpress-admin', sprintf(
            '%s { opacity: 0 !important; }',
            $notice_selectors
        ));

        // Enqueue React assets if we have React fields to render
        if (!empty($this->reactFieldsToRender)) {
            $this->enqueueReactAssets();
        }
    }

    /**
     * Enqueue React assets for rendering ReactField instances.
     *
     * @return void
     */
    public function enqueueReactAssets(): void
    {
        // Enqueue WordPress React dependencies
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-components');
        wp_enqueue_script('wp-block-editor');
        wp_enqueue_script('wp-i18n');

        // Enqueue React app for HyperFields
        $plugin_url = '';
        if (defined('HYPERFIELDS_PLUGIN_URL') && is_string(HYPERFIELDS_PLUGIN_URL) && HYPERFIELDS_PLUGIN_URL !== '') {
            $plugin_url = HYPERFIELDS_PLUGIN_URL;
        } elseif (function_exists('plugins_url')) {
            $resolved = plugins_url('', dirname(__DIR__) . '/bootstrap.php');
            if (is_string($resolved) && $resolved !== '') {
                $plugin_url = trailingslashit($resolved);
            }
        }

        $react_app_path = $plugin_url !== '' ? $plugin_url . 'assets/js/dist/react-fields.js' : '';

        if (empty($react_app_path)) {
            return;
        }

        wp_enqueue_script(
            'hyperfields-react-app',
            $react_app_path,
            ['wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n'],
            defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.1.0',
            true
        );

        // Localize data for React app
        wp_localize_script('hyperfields-react-app', 'hyperfieldsReactData', [
            'fields' => $this->reactFieldsToRender,
            'optionName' => $this->option_name,
            'values' => $this->option_values,
            'strings' => [
                'saveChanges' => __('Save Changes', 'hyperfields'),
                'saving' => __('Saving...', 'hyperfields'),
                'saved' => __('Saved', 'hyperfields'),
                'error' => __('Error', 'hyperfields'),
            ],
        ]);

        // Enqueue React-specific styles
        wp_enqueue_style(
            'hyperfields-react-styles',
            $plugin_url !== '' ? $plugin_url . 'assets/css/react-fields.css' : '',
            ['wp-components'],
            defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.1.0'
        );
    }
}
