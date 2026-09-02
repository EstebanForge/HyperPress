<?php

declare(strict_types=1);

/**
 * Handles REST API endpoint registration.
 */

namespace HyperBlocks;

// Prevent direct file access.
if (!defined('ABSPATH') && !defined('HYPERBLOCKS_TESTING_MODE')) {
    return;
}

/**
 * Manages the registration of REST API routes.
 */
class RestApi
{
    /**
     * The namespace for the REST API.
     *
     * @var string
     */
    private string $namespace;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = Config::get('rest_namespace', 'hyperblocks/v1');
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register the REST API routes.
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        // Block fields endpoint
        register_rest_route(
            $this->namespace,
            '/block-fields',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getBlockFields'],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'args'                => [
                    'name' => [
                        'required'          => true,
                        'validate_callback' => function ($param) {
                            return is_string($param);
                        },
                    ],
                ],
            ]
        );

        // Server-side preview endpoint
        register_rest_route(
            $this->namespace,
            '/render-preview',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'renderPreview'],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'args'                => [
                    'blockName' => [
                        'required'          => true,
                        'validate_callback' => function ($param) {
                            return is_string($param);
                        },
                    ],
                    'attributes' => [
                        'required'          => true,
                        'validate_callback' => function ($param) {
                            return is_array($param);
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Callback for the /block-fields endpoint.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function getBlockFields(\WP_REST_Request $request): \WP_REST_Response
    {
        $fields = BlockOperations::getFields((string) $request->get_param('name'));

        if ($fields !== null) {
            return new \WP_REST_Response($fields);
        }

        return new \WP_REST_Response(['error' => 'Block not found.'], 404);
    }

    /**
     * Callback for the /render-preview endpoint.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function renderPreview(\WP_REST_Request $request): \WP_REST_Response
    {
        $result = BlockOperations::preview(
            (string) $request->get_param('blockName'),
            (array) $request->get_param('attributes')
        );

        if ($result['status'] === 'ok') {
            return new \WP_REST_Response([
                'success' => true,
                'html'    => $result['html'],
            ]);
        }

        if ($result['status'] === 'not_found') {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => 'Block not found: ' . $request->get_param('blockName'),
            ], 404);
        }

        return new \WP_REST_Response([
            'success' => false,
            'error'   => $result['error'],
        ], $result['rest_status']);
    }

}
