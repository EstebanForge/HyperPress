<?php

declare(strict_types=1);

namespace HyperPress\Libraries;

use HyperPress\Main;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages Alpine Ajax library integration.
 */
class AlpineAjaxLib
{
    /**
     * The main plugin instance.
     *
     * @var Main
     */
    private Main $main;

    /**
     * Constructor.
     *
     * @param Main $main The main plugin instance.
     */
    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    /**
     * Get available extensions for Alpine Ajax.
     * This method is included for API consistency but currently returns an empty array
     * as Alpine Ajax does not have extensions in the same way HTMX does.
     *
     * @return array An empty array.
     */
    public function get_extensions(): array
    {
        return [];
    }
}
