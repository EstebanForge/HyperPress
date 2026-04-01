<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * @method self addArg(string $key, mixed $value)
 */
class TabsField extends Field
{
    private array $tabs = [];
    private string $layout = 'horizontal';
    private string $active_tab = '';

    /**
     * AddTab.
     *
     * @return self
     */
    public function addTab(string $id, string $label, array $fields = []): self
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

    /**
     * SetLayout.
     *
     * @return self
     */
    public function setLayout(string $layout): self
    {
        $this->layout = in_array($layout, ['horizontal', 'vertical']) ? $layout : 'horizontal';

        return $this;
    }

    /**
     * SetActiveTab.
     *
     * @return self
     */
    public function setActiveTab(string $tab_id): self
    {
        if (isset($this->tabs[$tab_id])) {
            $this->active_tab = $tab_id;
        }

        return $this;
    }

    /**
     * SetActiveTabFromUrl.
     *
     * @return self
     */
    public function setActiveTabFromUrl(string $param = 'tab'): self
    {
        if (isset($_GET[$param]) && isset($this->tabs[$_GET[$param]])) {
            $this->active_tab = sanitize_text_field($_GET[$param]);
        }

        return $this;
    }

    /**
     * GetTabUrl.
     *
     * @return string
     */
    public function getTabUrl(string $tab_id): string
    {
        $current_url = add_query_arg([]); // Get current URL with all parameters

        return add_query_arg('tab', $tab_id, $current_url);
    }

    /**
     * GetTabs.
     *
     * @return array
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * GetLayout.
     *
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * GetActiveTab.
     *
     * @return string
     */
    public function getActiveTab(): string
    {
        return $this->active_tab;
    }

    /**
     * GetTabFields.
     *
     * @return array
     */
    public function getTabFields(string $tab_id): array
    {
        return $this->tabs[$tab_id]['fields'] ?? [];
    }

    /**
     * Make.
     *
     * @return self
     */
    public static function make(string $name, string $label, string $type = 'tabs'): self
    {
        return new self($type, $name, $label);
    }

    /**
     * SanitizeValue.
     *
     * @return mixed
     */
    public function sanitizeValue(mixed $value): mixed
    {
        return is_string($value) ? sanitize_text_field($value) : '';
    }

    /**
     * ToArray.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'tabs' => $this->tabs,
            'layout' => $this->layout,
            'active_tab' => $this->active_tab,
        ]);
    }

    /**
     * Render.
     *
     * @return void
     */
    public function render(array $args = []): void
    {
        parent::render($args);
    }
}
