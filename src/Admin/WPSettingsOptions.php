<?php

declare(strict_types=1);

namespace HMApi\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use HMApi\Jeffreyvr\WPSettings\Options\OptionAbstract;

/**
 * WPSettingsOptions Class.
 * Custom option type for displaying information in WPSettings.
 *
 * @since 2.0.0
 */
class WPSettingsOptions extends OptionAbstract
{
    public $view = 'display';

    /**
     * Override the render method to handle our custom display logic.
     *
     * @return void
     */
    public function render()
    {
        echo '<tr valign="top"><td colspan="2">';

        // Check for specific content types to render
        if (isset($this->args['content'])) {
            echo $this->render_content();
        } elseif (isset($this->args['api_url'])) {
            echo $this->render_api_url_info();
        } elseif (isset($this->args['debug_data'])) {
            echo $this->render_debug_table();
        } elseif ($this->args['name'] === 'datastar_sdk_status') {
            echo wp_kses_post($this->args['html']);
        }

        echo '</td></tr>';
    }

    /**
     * Render general content.
     *
     * @return string
     */
    private function render_content(): string
    {
        $content = $this->get_arg('content', '');
        $title = $this->get_arg('title', '');
        $description = $this->get_arg('description', '');
        $html = '';

        if (!empty($title)) {
            $html .= '<h3>' . esc_html($title) . '</h3>';
        }
        if (is_string($content) && !empty($content)) {
            $html .= '<div>' . wp_kses_post($content) . '</div>'; // Allow HTML in content
        }
        if (!empty($description)) {
            $html .= '<p class="description">' . esc_html($description) . '</p>';
        }

        return $html;
    }

    /**
     * Render API URL information with a copy button.
     *
     * @return string
     */
    private function render_api_url_info(): string
    {
        $api_url = $this->args['api_url'] ?? '';
        $title = $this->args['title'] ?? esc_html__('API Endpoint URL', 'api-for-htmx');
        $description = $this->args['description'] ?? '';

        $html = '<div style="background: #f6f7f7; border-left: 4px solid #007cba; padding: 15px 20px; border-radius: 4px; margin-top: 10px;">';
        $html .= '<h3 style="margin-top: 0; margin-bottom: 15px;">' . esc_html($title) . '</h3>';
        $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
        $html .= '  <input type="text" readonly value="' . esc_attr($api_url) . '" class="large-text" id="hmapi-api-url-field" style="background: #fff; font-family: monospace; font-size: 14px;">';
        $html .= '  <button type="button" class="button button-secondary" onclick="hmapiCopyText(\'hmapi-api-url-field\', this)">';
        $html .= '    <span class="dashicons dashicons-admin-page" style="vertical-align: text-bottom; margin-right: 3px;"></span>' . esc_html__('Copy', 'api-for-htmx');
        $html .= '  </button>';
        $html .= '</div>';
        if (!empty($description)) {
            $html .= '<p class="description" style="margin-top: 10px; margin-bottom: 0;">' . esc_html($description) . '</p>';
        }
        $html .= '</div>';

        // Add simple JS for copy functionality
        $html .= "<script>
            function hmapiCopyText(elementId, button) {
                var copyText = document.getElementById(elementId);
                copyText.select();
                copyText.setSelectionRange(0, 99999); /* For mobile devices */
                document.execCommand('copy');

                var originalHtml = button.innerHTML;
                button.innerHTML = '<span class=\"dashicons dashicons-yes-alt\" style=\"vertical-align: text-bottom; margin-right: 3px;\"></span>' + 'Copied!';

                setTimeout(function() {
                    button.innerHTML = originalHtml;
                }, 2000);
            }
        </script>";

        return $html;
    }

    /**
     * Render debug data as a table.
     *
     * @return string
     */
    private function render_debug_table(): string
    {
        $debug_data = $this->args['debug_data'] ?? [];
        $table_title = $this->args['table_title'] ?? '';
        $table_headers = $this->args['table_headers'] ?? [];
        $html = '';

        if (!empty($table_title)) {
            $html .= '<h4>' . esc_html($table_title) . '</h4>';
        }

        if (empty($debug_data)) {
            return $html . '<p>' . esc_html__('No data available.', 'api-for-htmx') . '</p>';
        }

        $html .= '<table class="widefat striped" style="margin-bottom: 20px;">';

        // Table Headers
        if (!empty($table_headers)) {
            $html .= '<thead><tr>';
            foreach ($table_headers as $header) {
                $style = isset($header['style']) ? ' style="' . esc_attr($header['style']) . ' padding: 8px 10px;"' : ' style="padding: 8px 10px;"';
                $html .= '<th' . $style . '>' . esc_html($header['text']) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        // Table Body
        $html .= '<tbody>';
        foreach ($debug_data as $key_or_row => $value_or_cells) {
            $html .= '<tr>';
            if (is_array($value_or_cells)) {
                // Data is structured for multiple columns
                foreach ($value_or_cells as $cell_value) {
                    // Allow HTML for links in CDN URLs but escape other content
                    if (strpos($cell_value, '<a href=') === 0) {
                        $html .= '<td style="padding: 8px 10px;">' . wp_kses($cell_value, ['a' => ['href' => [], 'target' => []]]) . '</td>';
                    } else {
                        $html .= '<td style="padding: 8px 10px;">' . esc_html((string) $cell_value) . '</td>';
                    }
                }
            } else {
                // Simple key-value pairs for two columns
                $html .= '<td style="padding: 8px 10px;"><strong>' . esc_html((string) $key_or_row) . '</strong></td>';
                $html .= '<td style="padding: 8px 10px;">' . esc_html((string) $value_or_cells) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}
