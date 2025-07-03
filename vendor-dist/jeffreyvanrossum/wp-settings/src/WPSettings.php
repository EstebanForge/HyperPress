<?php

namespace HMApi\Jeffreyvr\WPSettings;

use HMApi\Adbar\Dot;

class WPSettings
{
    public $title;

    public $menu_title;

    public $slug;

    public $parent_slug;

    public $capability = 'manage_options';

    public $menu_icon;

    public $menu_position;

    public $option_name;

    public $tabs = [];

    public $errors;

    public $flash;

    public function __construct($title, $slug = null)
    {
        $this->title = $title;
        $this->option_name = strtolower(str_replace('-', '_', sanitize_title($this->title)));
        $this->slug = $slug;

        if ($this->slug === null) {
            $this->slug = sanitize_title($title);
        }
    }

    public function set_menu_parent_slug($slug)
    {
        $this->parent_slug = $slug;

        return $this;
    }

    public function set_menu_title($title)
    {
        $this->menu_title = $title;

        return $this;
    }

    public function get_menu_title()
    {
        return $this->menu_title ?? $this->title;
    }

    public function set_capability($capability)
    {
        $this->capability = $capability;

        return $this;
    }

    public function set_option_name($name)
    {
        $this->option_name = $name;

        return $this;
    }

    public function set_menu_icon($icon)
    {
        $this->menu_icon = $icon;

        return $this;
    }

    public function set_menu_position($position)
    {
        $this->menu_position = $position;

        return $this;
    }

    public function add_to_menu()
    {
        if ($this->parent_slug) {
            \add_submenu_page($this->parent_slug, $this->title, $this->get_menu_title(), $this->capability, $this->slug, [$this, 'render'], $this->menu_position);
        } else {
            \add_menu_page($this->title, $this->get_menu_title(), $this->capability, $this->slug, [$this, 'render'], $this->menu_icon, $this->menu_position);
        }
    }

    public function make()
    {
        $this->errors = new Error($this);
        $this->flash = new Flash($this);

        add_action('admin_init', [$this, 'save'], 20);
        add_action('admin_menu', [$this, 'add_to_menu'], 20);
        add_action('admin_head', [$this, 'styling'], 20);
    }

    public function is_on_settings_page()
    {
        $screen = get_current_screen();

        if ($screen->base === 'settings_page_'.$this->slug) {
            return true;
        }

        return false;
    }

    public function styling()
    {
        if (! $this->is_on_settings_page()) {
            return;
        }
        ?>
        <style>
            .wps-error-feedback {
                color: #d63638;
                margin: 5px 0;
            }
        </style>
        <?php
    }

    public function get_tab_by_slug($slug)
    {
        foreach ($this->tabs as $tab) {
            if ($tab->slug === $slug) {
                return $tab;
            }
        }

        return false;
    }

    public function get_active_tab()
    {
        $default = $this->tabs[0] ?? false;

        if (isset($_GET['tab'])) {
            return in_array($_GET['tab'], array_map(function ($tab) {
                return $tab->slug;
            }, $this->tabs)) ? $this->get_tab_by_slug($_GET['tab']) : $default;
        }

        return $default;
    }

    public function add_tab($title, $slug = null)
    {
        $tab = new Tab($this, $title, $slug);

        $this->tabs[] = $tab;

        return $tab;
    }

    public function add_section($title, $args = [])
    {
        if (empty($this->tabs)) {
            $tab = $this->add_tab('Unnamed tab');
        } else {
            $tab = end($this->tabs);
        }

        return $tab->add_section($title, $args);
    }

    public function add_option($type, $args = [])
    {
        $tab = end($this->tabs);

        if (! $tab instanceof Tab) {
            return false;
        }

        $section = end($tab->sections);

        if (! $section instanceof Section) {
            return false;
        }

        return $section->add_option($type, $args);
    }

    public function should_make_tabs()
    {
        return count($this->tabs) > 1;
    }

    public function get_url()
    {
        if ($this->parent_slug && strpos($this->parent_slug, '.php') !== false) {
            return \add_query_arg('page', $this->slug, \admin_url($this->parent_slug));
        }

        return \admin_url("admin.php?page=$this->slug");
    }

    public function get_full_url()
    {
        $params = [];

        if ($active_tab = $this->get_active_tab()) {
            $params['tab'] = $active_tab->slug;

            if ($active_section = $active_tab->get_active_section()) {
                $params['section'] = $active_section->slug;
            }
        }

        return add_query_arg($params, $this->get_url());
    }

    public function render_tab_menu()
    {
        if (! $this->should_make_tabs()) {
            return;
        }

        view('tab-menu', ['settings' => $this]);
    }

    public function render_active_sections()
    {
        view('sections', ['settings' => $this]);
    }

    public function render()
    {
        Enqueuer::setEnqueueManager(new EnqueueManager);

        do_action('wp_settings_before_render_settings_page');

        Enqueuer::enqueue();

        view('settings-page', ['settings' => $this]);

        do_action('wp_settings_after_render_settings_page');
    }

    public function get_options()
    {
        return get_option($this->option_name, []);
    }

    public function find_option($search_option)
    {
        foreach ($this->tabs as $tab) {
            foreach ($tab->sections as $section) {
                foreach ($section->options as $option) {
                    if ($option->args['name'] == $search_option) {
                        return $option;
                    }
                }
            }
        }

        return false;
    }

    public function save()
    {
        if (! isset($_POST['wp_settings_save']) || ! wp_verify_nonce($_POST['wp_settings_save'], 'wp_settings_save_'.$this->option_name)) {
            return;
        }

        if (! current_user_can($this->capability)) {
            wp_die(__('What do you think you are doing?'));
        }

        $current_options = $this->get_options();
        $submitted_options = apply_filters('wp_settings_new_options', $_POST[$this->option_name] ?? [], $current_options);
        $new_options = new Dot($current_options);

        foreach ($this->get_active_tab()->get_active_sections() as $section) {
            foreach ($section->options as $option) {
                $value = (new Dot($submitted_options))
                    ->get($option->implementation->get_option_key_path());

                $valid = $option->validate($value);

                if (! $valid) {
                    continue;
                }

                $value = apply_filters('wp_settings_new_options_'.$option->implementation->get_name(), $option->implementation->sanitize($value), $option->implementation);

                $new_options->set($option->implementation->get_option_key_path(), $value);
            }
        }

        update_option($this->option_name, $new_options->pull());

        $this->flash->set('success', __('Saved changes!'));
    }

    /**
     * @deprecated
     */
    public function maybe_unset_options($current_options, $new_options)
    {
        if (! isset($_REQUEST['wp_settings_submitted'])) {
            return $current_options;
        }

        foreach ($_REQUEST['wp_settings_submitted'] as $submitted) {
            if (empty($new_options[$submitted])) {
                unset($current_options[$submitted]);
            }
        }

        return $current_options;
    }
}
