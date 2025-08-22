<?php

declare(strict_types=1);

namespace HyperPress\Fields;

class CustomField extends Field
{
    private string $render_callback = '';
    private string $sanitize_callback = '';
    private string $validate_callback = '';
    private array $assets = [];

    public static function build(string $name, string $label): self
    {
        return new self('custom', $name, $label);
    }

    public function setRenderCallback(string $callback): self
    {
        $this->render_callback = $callback;

        return $this;
    }

    public function setSanitizeCallback(string $callback): self
    {
        $this->sanitize_callback = $callback;

        return $this;
    }

    public function setValidateCallback(string $callback): self
    {
        $this->validate_callback = $callback;

        return $this;
    }

    public function setAssets(array $assets): self
    {
        $this->assets = $assets;

        return $this;
    }

    public function getRenderCallback(): string
    {
        return $this->render_callback;
    }

    public function getSanitizeCallback(): string
    {
        return $this->sanitize_callback;
    }

    public function getValidateCallback(): string
    {
        return $this->validate_callback;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function sanitizeValue(mixed $value): mixed
    {
        if (!empty($this->sanitize_callback) && is_callable($this->sanitize_callback)) {
            return call_user_func($this->sanitize_callback, $value);
        }

        return sanitize_text_field((string) $value);
    }

    public function validateValue(mixed $value): bool
    {
        if (!empty($this->validate_callback) && is_callable($this->validate_callback)) {
            return (bool) call_user_func($this->validate_callback, $value);
        }

        return true;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'render_callback' => $this->render_callback,
            'sanitize_callback' => $this->sanitize_callback,
            'validate_callback' => $this->validate_callback,
            'assets' => $this->assets,
        ]);
    }
}
