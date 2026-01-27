<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Renderer.
 *
 * This facade maintains backward compatibility by extending the HyperBlocks Renderer class.
 * It initializes the Renderer with HyperPress-specific configuration.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Renderer as HyperBlocksRenderer;
use HyperBlocks\Config;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Renderer, extending HyperBlocks\Renderer.
 */
class Renderer extends HyperBlocksRenderer
{
    /**
     * Constructor.
     *
     * @param Config|null $config Optional configuration object.
     */
    public function __construct(?Config $config = null)
    {
        // If no config provided, create from HyperPress constants
        if (null === $config) {
            $config = Config::fromHyperPress();
        }

        // Call parent constructor with config
        parent::__construct($config);
    }

    /**
     * Create a Renderer instance configured for HyperPress.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self(Config::fromHyperPress());
    }

    /**
     * Render with HyperPress-specific pre-processing.
     *
     * @param string $template The template string or file path.
     * @param array $attributes The block attributes.
     * @return string The rendered HTML.
     */
    public function render(string $template, array $attributes): string
    {
        // Add HyperPress-specific pre-processing if needed
        $attributes = $this->preProcessAttributes($attributes);

        // Call parent render
        return parent::render($template, $attributes);
    }

    /**
     * Pre-process attributes for HyperPress-specific handling.
     *
     * This method can be used to add any HyperPress-specific attribute processing.
     *
     * @param array $attributes The attributes to process.
     * @return array The processed attributes.
     */
    private function preProcessAttributes(array $attributes): array
    {
        // Add any HyperPress-specific attribute processing here
        // For example, hypermedia-specific transformations

        return $attributes;
    }
}
