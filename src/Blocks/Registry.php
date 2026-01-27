<?php

declare(strict_types=1);

/**
 * Facade for HyperBlocks\Registry.
 *
 * This facade maintains backward compatibility by extending the HyperBlocks Registry class.
 * It initializes the Registry with HyperPress-specific configuration.
 */

namespace HyperPress\Blocks;

use HyperBlocks\Registry as HyperBlocksRegistry;
use HyperBlocks\Config;

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Facade class for Registry, extending HyperBlocks\Registry.
 */
class Registry extends HyperBlocksRegistry
{
    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Create config from HyperPress constants
        $config = Config::fromHyperPress();

        // Call parent constructor with config
        parent::__construct($config);
    }

    /**
     * Get the single instance of the Registry.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        $instance = parent::getInstance();

        // Ensure the instance is of the correct type
        if (!$instance instanceof self) {
            // If parent was called first, need to reset and create proper instance
            self::$instance = new self();
            $instance = self::$instance;
        }

        return $instance;
    }

    /**
     * Initialize the registry with HyperPress-specific hooks.
     *
     * @return void
     */
    public function init(): void
    {
        // Call parent init to register basic hooks
        parent::init();

        // Add HyperPress-specific hooks if needed
        add_action('init', [$this, 'registerHyperPressBlocks'], 15);
    }

    /**
     * Register HyperPress-specific blocks.
     *
     * This method can be used to add any HyperPress-specific block registration logic.
     *
     * @return void
     */
    public function registerHyperPressBlocks(): void
    {
        // Add any HyperPress-specific block registration logic here
        // For example, auto-discover hyperblocks directory in plugin

        $basePath = $this->getConfig()->getBasePath();
        if (!empty($basePath)) {
            $hyperblocksPath = $basePath . '/hyperblocks';
            if (is_dir($hyperblocksPath)) {
                $this->getConfig()->addScanPath($hyperblocksPath);
            }
        }
    }
}
