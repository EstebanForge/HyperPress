<?php

declare(strict_types=1);

/**
 * HyperFields Helper Functions Examples
 * Demonstrates hm_get_field(), hm_save_field(), and hm_delete_field() across
 * options, post, user, and term contexts.
 *
 * ⚠️  EXAMPLE FILE - NOT AUTO-ACTIVATED
 *
 * To use:
 * 1. Copy any function below to your theme/plugin, or include this file.
 * 2. Activate by calling add_action for the function you want to run.
 *
 * @since 2025-08-16
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Options example: get/save/delete using default option group (hmapi_options)
 */
function hyperfields_helper_examples_options(): void
{
    // Get with default
    $site_mode = hm_get_field('site_mode', 'options', ['default' => 'live']);

    // Save (sanitize as select). Explicit option_group is optional if using default.
    hm_save_field('site_mode', 'maintenance', 'options', [
        'type' => 'select',
        'option_group' => 'hmapi_options',
    ]);

    // Delete (uncomment if you need to remove it)
    // hm_delete_field('site_mode', 'options', ['option_group' => 'hmapi_options']);
}

// Activate with: add_action('init', 'hyperfields_helper_examples_options');

/**
 * Post meta example: get/save/delete with a specific post ID
 */
function hyperfields_helper_examples_post(): void
{
    // Use a specific post ID (avoid relying on The Loop in examples)
    $post_id = 1; // Replace with a real post ID in your environment

    // Save (sanitize as text)
    hm_save_field('subtitle', 'Hello World', $post_id, ['type' => 'text']);

    // Get with a default
    $subtitle = hm_get_field('subtitle', $post_id, ['default' => '']);

    // Delete (uncomment to remove)
    // hm_delete_field('subtitle', $post_id);
}

// Activate with: add_action('init', 'hyperfields_helper_examples_post');

/**
 * User meta example: get/save/delete for the current user
 */
function hyperfields_helper_examples_user(): void
{
    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        return; // Not logged in
    }

    $user_ctx = 'user_' . $user_id;

    // Save (sanitize as textarea)
    hm_save_field('profile_bio', "Short bio here", $user_ctx, ['type' => 'textarea']);

    // Get with default
    $bio = hm_get_field('profile_bio', $user_ctx, ['default' => '']);

    // Delete (uncomment to remove)
    // hm_delete_field('profile_bio', $user_ctx);
}

// Activate with: add_action('init', 'hyperfields_helper_examples_user');

/**
 * Term meta example: get/save/delete for a term ID
 */
function hyperfields_helper_examples_term(): void
{
    $term_id = 1; // Replace with a real term ID in your environment
    $term_ctx = 'term_' . $term_id;

    // Save (sanitize as color)
    hm_save_field('highlight_color', '#ff0000', $term_ctx, ['type' => 'color']);

    // Get with default
    $color = hm_get_field('highlight_color', $term_ctx, ['default' => '#000000']);

    // Delete (uncomment to remove)
    // hm_delete_field('highlight_color', $term_ctx);
}

// Activate with: add_action('init', 'hyperfields_helper_examples_term');
