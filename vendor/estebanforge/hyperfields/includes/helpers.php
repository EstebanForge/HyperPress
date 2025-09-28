<?php

declare(strict_types=1);

use HyperFields\Field;
use HyperFields\OptionsPage;
use HyperFields\OptionsSection;
use HyperFields\RepeaterField;
use HyperFields\TabsField;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Create an OptionsPage instance.
 *
 * @param string $page_title The title of the page
 * @param string $menu_slug  The slug for the menu
 * @return OptionsPage
 */
function hf_option_page(string $page_title, string $menu_slug): OptionsPage
{
    return OptionsPage::make($page_title, $menu_slug);
}

/**
 * Create a Field instance.
 *
 * @param string $type  The field type
 * @param string $name  The field name
 * @param string $label The field label
 * @return Field
 */
function hf_field(string $type, string $name, string $label): Field
{
    return Field::make($type, $name, $label);
}

/**
 * Create a TabsField instance.
 *
 * @param string $name  The field name
 * @param string $label The field label
 * @return TabsField
 */
function hf_tabs(string $name, string $label): TabsField
{
    return TabsField::make($name, $label);
}

/**
 * Create a RepeaterField instance.
 *
 * @param string $name  The field name
 * @param string $label The field label
 * @return RepeaterField
 */
function hf_repeater(string $name, string $label): RepeaterField
{
    return RepeaterField::make($name, $label);
}

/**
 * Create an OptionsSection instance.
 *
 * @param string $id    The section ID
 * @param string $title The section title
 * @return OptionsSection
 */
function hf_section(string $id, string $title): OptionsSection
{
    return OptionsSection::make($id, $title);
}

/**
 * Resolve field context into a normalized structure.
 *
 * @param mixed $source Context source
 * @param array $args   Additional arguments
 * @return array
 */
function hf_resolve_field_context($source = null, array $args = []): array
{
    $context = [
        'type' => 'option',
        'object_id' => 0,
        'option_group' => $args['option_group'] ?? apply_filters('hyperfields/helpers/default_option_group', 'hyperfields_options'),
    ];

    if (is_array($source)) {
        $context['type'] = $source['type'] ?? $context['type'];
        if (isset($source['id'])) {
            $context['object_id'] = (int) $source['id'];
        }
        if (isset($source['option_group'])) {
            $context['option_group'] = (string) $source['option_group'];
        }

        return $context;
    }

    if ($source instanceof WP_Post) {
        $context['type'] = 'post';
        $context['object_id'] = (int) $source->ID;

        return $context;
    }

    if ($source instanceof WP_User) {
        $context['type'] = 'user';
        $context['object_id'] = (int) $source->ID;

        return $context;
    }

    if ($source instanceof WP_Term) {
        $context['type'] = 'term';
        $context['object_id'] = (int) $source->term_id;

        return $context;
    }

    if (is_numeric($source)) {
        $context['type'] = 'post';
        $context['object_id'] = (int) $source;

        return $context;
    }

    if (is_string($source)) {
        if (strpos($source, 'user_') === 0) {
            $context['type'] = 'user';
            $context['object_id'] = (int) substr($source, 5);

            return $context;
        }
        if (strpos($source, 'term_') === 0) {
            $context['type'] = 'term';
            $context['object_id'] = (int) substr($source, 5);

            return $context;
        }
        if ($source === 'option' || $source === 'options') {
            $context['type'] = 'option';

            return $context;
        }
    }

    // Fallbacks when $source is null or unrecognized
    $post_id = get_the_ID();
    if ($post_id) {
        $context['type'] = 'post';
        $context['object_id'] = (int) $post_id;

        return $context;
    }

    return $context; // default is option
}

/**
 * Optionally sanitize a value using Field::sanitizeValue when a type is provided.
 *
 * @param string $name  Field name
 * @param mixed  $value Value to sanitize
 * @param array  $args  Arguments including type
 * @return mixed
 */
function hf_maybe_sanitize_field_value(string $name, $value, array $args = [])
{
    $type = $args['type'] ?? null;
    if (is_string($type) && $type !== '') {
        try {
            $field = Field::make($type, $name, $name);

            return $field->sanitizeValue($value);
        } catch (Throwable $e) {
            // Fall through to filters if Field cannot be created
        }
    }

    // Allow external sanitization via filter when no type is provided
    return apply_filters('hyperfields/helpers/update_field_sanitize', $value, $name, $args);
}

/**
 * Get a field value from post/user/term meta or options.
 *
 * @param string $name   Meta key / option key
 * @param mixed  $source Context
 * @param array  $args   Arguments
 * @return mixed
 */
function hf_get_field(string $name, $source = null, array $args = [])
{
    $ctx = hf_resolve_field_context($source, $args);

    switch ($ctx['type']) {
        case 'post':
            if ($ctx['object_id'] > 0) {
                $val = get_post_meta($ctx['object_id'], $name, true);

                return $val !== '' ? $val : ($args['default'] ?? null);
            }
            break;
        case 'user':
            if ($ctx['object_id'] > 0) {
                $val = get_user_meta($ctx['object_id'], $name, true);

                return $val !== '' ? $val : ($args['default'] ?? null);
            }
            break;
        case 'term':
            if ($ctx['object_id'] > 0) {
                $val = get_term_meta($ctx['object_id'], $name, true);

                return $val !== '' ? $val : ($args['default'] ?? null);
            }
            break;
        case 'option':
        default:
            $group = $ctx['option_group'];
            $options = get_option($group, []);
            if (is_array($options) && array_key_exists($name, $options)) {
                return $options[$name];
            }

            return $args['default'] ?? null;
    }

    return $args['default'] ?? null;
}

/**
 * Update (save) a field value into post/user/term meta or options.
 *
 * @param string $name  Field name
 * @param mixed  $value Value to save
 * @param mixed  $source Context
 * @param array  $args  Arguments
 * @return bool
 */
function hf_update_field(string $name, $value, $source = null, array $args = []): bool
{
    $ctx = hf_resolve_field_context($source, $args);
    $sanitized = hf_maybe_sanitize_field_value($name, $value, $args);

    switch ($ctx['type']) {
        case 'post':
            if ($ctx['object_id'] > 0) {
                return (bool) update_post_meta($ctx['object_id'], $name, $sanitized);
            }
            break;
        case 'user':
            if ($ctx['object_id'] > 0) {
                return (bool) update_user_meta($ctx['object_id'], $name, $sanitized);
            }
            break;
        case 'term':
            if ($ctx['object_id'] > 0) {
                return (bool) update_term_meta($ctx['object_id'], $name, $sanitized);
            }
            break;
        case 'option':
        default:
            $group = $ctx['option_group'];
            $options = get_option($group, []);
            if (!is_array($options)) {
                $options = [];
            }
            $options[$name] = $sanitized;

            return (bool) update_option($group, $options);
    }

    return false;
}

/**
 * Delete a field value from post/user/term meta or options.
 *
 * @param string $name  Field name
 * @param mixed  $source Context
 * @param array  $args  Arguments
 * @return bool
 */
function hf_delete_field(string $name, $source = null, array $args = []): bool
{
    $ctx = hf_resolve_field_context($source, $args);

    switch ($ctx['type']) {
        case 'post':
            if ($ctx['object_id'] > 0) {
                return (bool) delete_post_meta($ctx['object_id'], $name);
            }
            break;
        case 'user':
            if ($ctx['object_id'] > 0) {
                return (bool) delete_user_meta($ctx['object_id'], $name);
            }
            break;
        case 'term':
            if ($ctx['object_id'] > 0) {
                return (bool) delete_term_meta($ctx['object_id'], $name);
            }
            break;
        case 'option':
        default:
            $group = $ctx['option_group'];
            $options = get_option($group, []);
            if (!is_array($options)) {
                return false;
            }
            if (array_key_exists($name, $options)) {
                unset($options[$name]);

                return (bool) update_option($group, $options);
            }

            return false;
    }

    return false;
}

/**
 * Alias of hf_update_field for parity with the initial TODO wording.
 *
 * @param string $name  Field name
 * @param mixed  $value Value to save
 * @param mixed  $source Context
 * @param array  $args  Arguments
 * @return bool
 */
function hf_save_field(string $name, $value, $source = null, array $args = []): bool
{
    return hf_update_field($name, $value, $source, $args);
}
