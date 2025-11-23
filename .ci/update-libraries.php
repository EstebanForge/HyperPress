#!/usr/bin/env php
<?php

/**
 * Download Libraries Script (PHP CLI)
 *
 * This script uses the Main class directly to get CDN URLs and downloads
 * the latest versions of all libraries to the local assets directory.
 *
 * Usage:
 *   php .ci/download-libraries.php [--library=name] [--all]
 *
 * Examples:
 *   php .ci/download-libraries.php --all
 *   php .ci/download-libraries.php --library=htmx
 *   php .ci/download-libraries.php --library=htmx-extensions
 */

// Configuration
define('ASSETS_DIR', 'assets/libs');
define('EXTENSIONS_DIR', ASSETS_DIR . '/htmx-extensions');

/**
 * Extract CDN URLs from Main.php file
 * This ensures we always use the same URLs as defined in the main plugin
 */
function getCdnUrls()
{
    $main_php_path = 'src/Main.php';

    if (!file_exists($main_php_path)) {
        throw new Exception("Main.php file not found at: $main_php_path");
    }

    $content = file_get_contents($main_php_path);

    // Extract the getCdnUrls method content
    $pattern = '/public function getCdnUrls\(\): array\s*\{(.*?)\n    \}/s';
    preg_match($pattern, $content, $matches);

    if (!$matches) {
        throw new Exception("Could not find getCdnUrls method in Main.php");
    }

    $method_content = $matches[1];

    // Extract the return array
    $return_pattern = '/return\s*(\[.*?\]);/s';
    preg_match($return_pattern, $method_content, $return_matches);

    if (!$return_matches) {
        throw new Exception("Could not find return array in getCdnUrls method");
    }

    $array_string = $return_matches[1];

    // Convert PHP array syntax to something we can eval safely
    // Replace single quotes with double quotes and ensure proper PHP syntax
    $array_string = str_replace("'", '"', $array_string);

    // Use eval to parse the array (safe since we're controlling the input)
    $cdn_urls = eval("return $array_string;");

    if (!is_array($cdn_urls)) {
        throw new Exception("Failed to parse CDN URLs array from Main.php");
    }

    return $cdn_urls;
}

/**
 * Create directories if they don't exist
 */
function ensure_dir($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Created directory: $dir\n";
    }
}

/**
 * Download a file from URL to local path
 */
function download_file($url, $output_path) {
    echo "📥 Downloading: $url\n";
    echo "📍 To: $output_path\n";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HTMX-API-WP Library Downloader');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    unset($ch);

    if ($data === false || !empty($error)) {
        throw new Exception("cURL error: $error");
    }

    if ($http_code !== 200) {
        throw new Exception("HTTP $http_code: Download failed");
    }

    if (file_put_contents($output_path, $data) === false) {
        throw new Exception("Failed to write file: $output_path");
    }

    echo "✅ Downloaded: " . basename($output_path) . "\n";
}

/**
 * Download core library
 */
function download_core_library($name, $url) {
    echo "\n📦 Downloading core library: $name\n";

    ensure_dir(ASSETS_DIR);

    $filename_map = [
        'htmx' => 'htmx.min.js',
        'hyperscript' => '_hyperscript.min.js',
        'alpinejs' => 'alpinejs.min.js',
        'alpine_ajax' => 'alpine-ajax.min.js',
        'datastar' => 'datastar.min.js',
    ];

    $filename = $filename_map[$name] ?? "$name.min.js";
    $output_path = ASSETS_DIR . '/' . $filename;

    download_file($url, $output_path);
}

/**
 * Download HTMX extension
 */
function download_extension($name, $url) {
    echo "\n🔌 Downloading HTMX extension: $name\n";

    ensure_dir(EXTENSIONS_DIR);

    $filename = "$name.js";
    $output_path = EXTENSIONS_DIR . '/' . $filename;

    download_file($url, $output_path);
}

/**
 * Parse command line arguments
 */
function parse_args($argv) {
    $target_library = null;

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if ($arg === '--all') {
            $target_library = 'all';
        } elseif (strpos($arg, '--library=') === 0) {
            $target_library = substr($arg, 10);
        } elseif ($arg === '--library' && isset($argv[$i + 1])) {
            $target_library = $argv[$i + 1];
            $i++; // Skip next argument
        }
    }

    return $target_library;
}

/**
 * Main download function
 */
function download_libraries($target_library = null) {
    try {
        echo "🔍 Getting CDN URLs...\n";

        $cdn_urls = getCdnUrls();
        $core_count = count($cdn_urls) - (isset($cdn_urls['htmx_extensions']) ? 1 : 0);
        $extensions_count = isset($cdn_urls['htmx_extensions']) ? count($cdn_urls['htmx_extensions']) : 0;

        echo "✅ Found $core_count core libraries and $extensions_count HTMX extensions\n";

        if ($target_library === 'htmx-extensions') {
            // Download all HTMX extensions
            echo "\n🚀 Downloading all HTMX extensions...\n";
            if (isset($cdn_urls['htmx_extensions'])) {
                foreach ($cdn_urls['htmx_extensions'] as $name => $config) {
                    download_extension($name, $config['url']);
                }
            }
        } elseif ($target_library && $target_library !== 'all') {
            // Download specific library
            if (isset($cdn_urls[$target_library])) {
                download_core_library($target_library, $cdn_urls[$target_library]['url']);
            } else {
                throw new Exception("Library '$target_library' not found in CDN URLs");
            }
        } else {
            // Download all libraries
            echo "\n🚀 Downloading all libraries...\n";

            // Download core libraries
            foreach ($cdn_urls as $name => $config) {
                if ($name !== 'htmx_extensions') {
                    download_core_library($name, $config['url']);
                }
            }

            // Download HTMX extensions
            if (isset($cdn_urls['htmx_extensions'])) {
                foreach ($cdn_urls['htmx_extensions'] as $name => $config) {
                    download_extension($name, $config['url']);
                }
            }
        }

        echo "\n🎉 All downloads completed successfully!\n";

    } catch (Exception $e) {
        echo "\n❌ Error during download: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "🔽 HTMX API WordPress Plugin - Library Downloader (PHP)\n";
    echo "====================================================\n\n";

    $target_library = parse_args($argv);

    if ($target_library) {
        echo "🎯 Target: " . ($target_library === 'all' ? 'All libraries' : $target_library) . "\n";
    } else {
        echo "🎯 Target: All libraries (default)\n";
    }

    download_libraries($target_library);
}
