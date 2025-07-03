<?php

namespace HMApi\Jeffreyvr\WPSettings;

class Enqueuer
{
    protected static EnqueueManager $enqueueManager;

    public static function setEnqueueManager($manager)
    {
        static::$enqueueManager = $manager;
    }

    public static function manager()
    {
        return static::$enqueueManager;
    }

    public static function add($handle, $callback)
    {
        static::manager()->add($handle, $callback);
    }

    public function remove($handle)
    {
        static::manager()->remove($handle);
    }

    public static function enqueue()
    {
        static::manager()->enqueue();
    }
}
