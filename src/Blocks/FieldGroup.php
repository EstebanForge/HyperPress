<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\FieldGroup.
 *
 * This facade maintains backward compatibility by extending the HyperBlocks FieldGroup class.
 * Any HyperPress-specific functionality can be added here.
 */

namespace HyperPress\Blocks;

use HyperBlocks\FieldGroup as HyperBlocksFieldGroup;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for FieldGroup, extending HyperBlocks\FieldGroup.
 */
class FieldGroup extends HyperBlocksFieldGroup
{
    /**
     * Constructor.
     *
     * @param string $id The field group ID.
     * @param string $title The field group title.
     * @param array $fields Optional array of Field objects.
     */
    public function __construct(string $id, string $title, array $fields = [])
    {
        parent::__construct($id, $title, $fields);
    }

    /**
     * Add HyperPress-specific behavior if needed.
     *
     * This method can be used to add any HyperPress-specific extensions
     * to the base FieldGroup functionality.
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
