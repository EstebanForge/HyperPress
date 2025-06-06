<?php

declare(strict_types=1);

namespace HMApi\Admin;

use Jeffreyvr\WPSettings\Options\OptionAbstract;

/**
 * Unified Custom Options for WP Settings Library.
 *
 * This class provides flexible display-only options for the WP Settings library,
 * supporting multiple content types:
 * - Info cards with styled display for API endpoints, status info, etc.
 * - Raw HTML content for custom layouts and information
 * - Debug tables for technical information
 *
 * The option type is determined by which arguments are provided.
 *
 * @since 1.3.0
 */
class WPSettingsOptions extends OptionAbstract
{
    /**
     * These options don't store data, so no sanitization needed.
     *
     * @param mixed $value The value to sanitize.
     * @return string Empty string since display options don't store data.
     */
    public function sanitize($value): string
    {
        return '';
    }

    /**
     * These options don't store data, so validation always passes.
     *
     * @param mixed $value The value to validate.
     * @return bool Always returns true.
     */
    public function validate($value): bool
    {
        return true;
    }

    /**
     * Renders the appropriate content based on the provided arguments.
     *
     * Supports multiple content types:
     * - Info card: When 'api_url', 'title', and 'description' are provided
     * - Raw HTML: When 'content' is provided
     * - Debug table: When 'debug_data' is provided
     *
     * @return string The rendered HTML content.
     */
    public function render(): string
    {
        // Info Card rendering (for API endpoints, status info, etc.)
        if ($this->get_arg('api_url') && $this->get_arg('title')) {
            return $this->render_info_card();
        }

        // Debug table rendering
        if ($this->get_arg('debug_data')) {
            return $this->render_debug_table();
        }

        // Raw HTML content rendering
        return $this->get_arg('content', '');
    }

    /**
     * Renders an info card with styled display.
     *
     * @return string The rendered info card HTML.
     */
    private function render_info_card(): string
    {
        $api_url = $this->get_arg('api_url', '');
        $title = $this->get_arg('title', '');
        $description = $this->get_arg('description', '');

        return sprintf(
            '<div class="hmapi-info-card" style="background: #f6f7f7; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; border-radius: 4px; padding: 12px; margin: 1rem 0; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="color: #00a32a; font-size: 16px;">🔗</span>
                    <strong style="color: #1d2327; font-size: 14px;">%s</strong>
                </div>
                <code style="background: #fff; border: 1px solid #c3c4c7; border-radius: 3px; padding: 8px 10px; font-family: Consolas, Monaco, monospace; font-size: 13px; display: block; word-break: break-all; color: #2271b1; cursor: text; user-select: all;">%s</code>
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #646970; line-height: 1.4;">%s</p>
            </div>',
            esc_html($title),
            esc_html($api_url),
            esc_html($description)
        );
    }

    /**
     * Renders a debug table with technical information.
     *
     * @return string The rendered debug table HTML.
     */
    private function render_debug_table(): string
    {
        $debug_data = $this->get_arg('debug_data', []);
        $table_title = $this->get_arg('table_title', esc_html__('Debug Information', 'api-for-htmx'));

        if (empty($debug_data)) {
            return '<div class="notice notice-warning inline"><p>' . esc_html__('No debug data available.', 'api-for-htmx') . '</p></div>';
        }

        $html = '<h3>' . esc_html($table_title) . '</h3>';
        $html .= '<table class="wp-list-table widefat fixed striped" style="max-width: 100%;">';

        // Add table headers if provided
        if ($this->get_arg('table_headers')) {
            $headers = $this->get_arg('table_headers');
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $style = isset($header['style']) ? ' style="' . esc_attr($header['style']) . '"' : '';
                $html .= '<th' . $style . '>' . esc_html($header['text']) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';

        foreach ($debug_data as $key => $value) {
            if (is_array($value)) {
                // Handle nested arrays (like CDN URLs with version info)
                if (isset($value['version']) && isset($value['url'])) {
                    $html .= '<tr>';
                    $html .= '<td><strong>' . esc_html(ucfirst(str_replace('_', ' ', $key))) . '</strong></td>';
                    $html .= '<td><code>' . esc_html($value['version']) . '</code></td>';
                    $html .= '<td><code style="word-break: break-all; font-size: 11px;">' . esc_html($value['url']) . '</code></td>';
                    $html .= '</tr>';
                }
            } else {
                // Simple key-value pairs
                $html .= '<tr>';
                $html .= '<td><strong>' . esc_html($key) . '</strong></td>';
                $html .= '<td>' . esc_html($value) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
