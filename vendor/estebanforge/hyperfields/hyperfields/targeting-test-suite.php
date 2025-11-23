<?php

declare(strict_types=1);

/**
 * HyperFields Complete Targeting Test
 * Enable this to test all targeting capabilities.
 *
 * ⚠️  EXAMPLE FILE - NOT AUTO-ACTIVATED
 *
 * Instructions:
 * 1. Copy this file to your theme or plugin
 * 2. Include it: require_once 'path/to/this/file.php';
 * 3. Uncomment the add_action line at the bottom
 * 4. Test in WordPress admin for posts, users, and terms
 *
 * @since 2025-08-04
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use HyperFields\HyperFields;

/**
 * Test 1: Post targeting by ID
 * Will show only on post ID 1.
 */
function hp_test_post_by_id(): void
{
    $container = HyperFields::makePostMeta('test_post_id', 'Test: Post ID Targeting')
        ->wherePostId(1) // Change this to an existing post ID
        ->setContext('side');

    $container->addField(
        HyperFields::makeField('text', 'test_post_id_field', 'Post ID Test')
            ->setPlaceholder('This only shows on post ID 1')
            ->setHelp('Testing post ID targeting')
    );
}

/**
 * Test 2: Post targeting by slug
 * Will show only on posts with specific slugs.
 */
function hp_test_post_by_slug(): void
{
    $container = HyperFields::makePostMeta('test_post_slug', 'Test: Post Slug Targeting')
        ->wherePostSlug('hello-world') // Default WordPress post
        ->wherePostSlug('sample-page') // Default WordPress page
        ->setContext('normal');

    $container->addField(
        HyperFields::makeField('textarea', 'test_post_slug_field', 'Post Slug Test')
            ->setPlaceholder('This shows on hello-world or sample-page')
            ->setHelp('Testing post slug targeting')
    );
}

/**
 * Test 3: Post targeting by type
 * Will show on all posts and pages.
 */
function hp_test_post_by_type(): void
{
    $container = HyperFields::makePostMeta('test_post_type', 'Test: Post Type Targeting')
        ->where('post')
        ->where('page')
        ->setContext('advanced')
        ->setPriority('low');

    $container
        ->addField(HyperFields::makeField('checkbox', 'test_post_type_checkbox', 'Post Type Test')
        ->setHelp('This shows on all posts and pages'))
        ->addField(HyperFields::makeField('select', 'test_priority', 'Test Priority')
        ->setOptions([
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ])
            ->setDefault('medium'));
}

/**
 * Test 4: User targeting by role
 * Will show for administrators and editors.
 */
function hp_test_user_by_role(): void
{
    $container = HyperFields::makeUserMeta('test_user_role', 'Test: User Role Targeting')
        ->where('administrator')
        ->where('editor');

    $container
        ->addField(HyperFields::makeField('text', 'test_user_role_field', 'User Role Test')
        ->setPlaceholder('This shows for admins and editors')
        ->setHelp('Testing user role targeting'))
        ->addField(HyperFields::makeField('checkbox', 'test_admin_access', 'Admin Access')
            ->setHelp('Special admin-level access'));
}

/**
 * Test 5: User targeting by ID
 * Will show only for user ID 1 (usually the first admin).
 */
function hp_test_user_by_id(): void
{
    $container = HyperFields::makeUserMeta('test_user_id', 'Test: User ID Targeting')
        ->whereUserId(1); // Change this to an existing user ID

    $container->addField(
        HyperFields::makeField('textarea', 'test_user_id_field', 'User ID Test')
            ->setPlaceholder('This only shows for user ID 1')
            ->setHelp('Testing user ID targeting')
    );
}

/**
 * Test 6: Term targeting by taxonomy
 * Will show for all categories and tags.
 */
function hp_test_term_by_taxonomy(): void
{
    $container = HyperFields::makeTermMeta('test_term_taxonomy', 'Test: Term Taxonomy Targeting')
        ->where('category')
        ->where('post_tag');

    $container
        ->addField(HyperFields::makeField('color', 'test_term_color', 'Term Color Test')
        ->setDefault('#007cba')
        ->setHelp('This shows for all categories and tags'))
        ->addField(HyperFields::makeField('text', 'test_term_note', 'Term Note')
            ->setPlaceholder('Testing taxonomy targeting'));
}

/**
 * Test 7: Term targeting by ID
 * Will show only for term ID 1 (usually "Uncategorized").
 */
function hp_test_term_by_id(): void
{
    $container = HyperFields::makeTermMeta('test_term_id', 'Test: Term ID Targeting')
        ->where('category')
        ->whereTermId(1); // Change this to an existing term ID

    $container->addField(
        HyperFields::makeField('image', 'test_term_id_field', 'Term ID Test')
            ->setHelp('This only shows for term ID 1 (usually Uncategorized)')
    );
}

/**
 * Test 8: Term targeting by slug
 * Will show only for terms with specific slugs.
 */
function hp_test_term_by_slug(): void
{
    $container = HyperFields::makeTermMeta('test_term_slug', 'Test: Term Slug Targeting')
        ->where('category')
        ->whereTermSlug('uncategorized'); // Default category slug

    $container->addField(
        HyperFields::makeField('checkbox', 'test_term_slug_field', 'Term Slug Test')
            ->setHelp('This only shows for "uncategorized" category')
    );
}

/**
 * Test 9: Complex targeting with conditional logic
 * Multiple targeting methods combined.
 */
function hp_test_complex_targeting(): void
{
    $container = HyperFields::makePostMeta('test_complex', 'Test: Complex Targeting')
        ->where('post')
        ->wherePostIds([1, 2, 3]) // Specific posts
        ->setContext('side');

    $container
        ->addField(HyperFields::makeField('select', 'test_layout_type', 'Layout Type')
        ->setOptions([
            'default' => 'Default',
            'custom' => 'Custom',
            'special' => 'Special',
        ])
        ->setDefault('default'))

        // Conditional field - only shows when custom layout is selected
        ->addField(HyperFields::makeField('textarea', 'test_custom_css', 'Custom CSS')
        ->setConditionalLogic([
            'conditions' => [[
                'field' => 'test_layout_type',
                'operator' => '=',
                'value' => 'custom',
            ]],
        ])
        ->setHelp('This field only appears when Custom layout is selected'))

        // Another conditional field
        ->addField(HyperFields::makeField('text', 'test_special_title', 'Special Title')
        ->setConditionalLogic([
            'conditions' => [[
                'field' => 'test_layout_type',
                'operator' => '=',
                'value' => 'special',
            ]],
        ])
            ->setPlaceholder('Special title for special layout'));
}

/**
 * Test 10: Multiple posts by IDs array.
 */
function hp_test_multiple_posts(): void
{
    $container = HyperFields::makePostMeta('test_multiple_posts', 'Test: Multiple Posts')
        ->wherePostIds([1, 2, 3, 4, 5]) // Multiple specific posts
        ->setContext('normal');

    $container->addField(
        HyperFields::makeField('text', 'test_multiple_field', 'Multiple Posts Test')
            ->setPlaceholder('This shows on posts 1, 2, 3, 4, and 5')
            ->setHelp('Testing multiple post ID targeting')
    );
}

// ============================================================================
// ACTIVATION
// ============================================================================

/**
 * Activate all tests
 * Uncomment the lines below to enable specific tests.
 */
function hyperfields_activate_targeting_tests(): void
{
    // Post targeting tests
    hp_test_post_by_id();
    hp_test_post_by_slug();
    hp_test_post_by_type();
    hp_test_multiple_posts();

    // User targeting tests
    hp_test_user_by_role();
    hp_test_user_by_id();

    // Term targeting tests
    hp_test_term_by_taxonomy();
    hp_test_term_by_id();
    hp_test_term_by_slug();

    // Complex targeting test
    hp_test_complex_targeting();
}

// Activate all tests - UNCOMMENT THE LINE BELOW TO TEST
// add_action('init', 'hyperfields_activate_targeting_tests');

/*
================================================================================
TESTING INSTRUCTIONS
================================================================================

1. SETUP:
   - Activate this file by including it in your theme/plugin
   - Make sure you have some posts, users, and terms to test with

2. POST TESTING:
   - Go to Posts → All Posts
   - Edit post ID 1 (or change the ID in hp_test_post_by_id())
   - You should see "Test: Post ID Targeting" metabox on the right side
   - Edit a post with slug 'hello-world' or 'sample-page'
   - You should see "Test: Post Slug Targeting" metabox
   - All posts/pages should show "Test: Post Type Targeting" metabox

3. USER TESTING:
   - Go to Users → All Users
   - Edit any administrator or editor account
   - You should see "Test: User Role Targeting" fields in the profile
   - Edit user ID 1 (or change the ID in hp_test_user_by_id())
   - You should see "Test: User ID Targeting" fields

4. TERM TESTING:
   - Go to Posts → Categories
   - Edit any category (especially "Uncategorized")
   - You should see "Test: Term Taxonomy Targeting" fields
   - Edit the category with ID 1 (usually "Uncategorized")
   - You should see "Test: Term ID Targeting" fields
   - Edit the "uncategorized" category (slug test)
   - You should see "Test: Term Slug Targeting" fields

5. CONDITIONAL LOGIC TESTING:
   - Edit posts with IDs 1, 2, or 3
   - In the "Test: Complex Targeting" metabox:
   - Change "Layout Type" to "Custom" → "Custom CSS" field should appear
   - Change "Layout Type" to "Special" → "Special Title" field should appear
   - Change to "Default" → both conditional fields should hide

6. TROUBLESHOOTING:
   - If metaboxes don't appear, check the targeting IDs match your content
   - Modify the post IDs, user IDs, and term IDs in the functions above
   - Check WordPress admin for any PHP errors
   - Make sure HyperFields is properly loaded

7. CUSTOMIZATION:
   - Adjust the IDs in each test function to match your content
   - Comment out tests you don't need
   - Add your own fields to test more functionality
*/
