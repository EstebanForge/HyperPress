<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Block.
 *
 * This facade maintains backward compatibility by extending the HyperBlocks Block class.
 * Any HyperPress-specific functionality can be added here.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Block as HyperBlocksBlock;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Block, extending HyperBlocks\Block.
 */
class Block extends HyperBlocksBlock
{
    /**
     * Constructor.
     *
     * @param string $name The block name (namespace/block-name).
     * @param string $title The block title.
     * @param array $config Optional configuration options.
     */
    public function __construct(string $name, string $title, array $config = [])
    {
        parent::__construct($name, $title, $config);
    }

    /**
     * Add HyperPress-specific behavior if needed.
     *
     * This method can be used to add any HyperPress-specific extensions
     * to the base Block functionality.
     *
     * @param array $options Additional options.
     * @return self
     */
    public function withHyperPressOptions(array $options = []): self
    {
        // Add any HyperPress-specific options here
        if (isset($options['hypermedia'])) {
            // Store hypermedia-specific settings
            $this->metadata['hypermedia'] = $options['hypermedia'];
        }

        return $this;
    }
}
