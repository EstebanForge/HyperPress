<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Field.
 *
 * This facade maintains backward compatibility by extending the HyperBlocks Field class.
 * Any HyperPress-specific functionality can be added here.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Field as HyperBlocksField;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Field, extending HyperBlocks\Field.
 */
class Field extends HyperBlocksField
{
    /**
     * Constructor.
     *
     * @param string $name The field name.
     * @param string $type The field type.
     * @param array $config Optional configuration options.
     */
    public function __construct(string $name, string $type, array $config = [])
    {
        parent::__construct($name, $type, $config);
    }

    /**
     * Add HyperPress-specific behavior if needed.
     *
     * This method can be used to add any HyperPress-specific extensions
     * to the base Field functionality.
     *
     * @param array $options Additional options.
     * @return self
     */
    public function withHyperPressOptions(array $options = []): self
    {
        // Add any HyperPress-specific options here
        if (isset($options['hypermedia'])) {
            // Store hypermedia-specific settings
            $this->config['hypermedia'] = $options['hypermedia'];
        }

        return $this;
    }
}
