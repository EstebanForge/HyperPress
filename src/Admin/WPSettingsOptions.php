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
    public $type = 'display';

    /**
     * Render the option.
     *
     * @return string
     */
    public function render(): string
    {
        $html = '';

        // Check for specific content types to render
        if (isset($this->arguments['content'])) {
            $html .= $this->render_content();
        } elseif (isset($this->arguments['api_url'])) {
            $html .= $this->render_api_url_info();
        } elseif (isset($this->arguments['debug_data'])) {
            $html .= $this->render_debug_table();
        }

        return $html;
    }

    /**
     * Render general content.
     *
     * @return string
     */
    private function render_content(): string
    {
        $content = $this->arguments['content'] ?? '';
        $title = $this->arguments['title'] ?? '';
        $description = $this->arguments['description'] ?? '';
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
        $api_url = $this->arguments['api_url'] ?? '';
        $title = $this->arguments['title'] ?? esc_html__('API Endpoint URL', 'api-for-htmx');
        $description = $this->arguments['description'] ?? '';

        $html = '<h3>' . esc_html($title) . '</h3>';
        $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
        $html .= '  <input type="text" readonly value="' . esc_attr($api_url) . '" class="large-text" id="hmapi-api-url-field">';
        $html .= '  <button type="button" class="button" onclick="hmapiCopyText(\'hmapi-api-url-field\')">' . esc_html__('Copy', 'api-for-htmx') . '</button>';
        $html .= '</div>';
        if (!empty($description)) {
            $html .= '<p class="description">' . esc_html($description) . '</p>';
        }
        // Add simple JS for copy functionality
        $html .= "<script>
            function hmapiCopyText(elementId) {
                var copyText = document.getElementById(elementId);
                copyText.select();
                copyText.setSelectionRange(0, 99999); /* For mobile devices */
                document.execCommand('copy');
                // Optional: Add some visual feedback, like changing button text
                var button = copyText.nextElementSibling;
                var originalText = button.textContent;
                button.textContent = 'Copied!';
                setTimeout(function() { button.textContent = originalText; }, 2000);
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
        $debug_data = $this->arguments['debug_data'] ?? [];
        $table_title = $this->arguments['table_title'] ?? '';
        $table_headers = $this->arguments['table_headers'] ?? []; // Expects array of ['text' => 'Header', 'style' => 'width: 100px;']
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
                $style = isset($header['style']) ? ' style="' . esc_attr($header['style']) . '"' : '';
                $html .= '<th' . $style . '>' . esc_html($header['text']) . '</th>';
            }
            $html .= '</tr></thead>';
        } elseif (is_array(reset($debug_data))) { // If data is an array of arrays, try to infer headers
            $html .= '<thead><tr>';
            foreach (array_keys(reset($debug_data)) as $header_key) {
                $html .= '<th>' . esc_html(ucwords(str_replace('_', ' ', $header_key))) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        // Table Body
        $html .= '<tbody>';
        foreach ($debug_data as $key_or_row => $value_or_cells) {
            $html .= '<tr>';
            if (is_array($value_or_cells)) { // Data is structured for multiple columns
                foreach ($value_or_cells as $cell_value) {
                    $html .= '<td>' . esc_html((string) $cell_value) . '</td>';
                }
            } else { // Simple key-value pairs for two columns
                $html .= '<td><strong>' . esc_html((string) $key_or_row) . '</strong></td>';
                $html .= '<td>' . esc_html((string) $value_or_cells) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}
