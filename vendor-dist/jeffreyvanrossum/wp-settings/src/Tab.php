<?php

namespace HMApi\Jeffreyvr\WPSettings;

use HMApi\Jeffreyvr\WPSettings\Traits\HasOptionLevel;

class Tab
{
    use HasOptionLevel;

    public $settings;

    public $title;

    public $slug;

    public $sections = [];

    public function __construct($settings, $title, $slug = null)
    {
        $this->title = $title;
        $this->settings = $settings;

        if ($this->slug === null) {
            $this->slug = sanitize_title($title);
        }
    }

    public function add_section($title, $args = [])
    {
        $section = new Section($this, $title, $args);

        $this->sections[] = $section;

        return $section;
    }

    public function get_section_links()
    {
        return array_filter($this->sections, function ($section) {
            return $section->as_link;
        });
    }

    public function contains_only_section_links()
    {
        return count($this->get_section_links()) === count($this->sections);
    }

    public function get_section_by_name($name)
    {
        foreach ($this->sections as $section) {
            if ($section->slug == $name) {
                return $section;
            }
        }

        return false;
    }

    public function get_active_section()
    {
        if (empty($this->get_section_links())) {
            return;
        }

        if (isset($_REQUEST['section'])) {
            return $this->get_section_by_name($_REQUEST['section']);
        }

        if ($this->contains_only_section_links()) {
            return $this->sections[0];
        }
    }

    public function get_active_sections()
    {
        if (! isset($_REQUEST['section']) && $this->contains_only_section_links()) {
            return [$this->sections[0]];
        }

        return array_filter($this->sections, function ($section) {
            if (isset($_REQUEST['section'])) {
                return $section->as_link && $_REQUEST['section'] == $section->slug;
            }

            return ! $section->as_link;
        });
    }
}
