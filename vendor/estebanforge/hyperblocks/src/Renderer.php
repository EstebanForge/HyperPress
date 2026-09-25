<?php

declare(strict_types=1);

/**
 * Core rendering engine for blocks.
 */

namespace HyperBlocks;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Handles secure PHP template execution and custom component parsing.
 */
class Renderer
{
    /**
     * Sentinel emitted where <InnerBlocks /> appears but no inner-blocks
     * content is available (editor preview). The editor client splits the
     * preview HTML on this marker and injects the live InnerBlocks area
     * between the two halves. An inert HTML comment on the front end.
     */
    public const INNER_BLOCKS_SENTINEL = '<!--hyperblocks:innerblocks-->';

    /**
     * Render a block using its template and attributes.
     *
     * @param string $template   The template string or file path.
     * @param array  $attributes The block attributes.
     * @param string $content    Inner-blocks markup for the block (the second
     *                           argument WordPress passes to every dynamic
     *                           block render callback). Replaces <InnerBlocks />
     *                           markers in the template output.
     * @return string The rendered HTML.
     */
    public function render(string $template, array $attributes = [], string $content = ''): string
    {
        try {
            // Execute the template to get initial HTML
            $initialHtml = $this->executeTemplate($template, $attributes);

            // Parse and replace custom components
            $finalHtml = $this->parseCustomComponents($initialHtml, $attributes, $content);

            return $finalHtml;
        } catch (\Throwable $e) {
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
     * @param string $template   The template string or file path.
     * @param array  $attributes The block attributes.
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
     * @param string $templatePath The path to template file.
     * @param array  $attributes   The block attributes.
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
     * @param array  $attributes     The block attributes.
     * @return string The executed template output.
     * @throws \Exception If temp file creation fails.
     */
    private function executeStringTemplate(string $templateString, array $attributes): string
    {
        // Create temporary file for string template
        $tempFile = $this->createTempTemplate($templateString);

        try {
            return $this->executeInIsolatedScope($tempFile, $attributes);
        } finally {
            @unlink($tempFile); // Clean up temp file on any exit
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

        // Ensure path is within allowed directories
        $allowedBases = [];

        // Add WordPress content directory
        if (defined('WP_CONTENT_DIR')) {
            $allowedBases[] = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
        }

        // Add theme directory
        if (function_exists('get_template_directory')) {
            $themeDir = get_template_directory();
            if ($themeDir) {
                $allowedBases[] = $themeDir;
            }
        }

        // Add library root path if set (runtime identity, prefix-safe).
        if (Config::$abspath !== '') {
            $allowedBases[] = Config::$abspath;
        }

        // Add registered paths allowed for template resolution. This is
        // the union of discovery paths (block_paths) and template-only
        // paths (template_paths), so a template-only registration still
        // resolves templates without being scanned for block definitions.
        foreach (Config::getTemplateValidationPaths() as $path) {
            if (is_dir($path)) {
                $allowedBases[] = $path;
            }
        }

        // If path is relative, try with base paths
        if (!str_starts_with($templatePath, '/') && !file_exists($templatePath)) {
            foreach ($allowedBases as $base) {
                $fullPath = $base . '/' . ltrim($templatePath, '/');
                if (file_exists($fullPath)) {
                    $templatePath = $fullPath;
                    break;
                }
            }
        }

        // Normalize the final path
        $templatePath = wp_normalize_path($templatePath);

        $realPath = realpath($templatePath);
        if ($realPath === false) {
            throw new \Exception('Invalid template path: ' . esc_html($templatePath));
        }

        // Check if path is within allowed directories. The containment check
        // requires the base plus a trailing separator: without it,
        // str_starts_with('/var/www/blocks-evil/x.php', '/var/www/blocks')
        // would treat an unregistered sibling directory whose name shares a
        // prefix as "inside" the allowed base, letting an absolute file: path
        // escape into it.
        $isValid = false;
        foreach ($allowedBases as $base) {
            $realBase = realpath($base);
            if (!$realBase) {
                continue;
            }
            // hb_path_within_base() normalizes both sides: realpath() returns
            // backslash separators on Windows, where a raw '/'-suffixed base
            // never prefix-matched. The trailing-separator anchor inside the
            // helper still keeps sibling-prefix paths (blocks vs blocks-evil)
            // from counting as inside the base.
            if (\hb_path_within_base($realPath, $realBase)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
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
     * @param string $templatePath The path to template file.
     * @param array  $attributes   The block attributes.
     * @return string The executed template output.
     * @throws \Exception If execution fails.
     */
    private function executeInIsolatedScope(string $templatePath, array $attributes): string
    {
        // Create isolated function scope
        $executeTemplate = function ($__template_path, $__attributes) {
            // Extract attributes as variables for template use
            extract($__attributes, EXTR_SKIP);

            $__ob_level = ob_get_level();

            // Start output buffering
            ob_start();

            try {
                // Include the template file
                include $__template_path;

                // Get the output and clean the buffer
                return ob_get_clean();
            } catch (\Throwable $e) {
                // Template threw: unwind all buffer levels opened since the
                // baseline so nested ob_start calls in the template do not leak.
                while (ob_get_level() > $__ob_level) {
                    ob_end_clean();
                }
                throw $e;
            }
        };

        // Execute with error handling
        $previousErrorHandler = set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $output = $executeTemplate($templatePath, $attributes);

            if ($output === false) {
                throw new \Exception('Template execution failed');
            }

            return $output;
        } catch (\Throwable $e) {
            throw new \Exception('Template execution error: ' . $e->getMessage(), 0, $e);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Parse and replace custom components in HTML.
     *
     * @param string $html        The HTML to parse.
     * @param array  $attributes The block attributes.
     * @param string $content    Inner-blocks markup for <InnerBlocks /> markers.
     * @return string The HTML with custom components replaced.
     */
    private function parseCustomComponents(string $html, array $attributes, string $content = ''): string
    {
        try {
            // First, try simple string replacement for RichText components
            $html = $this->parseRichTextWithRegex($html, $attributes);

            // Then handle InnerBlocks markers. Case-insensitive so lowercase
            // or mixed-case author tags are caught too.
            if (stripos($html, '<InnerBlocks') !== false) {
                $html = $this->parseInnerBlocksWithRegex($html, $content);
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
     * @param string $html        The HTML to parse.
     * @param array  $attributes The block attributes.
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
     * Replace InnerBlocks markers with the block's inner-blocks markup.
     *
     * Uses preg_replace_callback (never preg_replace with $content as the
     * replacement) so $ and \ sequences inside real block markup are never
     * interpreted as PCRE backreferences. Attribute sections tolerate quoted
     * values containing '>' and both bare and self-closing tag forms.
     *
     * @param string $html    The HTML to parse.
     * @param string $content The inner-blocks markup, or '' when unavailable.
     * @return string The HTML with InnerBlocks markers replaced by the markup,
     *                or by the editor split sentinel when no markup exists.
     */
    private function parseInnerBlocksWithRegex(string $html, string $content = ''): string
    {
        $replacement = $content !== '' ? $content : self::INNER_BLOCKS_SENTINEL;

        // Attribute section shared by both passes: quoted values may contain '>'.
        // preg_replace_callback returns null on PCRE failure (e.g. the backtrack
        // limit hit by a pathological template); fall back to the original HTML
        // rather than collapsing the block output to null.
        // Paired form first (<InnerBlocks>junk</InnerBlocks>), so stray author
        // markup between the tags is consumed. The lookbehind keeps self-closing
        // tags (<InnerBlocks />) for the second pass so mixed usage never
        // collapses two markers into one replacement.
        $parsed = preg_replace_callback(
            '/<InnerBlocks\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*(?<!\/)>.*?<\/InnerBlocks>/is',
            static fn (): string => $replacement,
            $html
        );
        $html = $parsed ?? $html;

        // Then self-closing and bare open tags (slash optional).
        return preg_replace_callback(
            '/<InnerBlocks\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*\/?>/i',
            static fn (): string => $replacement,
            $html
        ) ?? $html;
    }
}
