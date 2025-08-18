<?php

declare(strict_types=1);

/**
 * Advanced HyperFields Targeting Demo
 * Demonstrates all targeting capabilities for metaboxes.
 *
 * ⚠️  EXAMPLE FILE - NOT AUTO-ACTIVATED
 *
 * To use these examples:
 * 1. Copy the functions you want to your theme/plugin
 * 2. Uncomment the add_action lines at the bottom of this file
 * 3. Or manually call the functions in your code
 *
 * @since 2025-08-04
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use HyperPress\Fields\HyperFields;

/**
 * Demo: Target specific post by ID.
 */
function hyperfields_target_post_by_id(): void
{
    $container = HyperFields::makePostMeta('specific_post_meta', 'Fields for Specific Post')
        ->wherePostId(1) // Only show for post ID 1
        ->setContext('normal')
        ->setPriority('high');

    $container
        ->addField(HyperFields::makeField('text', 'special_post_note', 'Special Note')
            ->set_placeholder('This field only appears on post ID 1'))
        ->addField(HyperFields::makeField('textarea', 'admin_comments', 'Admin Comments')
            ->set_help('Internal notes for this specific post'));
}

/**
 * Demo: Target specific post by slug.
 */
function hyperfields_target_post_by_slug(): void
{
    $container = HyperFields::makePostMeta('homepage_meta', 'Homepage Settings')
        ->wherePostSlug('homepage') // Only show for post with slug 'homepage'
        ->wherePostSlug('home')     // Also show for post with slug 'home'
        ->setContext('side');

    $container
        ->addField(HyperFields::makeField('checkbox', 'featured_on_homepage', 'Featured on Homepage')
            ->set_help('Show this content prominently'))
        ->addField(HyperFields::makeField('color', 'homepage_accent_color', 'Accent Color')
            ->set_default('#007cba'));
}

/**
 * Demo: Target multiple posts by IDs.
 */
function hyperfields_target_multiple_posts(): void
{
    $container = HyperFields::makePostMeta('vip_posts_meta', 'VIP Post Settings')
        ->wherePostIds([1, 5, 10, 15]) // Multiple specific posts
        ->where('post') // Also ensure it's a post type
        ->setContext('normal');

    $container
        ->addField(HyperFields::makeField('select', 'vip_priority', 'VIP Priority')
            ->set_options([
                'low' => 'Low Priority',
                'medium' => 'Medium Priority',
                'high' => 'High Priority',
                'urgent' => 'Urgent',
            ])
            ->set_default('medium'))
        ->addField(HyperFields::makeField('text', 'vip_contact', 'VIP Contact')
            ->set_placeholder('Special contact for this content'));
}

/**
 * Demo: Target post type and all its posts.
 */
function hyperfields_target_post_type(): void
{
    $container = HyperFields::makePostMeta('product_meta', 'Product Information')
        ->where('product') // All posts of 'product' post type
        ->setContext('normal')
        ->setPriority('high');

    $container
        ->addField(HyperFields::makeField('number', 'product_price', 'Price')
            ->set_validation(['min' => 0])
            ->set_placeholder('0.00'))
        ->addField(HyperFields::makeField('text', 'product_sku', 'SKU')
            ->set_placeholder('Enter product SKU'))
        ->addField(HyperFields::makeField('checkbox', 'product_featured', 'Featured Product'))
        ->addField(HyperFields::makeField('select', 'product_status', 'Availability')
            ->set_options([
                'in_stock' => 'In Stock',
                'out_of_stock' => 'Out of Stock',
                'pre_order' => 'Pre-Order',
                'discontinued' => 'Discontinued',
            ])
            ->set_default('in_stock'));
}

/**
 * Demo: Target user by role.
 */
function hyperfields_target_user_by_role(): void
{
    $container = HyperFields::makeUserMeta('admin_profile', 'Administrator Settings')
        ->where('administrator') // Only for administrators
        ->where('editor');       // Also for editors

    $container
        ->addField(HyperFields::makeField('text', 'admin_phone', 'Admin Phone')
            ->set_placeholder('Emergency contact number'))
        ->addField(HyperFields::makeField('textarea', 'admin_notes', 'Admin Notes')
            ->set_help('Internal administrative notes'))
        ->addField(HyperFields::makeField('checkbox', 'receive_alerts', 'Receive System Alerts'));
}

/**
 * Demo: Target specific user by ID.
 */
function hyperfields_target_user_by_id(): void
{
    $container = HyperFields::makeUserMeta('super_admin_profile', 'Super Admin Settings')
        ->whereUserId(1) // Only for user ID 1 (usually the first admin)
        ->whereUserId(2); // Also for user ID 2

    $container
        ->addField(HyperFields::makeField('text', 'super_admin_key', 'Super Admin Key')
            ->set_placeholder('Special access key'))
        ->addField(HyperFields::makeField('url', 'emergency_contact_url', 'Emergency Contact URL'))
        ->addField(HyperFields::makeField('checkbox', 'system_maintenance_mode', 'Can Enable Maintenance Mode'));
}

/**
 * Demo: Target multiple users by IDs.
 */
function hyperfields_target_multiple_users(): void
{
    $container = HyperFields::makeUserMeta('team_leads_profile', 'Team Lead Settings')
        ->whereUserIds([3, 7, 12, 18]); // Multiple specific users

    $container
        ->addField(HyperFields::makeField('text', 'team_name', 'Team Name')
            ->set_placeholder('Name of the team you lead'))
        ->addField(HyperFields::makeField('number', 'team_size', 'Team Size')
            ->set_validation(['min' => 1, 'max' => 50]))
        ->addField(HyperFields::makeField('textarea', 'team_goals', 'Team Goals')
            ->set_help('Current team objectives and goals'));
}

/**
 * Demo: Target term by ID.
 */
function hyperfields_target_term_by_id(): void
{
    $container = HyperFields::makeTermMeta('featured_category_meta', 'Featured Category Settings')
        ->where('category')
        ->whereTermId(1); // Only for category with ID 1

    $container
        ->addField(HyperFields::makeField('color', 'featured_color', 'Featured Color')
            ->set_default('#ff6b35'))
        ->addField(HyperFields::makeField('image', 'featured_banner', 'Featured Banner')
            ->set_help('Special banner for this featured category'))
        ->addField(HyperFields::makeField('checkbox', 'show_in_homepage', 'Show on Homepage'));
}

/**
 * Demo: Target term by slug.
 */
function hyperfields_target_term_by_slug(): void
{
    $container = HyperFields::makeTermMeta('special_category_meta', 'Special Category Settings')
        ->where('category')
        ->whereTermSlug('featured')    // Category with slug 'featured'
        ->whereTermSlug('trending');   // Also category with slug 'trending'

    $container
        ->addField(HyperFields::makeField('text', 'special_badge_text', 'Badge Text')
            ->set_placeholder('Featured, Trending, etc.'))
        ->addField(HyperFields::makeField('select', 'badge_style', 'Badge Style')
            ->set_options([
                'primary' => 'Primary',
                'secondary' => 'Secondary',
                'success' => 'Success',
                'warning' => 'Warning',
                'danger' => 'Danger',
            ])
            ->set_default('primary'));
}

/**
 * Demo: Target custom taxonomy.
 */
function hyperfields_target_custom_taxonomy(): void
{
    $container = HyperFields::makeTermMeta('product_category_meta', 'Product Category Details')
        ->where('product_category'); // Custom taxonomy

    $container
        ->addField(HyperFields::makeField('image', 'category_icon', 'Category Icon')
            ->set_help('Icon for this product category'))
        ->addField(HyperFields::makeField('textarea', 'category_description', 'Extended Description')
            ->set_help('Detailed description for SEO and display'))
        ->addField(HyperFields::makeField('number', 'sort_order', 'Sort Order')
            ->set_validation(['min' => 0])
            ->set_default(0)
            ->set_help('Order for displaying categories'));
}

/**
 * Demo: Target multiple terms by IDs.
 */
function hyperfields_target_multiple_terms(): void
{
    $container = HyperFields::makeTermMeta('priority_tags_meta', 'Priority Tag Settings')
        ->where('post_tag')
        ->whereTermIds([5, 12, 18, 25]); // Multiple specific tags

    $container
        ->addField(HyperFields::makeField('select', 'tag_priority', 'Priority Level')
            ->set_options([
                'low' => 'Low Priority',
                'medium' => 'Medium Priority',
                'high' => 'High Priority',
            ])
            ->set_default('medium'))
        ->addField(HyperFields::makeField('color', 'tag_color', 'Tag Color')
            ->set_default('#6c757d'));
}

/**
 * Demo: Complex targeting with conditional logic.
 */
function hyperfields_complex_targeting_demo(): void
{
    // Target specific posts with conditional fields
    $container = HyperFields::makePostMeta('advanced_post_settings', 'Advanced Post Settings')
        ->where('post')
        ->where('page')
        ->wherePostIds([1, 5, 10]) // Only on specific posts
        ->setContext('normal');

    $container
        ->addField(HyperFields::makeField('select', 'post_layout', 'Layout Type')
            ->set_options([
                'default' => 'Default Layout',
                'custom' => 'Custom Layout',
                'landing' => 'Landing Page',
                'fullwidth' => 'Full Width',
            ])
            ->set_default('default'))

        // Show custom CSS field only when custom layout is selected
        ->addField(HyperFields::makeField('textarea', 'custom_css', 'Custom CSS')
            ->set_conditional_logic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'custom',
                ]],
            ])
            ->set_help('Custom CSS for this post'))

        // Show landing page fields only for landing layout
        ->addField(HyperFields::makeField('text', 'landing_headline', 'Landing Headline')
            ->set_conditional_logic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'landing',
                ]],
            ])
            ->set_placeholder('Compelling headline for landing page'))

        ->addField(HyperFields::makeField('url', 'cta_url', 'Call-to-Action URL')
            ->set_conditional_logic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'landing',
                ]],
            ]));
}

// Activate all demos - UNCOMMENT LINES BELOW TO TEST
// add_action('init', 'hyperfields_target_post_by_id');
// add_action('init', 'hyperfields_target_post_by_slug');
// add_action('init', 'hyperfields_target_multiple_posts');
// add_action('init', 'hyperfields_target_post_type');
// add_action('init', 'hyperfields_target_user_by_role');
// add_action('init', 'hyperfields_target_user_by_id');
// add_action('init', 'hyperfields_target_multiple_users');
// add_action('init', 'hyperfields_target_term_by_id');
// add_action('init', 'hyperfields_target_term_by_slug');
// add_action('init', 'hyperfields_target_custom_taxonomy');
// add_action('init', 'hyperfields_target_multiple_terms');
// add_action('init', 'hyperfields_complex_targeting_demo');
