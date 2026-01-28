<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\RestApi.
 *
 * This facade maintains backward compatibility by extending the HyperBlocks RestApi class.
 */

namespace HyperPress\Blocks;

use HyperBlocks\RestApi as HyperBlocksRestApi;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for RestApi, extending HyperBlocks\RestApi.
 */
class RestApi extends HyperBlocksRestApi
{
    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the single instance of the RestApi.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Initialize the RestApi with HyperPress-specific hooks.
     *
     * @return void
     */
    public function init(): void
    {
        // Call parent init to register basic routes
        parent::init();

        // Add HyperPress-specific routes if needed
        add_action('rest_api_init', [$this, 'registerHyperPressRoutes'], 15);
    }

    /**
     * Register HyperPress-specific REST API routes.
     *
     * This method can be used to add any HyperPress-specific API endpoints.
     *
     * @return void
     */
    public function registerHyperPressRoutes(): void
    {
        // Add any HyperPress-specific routes here
        // For example, hypermedia-specific endpoints
    }
}
