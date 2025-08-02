<?php

declare(strict_types=1);

namespace HMApi\Fields;

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
     * Create an OptionsPage instance.
     *
     * @param string $page_title The title of the page
     * @param string $menu_slug The slug for the menu
     * @return OptionsPage
     */
    public static function makeOptionPage(string $page_title, string $menu_slug): OptionsPage
    {
        return OptionsPage::make($page_title, $menu_slug);
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
        return update_option($option_name, $options);
    }
}
