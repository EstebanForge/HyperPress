<?php

declare(strict_types=1);

namespace HyperFields;

class CustomField extends Field
{
    private $render_callback = '';
    private $sanitize_callback = '';
    private $validate_callback = '';
    private array $assets = [];

    /**
     * Build.
     *
     * @return self
     */
    public static function build(string $name, string $label): self
    {
        return new self('custom', $name, $label);
    }

    /**
     * SetRenderCallback.
     *
     * @return self
     */
    public function setRenderCallback(callable|string $callback): self
    {
        $this->render_callback = $callback;

        return $this;
    }

    /**
     * SetSanitizeCallback.
     *
     * @return self
     */
    public function setSanitizeCallback(callable|string $callback): self
    {
        $this->sanitize_callback = $callback;

        return $this;
    }

    /**
     * SetValidateCallback.
     *
     * @return self
     */
    public function setValidateCallback(callable|string $callback): self
    {
        $this->validate_callback = $callback;

        return $this;
    }

    /**
     * SetAssets.
     *
     * @return self
     */
    public function setAssets(array $assets): self
    {
        $this->assets = $assets;

        return $this;
    }

    /**
     * GetRenderCallback.
     *
     * @return mixed
     */
    public function getRenderCallback(): mixed
    {
        return $this->render_callback;
    }

    /**
     * GetSanitizeCallback.
     *
     * @return mixed
     */
    public function getSanitizeCallback(): mixed
    {
        return $this->sanitize_callback;
    }

    /**
     * GetValidateCallback.
     *
     * @return mixed
     */
    public function getValidateCallback(): mixed
    {
        return $this->validate_callback;
    }

    /**
     * GetAssets.
     *
     * @return array
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    /**
     * SanitizeValue.
     *
     * @return mixed
     */
    public function sanitizeValue(mixed $value): mixed
    {
        if (!empty($this->sanitize_callback) && is_callable($this->sanitize_callback)) {
            return call_user_func($this->sanitize_callback, $value);
        }

        return sanitize_text_field((string) $value);
    }

    /**
     * ValidateValue.
     *
     * @return bool
     */
    public function validateValue(mixed $value): bool
    {
        if (!empty($this->validate_callback) && is_callable($this->validate_callback)) {
            return (bool) call_user_func($this->validate_callback, $value);
        }

        return true;
    }

    /**
     * ToArray.
     *
     * @return array
     */
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
