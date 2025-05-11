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
     *
     * @since 2023-11-30
     * @param string $path
     *
     * @return string | bool
     */
    private function sanitize_path($path = '')
    {
        if (empty($path)) {
            return false;
        }

        // Ensure path is always a string
        $path = (string) $path;

        // Replace spaces with hyphens (standard behavior)
        $path = str_replace(' ', '-', $path);

        // Don't allow directory traversal
        $path = str_replace('..', '', $path);

        // Remove accents
        $path = remove_accents($path);

        // Split the path into an array
        $path = explode('/', $path);

        // Remove empty values
        $path = array_filter($path);

        // Last element is the file name, sanitize it
        $path[count($path) - 1] = $this->sanitize_file_name(end($path));

        // Reconstruct the path with forward slashes
        $path = implode('/', $path);

        return $path;
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
     * It first checks for templates in paths registered via 'hxwp/register_namespaced_template_path'.
     * If a namespaced template is requested (e.g., "namespace/template-name") and found, it's used.
     * Otherwise, it falls back to the default theme's htmx-templates directory,
     * which is filterable by 'hxwp/get_template_file/templates_path'.
     *
     * @since 2023-11-30
     * @param string $template_name The sanitized template name, possibly including a namespace (e.g., "namespace/template-file").
     *
     * @return string|false The full, sanitized path to the template file, or false if not found.
     */
    protected function get_template_file($template_name = '')
    {
        if (empty($template_name)) {
            return false;
        }

        // Allow plugins/themes to register their own namespaced template paths.
        // Expected format: ['namespace' => '/path/to/templates/', ...]
        // Example: add_filter('hxwp/register_namespaced_template_path', function($paths) { $paths['myplugin'] = plugin_dir_path(__FILE__) . 'htmx-tpl/'; return $paths; });
        // A request to 'myplugin/my-template' would then resolve to 'wp-content/plugins/myplugin/htmx-tpl/my-template.htmx.php'.
        $namespaced_paths = apply_filters('hxwp/register_namespaced_template_path', []);
        $parsed_template = $this->parse_namespaced_template($template_name);

        if ($parsed_template && !empty($parsed_template['namespace']) && isset($namespaced_paths[$parsed_template['namespace']])) {
            $base_path = trailingslashit((string) $namespaced_paths[$parsed_template['namespace']]);
            $potential_path = $base_path . $parsed_template['template'] . HXWP_EXT;
            $sanitized_path = $this->sanitize_full_path($potential_path);

            if ($sanitized_path) { // realpath succeeded, so file exists and path is canonical.
                return $sanitized_path;
            }
        }

        // Fallback: Let users filter the default templates path (theme-based).
        // This is the original behavior for non-namespaced templates or if namespaced lookup fails.
        // If $template_name was "namespace/template" but 'namespace' wasn't registered,
        // it will be treated as "namespace/template.htmx.php" within the default path.
        $default_templates_path = apply_filters_deprecated(
            'hxwp/get_template_file/templates_path',
            [$this->get_theme_path() . HXWP_TEMPLATE_DIR . '/'],
            '1.2.0',
            'hxwp/register_namespaced_template_path',
            esc_html__('Use namespaced template paths for better organization and to avoid conflicts.', 'api-for-htmx')
        );
        $template_file_path = trailingslashit((string) $default_templates_path) . $template_name . HXWP_EXT;

        // Sanitize full path for the default location.
        $sanitized_default_path = $this->sanitize_full_path($template_file_path);

        if ($sanitized_default_path) { // realpath succeeded
            return $sanitized_default_path;
        }

        return false; // No valid template found
    }

    /**
     * Parses a template name that might contain a namespace.
     * e.g., "myplugin/template-name" -> ['namespace' => 'myplugin', 'template' => 'template-name'].
     *
     * @since 1.1.1
     * @param string $template_name The template name to parse.
     * @return array{'namespace': string, 'template': string}|false Array with 'namespace' and 'template' keys, or false if no '/' is found or parts are empty.
     */
    protected function parse_namespaced_template($template_name)
    {
        if (str_contains((string) $template_name, '/')) {
            $parts = explode('/', (string) $template_name, 2);
            if (count($parts) === 2 && !empty($parts[0]) && !empty($parts[1])) {
                return [
                    'namespace' => $parts[0],
                    'template'  => $parts[1],
                ];
            }
        }

        return false;
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
