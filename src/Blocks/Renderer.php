<?php

declare(strict_types=1);

/**
 * Core rendering engine for blocks.
 */

namespace HyperPress\Blocks;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles secure PHP template execution and custom component parsing.
 */
class Renderer
{
    /**
     * Render a block using its template and attributes.
     *
     * @param string $template The template string or file path.
     * @param array $attributes The block attributes.
     * @return string The rendered HTML.
     */
    public function render(string $template, array $attributes): string
    {
        try {
            // Execute the template to get initial HTML
            $initialHtml = $this->executeTemplate($template, $attributes);

            // Parse and replace custom components
            $finalHtml = $this->parseCustomComponents($initialHtml, $attributes);

            return $finalHtml;
        } catch (\Exception $e) {
            // Return error HTML for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return '<div class="hyperblocks-error">Rendering Error: ' . esc_html($e->getMessage()) . '</div>';
            }

            return '<div class="hyperblocks-error">Block rendering failed</div>';
        }
    }

    /**
     * Securely execute PHP template with attributes.
     *
     * @param string $template The template string or file path.
     * @param array $attributes The block attributes.
     * @return string The executed template output.
     * @throws \Exception If template execution fails.
     */
    private function executeTemplate(string $template, array $attributes): string
    {
        // Check if template is a file path
        if ($this->isFilePath($template)) {
            return $this->executeFileTemplate($template, $attributes);
        }

        // Handle string template
        return $this->executeStringTemplate($template, $attributes);
    }

    /**
     * Check if template is a file path.
     *
     * @param string $template The template to check.
     * @return bool True if it's a file path.
     */
    private function isFilePath(string $template): bool
    {
        return str_starts_with($template, 'file:');
    }

    /**
     * Execute a file-based template.
     *
     * @param string $templatePath The path to the template file.
     * @param array $attributes The block attributes.
     * @return string The executed template output.
     * @throws \Exception If file doesn't exist or execution fails.
     */
    private function executeFileTemplate(string $templatePath, array $attributes): string
    {
        // Validate file path for security
        $templatePath = $this->validateTemplatePath($templatePath);

        if (!file_exists($templatePath)) {
            throw new \Exception('Template file not found: ' . esc_html($templatePath));
        }

        // Execute template in isolated scope
        return $this->executeInIsolatedScope($templatePath, $attributes);
    }

    /**
     * Execute a string template by writing to temp file.
     *
     * @param string $templateString The template string.
     * @param array $attributes The block attributes.
     * @return string The executed template output.
     * @throws \Exception If temp file creation fails.
     */
    private function executeStringTemplate(string $templateString, array $attributes): string
    {
        // Create temporary file for string template
        $tempFile = $this->createTempTemplate($templateString);

        try {
            $output = $this->executeInIsolatedScope($tempFile, $attributes);
            unlink($tempFile); // Clean up temp file

            return $output;
        } catch (\Exception $e) {
            unlink($tempFile); // Clean up temp file on error
            throw $e;
        }
    }

    /**
     * Validate template file path for security.
     *
     * @param string $templatePath The template path to validate.
     * @return string The validated path.
     * @throws \Exception If path is invalid.
     */
    private function validateTemplatePath(string $templatePath): string
    {
        // Remove file:// prefix if present
        if (str_starts_with($templatePath, 'file:')) {
            $templatePath = substr($templatePath, 5);
        }

        // Normalize path separators
        $templatePath = wp_normalize_path($templatePath);

        // Remove any directory traversal attempts
        $templatePath = str_replace('..', '', $templatePath);

        // Ensure path is within WordPress content directory or plugin directory
        $contentDir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
        $pluginDir = defined('HYPERPRESS_ABSPATH') ? HYPERPRESS_ABSPATH : dirname(__DIR__, 2);

        // If path is relative, make it absolute
        if (!file_exists($templatePath) && !str_starts_with($templatePath, '/')) {
            $templatePath = $pluginDir . '/' . ltrim($templatePath, '/');
        }

        // Normalize the final path
        $templatePath = wp_normalize_path($templatePath);

        $realPath = realpath($templatePath);
        if ($realPath === false) {
            // Try with ABSPATH prefix
            $realPath = realpath(ABSPATH . ltrim($templatePath, '/'));
            if ($realPath === false) {
                throw new \Exception('Invalid template path: ' . esc_html($templatePath));
            }
        }

        $realContentDir = realpath($contentDir);
        $realPluginDir = realpath($pluginDir);

        // Check if path is within allowed directories
        if ($realContentDir && !str_starts_with($realPath, $realContentDir)
            && $realPluginDir && !str_starts_with($realPath, $realPluginDir)) {
            throw new \Exception('Template path outside allowed directories: ' . esc_html($templatePath));
        }

        return $realPath;
    }

    /**
     * Create temporary file for string template.
     *
     * @param string $templateString The template string.
     * @return string The path to the temporary file.
     * @throws \Exception If temp file creation fails.
     */
    private function createTempTemplate(string $templateString): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'hyperblocks_');

        if ($tempFile === false) {
            throw new \Exception('Failed to create temporary template file');
        }

        if (file_put_contents($tempFile, $templateString) === false) {
            throw new \Exception('Failed to write temporary template file');
        }

        return $tempFile;
    }

    /**
     * Execute template in isolated scope with error handling.
     *
     * @param string $templatePath The path to the template file.
     * @param array $attributes The block attributes.
     * @return string The executed template output.
     * @throws \Exception If execution fails.
     */
    private function executeInIsolatedScope(string $templatePath, array $attributes): string
    {
        // Create isolated function scope
        $executeTemplate = function ($__template_path, $__attributes) {
            // Extract attributes as variables for template use
            extract($__attributes, EXTR_SKIP);

            // Start output buffering
            ob_start();

            // Include the template file
            include $__template_path;

            // Get the output and clean the buffer
            return ob_get_clean();
        };

        // Execute with error handling
        $previousErrorHandler = set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $output = $executeTemplate($templatePath, $attributes);
            restore_error_handler();

            if ($output === false) {
                throw new \Exception('Template execution failed');
            }

            return $output;
        } catch (\Exception $e) {
            restore_error_handler();
            throw new \Exception('Template execution error: ' . $e->getMessage());
        }
    }

    /**
     * Parse and replace custom components in HTML.
     *
     * @param string $html The HTML to parse.
     * @param array $attributes The block attributes.
     * @return string The HTML with custom components replaced.
     */
    private function parseCustomComponents(string $html, array $attributes): string
    {
        try {
            // First, try simple string replacement for RichText components
            $html = $this->parseRichTextWithRegex($html, $attributes);

            // Then handle InnerBlocks with DOM parsing if needed
            if (strpos($html, '<InnerBlocks') !== false) {
                $html = $this->parseInnerBlocksWithRegex($html);
            }

            return $html;
        } catch (\Exception $e) {
            // Return original HTML if parsing fails
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return $html . '<!-- Component parsing error: ' . esc_html($e->getMessage()) . ' -->';
            }

            return $html;
        }
    }

    /**
     * Parse RichText components using regex.
     *
     * @param string $html The HTML to parse.
     * @param array $attributes The block attributes.
     * @return string The HTML with RichText components replaced.
     */
    private function parseRichTextWithRegex(string $html, array $attributes): string
    {
        // Pattern to match RichText components
        $pattern = '/<RichText\s+([^>]*?)(?:\s*\/?>|><\/RichText>)/i';

        return preg_replace_callback($pattern, function ($matches) use ($attributes) {
            $attributeString = $matches[1];

            // Parse attributes from the RichText tag
            $attributeName = '';
            $tagName = 'div';
            $placeholder = '';
            $style = '';

            // Extract attribute name
            if (preg_match('/attribute=["\']([^"\']*)["\']/', $attributeString, $attrMatch)) {
                $attributeName = $attrMatch[1];
            }

            // Extract tag name
            if (preg_match('/tag=["\']([^"\']*)["\']/', $attributeString, $tagMatch)) {
                $tagName = $tagMatch[1];
            }

            // Extract placeholder
            if (preg_match('/placeholder=["\']([^"\']*)["\']/', $attributeString, $placeholderMatch)) {
                $placeholder = $placeholderMatch[1];
            }

            // Extract style
            if (preg_match('/style=["\']([^"\']*)["\']/', $attributeString, $styleMatch)) {
                $style = $styleMatch[1];
            }

            // Get the content from attributes
            $content = $attributes[$attributeName] ?? $placeholder;

            // Build the replacement HTML
            $styleAttr = $style ? ' style="' . esc_attr($style) . '"' : '';

            return "<{$tagName}{$styleAttr}>" . esc_html($content) . "</{$tagName}>";
        }, $html);
    }

    /**
     * Parse InnerBlocks components using regex.
     *
     * @param string $html The HTML to parse.
     * @return string The HTML with InnerBlocks components replaced.
     */
    private function parseInnerBlocksWithRegex(string $html): string
    {
        // Replace InnerBlocks with a placeholder for WordPress to handle
        return preg_replace('/<InnerBlocks\s*(?:\s*\/?>|><\/InnerBlocks>)/i', '<!-- wp:innerblocks /-->', $html);
    }
}
