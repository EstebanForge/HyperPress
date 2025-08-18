<?php

declare(strict_types=1);

namespace HyperPress\Fields;

/**
 * @method self add_arg(string $key, mixed $value)
 */
class TabsField extends Field
{
    private array $tabs = [];
    private string $layout = 'horizontal';
    private string $active_tab = '';

    public function add_tab(string $id, string $label, array $fields = []): self
    {
        $this->tabs[$id] = [
            'id' => $id,
            'label' => $label,
            'fields' => $fields,
        ];

        if (empty($this->active_tab)) {
            $this->active_tab = $id;
        }

        return $this;
    }

    public function set_layout(string $layout): self
    {
        $this->layout = in_array($layout, ['horizontal', 'vertical']) ? $layout : 'horizontal';

        return $this;
    }

    public function set_active_tab(string $tab_id): self
    {
        if (isset($this->tabs[$tab_id])) {
            $this->active_tab = $tab_id;
        }

        return $this;
    }

    public function set_active_tab_from_url(string $param = 'tab'): self
    {
        if (isset($_GET[$param]) && isset($this->tabs[$_GET[$param]])) {
            $this->active_tab = sanitize_text_field($_GET[$param]);
        }

        return $this;
    }

    public function get_tab_url(string $tab_id): string
    {
        $current_url = add_query_arg([]); // Get current URL with all parameters

        return add_query_arg('tab', $tab_id, $current_url);
    }

    public function get_tabs(): array
    {
        return $this->tabs;
    }

    public function get_layout(): string
    {
        return $this->layout;
    }

    public function get_active_tab(): string
    {
        return $this->active_tab;
    }

    public function get_tab_fields(string $tab_id): array
    {
        return $this->tabs[$tab_id]['fields'] ?? [];
    }

    public static function make(string $name, string $label, string $type = 'tabs'): self
    {
        return new self($type, $name, $label);
    }

    public function sanitize_value(mixed $value): mixed
    {
        return is_string($value) ? sanitize_text_field($value) : '';
    }

    public function to_array(): array
    {
        return array_merge(parent::to_array(), [
            'tabs' => $this->tabs,
            'layout' => $this->layout,
            'active_tab' => $this->active_tab,
        ]);
    }

    public function render(array $args = []): void
    {
        parent::render($args);
    }
}
