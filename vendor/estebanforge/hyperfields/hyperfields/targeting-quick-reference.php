<?php

declare(strict_types=1);

/**
 * HyperFields Targeting Quick Reference
 * All targeting methods at a glance.
 *
 * ⚠️  EXAMPLE FILE - NOT AUTO-ACTIVATED
 *
 * This file contains reference examples showing all targeting methods.
 * Copy the patterns you need to your theme/plugin code.
 *
 * @since 2025-08-04
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use HyperPress\Fields\HyperFields;

/**
 * Quick Reference: All targeting methods.
 */
function hyperfields_targeting_quick_reference(): void
{
    // ============================================================================
    // POST TARGETING
    // ============================================================================

    // Target by post type
    $post_type_container = HyperFields::makePostMeta('post_type_meta', 'Post Type Fields')
        ->where('post')     // Single post type
        ->where('page')     // Multiple post types
        ->where('product'); // Custom post type

    // Target by specific post ID
    $post_id_container = HyperFields::makePostMeta('specific_post_meta', 'Specific Post Fields')
        ->wherePostId(1)    // Single post ID
        ->wherePostId(5);   // Multiple specific posts

    // Target by post slug
    $post_slug_container = HyperFields::makePostMeta('post_slug_meta', 'Post Slug Fields')
        ->wherePostSlug('homepage')  // Single post slug
        ->wherePostSlug('about');    // Multiple post slugs

    // Target multiple posts by IDs array
    $multiple_posts_container = HyperFields::makePostMeta('multiple_posts_meta', 'Multiple Posts Fields')
        ->wherePostIds([1, 5, 10, 15]);

    // Target multiple posts by slugs array
    $multiple_slugs_container = HyperFields::makePostMeta('multiple_slugs_meta', 'Multiple Slugs Fields')
        ->wherePostSlugs(['homepage', 'contact', 'about']);

    // Combined targeting: specific posts + post type restriction
    $combined_post_container = HyperFields::makePostMeta('combined_post_meta', 'Combined Post Targeting')
        ->where('post')          // Must be a post
        ->wherePostIds([1, 5])   // AND one of these IDs
        ->wherePostSlug('featured'); // OR this slug

    // ============================================================================
    // USER TARGETING
    // ============================================================================

    // Target by user role
    $user_role_container = HyperFields::makeUserMeta('role_meta', 'Role-Based Fields')
        ->where('administrator')  // Single role
        ->where('editor')         // Multiple roles
        ->where('author');

    // Target by specific user ID
    $user_id_container = HyperFields::makeUserMeta('specific_user_meta', 'Specific User Fields')
        ->whereUserId(1)    // Single user ID
        ->whereUserId(2);   // Multiple specific users

    // Target multiple users by IDs array
    $multiple_users_container = HyperFields::makeUserMeta('multiple_users_meta', 'Multiple Users Fields')
        ->whereUserIds([1, 5, 10, 15]);

    // Role + specific user targeting
    $combined_user_container = HyperFields::makeUserMeta('combined_user_meta', 'Combined User Targeting')
        ->where('administrator')    // Administrators
        ->whereUserId(5);          // AND/OR specific user ID 5

    // ============================================================================
    // TERM TARGETING
    // ============================================================================

    // Target by taxonomy
    $taxonomy_container = HyperFields::makeTermMeta('taxonomy_meta', 'Taxonomy Fields')
        ->where('category')         // Built-in taxonomy
        ->where('post_tag')         // Built-in taxonomy
        ->where('product_category'); // Custom taxonomy

    // Target by specific term ID
    $term_id_container = HyperFields::makeTermMeta('specific_term_meta', 'Specific Term Fields')
        ->where('category')
        ->whereTermId(1)    // Single term ID
        ->whereTermId(5);   // Multiple specific terms

    // Target by term slug
    $term_slug_container = HyperFields::makeTermMeta('term_slug_meta', 'Term Slug Fields')
        ->where('category')
        ->whereTermSlug('featured')  // Single term slug
        ->whereTermSlug('trending'); // Multiple term slugs

    // Target multiple terms by IDs array
    $multiple_terms_container = HyperFields::makeTermMeta('multiple_terms_meta', 'Multiple Terms Fields')
        ->where('post_tag')
        ->whereTermIds([1, 5, 10, 15]);

    // Target multiple terms by slugs array
    $multiple_term_slugs_container = HyperFields::makeTermMeta('multiple_term_slugs_meta', 'Multiple Term Slugs Fields')
        ->where('category')
        ->whereTermSlugs(['featured', 'trending', 'popular']);

    // Combined targeting: taxonomy + specific terms
    $combined_term_container = HyperFields::makeTermMeta('combined_term_meta', 'Combined Term Targeting')
        ->where('category')              // Must be a category
        ->whereTermIds([1, 5])          // AND one of these IDs
        ->whereTermSlug('special');     // OR this slug

    // ============================================================================
    // METABOX SETTINGS
    // ============================================================================

    // Post metabox with context and priority
    $styled_container = HyperFields::makePostMeta('styled_meta', 'Styled Metabox')
        ->where('post')
        ->setContext('side')        // 'normal', 'side', 'advanced'
        ->setPriority('high');      // 'high', 'core', 'default', 'low'

    // Add sample fields to demonstrate
    $styled_container
        ->addField(HyperFields::makeField('text', 'sample_field', 'Sample Field')
        ->setPlaceholder('This is a sample field'))
        ->addField(HyperFields::makeField('checkbox', 'sample_checkbox', 'Sample Checkbox'));

    // ============================================================================
    // PRACTICAL EXAMPLES
    // ============================================================================

    // Example 1: VIP content management
    $vip_container = HyperFields::makePostMeta('vip_content', 'VIP Content Settings')
        ->where('post')
        ->where('page')
        ->wherePostIds([1, 5, 10])  // Homepage, About, Contact
        ->setContext('side');

    $vip_container->addField(
        HyperFields::makeField('checkbox', 'is_vip_content', 'VIP Content')
            ->setHelp('Mark as VIP content for special treatment')
    );

    // Example 2: Staff directory
    $staff_container = HyperFields::makeUserMeta('staff_directory', 'Staff Directory')
        ->where('editor')
        ->where('author')
        ->whereUserIds([3, 7, 12]); // Specific staff members

    $staff_container
        ->addField(HyperFields::makeField('text', 'job_title', 'Job Title'))
        ->addField(HyperFields::makeField('text', 'department', 'Department'));

    // Example 3: Featured categories
    $featured_categories = HyperFields::makeTermMeta('featured_categories', 'Featured Category Settings')
        ->where('category')
        ->whereTermSlugs(['featured', 'trending', 'popular']);

    $featured_categories->addField(
        HyperFields::makeField('color', 'featured_color', 'Featured Color')
            ->setDefault('#007cba')
    );
}

// Register all examples (commented out to avoid conflicts)
// add_action('init', 'hyperfields_targeting_quick_reference');

/*
==============================================================================
SUMMARY OF ALL TARGETING METHODS
==============================================================================

POST METABOXES:
✅ ->where('post_type')               // Target post type
✅ ->wherePostId(123)                 // Target specific post ID
✅ ->wherePostSlug('slug')            // Target specific post slug
✅ ->wherePostIds([1, 2, 3])          // Target multiple post IDs
✅ ->wherePostSlugs(['slug1', 'slug2']) // Target multiple post slugs
✅ ->setContext('normal|side|advanced') // Metabox position
✅ ->setPriority('high|core|default|low') // Metabox priority

USER PROFILES:
✅ ->where('role')                    // Target user role
✅ ->whereUserId(123)                 // Target specific user ID
✅ ->whereUserIds([1, 2, 3])          // Target multiple user IDs

TERM FORMS:
✅ ->where('taxonomy')                // Target taxonomy
✅ ->whereTermId(123)                 // Target specific term ID
✅ ->whereTermSlug('slug')            // Target specific term slug
✅ ->whereTermIds([1, 2, 3])          // Target multiple term IDs
✅ ->whereTermSlugs(['slug1', 'slug2']) // Target multiple term slugs

COMBINING TARGETING:
- You can chain multiple targeting methods
- Post containers: combine post types + specific IDs/slugs
- User containers: combine roles + specific user IDs
- Term containers: combine taxonomies + specific term IDs/slugs
- More specific targeting takes precedence

USAGE PATTERNS:
1. General: ->where('post_type') for all posts of a type
2. Specific: ->wherePostId(1) for one specific post
3. Multiple: ->wherePostIds([1,2,3]) for several specific posts
4. Hybrid: ->where('post')->wherePostIds([1,2,3]) for specific posts of a type
5. Slug-based: ->wherePostSlug('homepage') for posts with specific slugs
*/
