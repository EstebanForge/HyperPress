<?php

/**
 * Plugin Name: HyperPress MCP Server (example)
 * Description: Registers a custom MCP server exposing the read-only Hyper
 *              abilities (HyperPress, HyperBlocks, HyperFields) to AI agents.
 *              Requires the official MCP Adapter plugin
 *              (https://github.com/WordPress/mcp-adapter) to be active.
 * Version:     1.0.0
 *
 * Drop this file into wp-content/mu-plugins/ (or activate it as a normal
 * plugin). It is an EXAMPLE: copy it, adjust the ability list, and own it.
 *
 * How it fits together:
 * - The Hyper libraries register their abilities through the WordPress
 *   Abilities API (core 6.9+). They are registered but NOT exposed: no
 *   meta.mcp.public, no meta.show_in_rest.
 * - This mu-plugin creates a dedicated MCP server listing the non-
 *   destructive abilities explicitly (render-preview renders HTML but
 *   writes nothing), which is the documented way to expose abilities
 *   through a custom server without flipping meta.mcp.public site-wide.
 * - Write abilities (hyperfields/update-option) are deliberately excluded.
 *   To include writes, add the name to the list below and make sure you
 *   understand the two-layer permission model: transport authentication
 *   (application passwords) AND each ability's own capability check.
 *
 * Client configuration (STDIO, local):
 *   wp mcp-adapter serve --user=<admin> --server=hyperpress-mcp-server
 *
 * Client configuration (HTTP, remote): point @automattic/mcp-wordpress-remote
 * at /wp-json/hyperpress-mcp-server/mcp with an application password.
 *
 * @package hyperpress
 */

declare(strict_types=1);

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

add_action('mcp_adapter_init', 'hyperpress_example_register_mcp_server');

/**
 * Register the example MCP server when the adapter initializes.
 *
 * @return void
 */
function hyperpress_example_register_mcp_server(): void
{
    if (!class_exists(\WP\MCP\Core\McpAdapter::class)) {
        return;
    }

    // Non-destructive abilities from the three Hyper packages (render-
    // preview executes a render but writes nothing). Filter to adjust.
    $abilities = apply_filters('hyperpress/mcp/server/abilities', [
        'hyperpress/get-config',
        'hyperpress/list-endpoints',
        'hyperpress/get-extension-status',
        'hyperblocks/list-blocks',
        'hyperblocks/get-block-fields',
        'hyperblocks/render-preview',
        'hyperfields/list-option-pages',
        'hyperfields/get-option',
        // 'hyperfields/update-option', // write: opt in deliberately, if ever
    ]);

    // Only expose abilities that are actually registered on this site.
    $abilities = array_values(array_filter(
        $abilities,
        static fn (string $name): bool => function_exists('wp_has_ability') && wp_has_ability($name)
    ));

    if ($abilities === []) {
        return;
    }

    $server = \WP\MCP\Core\McpAdapter::instance()->create_server(
        'hyperpress-mcp-server',              // Unique server identifier.
        'hyperpress-mcp-server',              // REST namespace: /wp-json/hyperpress-mcp-server/mcp
        'mcp',                                // REST route.
        'HyperPress MCP Server',              // Server name shown to clients.
        'Non-destructive access to HyperPress site configuration, hypermedia endpoints, blocks, and options fields.',
        '1.0.0',                              // Server version.
        [\WP\MCP\Transport\HttpTransport::class],
        \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
        \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
        $abilities,                           // Abilities exposed as tools.
        [],                                   // Resources.
        []                                    // Prompts.
    );

    // create_server returns McpAdapter or WP_Error (duplicate id, bad
    // transport class, ...). Example code integrators copy: surface it.
    if (is_wp_error($server)) {
        error_log('HyperPress MCP example server failed to register: ' . $server->get_error_message());
    }
}
