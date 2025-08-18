<?php

/**
 * Example: Creating an Options Page with Hyper Fields
 *
 * This demonstrates how to use the new hyper fields system
 * to create plugin/theme options pages in WordPress.
 */

declare(strict_types=1);

// Simplified import - just one class instead of multiple imports
use HyperPress\Fields\HyperFields;

// Example 1: Basic Plugin Options Page
$plugin_options = HyperFields::makeOptionPage(__('My Plugin Settings', 'hyperpress'), 'my-plugin-settings')
    ->set_menu_title(__('My Plugin', 'hyperpress'))
    ->set_parent_slug('options-general.php')
    ->set_footer_content('<span>' . __('Demo Footer: Powered by HyperFields', 'hyperpress') . '</span>');

// Add sections and fields
$general_section = $plugin_options->add_section('general', __('General Settings', 'hyperpress'), __('Configure basic plugin settings', 'hyperpress'));
$general_section->add_field(
    HyperFields::makeField('text', 'plugin_title', __('Plugin Title', 'hyperpress'))
        ->set_default(__('My Awesome Plugin', 'hyperpress'))
        ->set_placeholder(__('Enter plugin title...', 'hyperpress'))
);

$general_section->add_field(
    HyperFields::makeField('textarea', 'plugin_description', __('Plugin Description', 'hyperpress'))
        ->set_placeholder(__('Describe your plugin...', 'hyperpress'))
        ->set_help(__('This description will appear in the plugin header.', 'hyperpress'))
);

$general_section->add_field(
    HyperFields::makeField('color', 'primary_color', __('Primary Color', 'hyperpress'))
        ->set_default('#007cba')
        ->set_help(__('Choose the primary color for your plugin interface', 'hyperpress'))
);

$general_section->add_field(
    HyperFields::makeField('image', 'plugin_logo', __('Plugin Logo', 'hyperpress'))
        ->set_help(__('Recommended size: 300x100px', 'hyperpress'))
);

$general_section->add_field(
    HyperFields::makeField('checkbox', 'enable_feature_x', __('Enable Feature X', 'hyperpress'))
        ->set_default(true)
        ->set_help(__('Turn on the advanced Feature X functionality', 'hyperpress'))
);

// API Endpoint field using helper
$api_url = hp_get_endpoint_url();

$general_section->add_field(
    HyperFields::makeField('html', 'api_endpoint', __('API Endpoint', 'hyperpress'))
        ->set_html_content('<div><input type="text" readonly value="' . esc_attr($api_url) . '" style="width:100%" /></div>')
        ->set_help(__('This is the base API endpoint for your integration.', 'hyperpress'))
);

// HTML/script field demo
$general_section->add_field(
    HyperFields::makeField('html', 'custom_script', __('Custom Script Demo', 'hyperpress'))
        ->set_html_content('<button id="demo-btn">' . esc_html__('Click Me', 'hyperpress') . '</button><script>document.getElementById("demo-btn").onclick=function(){alert("' . esc_js(__('Hello from HyperFields!', 'hyperpress')) . '");}</script>')
        ->set_help(__('Demo of HTML field with script.', 'hyperpress'))
);

// Example 2: Advanced Settings with Tabs
$advanced_section = $plugin_options->add_section('advanced', 'Advanced Settings', 'Configure advanced functionality');

// Tabs field for organizing complex settings
$tabs_field = HyperFields::makeTabs('settings_tabs', 'Configuration Tabs')
    ->add_tab('api', 'API Settings', [
    HyperFields::makeField('text', 'api_key', 'API Key')
            ->set_placeholder('Enter your API key...')
            ->set_required(true),
    HyperFields::makeField('url', 'api_endpoint', 'API Endpoint')
            ->set_default('https://api.example.com/v1')
            ->set_required(true),
    HyperFields::makeField('select', 'api_version', 'API Version')
            ->set_options(['v1' => 'Version 1', 'v2' => 'Version 2'])
            ->set_default('v1')
    ])
    ->add_tab('notifications', 'Notifications', [
    HyperFields::makeField('email', 'notification_email', 'Notification Email')
            ->set_default(get_option('admin_email')),
    HyperFields::makeField('multiselect', 'notification_types', 'Notification Types')
            ->set_options([
                'new_user' => 'New User Registration',
                'new_order' => 'New Orders',
                'system_errors' => 'System Errors'
            ])
    ]);

$advanced_section->add_field($tabs_field);

// Example 3: Repeater Field for Multiple Items
$repeater_field = HyperFields::makeRepeater('social_links', 'Social Media Links')
    ->set_min_rows(1)
    ->set_max_rows(10)
    ->set_label_template('{platform} ({url})')
    ->add_sub_field(
    HyperFields::makeField('select', 'platform', 'Platform')
            ->set_options([
                'facebook' => 'Facebook',
                'twitter' => 'Twitter',
                'instagram' => 'Instagram',
                'linkedin' => 'LinkedIn',
                'youtube' => 'YouTube'
            ])
            ->set_required(true)
    )
    ->add_sub_field(
    HyperFields::makeField('url', 'url', 'URL')
            ->set_placeholder('https://...')
            ->set_required(true)
    )
    ->add_sub_field(
    HyperFields::makeField('color', 'color', 'Brand Color')
    );

$advanced_section->add_field($repeater_field);

// Example 4: Conditional Logic
$advanced_section->add_field(
    HyperFields::makeField('radio', 'display_mode', 'Display Mode')
        ->set_options([
            'simple' => 'Simple Display',
            'advanced' => 'Advanced Display'
        ])
        ->set_default('simple')
);

$advanced_section->add_field(
    HyperFields::makeField('number', 'items_per_page', 'Items Per Page')
        ->set_default(10)
        // ->set_min(1)
        // ->set_max(100)
        ->set_conditional_logic([
            'relation' => 'AND',
            'conditions' => [[
                'field' => 'display_mode',
                'operator' => '=',
                'value' => 'advanced'
            ]]
        ])
);

// Register the options page
$plugin_options->register();

// Example 5: Theme Options Page
$theme_options = HyperFields::makeOptionPage('Theme Settings', 'theme-settings')
    ->set_menu_title('Theme Options')
    ->set_parent_slug('themes.php')
    ->set_icon_url('dashicons-admin-customizer');

// Header settings
$header_section = $theme_options->add_section('header', 'Header Configuration', 'Customize your theme header');
$header_section->add_field(
    HyperFields::makeField('image', 'header_logo', 'Header Logo')
        ->set_help('Recommended: transparent PNG, 200x60px')
);

$header_section->add_field(
    HyperFields::makeField('radio_image', 'header_layout', 'Header Layout')
        ->set_options([
            'default' => 'https://via.placeholder.com/150x60/007cba/ffffff?text=Default',
            'centered' => 'https://via.placeholder.com/150x60/28a745/ffffff?text=Centered',
            'minimal' => 'https://via.placeholder.com/150x60/dc3545/ffffff?text=Minimal'
        ])
        ->set_default('default')
);

// Typography settings
$typography_section = $theme_options->add_section('typography', 'Typography', 'Font and text settings');
$typography_section->add_field(
    HyperFields::makeField('select', 'primary_font', 'Primary Font')
        ->set_options([
            'system' => 'System Fonts',
            'roboto' => 'Roboto',
            'opensans' => 'Open Sans',
            'lato' => 'Lato',
            'montserrat' => 'Montserrat'
        ])
        ->set_default('system')
);

$typography_section->add_field(
    HyperFields::makeField('number', 'base_font_size', 'Base Font Size (px)')
        ->set_default(16)
        // ->set_min(12)
        // ->set_max(24)
);

// Footer settings
$footer_section = $theme_options->add_section('footer', 'Footer Configuration', 'Customize your theme footer');
$footer_section->add_field(
    HyperFields::makeField('textarea', 'footer_text', 'Footer Text')
        ->set_default('&#169; ' . date('Y') . ' All rights reserved.')
        ->set_help('You can use HTML tags here')
);

$footer_section->add_field(
    HyperFields::makeField('footer_scripts', 'footer_scripts', 'Footer Scripts')
        ->set_help('Add tracking codes or custom JavaScript here')
);

// Register theme options
$theme_options->register();

// Example 6: Custom Top-Level Menu
$custom_menu = HyperFields::makeOptionPage('Custom Plugin', 'custom-plugin')
    ->set_menu_title('Custom Plugin')
    ->set_icon_url('dashicons-admin-generic')
    ->set_position(30);

// Dashboard section
$dashboard_section = $custom_menu->add_section('dashboard', 'Dashboard', 'Welcome to your custom plugin dashboard');
$dashboard_section->add_field(
    HyperFields::makeField('html', 'dashboard_welcome', 'Welcome Message')
        ->set_html_content('
        <div class="welcome-panel">
            <h2>Welcome to Custom Plugin!</h2>
            <p>Use the tabs below to configure your plugin settings.</p>
        </div>
        ')
);

// Settings sections
$settings_section = $custom_menu->add_section('settings', 'Plugin Settings', 'Configure your plugin behavior');
$settings_section->add_field(
    HyperFields::makeField('map', 'business_location', 'Business Location')
        // ->set_map_options([
        // 'zoom' => 15,
        // 'type' => 'roadmap'
        // ])
);

$settings_section->add_field(
    HyperFields::makeField('media_gallery', 'gallery_images', 'Gallery Images')
        // ->set_multiple(true)
);

// Register custom menu
$custom_menu->register();
