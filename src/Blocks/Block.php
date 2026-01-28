<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Block\Block.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Block\Block as HyperBlocksBlock;
use HyperBlocks\Block\Field as HyperBlocksField;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Block.
 */
class Block
{
    /**
     * Proxy block instance.
     *
     * @var HyperBlocksBlock
     */
    private HyperBlocksBlock $proxy;

    /**
     * Constructor for backward compatibility.
     *
     * @param HyperBlocksBlock|string $nameOrProxy
     * @param string|null             $title
     * @param array                   $config
     */
    public function __construct($nameOrProxy, ?string $title = null, array $config = [])
    {
        if ($nameOrProxy instanceof HyperBlocksBlock) {
            $this->proxy = $nameOrProxy;
            return;
        }

        $nameOrProxy = (string) $nameOrProxy;
        if (null === $title) {
            $this->proxy = HyperBlocksBlock::make($nameOrProxy);
            return;
        }

        $this->proxy = HyperBlocksBlock::make($title)->setName($nameOrProxy);
    }

    /**
     * Create a new Block.
     *
     * @param string $title
     * @return self
     */
    public static function make(string $title): self
    {
        return new self(HyperBlocksBlock::make($title));
    }

    /**
     * Wrap an existing HyperBlocks block.
     *
     * @param HyperBlocksBlock $proxy
     * @return self
     */
    public static function fromProxy(HyperBlocksBlock $proxy): self
    {
        return new self($proxy);
    }

    /**
     * Access the underlying proxy.
     *
     * @return HyperBlocksBlock
     */
    public function getProxy(): HyperBlocksBlock
    {
        return $this->proxy;
    }

    /**
     * Set the block name.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->proxy->setName($name);
        return $this;
    }

    /**
     * Set the block icon.
     *
     * @param string $iconName
     * @return self
     */
    public function setIcon(string $iconName): self
    {
        $this->proxy->setIcon($iconName);
        return $this;
    }

    /**
     * Add fields to the block.
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
     * Add a field group.
     *
     * @param string $groupName
     * @return self
     */
    public function addFieldGroup(string $groupName): self
    {
        $this->proxy->addFieldGroup($groupName);
        return $this;
    }

    /**
     * Set render template.
     *
     * @param string $template
     * @return self
     */
    public function setRenderTemplate(string $template): self
    {
        $this->proxy->setRenderTemplate($template);
        return $this;
    }

    /**
     * Set render template file.
     *
     * @param string $relativePath
     * @return self
     */
    public function setRenderTemplateFile(string $relativePath): self
    {
        $this->proxy->setRenderTemplateFile($relativePath);
        return $this;
    }

    /**
     * Proxy unknown calls to underlying block.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $result = $this->proxy->$name(...$arguments);
        if ($result instanceof HyperBlocksBlock) {
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
