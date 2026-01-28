<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Block\Field.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Block\Field as HyperBlocksField;
use HyperFields\Field as HyperField;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Field.
 */
class Field
{
    /**
     * Proxy field instance.
     *
     * @var HyperBlocksField
     */
    private HyperBlocksField $proxy;

    /**
     * Constructor for backward compatibility.
     *
     * @param HyperBlocksField|string $typeOrProxy
     * @param string|null             $name
     * @param string|null             $label
     */
    public function __construct($typeOrProxy, ?string $name = null, ?string $label = null)
    {
        if ($typeOrProxy instanceof HyperBlocksField) {
            $this->proxy = $typeOrProxy;
            return;
        }

        $typeOrProxy = (string) $typeOrProxy;
        $name = (string) $name;
        $label = (string) $label;
        $this->proxy = HyperBlocksField::make($typeOrProxy, $name, $label);
    }

    /**
     * Create a new Field.
     *
     * @param string $type
     * @param string $name
     * @param string $label
     * @return self
     */
    public static function make(string $type, string $name, string $label): self
    {
        return new self(HyperBlocksField::make($type, $name, $label));
    }

    /**
     * Wrap an existing HyperBlocks field.
     *
     * @param HyperBlocksField $proxy
     * @return self
     */
    public static function fromProxy(HyperBlocksField $proxy): self
    {
        return new self($proxy);
    }

    /**
     * Access the underlying proxy.
     *
     * @return HyperBlocksField
     */
    public function getProxy(): HyperBlocksField
    {
        return $this->proxy;
    }

    /**
     * Set default value.
     *
     * @param mixed $default
     * @return self
     */
    public function setDefault($default): self
    {
        $this->proxy->setDefault($default);
        return $this;
    }

    /**
     * Set placeholder.
     *
     * @param string $placeholder
     * @return self
     */
    public function setPlaceholder(string $placeholder): self
    {
        $this->proxy->setPlaceholder($placeholder);
        return $this;
    }

    /**
     * Set required flag.
     *
     * @param bool $required
     * @return self
     */
    public function setRequired(bool $required = true): self
    {
        $this->proxy->setRequired($required);
        return $this;
    }

    /**
     * Set help text.
     *
     * @param string $help
     * @return self
     */
    public function setHelp(string $help): self
    {
        $this->proxy->setHelp($help);
        return $this;
    }

    /**
     * Set options.
     *
     * @param array $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->proxy->setOptions($options);
        return $this;
    }

    /**
     * Set validation rules.
     *
     * @param array $validation
     * @return self
     */
    public function setValidation(array $validation): self
    {
        $this->proxy->setValidation($validation);
        return $this;
    }

    /**
     * Get underlying HyperFields Field.
     *
     * @return HyperField
     */
    public function getHyperField(): HyperField
    {
        return $this->proxy->getHyperField();
    }

    /**
     * Proxy unknown calls to underlying field.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $result = $this->proxy->$name(...$arguments);
        if ($result instanceof HyperBlocksField) {
            return $this;
        }

        return $result;
    }

    /**
     * Magic property access.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->proxy->$name;
    }

    /**
     * Magic property setter.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->proxy->$name = $value;
    }
}
