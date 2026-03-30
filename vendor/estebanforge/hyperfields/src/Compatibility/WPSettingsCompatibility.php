<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

use HyperFields\CustomField;
use HyperFields\Field;
use HyperFields\OptionsPage;
use WP_Error;

final class WPSettingsCompatibility
{
    /**
     * @var array<string, bool>
     */
    private static array $registered_lifecycle = [];

    public static function register(array $config): OptionsPage
    {
        $title = (string) ($config['title'] ?? $config['page_title'] ?? '');
        $slug = (string) ($config['slug'] ?? $config['menu_slug'] ?? '');
        if ($title === '' || $slug === '') {
            throw new \InvalidArgumentException('Settings compatibility config requires title and slug.');
        }

        $prefix = isset($config['prefix']) && is_string($config['prefix']) ? $config['prefix'] : '';
        $page = OptionsPage::make($title, $slug, $prefix);

        if (isset($config['menu_title']) && is_string($config['menu_title'])) {
            $page->setMenuTitle($config['menu_title']);
        }
        if (isset($config['parent_slug']) && is_string($config['parent_slug'])) {
            $page->setParentSlug($config['parent_slug']);
        }
        if (isset($config['capability']) && is_string($config['capability'])) {
            $page->setCapability($config['capability']);
        }
        if (isset($config['menu_icon']) && is_string($config['menu_icon'])) {
            $page->setIconUrl($config['menu_icon']);
        }
        if (isset($config['menu_position'])) {
            $position = (int) $config['menu_position'];
            $page->setPosition($position > 0 ? $position : null);
        }
        if (isset($config['option_name']) && is_string($config['option_name'])) {
            $page->setOptionName($config['option_name']);
        }
        if (isset($config['footer_content']) && is_string($config['footer_content'])) {
            $page->setFooterContent($config['footer_content']);
        }

        $hook_prefix = self::resolveHookPrefix($config);
        $tabs = self::resolveTabs($config);
        $tabs = apply_filters('hyperfields/settings/tabs', $tabs, $config, $page);
        $tabs = apply_filters($hook_prefix . '_tabs', $tabs, $config, $page);
        $tabs = self::sortTabs($tabs);

        foreach ($tabs as $tab) {
            $key = (string) ($tab['key'] ?? '');
            $label = (string) ($tab['label'] ?? $tab['title'] ?? $key);
            if ($key === '') {
                continue;
            }

            $page->addTab($key, $label);
            $tab_proxy = new TabProxy($key, $label);
            if (isset($tab['option_level'])) {
                $tab_proxy->option_level((bool) $tab['option_level']);
            }
            if (isset($tab['callback']) && is_callable($tab['callback'])) {
                call_user_func($tab['callback'], $tab_proxy);
            }

            $tab_proxy = apply_filters('hyperfields/settings/tab/' . $key, $tab_proxy, $tab, $config, $page);
            $tab_proxy = apply_filters($hook_prefix . '_tab_' . $key, $tab_proxy, $tab, $config, $page);

            foreach ($tab_proxy->getSections() as $section_proxy) {
                $description = '';
                $section_args = $section_proxy->getArgs();
                if (isset($section_args['description']) && is_string($section_args['description'])) {
                    $description = $section_args['description'];
                }
                if (isset($section_args['option_level'])) {
                    $section_proxy->option_level((bool) $section_args['option_level']);
                }

                $section = $page->addSectionToTab(
                    $tab_proxy->getKey(),
                    $section_proxy->getId(),
                    $section_proxy->getTitle(),
                    $description,
                    [
                        'slug' => isset($section_args['slug']) && is_string($section_args['slug']) ? $section_args['slug'] : '',
                        'as_link' => isset($section_args['as_link']) ? (bool) $section_args['as_link'] : false,
                        'allow_html_description' => isset($section_args['allow_html_description'])
                            ? (bool) $section_args['allow_html_description']
                            : true,
                    ]
                );
                self::attachSectionOptions($page, $tab_proxy, $section_proxy, $section);
            }
        }

        $page = apply_filters('hyperfields/settings/extend', $page, $config);
        $page = apply_filters($hook_prefix . '_extend', $page, $config);

        self::registerLifecycleHooks($page, $config, $hook_prefix);
        $page->register();

        return $page;
    }

    private static function resolveHookPrefix(array $config): string
    {
        $prefix = isset($config['hook_prefix']) && is_string($config['hook_prefix'])
            ? $config['hook_prefix']
            : 'hyperfields_settings';

        return preg_replace('/[^a-z0-9_]/', '_', strtolower($prefix)) ?: 'hyperfields_settings';
    }

    private static function resolveTabs(array $config): array
    {
        $tabs = $config['tabs'] ?? [];
        if (!is_array($tabs)) {
            return [];
        }

        $normalized = [];
        foreach ($tabs as $priority => $tab) {
            if (!is_array($tab)) {
                continue;
            }
            if (!isset($tab['priority']) && is_int($priority)) {
                $tab['priority'] = $priority;
            }
            $normalized[] = $tab;
        }

        return $normalized;
    }

    private static function sortTabs(array $tabs): array
    {
        usort($tabs, static function (array $left, array $right): int {
            $left_priority = isset($left['priority']) ? (int) $left['priority'] : 9999;
            $right_priority = isset($right['priority']) ? (int) $right['priority'] : 9999;

            return $left_priority <=> $right_priority;
        });

        return $tabs;
    }

    private static function attachSectionOptions(
        OptionsPage $page,
        TabProxy $tab_proxy,
        SectionProxy $section_proxy,
        \HyperFields\OptionsSection $section
    ): void
    {
        $type_map = self::resolveLegacyOptionTypeMap();
        $section_args = $section_proxy->getArgs();
        $section_slug = isset($section_args['slug']) && is_string($section_args['slug']) && $section_args['slug'] !== ''
            ? $section_args['slug']
            : $section_proxy->getId();

        $tab_slug = $tab_proxy->getKey();

        $section_option_level = $section_proxy->is_option_level();
        $tab_option_level = $tab_proxy->is_option_level();

        $option_name = $page->getOptionName();

        $index = 0;
        foreach ($section_proxy->getOptions() as $option) {
            $type = isset($option['type']) ? (string) $option['type'] : 'text';
            $args = isset($option['args']) && is_array($option['args']) ? $option['args'] : [];
            $field = self::buildFieldFromOption(
                $type,
                $args,
                $tab_proxy,
                $section_proxy,
                $index,
                $option_name,
                $type_map
            );
            $index++;

            if (!$field) {
                continue;
            }

            $option_path = self::buildOptionPath(
                (string) ($args['name'] ?? ''),
                $tab_slug,
                $section_slug,
                $tab_option_level,
                $section_option_level
            );
            if ($option_path !== '') {
                $field->addArg('option_path', $option_path);
            }

            $section->addField($field);
        }
    }

    private static function buildFieldFromOption(
        string $type,
        array $args,
        TabProxy $tab_proxy,
        SectionProxy $section_proxy,
        int $index,
        string $option_name,
        array $type_map
    ): ?Field {
        if ($type === '') {
            return null;
        }

        if (isset($args['visible']) && is_callable($args['visible']) && call_user_func($args['visible']) === false) {
            return null;
        }

        if (in_array($type, ['code-editor', 'code_editor'], true)) {
            return self::buildCodeEditorField($args, $section_proxy, $index);
        }

        if (OptionTypeRegistry::has($type)) {
            return self::buildRegisteredOptionTypeField($type, $args, $section_proxy, $index);
        }

        $legacy_field = self::buildLegacyOptionTypeField(
            $type,
            $args,
            $section_proxy,
            $option_name,
            $type_map,
            $tab_proxy->is_option_level(),
            $section_proxy->is_option_level()
        );
        if ($legacy_field instanceof Field) {
            return $legacy_field;
        }

        $mapped = self::mapFieldType($type);
        $name = isset($args['name']) && is_string($args['name']) && $args['name'] !== ''
            ? $args['name']
            : sanitize_key($section_proxy->getId() . '_' . $type . '_' . $index);
        $label = isset($args['label']) && is_string($args['label']) ? $args['label'] : '';

        if (isset($args['render']) && is_callable($args['render'])) {
            $field = CustomField::build($name, $label);
            $field->setRenderCallback(static function (array $field_data, mixed $value) use ($args): void {
                $rendered = call_user_func($args['render'], $field_data, $value);
                if (is_string($rendered)) {
                    echo wp_kses_post($rendered);
                }
            });
            self::applyCommonFieldArgs($field, $args);

            return $field;
        }

        $field = Field::make($mapped, $name, $label);
        self::applyCommonFieldArgs($field, $args);

        return $field;
    }

    private static function buildRegisteredOptionTypeField(
        string $type,
        array $args,
        SectionProxy $section_proxy,
        int $index
    ): Field {
        $definition = OptionTypeRegistry::get($type);
        $name = isset($args['name']) && is_string($args['name']) && $args['name'] !== ''
            ? $args['name']
            : sanitize_key($section_proxy->getId() . '_' . $type . '_' . $index);
        $label = isset($args['label']) && is_string($args['label']) ? $args['label'] : '';

        $field = CustomField::build($name, $label)
            ->setRenderCallback(static function (array $field_data, mixed $value) use ($definition, $args): void {
                $output = call_user_func($definition['render'], $field_data, $value, $args);
                if (is_string($output)) {
                    echo wp_kses_post($output);
                }
            });

        if (is_callable($definition['sanitize'])) {
            $field->setSanitizeCallback($definition['sanitize']);
        }
        if (is_callable($definition['validate'])) {
            $field->setValidateCallback($definition['validate']);
        }

        return $field;
    }

    private static function buildCodeEditorField(
        array $args,
        SectionProxy $section_proxy,
        int $index
    ): Field {
        $name = isset($args['name']) && is_string($args['name']) && $args['name'] !== ''
            ? $args['name']
            : sanitize_key($section_proxy->getId() . '_code_editor_' . $index);
        $label = isset($args['label']) && is_string($args['label']) ? $args['label'] : '';
        $editor_type = isset($args['editor_type']) && is_string($args['editor_type'])
            ? $args['editor_type']
            : 'text/html';
        $description = isset($args['description']) && is_string($args['description'])
            ? $args['description']
            : '';

        $field = CustomField::build($name, $label)
            ->setSanitizeCallback(static fn (mixed $value): mixed => $value)
            ->setRenderCallback(static function (array $field_data, mixed $value) use ($editor_type, $description): void {
                $name_attr = isset($field_data['name_attr']) && is_string($field_data['name_attr'])
                    ? $field_data['name_attr']
                    : (string) ($field_data['name'] ?? '');
                $id = isset($field_data['name']) && is_string($field_data['name'])
                    ? $field_data['name']
                    : sanitize_key($name_attr);
                $editor_handle = 'hyperfields-editor-' . sanitize_key($id);

                if (function_exists('wp_enqueue_script')) {
                    wp_enqueue_script('wp-theme-plugin-editor');
                }
                if (function_exists('wp_enqueue_style')) {
                    wp_enqueue_style('wp-codemirror');
                }
                if (function_exists('wp_enqueue_code_editor') && function_exists('wp_localize_script')) {
                    $settings = wp_enqueue_code_editor(['type' => $editor_type]);
                    wp_localize_script('jquery', $editor_handle, $settings);
                    if (function_exists('wp_add_inline_script')) {
                        wp_add_inline_script(
                            'wp-theme-plugin-editor',
                            'jQuery(function($){if($("#' . esc_js($id) . '").length>0){wp.codeEditor.initialize($("#' . esc_js($id) . '"),' . $editor_handle . ');}});'
                        );
                    }
                }

                echo '<div class="hyperpress-field-wrapper"><div class="hyperpress-field-row">';
                echo '<div class="hyperpress-field-label"><label for="' . esc_attr($id) . '">' . esc_html((string) ($field_data['label'] ?? '')) . '</label></div>';
                echo '<div class="hyperpress-field-input-wrapper">';
                echo '<textarea id="' . esc_attr($id) . '" class="wp-settings-code-editor regular-text" name="' . esc_attr($name_attr) . '">' . esc_textarea((string) $value) . '</textarea>';
                if ($description !== '') {
                    echo '<p class="description">' . wp_kses_post($description) . '</p>';
                }
                echo '</div></div></div>';
            });

        self::applyCommonFieldArgs($field, $args);

        return $field;
    }

    private static function applyCommonFieldArgs(Field $field, array $args): void
    {
        if (array_key_exists('default', $args)) {
            $field->setDefault($args['default']);
        }
        if (isset($args['placeholder']) && is_string($args['placeholder'])) {
            $field->setPlaceholder($args['placeholder']);
        }
        if (isset($args['description']) && is_string($args['description'])) {
            $field->setDescription($args['description']);
            $field->addArg('help_is_html', true);
        } elseif (isset($args['desc']) && is_string($args['desc'])) {
            $field->setDescription($args['desc']);
            $field->addArg('help_is_html', true);
        }
        if (isset($args['required']) && (bool) $args['required'] === true) {
            $field->setRequired(true);
        }
        if (isset($args['options'])) {
            $options = $args['options'];
            if (is_callable($options)) {
                $options = call_user_func($options);
            }
            if (is_array($options)) {
                $field->setOptions($options);
            }
        }
        if (isset($args['validation']) && is_array($args['validation'])) {
            $field->setValidation($args['validation']);
        }
        if (isset($args['validate'])) {
            $field->addArg('wps_validate', $args['validate']);
            if (is_array($args['validate'])) {
                foreach ($args['validate'] as $rule) {
                    if (is_array($rule) && isset($rule['feedback']) && is_string($rule['feedback'])) {
                        $field->addArg('wps_validate_feedback', $rule['feedback']);
                        break;
                    }
                }
            }
        }
        if (isset($args['sanitize']) && is_callable($args['sanitize'])) {
            $field->addArg('wps_sanitize', $args['sanitize']);
        }
        if (isset($args['conditional_logic']) && is_array($args['conditional_logic'])) {
            $field->setConditionalLogic($args['conditional_logic']);
        }
        if (isset($args['attributes']) && is_array($args['attributes'])) {
            $field->addArg('attributes', $args['attributes']);
        }
        if (isset($args['custom_attributes']) && is_array($args['custom_attributes'])) {
            $attributes = [];
            if (isset($args['attributes']) && is_array($args['attributes'])) {
                $attributes = $args['attributes'];
            }
            $field->addArg('attributes', array_merge($attributes, $args['custom_attributes']));
        }
        if (isset($args['css']) && is_array($args['css'])) {
            if (isset($args['css']['input_class']) && is_string($args['css']['input_class'])) {
                $field->addArg('input_class', $args['css']['input_class']);
            }
            if (isset($args['css']['label_class']) && is_string($args['css']['label_class'])) {
                $field->addArg('label_class', $args['css']['label_class']);
            }
        }
        if (
            isset($args['type'])
            && is_string($args['type'])
            && in_array($args['type'], ['number', 'email', 'url'], true)
            && $field->getType() === 'text'
        ) {
            $field->addArg('input_type', $args['type']);
        }
        if (isset($args['rows'])) {
            $field->addArg('rows', (int) $args['rows']);
        }
        if (isset($args['cols'])) {
            $field->addArg('cols', (int) $args['cols']);
        }
        if ($field->getType() === 'checkbox') {
            $field->addArg('checkbox_unchecked_value', '');
        }
        if ($field->getType() === 'rich_text') {
            $editor_settings = [];
            foreach ([
                'wpautop',
                'teeny',
                'media_buttons',
                'default_editor',
                'drag_drop_upload',
                'textarea_rows',
                'tabindex',
                'tabfocus_elements',
                'editor_css',
                'editor_class',
                'tinymce',
                'quicktags',
            ] as $editor_key) {
                if (array_key_exists($editor_key, $args)) {
                    $editor_settings[$editor_key] = $args[$editor_key];
                }
            }
            if ($editor_settings !== []) {
                $field->addArg('editor_settings', $editor_settings);
            }
        }
    }

    private static function mapFieldType(string $type): string
    {
        return match ($type) {
            'choices' => 'radio',
            'select-multiple', 'select_multiple' => 'multiselect',
            'wp-editor', 'wp_editor' => 'rich_text',
            'media', 'video' => 'image',
            'code-editor', 'code_editor' => 'textarea',
            default => $type,
        };
    }

    private static function resolveLegacyOptionTypeMap(): array
    {
        $map = apply_filters('wp_settings_option_type_map', []);

        return is_array($map) ? $map : [];
    }

    private static function buildOptionPath(
        string $field_name,
        string $tab_slug,
        string $section_slug,
        bool $tab_option_level,
        bool $section_option_level
    ): string {
        $field_name = trim($field_name);
        if ($field_name === '') {
            return '';
        }

        $parts = [];
        if ($tab_option_level) {
            $parts[] = str_replace('-', '_', $tab_slug);
        }
        if ($section_option_level) {
            $parts[] = str_replace('-', '_', $section_slug);
        }
        $parts[] = $field_name;

        return implode('.', $parts);
    }

    private static function buildLegacyOptionTypeField(
        string $type,
        array $args,
        SectionProxy $section_proxy,
        string $option_name,
        array $type_map,
        bool $tab_option_level,
        bool $section_option_level
    ): ?Field {
        if (!isset($type_map[$type]) || !is_string($type_map[$type])) {
            return null;
        }

        $class_name = $type_map[$type];
        if (!class_exists($class_name)) {
            return null;
        }

        $name = isset($args['name']) && is_string($args['name']) && $args['name'] !== ''
            ? $args['name']
            : sanitize_key($section_proxy->getId() . '_' . $type);
        $label = isset($args['label']) && is_string($args['label']) ? $args['label'] : '';

        $settings = new class($option_name)
        {
            public function __construct(public string $option_name)
            {
            }
        };

        $tab_slug = $section_proxy->getTabKey();
        $tab = new class($settings, $tab_slug, $tab_option_level)
        {
            public function __construct(
                public object $settings,
                public string $slug,
                private bool $option_level
            ) {
            }

            public function is_option_level(): bool
            {
                return $this->option_level;
            }
        };

        $section_args = $section_proxy->getArgs();
        $section_slug = isset($section_args['slug']) && is_string($section_args['slug']) && $section_args['slug'] !== ''
            ? $section_args['slug']
            : $section_proxy->getId();
        $section = new class($tab, $section_slug, $section_option_level)
        {
            public function __construct(
                public object $tab,
                public string $slug,
                private bool $option_level
            ) {
            }

            public function is_option_level(): bool
            {
                return $this->option_level;
            }
        };

        $instance = new $class_name($section, $args);
        if (!method_exists($instance, 'render')) {
            return null;
        }

        $field = CustomField::build($name, $label)
            ->setRenderCallback(static function () use ($instance): void {
                ob_start();
                $instance->render();
                echo wp_kses_post((string) ob_get_clean());
            });

        if (method_exists($instance, 'sanitize')) {
            $field->setSanitizeCallback(static fn (mixed $value): mixed => $instance->sanitize($value));
        }
        if (method_exists($instance, 'validate')) {
            $field->setValidateCallback(static fn (mixed $value): bool => (bool) $instance->validate($value));
        }

        self::applyCommonFieldArgs($field, $args);

        return $field;
    }

    private static function registerLifecycleHooks(OptionsPage $page, array $config, string $hook_prefix): void
    {
        $option_name = $page->getOptionName();
        if (isset(self::$registered_lifecycle[$option_name])) {
            return;
        }

        add_filter(
            'pre_update_option_' . $option_name,
            static function (mixed $new_value, mixed $old_value, string $option) use ($config, $hook_prefix): mixed {
                do_action('hyperfields/settings/before_save', $new_value, $old_value, $option, $config);
                do_action($hook_prefix . '_before_save', $new_value, $old_value, $option, $config);

                $validated = apply_filters('hyperfields/settings/validate', $new_value, $old_value, $option, $config);
                $validated = apply_filters($hook_prefix . '_validate', $validated, $old_value, $option, $config);

                if ($validated instanceof WP_Error) {
                    if (function_exists('add_settings_error')) {
                        add_settings_error(
                            $option,
                            'hyperfields_settings_validation_error',
                            'Settings validation failed.',
                            'error'
                        );
                    }

                    return $old_value;
                }

                return $validated;
            },
            10,
            3
        );

        add_action(
            'updated_option_' . $option_name,
            static function (mixed $old_value, mixed $new_value, string $option) use ($config, $hook_prefix): void {
                do_action('hyperfields/settings/after_save', $new_value, $old_value, $option, $config);
                do_action($hook_prefix . '_after_save', $new_value, $old_value, $option, $config);
            },
            10,
            3
        );

        self::$registered_lifecycle[$option_name] = true;
    }
}
