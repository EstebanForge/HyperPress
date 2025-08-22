<?php

declare(strict_types=1);

/**
 * HyperFields Metabox Examples
 * Example usage of HyperFields containers for post meta, term meta, and user meta.
 *
 * ⚠️  EXAMPLE FILE - NOT AUTO-ACTIVATED
 *
 * To use these examples:
 * 1. Copy the functions you want to your theme/plugin
 * 2. Uncomment the add_action line at the bottom
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
 * Post Meta Example
 * Adds custom fields to posts and pages.
 */
function hyperfields_demo_post_meta(): void
{
    $post_container = HyperFields::makePostMeta('post_details', 'Post Details')
        ->where('post')
        ->where('page')
        ->setContext('side')
        ->setPriority('high');

    $post_container
        ->addField(HyperFields::makeField('text', 'custom_subtitle', 'Subtitle')
        ->setPlaceholder('Enter a subtitle for this post'))
        ->addField(HyperFields::makeField('textarea', 'custom_excerpt', 'Custom Excerpt')
        ->setHelp('Override the default excerpt'))
        ->addField(HyperFields::makeField('select', 'post_priority', 'Priority')
        ->setOptions([
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
                'urgent' => 'Urgent',
            ])
        ->setDefault('normal'))
        ->addField(HyperFields::makeField('checkbox', 'featured_post', 'Featured Post')
        ->setHelp('Mark this post as featured'))
        ->addField(HyperFields::makeField('url', 'external_link', 'External Link')
            ->setPlaceholder('https://example.com')
            ->setHelp('Link to external source'));
}

/**
 * Term Meta Example
 * Adds custom fields to category and tag terms.
 */
function hyperfields_demo_term_meta(): void
{
    $term_container = HyperFields::makeTermMeta('category_details', 'Category Details')
        ->where('category');

    $term_container
        ->addField(HyperFields::makeField('text', 'category_subtitle', 'Category Subtitle')
        ->setPlaceholder('Short description for this category'))
        ->addField(HyperFields::makeField('textarea', 'category_description', 'Extended Description')
        ->setHelp('Detailed description for this category'))
        ->addField(HyperFields::makeField('color', 'category_color', 'Category Color')
        ->setDefault('#007cba')
        ->setHelp('Color associated with this category'))
        ->addField(HyperFields::makeField('image', 'category_icon', 'Category Icon')
            ->setHelp('Icon image for this category'));

    // Tag meta example
    $tag_container = HyperFields::makeTermMeta('tag_details', 'Tag Details')
        ->where('post_tag');

    $tag_container
        ->addField(HyperFields::makeField('text', 'tag_synonym', 'Synonym')
        ->setPlaceholder('Alternative name for this tag'))
        ->addField(HyperFields::makeField('select', 'tag_importance', 'Importance')
        ->setOptions([
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
            ])
            ->setDefault('medium'));
}

/**
 * User Meta Example
 * Adds custom fields to user profiles.
 */
function hyperfields_demo_user_meta(): void
{
    $user_container = HyperFields::makeUserMeta('user_profile', 'Additional Profile Information');

    $user_container
        ->addField(HyperFields::makeField('text', 'job_title', 'Job Title')
        ->setPlaceholder('e.g., Senior Developer'))
        ->addField(HyperFields::makeField('text', 'company', 'Company')
        ->setPlaceholder('Company name'))
        ->addField(HyperFields::makeField('url', 'linkedin_profile', 'LinkedIn Profile')
        ->setPlaceholder('https://linkedin.com/in/username'))
        ->addField(HyperFields::makeField('url', 'twitter_profile', 'Twitter Profile')
        ->setPlaceholder('https://twitter.com/username'))
        ->addField(HyperFields::makeField('textarea', 'bio', 'Bio')
        ->setHelp('Short biography or description'))
        ->addField(HyperFields::makeField('select', 'skill_level', 'Skill Level')
        ->setOptions([
                'beginner' => 'Beginner',
                'intermediate' => 'Intermediate',
                'advanced' => 'Advanced',
                'expert' => 'Expert',
            ])
            ->setDefault('intermediate'));

    // Author-specific fields
    $author_container = HyperFields::makeUserMeta('author_settings', 'Author Settings')
        ->where('author')
        ->where('editor');

    $author_container
        ->addField(HyperFields::makeField('checkbox', 'show_author_box', 'Show Author Box')
        ->setHelp('Display author information box on posts'))
        ->addField(HyperFields::makeField('image', 'author_avatar', 'Custom Avatar')
        ->setHelp('Custom avatar image (overrides Gravatar)'))
        ->addField(HyperFields::makeField('textarea', 'author_signature', 'Author Signature')
            ->setHelp('Signature to append to posts'));
}

/**
 * Advanced Example with Conditional Logic
 * Demonstrates conditional fields in metaboxes.
 */
function hyperfields_demo_conditional_metabox(): void
{
    $advanced_container = HyperFields::makePostMeta('advanced_post_settings', 'Advanced Settings')
        ->where('post')
        ->setContext('normal')
        ->setPriority('low');

    $advanced_container
        ->addField(HyperFields::makeField('select', 'post_layout', 'Post Layout')
        ->setOptions([
                'default' => 'Default',
                'wide' => 'Wide',
                'fullwidth' => 'Full Width',
                'custom' => 'Custom',
            ])
        ->setDefault('default'))
        ->addField(HyperFields::makeField('text', 'custom_css_class', 'Custom CSS Class')
        ->setConditionalLogic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'custom',
                ]],
            ])
        ->setPlaceholder('custom-class-name'))
        ->addField(HyperFields::makeField('textarea', 'custom_css', 'Custom CSS')
        ->setConditionalLogic([
                'conditions' => [[
                    'field' => 'post_layout',
                    'operator' => '=',
                    'value' => 'custom',
                ]],
            ])
        ->setHelp('Custom CSS for this post'))
        ->addField(HyperFields::makeField('checkbox', 'disable_comments', 'Disable Comments')
        ->setHelp('Override global comment settings for this post'))
        ->addField(HyperFields::makeField('date', 'publish_date', 'Scheduled Publish Date')
            ->setHelp('Alternative publish date for scheduling'));
}

/**
 * Initialize all demo metaboxes
 * Call this function to register all example containers.
 */
function hyperfields_init_metabox_demos(): void
{
    hyperfields_demo_post_meta();
    hyperfields_demo_term_meta();
    hyperfields_demo_user_meta();
    hyperfields_demo_conditional_metabox();
}

// Uncomment the line below to activate the demos
// add_action('init', 'hyperfields_init_metabox_demos');
