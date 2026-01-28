<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Block\FieldGroup.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Block\FieldGroup as HyperBlocksFieldGroup;
use HyperBlocks\Block\Field as HyperBlocksField;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for FieldGroup.
 */
class FieldGroup
{
    /**
     * Proxy field group instance.
     *
     * @var HyperBlocksFieldGroup
     */
    private HyperBlocksFieldGroup $proxy;

    /**
     * Constructor for backward compatibility.
     *
     * @param HyperBlocksFieldGroup|string $nameOrProxy
     * @param string|null                  $id
     */
    public function __construct($nameOrProxy, ?string $id = null)
    {
        if ($nameOrProxy instanceof HyperBlocksFieldGroup) {
            $this->proxy = $nameOrProxy;
            return;
        }

        $nameOrProxy = (string) $nameOrProxy;
        $id = (string) $id;
        $this->proxy = HyperBlocksFieldGroup::make($nameOrProxy, $id);
    }

    /**
     * Create a new FieldGroup.
     *
     * @param string $name
     * @param string $id
     * @return self
     */
    public static function make(string $name, string $id): self
    {
        return new self(HyperBlocksFieldGroup::make($name, $id));
    }

    /**
     * Wrap an existing HyperBlocks field group.
     *
     * @param HyperBlocksFieldGroup $proxy
     * @return self
     */
    public static function fromProxy(HyperBlocksFieldGroup $proxy): self
    {
        return new self($proxy);
    }

    /**
     * Access the underlying proxy.
     *
     * @return HyperBlocksFieldGroup
     */
    public function getProxy(): HyperBlocksFieldGroup
    {
        return $this->proxy;
    }

    /**
     * Add fields to the group.
     *
     * @param array $fields
     * @return self
     */
    public function addFields(array $fields): self
    {
        $normalized = [];
        foreach ($fields as $field) {
            if ($field instanceof Field) {
                $normalized[] = $field->getProxy();
                continue;
            }
            if ($field instanceof HyperBlocksField) {
                $normalized[] = $field;
            }
        }

        $this->proxy->addFields($normalized);
        return $this;
    }

    /**
     * Proxy unknown calls to underlying field group.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $result = $this->proxy->$name(...$arguments);
        if ($result instanceof HyperBlocksFieldGroup) {
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
