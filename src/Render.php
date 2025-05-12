<?php

/**
 * Handles rendering the HTMX template.
 *
 * @since   2023-11-22
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Class.
 */
class Render
{
    // Properties
    protected $template_name;
    protected $nonce;
    protected $hxvals = false;

    /**
     * Render the template.
     *
     * @since 2023-11-22
     * @return void
     */
    public function load_template()
    {
        global $wp_query;

        // Don't go further if this is not a request for our endpoint
        if (!isset($wp_query->query_vars[HXWP_ENDPOINT])) {
            return;
        }

        // Check if nonce exists and is valid, only on POST requests
        if (!$this->valid_nonce() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            wp_die(esc_html__('Invalid nonce', 'api-for-htmx'), esc_html__('Error', 'api-for-htmx'), ['response' => 403]);
        }

        // Sanitize template name
        $template_name = $this->sanitize_path($wp_query->query_vars[HXWP_ENDPOINT]);

        // Get hxvals from $_REQUEST
        $hxvals = $_REQUEST; // nonce already validated

        if (!isset($hxvals) || empty($hxvals)) {
            $hxvals = false;
        } else {
            $hxvals = $this->sanitize_params($hxvals);
        }

        // Load the requested template or fail with a 404
        $this->render_or_fail($template_name, $hxvals);
        die(); // No wp_die() here, we don't want to show the complete WP error page
    }

    /**
     * Render or fail
     * Load the requested template or fail with a 404.
     *
     * @since 2023-11-30
     * @param string $template_name
     * @param array|bool $hxvals
     *
     * @return void
     */
    protected function render_or_fail($template_name = '', $hxvals = false)
    {
        if (empty($template_name)) {
            status_header(404);

            wp_die(esc_html__('Invalid template name', 'api-for-htmx'), esc_html__('Error', 'api-for-htmx'), ['response' => 404]);
        }

        // Get our template file and vars
        $template_path = $this->get_template_file($template_name);

        if (!$template_path) {
            status_header(404);

            wp_die(esc_html__('Invalid route', 'api-for-htmx'), esc_html__('Error', 'api-for-htmx'), ['response' => 404]);
        }

        // Check if the template exists
        if (!file_exists($template_path)) {
            // Set 404 status
            status_header(404);

            wp_die(esc_html__('Template not found', 'api-for-htmx'), esc_html__('Error', 'api-for-htmx'), ['response' => 404]);
        }

        // To help developers know when template files were loaded via our plugin
        define('HXWP_REQUEST', true);

        // Load the template
        require_once $template_path;
    }

    /**
     * Check if nonce exists and is valid
     * nonce: hxwp_nonce.
     *
     * @since 2023-11-30
     *
     * @return bool
     */
    protected function valid_nonce()
    {
        // https://github.com/WP-API/api-core/blob/develop/wp-includes/rest-api.php#L555
        $nonce = null;

        if (isset($_REQUEST['_wpnonce'])) {
            $nonce = sanitize_key($_REQUEST['_wpnonce']);
        } elseif (isset($_SERVER['HTTP_X_WP_NONCE'])) {
            $nonce = sanitize_key($_SERVER['HTTP_X_WP_NONCE']);
        }

        if (null === $nonce) {
            // No nonce at all, so act as if it's an unauthenticated request.
            wp_set_current_user(0);

            return false;
        }

        if (!wp_verify_nonce(
            sanitize_text_field(wp_unslash($nonce)),
            'hxwp_nonce'
        )) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize path.
     * This method sanitizes the template path string received from the URL.
     * If the path uses a colon for namespacing (e.g., "namespace:path/to/template"),
     * the namespace and the subsequent path segments are sanitized separately.
     * Otherwise, the entire string is treated as a theme-relative path and sanitized.
     *
     * @since 2023-11-30
     * @param string $path_string The raw path string from the query variable.
     *
     * @return string|false The sanitized path string, or false if sanitization fails or input is empty.
     */
    private function sanitize_path($path_string = '')
    {
        if (empty($path_string)) {
            return false;
        }

        $path_string = (string) $path_string;

        // Attempt to parse using the colon separator.
        $parsed_data = $this->parse_namespaced_template($path_string);

        if ($parsed_data !== false) {
            // Namespaced path: namespace:template_segment
            $namespace = sanitize_key($parsed_data['namespace']);
            $template_segment = $parsed_data['template'];

            // Sanitize the template_segment (which can be 'file' or 'subdir/file')
            $template_segment_parts = explode('/', $template_segment);
            $sanitized_template_segment_parts = [];

            foreach ($template_segment_parts as $index => $part) {
                if (empty($part) && count($template_segment_parts) > 1) { // Allow empty part if it's not the only part (e.g. trailing slash)
                    // However, explode usually doesn't create empty parts in the middle unless there are //
                    // For robustness, skip empty parts that are not significant.
                    continue;
                }
                $part_cleaned = str_replace('..', '', $part); // Basic traversal prevention
                $part_cleaned = remove_accents($part_cleaned);

                if ($index === count($template_segment_parts) - 1) {
                    // Last part is the filename
                    $sanitized_template_segment_parts[] = $this->sanitize_file_name($part_cleaned);
                } else {
                    // Directory part
                    $sanitized_template_segment_parts[] = sanitize_key($part_cleaned);
                }
            }
            // Filter out any truly empty parts that might result from sanitization or original string (e.g. "foo//bar")
            $filtered_parts = array_filter($sanitized_template_segment_parts, function($value) { return $value !== ''; });
            $sanitized_template_segment = implode('/', $filtered_parts);


            if (empty($namespace) || empty($sanitized_template_segment)) {
                return false; // Invalid if either part becomes empty after sanitization
            }
            return $namespace . ':' . $sanitized_template_segment;

        } else {
            // Not a namespaced path (no colon, or invalid format). Treat as theme-relative.
            $template_segment_parts = explode('/', $path_string);
            $sanitized_template_segment_parts = [];

            foreach ($template_segment_parts as $index => $part) {
                if (empty($part) && count($template_segment_parts) > 1) {
                    continue;
                }
                $part_cleaned = str_replace('..', '', $part); // Basic traversal prevention
                $part_cleaned = remove_accents($part_cleaned);

                if ($index === count($template_segment_parts) - 1) {
                    // Last part is the filename
                    $sanitized_template_segment_parts[] = $this->sanitize_file_name($part_cleaned);
                } else {
                    // Directory part
                    $sanitized_template_segment_parts[] = sanitize_key($part_cleaned);
                }
            }
            $filtered_parts = array_filter($sanitized_template_segment_parts, function($value) { return $value !== ''; });
            $sanitized_path = implode('/', $filtered_parts);

            return empty($sanitized_path) ? false : $sanitized_path;
        }
    }

    /**
     * Sanitize file name.
     *
     * @since 2023-11-30
     * @param string $file_name
     *
     * @return string | bool
     */
    private function sanitize_file_name($file_name = '')
    {
        if (empty($file_name)) {
            return false;
        }

        // Remove accents and sanitize it
        $file_name = sanitize_file_name(remove_accents($file_name));

        return $file_name;
    }

    /**
     * Sanitize hxvals.
     *
     * @since 2023-11-30
     * @param array $hxvals
     *
     * @return array | bool
     */
    private function sanitize_params($hxvals = [])
    {
        if (empty($hxvals)) {
            return false;
        }

        // Sanitize each param
        foreach ($hxvals as $key => $value) {
            // Sanitize key
            $key = apply_filters('hxwp/sanitize_param_key', sanitize_key($key), $key);

            // For form elements with multiple values
            // https://github.com/EstebanForge/HTMX-API-WP/discussions/8
            if (is_array($value)) {
                // Sanitize each value
                $value = apply_filters('hxwp/sanitize_param_array_value', array_map('sanitize_text_field', $value), $key);
            } else {
                // Sanitize single value
                $value = apply_filters('hxwp/sanitize_param_value', sanitize_text_field($value), $key);
            }

            // Update param
            $hxvals[$key] = $value;
        }

        // Remove nonce if exists
        if (isset($hxvals['hxwp_nonce'])) {
            unset($hxvals['hxwp_nonce']);
        }

        return $hxvals;
    }

    /**
     * Get active theme or child theme path
     * If a child theme is active, use it instead of the parent theme.
     *
     * @since 2023-11-30
     *
     * @return string
     */
    protected function get_theme_path()
    {
        $theme_path = trailingslashit(get_template_directory());

        if (is_child_theme()) {
            $theme_path = trailingslashit(get_stylesheet_directory());
        }

        return $theme_path;
    }

    /**
     * Determine our template file.
     * It first checks for templates in paths registered via 'hxwp/register_template_path'.
     * If a namespaced template is requested (e.g., "namespace:template-name") and found, it's used.
     * If an explicit namespace is used but not found, it will fail (no fallback).
     * Otherwise (no namespace in request), it falls back to the default theme's htmx-templates directory.
     *
     * @since 2023-11-30
     * @param string $template_name The sanitized template name, possibly including a namespace (e.g., "namespace:template-file").
     *
     * @return string|false The full, sanitized path to the template file, or false if not found.
     */
    protected function get_template_file($template_name = '')
    {
        if (empty($template_name)) {
            return false;
        }

        $namespaced_paths = apply_filters('hxwp/register_template_path', []);
        $parsed_template_data = $this->parse_namespaced_template($template_name);

        if ($parsed_template_data !== false) {
            // A colon was present and correctly parsed into namespace and template parts.
            // This is an explicit namespaced request.
            $namespace = $parsed_template_data['namespace'];
            $template_part = $parsed_template_data['template'];

            if (isset($namespaced_paths[$namespace])) {
                $base_dir_registered = trailingslashit((string) $namespaced_paths[$namespace]);
                $potential_path = $base_dir_registered . $template_part . HXWP_EXT;

                // Sanitize_full_path uses realpath.
                $resolved_path = $this->sanitize_full_path($potential_path);

                if ($resolved_path) {
                    // Ensure the resolved path is within the registered base directory.
                    $real_base_dir = realpath($base_dir_registered);
                    if ($real_base_dir && str_starts_with($resolved_path, $real_base_dir . DIRECTORY_SEPARATOR)) {
                        return $resolved_path;
                    }
                     // Check if the resolved path is the base directory itself (e.g. if template_part was empty and base_dir_registered was the file)
                    if ($real_base_dir && $resolved_path === $real_base_dir && str_ends_with($resolved_path, HXWP_EXT) ) {
                        return $resolved_path;
                    }
                }
            }
            // If colon was used (explicit namespace) but namespace not registered or file not found/allowed:
            return false; // No fallback for explicit namespaced requests.
        } else {
            // No colon found (or invalid colon format). Treat as a theme-relative path.
            $default_templates_paths_array = apply_filters_deprecated(
                'hxwp/get_template_file/templates_path',
                [$this->get_theme_path() . HXWP_TEMPLATE_DIR . '/'],
                '1.2.0',
                'hxwp/register_template_path',
                esc_html__('Use namespaced template paths for better organization and to avoid conflicts.', 'api-for-htmx')
            );

            foreach ((array) $default_templates_paths_array as $default_path_item_base) {
                if (empty($default_path_item_base)) continue;

                $base_dir_theme = trailingslashit((string) $default_path_item_base);
                $potential_path = $base_dir_theme . $template_name . HXWP_EXT;
                $resolved_path = $this->sanitize_full_path($potential_path);

                if ($resolved_path) {
                    // Ensure the resolved path is within the theme's template base directory.
                    $real_base_dir = realpath($base_dir_theme);
                    if ($real_base_dir && str_starts_with($resolved_path, $real_base_dir . DIRECTORY_SEPARATOR)) {
                        return $resolved_path;
                    }
                    // Check if the resolved path is the base directory itself
                    if ($real_base_dir && $resolved_path === $real_base_dir && str_ends_with($resolved_path, HXWP_EXT)) {
                        return $resolved_path;
                    }
                }
            }
        }

        return false; // No valid template found
    }

    /**
     * Parses a template name that might contain a namespace, using ':' as the separator.
     * e.g., "myplugin:template-name" -> ['namespace' => 'myplugin', 'template' => 'template-name'].
     *
     * @since 1.2.1 Changed separator from '/' to ':'.
     * @param string $template_name The template name to parse.
     * @return array{'namespace': string, 'template': string}|false Array with 'namespace' and 'template' keys if ':' is found and parts are valid, or false otherwise.
     */
    protected function parse_namespaced_template($template_name)
    {
        if (str_contains((string) $template_name, ':')) {
            $parts = explode(':', (string) $template_name, 2);
            if (count($parts) === 2 && !empty($parts[0]) && !empty($parts[1])) {
                return [
                    'namespace' => $parts[0],
                    'template'  => $parts[1],
                ];
            }
        }
        return false; // No valid colon separator found, or parts were empty.
    }

    /**
     * Sanitize full path.
     *
     * @since 2023-12-13
     *
     * @param string $full_path
     *
     * @return string | bool
     */
    protected function sanitize_full_path($full_path = '')
    {
        if (empty($full_path)) {
            return false;
        }

        // Ensure full path is always a string
        $full_path = (string) $full_path;

        // Realpath
        $full_path = realpath($full_path);

        return $full_path;
    }
}
