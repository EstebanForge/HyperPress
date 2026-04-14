<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * ReactField - Enhanced field with React component support.
 *
 * This class extends the base Field class to add React component capabilities
 * while maintaining full backward compatibility with the PHP-only API.
 *
 * Developers can opt-in to React rendering for specific fields that benefit
 * from enhanced interactivity (image pickers, color pickers, repeaters, etc.)
 * while keeping simple fields as traditional HTML inputs.
 *
 * @since 2.1.0
 */
class ReactField extends Field
{
    /**
     * Additional React-specific props passed to the component.
     *
     * @var array
     */
    private array $reactProps = [];

    /**
     * Custom React component name to override default.
     *
     * @var string|null
     */
    private ?string $reactComponent = null;

    /**
     * Whether this field should use React rendering.
     *
     * @var bool
     */
    private bool $useReact = true;

    /**
     * Create a new ReactField instance.
     *
     * @param string $name  Field name.
     * @param string $label Field label.
     * @param string $type  Field type (default: 'text').
     * @return self
     */
    public static function make(string $name, string $label, string $type = 'text'): self
    {
        return new self($type, $name, $label);
    }

    /**
     * Set additional props to pass to the React component.
     *
     * These props are merged with the standard field props and passed
     * directly to the React component, allowing for custom behavior.
     *
     * Example:
     * ```php
     * ReactField::make('image', 'logo', 'Logo')
     *     ->setReactProp('maxWidth', 400)
     *     ->setReactProp('aspectRatio', '16/9');
     * ```
     *
     * @param string $key   Prop name.
     * @param mixed  $value Prop value.
     * @return self
     */
    public function setReactProp(string $key, mixed $value): self
    {
        $this->reactProps[$key] = $value;

        return $this;
    }

    /**
     * Set multiple React props at once.
     *
     * @param array $props Associative array of props.
     * @return self
     */
    public function setReactProps(array $props): self
    {
        $this->reactProps = array_merge($this->reactProps, $props);

        return $this;
    }

    /**
     * Override the default React component for this field.
     *
     * Allows using custom React components instead of the built-in ones.
     * The component must be registered in the React app.
     *
     * Example:
     * ```php
     * ReactField::make('custom', 'my_field', 'My Field')
     *     ->setReactComponent('MyCustomComponent');
     * ```
     *
     * @param string $component Component name (must match registered React component).
     * @return self
     */
    public function setReactComponent(string $component): self
    {
        $this->reactComponent = $component;

        return $this;
    }

    /**
     * Enable or disable React rendering for this field.
     *
     * Allows turning off React for specific fields that would otherwise
     * default to React rendering, or enabling it conditionally.
     *
     * @param bool $useReact Whether to use React rendering.
     * @return self
     */
    public function setUseReact(bool $useReact): self
    {
        $this->useReact = $useReact;

        return $this;
    }

    /**
     * Check if this field should use React rendering.
     *
     * @return bool True if React should be used.
     */
    public function shouldUseReact(): bool
    {
        return $this->useReact;
    }

    /**
     * Get the useReact flag (alias for shouldUseReact).
     *
     * @return bool True if React should be used.
     */
    public function getUseReact(): bool
    {
        return $this->shouldUseReact();
    }

    /**
     * Get the React component name for this field.
     *
     * Returns the custom component if set, otherwise derives the
     * default component name from the field type.
     *
     * @return string Component name.
     */
    public function getReactComponent(): string
    {
        if ($this->reactComponent !== null) {
            return $this->reactComponent;
        }

        // Map field types to default component names
        $componentMap = [
            'text' => 'TextField',
            'textarea' => 'TextareaField',
            'number' => 'NumberField',
            'email' => 'EmailField',
            'url' => 'UrlField',
            'color' => 'ColorField',
            'date' => 'DateField',
            'datetime' => 'DateTimeField',
            'time' => 'TimeField',
            'image' => 'ImageField',
            'file' => 'FileField',
            'select' => 'SelectField',
            'multiselect' => 'MultiSelectField',
            'checkbox' => 'CheckboxField',
            'radio' => 'RadioField',
            'rich_text' => 'RichTextField',
            'media_gallery' => 'MediaGalleryField',
            'repeater' => 'RepeaterField',
            'association' => 'AssociationField',
            'map' => 'MapField',
            'oembed' => 'OEmbedField',
        ];

        return $componentMap[$this->getType()] ?? 'TextField';
    }

    /**
     * Get all React props for this field.
     *
     * Combines the field's standard properties with any custom React props.
     *
     * @return array Complete props array for React component.
     */
    public function getReactProps(): array
    {
        // Use parent's toArray() to access private properties
        $parentData = parent::toArray();

        return array_merge([
            'type' => $this->getType(),
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'default' => $this->getDefault(),
            'placeholder' => $this->getPlaceholder(),
            'required' => $parentData['required'] ?? false,
            'help' => $this->getHelp(),
            'options' => $this->getOptions(),
            'min' => $parentData['min'] ?? null,
            'max' => $parentData['max'] ?? null,
            'multiple' => $parentData['multiple'] ?? false,
            'layout' => $parentData['layout'] ?? 'grid',
        ], $this->reactProps);
    }

    /**
     * Get the required status.
     *
     * @return bool
     */
    public function getRequired(): bool
    {
        $data = parent::toArray();

        return $data['required'] ?? false;
    }

    /**
     * Get the multiple flag.
     *
     * @return bool
     */
    public function getMultiple(): bool
    {
        $data = parent::toArray();

        return $data['multiple'] ?? false;
    }

    /**
     * Get the min value.
     *
     * @return int|null
     */
    public function getMin(): ?int
    {
        $data = parent::toArray();

        return $data['min'] ?? null;
    }

    /**
     * Get the max value.
     *
     * @return int|null
     */
    public function getMax(): ?int
    {
        $data = parent::toArray();

        return $data['max'] ?? null;
    }

    /**
     * Get the layout.
     *
     * @return string
     */
    public function getLayout(): string
    {
        $data = parent::toArray();

        return $data['layout'] ?? 'grid';
    }

    /**
     * Convert field to array for serialization.
     *
     * Extends the parent toArray() to include React-specific data.
     *
     * @return array Field configuration array.
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        // Add React-specific flags (only when needed)
        $data['useReact'] = $this->useReact;
        if ($this->useReact) {
            $data['reactComponent'] = $this->getReactComponent();
            $data['reactProps'] = $this->getReactProps();
        }

        return $data;
    }

    /**
     * Get the underlying args array (for backward compatibility).
     *
     * @return array
     */
    public function getArgs(): array
    {
        $args = parent::getArgs();
        $args['useReact'] = $this->useReact;

        if ($this->useReact) {
            $args['reactComponent'] = $this->getReactComponent();
            $args['reactProps'] = $this->getReactProps();
        }

        return $args;
    }

    /**
     * Magic getter for React properties.
     *
     * @param string $name Property name.
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'reactProps':
                return $this->reactProps;
            case 'reactComponent':
                return $this->reactComponent;
            case 'useReact':
                return $this->useReact;
            default:
                // Try to call parent class getter if it exists
                $method = 'get' . ucfirst($name);
                if (method_exists($this, $method)) {
                    return $this->$method();
                }

                return null;
        }
    }

    /**
     * Magic setter for React properties.
     *
     * @param string $name  Property name.
     * @param mixed  $value Property value.
     */
    public function __set(string $name, $value)
    {
        switch ($name) {
            case 'reactProps':
                $this->reactProps = (array) $value;
                break;
            case 'reactComponent':
                $this->reactComponent = is_string($value) ? $value : null;
                break;
            case 'useReact':
                $this->useReact = (bool) $value;
                break;
            default:
                parent::__set($name, $value);
        }
    }
}
