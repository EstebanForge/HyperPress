<?php

declare(strict_types=1);

namespace HMApi\Fields;

class Registry
{
    private static ?self $instance = null;
    private array $fields = [];
    private array $field_groups = [];
    private array $contexts = [];

    private function __construct()
    {
        // Private constructor for singleton
    }

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register_field(string $context, string $name, Field $field): self
    {
        if (!isset($this->fields[$context])) {
            $this->fields[$context] = [];
        }

        $this->fields[$context][$name] = $field;

        return $this;
    }

    public function register_field_group(string $name, array $fields): self
    {
        $this->field_groups[$name] = $fields;

        return $this;
    }

    public function get_field(string $context, string $name): ?Field
    {
        return $this->fields[$context][$name] ?? null;
    }

    public function get_field_group(string $name): ?array
    {
        return $this->field_groups[$name] ?? null;
    }

    public function get_fields_by_context(string $context): array
    {
        return $this->fields[$context] ?? [];
    }

    public function get_all_fields(): array
    {
        return $this->fields;
    }

    public function get_all_field_groups(): array
    {
        return $this->field_groups;
    }

    public function has_field(string $context, string $name): bool
    {
        return isset($this->fields[$context][$name]);
    }

    public function has_field_group(string $name): bool
    {
        return isset($this->field_groups[$name]);
    }

    public function remove_field(string $context, string $name): self
    {
        if (isset($this->fields[$context][$name])) {
            unset($this->fields[$context][$name]);
        }

        return $this;
    }

    public function remove_field_group(string $name): self
    {
        if (isset($this->field_groups[$name])) {
            unset($this->field_groups[$name]);
        }

        return $this;
    }

    public function register_post_fields(array $fields): self
    {
        foreach ($fields as $name => $field) {
            $this->register_field('post', $name, $field);
        }

        return $this;
    }

    public function register_user_fields(array $fields): self
    {
        foreach ($fields as $name => $field) {
            $this->register_field('user', $name, $field);
        }

        return $this;
    }

    public function register_term_fields(array $fields): self
    {
        foreach ($fields as $name => $field) {
            $this->register_field('term', $name, $field);
        }

        return $this;
    }

    public function register_option_fields(array $fields): self
    {
        foreach ($fields as $name => $field) {
            $this->register_field('option', $name, $field);
        }

        return $this;
    }

    public function init(): self
    {
        add_action('init', [$this, 'register_all']);

        return $this;
    }

    public function register_all(): void
    {
        do_action('hmapi_fields_register');
        $this->register_admin_hooks();
    }

    private function register_admin_hooks(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('add_meta_boxes', [$this, 'register_post_meta_boxes']);
        add_action('show_user_profile', [$this, 'render_user_fields']);
        add_action('edit_user_profile', [$this, 'render_user_fields']);
        add_action('personal_options_update', [$this, 'save_user_fields']);
        add_action('edit_user_profile_update', [$this, 'save_user_fields']);
        add_action('edit_term', [$this, 'render_term_fields']);
        add_action('add_tag_form_fields', [$this, 'render_term_fields']);
        add_action('edit_tag_form_fields', [$this, 'render_term_fields']);
        add_action('created_term', [$this, 'save_term_fields']);
        add_action('edited_term', [$this, 'save_term_fields']);
    }

    public function register_post_meta_boxes(): void
    {
        $post_fields = $this->get_fields_by_context('post');
        if (empty($post_fields)) {
            return;
        }

        add_meta_box(
            'hmapi_post_fields',
            'Custom Fields',
            [$this, 'render_post_meta_box'],
            null,
            'normal',
            'default'
        );
    }

    public function render_post_meta_box(): void
    {
        $post_fields = $this->get_fields_by_context('post');
        if (empty($post_fields)) {
            return;
        }

        wp_nonce_field('hmapi_post_fields', 'hmapi_post_fields_nonce');

        foreach ($post_fields as $field) {
            $this->render_field_input($field);
        }
    }

    public function render_user_fields(): void
    {
        $user_fields = $this->get_fields_by_context('user');
        if (empty($user_fields)) {
            return;
        }

        foreach ($user_fields as $field) {
            $this->render_field_input($field);
        }
    }

    public function render_term_fields(): void
    {
        $term_fields = $this->get_fields_by_context('term');
        if (empty($term_fields)) {
            return;
        }

        foreach ($term_fields as $field) {
            $this->render_field_input($field);
        }
    }

    private function render_field_input(Field $field): void
    {
        $value = '';
        $context = $field->get_context();

        switch ($context) {
            case 'post':
                $post_field = PostField::for_post(get_the_ID(), $field->get_type(), $field->get_name(), $field->get_label());
                $value = $post_field->get_value();
                break;
            case 'user':
                $user_id = get_current_user_id();
                if (isset($_GET['user_id'])) {
                    $user_id = intval($_GET['user_id']);
                }
                $user_field = UserField::for_user($user_id, $field->get_type(), $field->get_name(), $field->get_label());
                $value = $user_field->get_value();
                break;
            case 'term':
                $term_id = 0;
                if (isset($_GET['tag_ID'])) {
                    $term_id = intval($_GET['tag_ID']);
                }
                if ($term_id > 0) {
                    $term_field = TermField::for_term($term_id, $field->get_type(), $field->get_name(), $field->get_label());
                    $value = $term_field->get_value();
                }
                break;
            case 'option':
                $option_field = OptionField::for_option($field->get_name(), $field->get_type(), $field->get_name(), $field->get_label());
                $value = $option_field->get_value();
                break;
        }

        $field_data = $field->to_array();
        $field_data['value'] = $value;

        include __DIR__ . '/templates/field-input.php';
    }

    public function save_post_fields(int $post_id): void
    {
        if (!isset($_POST['hmapi_post_fields_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['hmapi_post_fields_nonce'], 'hmapi_post_fields')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $post_fields = $this->get_fields_by_context('post');
        foreach ($post_fields as $field) {
            $field_name = $field->get_name();
            if (isset($_POST[$field_name])) {
                $post_field = PostField::for_post($post_id, $field->get_type(), $field_name, $field->get_label());
                $post_field->set_value($_POST[$field_name]);
            }
        }
    }

    public function save_user_fields(int $user_id): void
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $user_fields = $this->get_fields_by_context('user');
        foreach ($user_fields as $field) {
            $field_name = $field->get_name();
            if (isset($_POST[$field_name])) {
                $user_field = UserField::for_user($user_id, $field->get_type(), $field_name, $field->get_label());
                $user_field->set_value($_POST[$field_name]);
            }
        }
    }

    public function save_term_fields(int $term_id): void
    {
        if (!current_user_can('manage_categories')) {
            return;
        }

        $term_fields = $this->get_fields_by_context('term');
        foreach ($term_fields as $field) {
            $field_name = $field->get_name();
            if (isset($_POST[$field_name])) {
                $term_field = TermField::for_term($term_id, $field->get_type(), $field_name, $field->get_label());
                $term_field->set_value($_POST[$field_name]);
            }
        }
    }
}
