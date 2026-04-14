<?php

declare(strict_types=1);

namespace HyperFields;

use HyperFields\Admin\ExportImportUI;
use HyperFields\Compatibility\WPSettingsCompatibility;
use HyperFields\Container\ContainerFactory;
use HyperFields\Transfer\Manager as TransferManager;

/**
 * HyperFields Facade.
 *
 * A simplified API for creating HyperFields components.
 * This facade allows third-party developers to import just one class
 * instead of multiple individual field classes.
 *
 * @since 2.1.0
 */
class HyperFields
{
    /**
     * Register an options page from a configuration array.
     *
     * @param array $config The configuration array for the options page.
     * @return void
     */
    public static function registerOptionsPage(array $config): void
    {
        $prefix = $config['prefix'] ?? '';
        $options_page = self::makeOptionPage($config['title'], $config['slug'], $prefix);

        if (isset($config['menu_title'])) {
            $options_page->setMenuTitle($config['menu_title']);
        }
        if (isset($config['parent_slug'])) {
            $options_page->setParentSlug($config['parent_slug']);
        }
        if (isset($config['capability'])) {
            $options_page->setCapability($config['capability']);
        }
        if (isset($config['option_name'])) {
            $options_page->setOptionName($config['option_name']);
        }
        if (isset($config['footer_content'])) {
            $options_page->setFooterContent($config['footer_content']);
        }

        if (isset($config['sections']) && is_array($config['sections'])) {
            foreach ($config['sections'] as $section_config) {
                if (!isset($section_config['id'], $section_config['title'])) {
                    continue;
                }

                $section = $options_page->addSection($section_config['id'], $section_config['title'], $section_config['description'] ?? '');

                if (isset($section_config['fields']) && is_array($section_config['fields'])) {
                    foreach ($section_config['fields'] as $field_config) {
                        if (!isset($field_config['type'], $field_config['name'])) {
                            continue;
                        }

                        $field = self::makeField($field_config['type'], $field_config['name'], $field_config['label'] ?? '');

                        if (isset($field_config['default'])) {
                            $field->setDefault($field_config['default']);
                        }
                        if (isset($field_config['placeholder'])) {
                            $field->setPlaceholder($field_config['placeholder']);
                        }
                        if (isset($field_config['help'])) {
                            $field->setHelp($field_config['help']);
                        }
                        if (isset($field_config['options'])) {
                            $field->setOptions($field_config['options']);
                        }
                        if (isset($field_config['html_content'])) {
                            $field->setHtmlContent($field_config['html_content']);
                        }

                        $section->addField($field);
                    }
                }
            }
        }

        $options_page->register();
    }

    /**
     * Register an options page from a compatibility settings configuration.
     *
     * @param array $config The compatibility configuration.
     * @return OptionsPage
     */
    public static function registerWPSettingsCompatibilityPage(array $config): OptionsPage
    {
        return WPSettingsCompatibility::register($config);
    }

    /**
     * Register an options page from a compatibility settings configuration.
     *
     * @deprecated Use registerWPSettingsCompatibilityPage().
     *
     * @param array $config The compatibility configuration.
     * @return OptionsPage
     */
    public static function registerSettingsCompatibilityPage(array $config): OptionsPage
    {
        return self::registerWPSettingsCompatibilityPage($config);
    }

    /**
     * Create an OptionsPage instance.
     *
     * @param string $page_title The title of the page
     * @param string $menu_slug The slug for the menu
     * @param string $prefix Optional prefix for field names
     * @return OptionsPage
     */
    public static function makeOptionPage(string $page_title, string $menu_slug, string $prefix = ''): OptionsPage
    {
        return OptionsPage::make($page_title, $menu_slug, $prefix);
    }

    /**
     * Create a Field instance.
     *
     * @param string $type The field type
     * @param string $name The field name
     * @param string $label The field label (optional)
     * @return Field
     */
    public static function makeField(string $type, string $name, string $label = ''): Field
    {
        return Field::make($type, $name, $label);
    }

    /**
     * Create a TabsField instance.
     *
     * @param string $name The field name
     * @param string $label The field label
     * @return TabsField
     */
    public static function makeTabs(string $name, string $label): TabsField
    {
        return TabsField::make($name, $label);
    }

    /**
     * Create a RepeaterField instance.
     *
     * @param string $name The field name
     * @param string $label The field label
     * @return RepeaterField
     */
    public static function makeRepeater(string $name, string $label): RepeaterField
    {
        return RepeaterField::make($name, $label);
    }

    /**
     * Create an OptionsSection instance.
     *
     * @param string $id The section ID
     * @param string $title The section title
     * @return OptionsSection
     */
    public static function makeSection(string $id, string $title): OptionsSection
    {
        return OptionsSection::make($id, $title);
    }

    /**
     * Create a SeparatorField instance.
     *
     * @param string $name The field name
     * @return SeparatorField
     */
    public static function makeSeparator(string $name): Field
    {
        return self::makeField('separator', $name, '');
    }

    /**
     * Create a HeadingField instance.
     *
     * @param string $name The field name
     * @param string $label The field label
     * @return HeadingField
     */
    public static function makeHeading(string $name, string $label): Field
    {
        return self::makeField('html', $name, $label);
    }

    /**
     * Get all options for a given option name.
     *
     * @param string $option_name The name of the option.
     * @param array  $default     The default value to return if the option is not set.
     * @return array
     */
    public static function getOptions(string $option_name, array $default = []): array
    {
        $value = get_option($option_name, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * Get the value of a field.
     *
     * @param string $option_name The name of the option
     * @param string $field_name The name of the field
     * @param mixed $default The default value to return if the field is not set
     * @return mixed
     */
    public static function getFieldValue(string $option_name, string $field_name, mixed $default = null): mixed
    {
        $options = get_option($option_name, []);

        return $options[$field_name] ?? $default;
    }

    /**
     * Set the value of a field.
     *
     * @param string $option_name The name of the option
     * @param string $field_name The name of the field
     * @param mixed $value The value to set
     * @return bool
     */
    public static function setFieldValue(string $option_name, string $field_name, mixed $value): bool
    {
        $options = get_option($option_name, []);
        $options[$field_name] = $value;

        // Debug trace
        // error_log("setFieldValue called for $option_name, $field_name");

        return update_option($option_name, $options);
    }

    /**
     * Delete a field value from an option.
     *
     * @param string $option_name The name of the option
     * @param string $field_name The name of the field
     * @return bool
     */
    public static function deleteFieldOption(string $option_name, string $field_name): bool
    {
        $options = get_option($option_name, []);

        if (isset($options[$field_name])) {
            unset($options[$field_name]);

            return update_option($option_name, $options);
        }

        return false;
    }

    /**
     * Create a post meta container.
     *
     * @param string $id The container ID
     * @param string $title The container title
     * @return Container\PostMetaContainer
     */
    public static function createPostMetaContainer(string $id, string $title): Container\PostMetaContainer
    {
        return ContainerFactory::createPostMetaContainer($id, $title);
    }

    /**
     * Create a post meta container (alias).
     *
     * @param string $id The container ID
     * @param string $title The container title
     * @return Container\PostMetaContainer
     */
    public static function makePostMeta(string $id, string $title): Container\PostMetaContainer
    {
        return self::createPostMetaContainer($id, $title);
    }

    /**
     * Create a term meta container.
     *
     * @param string $id The container ID
     * @param string $title The container title
     * @return Container\TermMetaContainer
     */
    public static function makeTermMeta(string $id, string $title): Container\TermMetaContainer
    {
        return ContainerFactory::makeTermMeta($id, $title);
    }

    /**
     * Create a user meta container.
     *
     * @param string $id The container ID
     * @param string $title The container title
     * @return Container\UserMetaContainer
     */
    public static function makeUserMeta(string $id, string $title): Container\UserMetaContainer
    {
        return ContainerFactory::makeUserMeta($id, $title);
    }

    /**
     * Register an Export / Import admin page as a submenu of an existing menu.
     *
     * Must be called from inside an `admin_menu` action hook.
     *
     * @param string $parentSlug           Parent menu slug (e.g. 'my-plugin' or 'options-general.php').
     * @param string $pageSlug             Unique slug for this page (e.g. 'my-plugin-data-tools').
     * @param array  $options              Associative map of WP option names to human-readable labels.
     *                                     Example: ['myplugin_options' => 'My Plugin Settings']
     * @param array  $allowedImportOptions Whitelist of option names permitted on import.
     *                                     Defaults to all keys in $options.
     * @param string $prefix               Optional key prefix applied to both export and import.
     * @param string $title                Page heading and menu label.
     * @param string $capability           Required capability. Default: 'manage_options'.
     */
    public static function registerDataToolsPage(
        string $parentSlug,
        string $pageSlug,
        array $options = [],
        array $allowedImportOptions = [],
        string $prefix = '',
        string $title = 'Data Export / Import',
        string $capability = 'manage_options'
    ): void {
        ExportImportUI::registerPage(
            parentSlug: $parentSlug,
            pageSlug: $pageSlug,
            options: $options,
            allowedImportOptions: $allowedImportOptions,
            prefix: $prefix,
            title: $title,
            capability: $capability,
        );
    }

    /**
     * Export options to JSON.
     *
     * @param array $optionNames Option names to export.
     * @param string $prefix Optional prefix filter: only keys starting with this prefix are included.
     * @return string JSON string of the exported data.
     */
    public static function exportOptions(array $optionNames, string $prefix = ''): string
    {
        return ExportImport::exportOptions($optionNames, $prefix);
    }

    /**
     * Import options from JSON.
     *
     * @param string $jsonString The JSON string to import.
     * @param array $allowedOptionNames Whitelist of option names allowed to be written.
     *                                   Empty array means all option names in the JSON are allowed.
     * @param string $prefix Optional prefix filter: only keys starting with this prefix are imported.
     * @return array Result with 'success', 'message', and optional 'backup_keys'.
     */
    public static function importOptions(string $jsonString, array $allowedOptionNames = [], string $prefix = '', array $options = []): array
    {
        return ExportImport::importOptions($jsonString, $allowedOptionNames, $prefix, $options);
    }

    /**
     * Build a dry-run diff report for options import.
     *
     * @param string $jsonString JSON export payload.
     * @param array  $allowedOptionNames Import whitelist for options.
     * @param string $prefix Optional key prefix filter.
     * @param array  $options Optional import behavior (for example ['mode' => 'replace']).
     */
    public static function diffOptions(string $jsonString, array $allowedOptionNames = [], string $prefix = '', array $options = []): array
    {
        return ExportImport::diffOptions($jsonString, $allowedOptionNames, $prefix, $options);
    }

    /**
     * Export posts/pages/CPT content to JSON.
     */
    public static function exportPosts(array $postTypes, array $options = []): string
    {
        return ContentExportImport::exportPosts($postTypes, $options);
    }

    /**
     * Snapshot posts/pages/CPT content as arrays for compare workflows.
     */
    public static function snapshotPosts(array $postTypes, array $options = []): array
    {
        return ContentExportImport::snapshotPosts($postTypes, $options);
    }

    /**
     * Import posts/pages/CPT content from JSON.
     */
    public static function importPosts(string $jsonString, array $options = []): array
    {
        return ContentExportImport::importPosts($jsonString, $options);
    }

    /**
     * Dry-run compare for content imports.
     */
    public static function diffPosts(string $jsonString, array $options = []): array
    {
        return ContentExportImport::diffPosts($jsonString, $options);
    }

    /**
     * Create a transfer manager for pluggable module orchestration.
     */
    public static function makeTransferManager(): TransferManager
    {
        return new TransferManager();
    }
}
