<?php

declare(strict_types=1);

namespace HyperPress\Fields\Container;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Container Factory for HyperFields
 * Provides a simple API for creating metabox containers.
 *
 * @since 2025-08-04
 */
class ContainerFactory
{
    /**
     * Create a post meta container.
     */
    public static function makePostMeta(string $id, string $title): PostMetaContainer
    {
        $container = new PostMetaContainer($id, $title);
        $container->init();

        return $container;
    }

    /**
     * Create a term meta container.
     */
    public static function makeTermMeta(string $id, string $title): TermMetaContainer
    {
        $container = new TermMetaContainer($id, $title);
        $container->init();

        return $container;
    }

    /**
     * Create a user meta container.
     */
    public static function makeUserMeta(string $id, string $title): UserMetaContainer
    {
        $container = new UserMetaContainer($id, $title);
        $container->init();

        return $container;
    }
}
