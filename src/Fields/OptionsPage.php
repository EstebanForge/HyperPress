<?php

declare(strict_types=1);

namespace HMApi\Fields;

class OptionsPage
{
    private string $page_title;
    private string $menu_title;
    private string $capability;
    private string $menu_slug;
    private string $parent_slug;
    private string $icon_url;
    private int $position;
    private array $sections = [];
    private array $fields = [];

    public static function make(string $page_title, string $menu_slug): self
    {
        return new self($page_title, $menu_slug);
    }

    private function __construct(string $page_title, string $menu_slug)
    {
        $this->page_title = $page_title;
        $this->menu_title = $page_title;
        $this->menu_slug = $menu_slug;
        $this->capability = 'manage_options';
        $this->parent_slug = 'options-general.php';
        $this->icon_url = '';
        $this->position = 0;
    }

    public function set_menu_title(string $menu_title): self
    {
        $this->menu_title = $menu_title;

        return $this;
    }

    public function set_capability(string $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    public function set_parent_slug(string $parent_slug): self
    {
        $this->parent_slug = $parent_slug;

        return $this;
    }

    public function set_icon_url(string $icon_url): self
    {
        $this->icon_url = $icon_url;

        return $this;
    }

    public function set_position(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function add_section(string $id, string $title, string $description = ''): OptionsSection
    {
        $section = new OptionsSection($id, $title, $description);
        $this->sections[$id] = $section;

        return $section;
    }

    public function add_field(Field $field): self
    {
        $this->fields[$field->get_name()] = $field;

        return $this;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu_page(): void
    {
        if ($this->parent_slug === 'menu') {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'render_page'],
                $this->icon_url,
                $this->position
            );
        } else {
            add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'render_page'],
                $this->position
            );
        }
    }

    public function register_settings(): void
    {
        foreach ($this->sections as $section) {
            add_settings_section(
                $section->get_id(),
                $section->get_title(),
                [$section, 'render'],
                $this->menu_slug
            );

            foreach ($section->get_fields() as $field) {
                register_setting(
                    $section->get_id(),
                    $field->get_name(),
                    [
                        'type' => 'string',
                        'sanitize_callback' => [$field, 'sanitize_value'],
                        'default' => $field->get_default(),
                    ]
                );

                add_settings_field(
                    $field->get_name(),
                    $field->get_label(),
                    [$field, 'render'],
                    $this->menu_slug,
                    $section->get_id()
                );
            }
        }
    }

    public function render_page(): void
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->page_title); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->menu_slug);
        do_settings_sections($this->menu_slug);
        submit_button();
        ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'settings_page_' . $this->menu_slug &&
            $hook_suffix !== $this->parent_slug . '_page_' . $this->menu_slug) {
            return;
        }

        TemplateLoader::enqueue_assets();
    }
}
