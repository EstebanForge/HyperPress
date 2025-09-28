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
        ->setPlaceholder('This field only appears on post ID 1'))
        ->addField(HyperFields::makeField('textarea', 'admin_comments', 'Admin Comments')
            ->setHelp('Internal notes for this specific post'));
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
        ->setHelp('Show this content prominently'))
        ->addField(HyperFields::makeField('color', 'homepage_accent_color', 'Accent Color')
            ->setDefault('#007cba'));
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
        ->setOptions([
                'low' => 'Low Priority',
                'medium' => 'Medium Priority',
                'high' => 'High Priority',
                'urgent' => 'Urgent',
            ])
        ->setDefault('medium'))
        ->addField(HyperFields::makeField('text', 'vip_contact', 'VIP Contact')
            ->setPlaceholder('Special contact for this content'));
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
        ->setValidation(['min' => 0])
        ->setPlaceholder('0.00'))
        ->addField(HyperFields::makeField('text', 'product_sku', 'SKU')
        ->setPlaceholder('Enter product SKU'))
        ->addField(HyperFields::makeField('checkbox', 'product_featured', 'Featured Product'))
        ->addField(HyperFields::makeField('select', 'product_status', 'Availability')
        ->setOptions([
                'in_stock' => 'In Stock',
                'out_of_stock' => 'Out of Stock',
                'pre_order' => 'Pre-Order',
                'discontinued' => 'Discontinued',
            ])
            ->setDefault('in_stock'));
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
        ->setPlaceholder('Emergency contact number'))
        ->addField(HyperFields::makeField('textarea', 'admin_notes', 'Admin Notes')
        ->setHelp('Internal administrative notes'))
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
        ->setPlaceholder('Special access key'))
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
        ->setPlaceholder('Name of the team you lead'))
        ->addField(HyperFields::makeField('number', 'team_size', 'Team Size')
        ->setValidation(['min' => 1, 'max' => 50]))
        ->addField(HyperFields::makeField('textarea', 'team_goals', 'Team Goals')
            ->setHelp('Current team objectives and goals'));
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
        ->setDefault('#ff6b35'))
        ->addField(HyperFields::makeField('image', 'featured_banner', 'Featured Banner')
        ->setHelp('Special banner for this featured category'))
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
        ->setPlaceholder('Featured, Trending, etc.'))
        ->addField(HyperFields::makeField('select', 'badge_style', 'Badge Style')
        ->setOptions([
                'primary' => 'Primary',
                'secondary' => 'Secondary',
                'success' => 'Success',
                'warning' => 'Warning',
                'danger' => 'Danger',
            ])
            ->setDefault('primary'));
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
        ->setHelp('Icon for this product category'))
        ->addField(HyperFields::makeField('textarea', 'category_description', 'Extended Description')
        ->setHelp('Detailed description for SEO and display'))
        ->addField(HyperFields::makeField('number', 'sort_order', 'Sort Order')
            ->setValidation(['min' => 0])
            ->setDefault(0)
            ->setHelp('Order for displaying categories'));
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
        ->setOptions([
                'low' => 'Low Priority',
                'medium' => 'Medium Priority',
                'high' => 'High Priority',
            ])
        ->setDefault('medium'))
        ->addField(HyperFields::makeField('color', 'tag_color', 'Tag Color')
            ->setDefault('#6c757d'));
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
        ->setOptions([
                'default' => 'Default Layout',
                'custom' => 'Custom Layout',
                'landing' => 'Landing Page',
                'fullwidth' => 'Full Width',
            ])
        ->setDefault('default'))

        // Show custom CSS field only when custom layout is selected
        ->addField(HyperFields::makeField('textarea', 'custom_css', 'Custom CSS')
        ->setConditionalLogic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'custom',
                ]],
            ])
        ->setHelp('Custom CSS for this post'))

        // Show landing page fields only for landing layout
        ->addField(HyperFields::makeField('text', 'landing_headline', 'Landing Headline')
        ->setConditionalLogic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'landing',
                ]],
            ])
        ->setPlaceholder('Compelling headline for landing page'))

        ->addField(HyperFields::makeField('url', 'cta_url', 'Call-to-Action URL')
        ->setConditionalLogic([
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
