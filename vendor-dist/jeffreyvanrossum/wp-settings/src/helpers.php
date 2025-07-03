<?php

namespace HMApi\Jeffreyvr\WPSettings;

if (! function_exists('view')) {
    function view($file, $variables = [])
    {
        extract($variables, EXTR_SKIP);

        $full_path = __DIR__."/../resources/views/{$file}.php";

        if (! file_exists($full_path)) {
            return;
        }

        ob_start();

        include $full_path;

        echo apply_filters('wp_settings_render_view', ob_get_clean(), $file, $variables);
    }
}
