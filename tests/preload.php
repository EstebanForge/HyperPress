<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/wordpress/');
}

if (!defined('HYPERPRESS_TESTING_MODE')) {
    define('HYPERPRESS_TESTING_MODE', true);
}

if (!defined('HYPERFIELDS_TESTING_MODE')) {
    define('HYPERFIELDS_TESTING_MODE', true);
}

if (!defined('HYPERBLOCKS_TESTING_MODE')) {
    define('HYPERBLOCKS_TESTING_MODE', true);
}
