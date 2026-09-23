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
 *   php .ci/download-libraries.php --library=htmx4
 *   php .ci/download-libraries.php --library=htmx-extensions
 *   php .ci/download-libraries.php --library=htmx4-extensions
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

    // Versioned layout: htmx lines live in per-major directories. The htmx 2
    // download also refreshes the legacy top-level copy so consumers with
    // hardcoded legacy paths keep working.
    if ($name === 'htmx') {
        ensure_dir(ASSETS_DIR . '/htmx/2');
        download_file($url, ASSETS_DIR . '/htmx/2/htmx.min.js');
        copy(ASSETS_DIR . '/htmx/2/htmx.min.js', ASSETS_DIR . '/htmx.min.js');
        echo "♻️  Refreshed legacy copy: " . ASSETS_DIR . "/htmx.min.js\n";
        return;
    }
    if ($name === 'htmx4') {
        ensure_dir(ASSETS_DIR . '/htmx/4');
        download_file($url, ASSETS_DIR . '/htmx/4/htmx.min.js');
        return;
    }

    $filename_map = [
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
 * Download an htmx 4 extension (ships inside the htmx.org package).
 *
 * Stored under assets/libs/htmx/4/ext/ with the dist filename kept intact
 * ({slug}.min.js) so it stays recognizable next to the v2 extension layout.
 */
function download_htmx4_extension($name, $url) {
    echo "\n🔌 Downloading htmx 4 extension: $name\n";

    $dir = ASSETS_DIR . '/htmx/4/ext';
    ensure_dir($dir);

    download_file($url, "$dir/$name.min.js");
}

/**
 * Fetch and decode JSON from a remote URL.
 */
function fetch_json($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HyperPress-Updater');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    if ($code !== 200 || !$data) {
        return null;
    }
    return json_decode($data, true);
}

/**
 * Query upstream registries for the latest versions of core libraries.
 */
function get_upstream_latest_versions() {
    $upstream = [];

    // htmx 2.x and 4.x from npm
    $htmx_npm = fetch_json('https://registry.npmjs.org/htmx.org');
    if ($htmx_npm && !empty($htmx_npm['versions'])) {
        $versions = array_keys($htmx_npm['versions']);
        // 2.x line
        $v2 = array_filter($versions, fn($v) => preg_match('/^2\.[0-9]+\.[0-9]+$/', $v));
        if ($v2) {
            usort($v2, 'version_compare');
            $ver = end($v2);
            $upstream['htmx'] = [
                'version' => $ver,
                'url' => "https://cdn.jsdelivr.net/npm/htmx.org@{$ver}/dist/htmx.min.js",
            ];
        }
        // 4.x line
        $v4 = array_filter($versions, fn($v) => preg_match('/^4\.[0-9]+\.[0-9]+$/', $v));
        if ($v4) {
            usort($v4, 'version_compare');
            $ver = end($v4);
            $upstream['htmx4'] = [
                'version' => $ver,
                'url' => "https://cdn.jsdelivr.net/npm/htmx.org@{$ver}/dist/htmx.min.js",
            ];
        }
    }

    // Datastar from GitHub releases (production releases are published to GitHub)
    $gh_releases = fetch_json('https://api.github.com/repos/starfederation/datastar/releases');
    if (is_array($gh_releases)) {
        foreach ($gh_releases as $rel) {
            if (empty($rel['prerelease']) && preg_match('/^v?([0-9]+\.[0-9]+\.[0-9]+)$/', $rel['tag_name'] ?? '', $m)) {
                $ver = $m[1];
                $upstream['datastar'] = [
                    'version' => $ver,
                    'url' => "https://cdn.jsdelivr.net/gh/starfederation/datastar@v{$ver}/bundles/datastar.js",
                ];
                break;
            }
        }
    }

    // Alpine.js from npm
    $alpine_npm = fetch_json('https://registry.npmjs.org/alpinejs');
    if ($alpine_npm && !empty($alpine_npm['dist-tags']['latest'])) {
        $ver = $alpine_npm['dist-tags']['latest'];
        $upstream['alpinejs'] = [
            'version' => $ver,
            'url' => "https://cdn.jsdelivr.net/npm/alpinejs@{$ver}/dist/cdn.min.js",
        ];
    }

    // Alpine AJAX from npm
    $ajax_npm = fetch_json('https://registry.npmjs.org/@imacrayon/alpine-ajax');
    if ($ajax_npm && !empty($ajax_npm['dist-tags']['latest'])) {
        $ver = $ajax_npm['dist-tags']['latest'];
        $upstream['alpine_ajax'] = [
            'version' => $ver,
            'url' => "https://cdn.jsdelivr.net/npm/@imacrayon/alpine-ajax@{$ver}/dist/cdn.min.js",
        ];
    }

    // Hyperscript from npm
    $hs_npm = fetch_json('https://registry.npmjs.org/hyperscript.org');
    if ($hs_npm && !empty($hs_npm['dist-tags']['latest'])) {
        $ver = $hs_npm['dist-tags']['latest'];
        $upstream['hyperscript'] = [
            'version' => $ver,
            'url' => "https://cdn.jsdelivr.net/npm/hyperscript.org@{$ver}/dist/_hyperscript.min.js",
        ];
    }

    return $upstream;
}

/**
 * Update pinned version in src/Main.php and docs/hypermedia-libraries.md.
 */
function bump_main_php_version($library, $new_version, $new_url) {
    $main_file = 'src/Main.php';
    if (!file_exists($main_file)) {
        return false;
    }

    $content = file_get_contents($main_file);
    $pattern = "/('" . preg_quote($library, '/') . "'\s*=>\s*\[\s*'url'\s*=>\s*')[^']+('\s*,\s*'version'\s*=>\s*')[^']+(')/";

    if (preg_match($pattern, $content)) {
        $replacement = '${1}' . $new_url . '${2}' . $new_version . '${3}';
        $content = preg_replace($pattern, $replacement, $content, 1);
        file_put_contents($main_file, $content);

        update_docs_version_reference();
        return true;
    }

    return false;
}

/**
 * Update version for a specific htmx 2.x extension in src/Main.php.
 */
function bump_main_php_extension_version($ext_name, $new_version) {
    $main_file = 'src/Main.php';
    if (!file_exists($main_file)) {
        return false;
    }

    $content = file_get_contents($main_file);
    $pattern = "/('" . preg_quote($ext_name, '/') . "'\s*=>\s*\[\s*'url'\s*=>\s*'[^']+'\s*,\s*'version'\s*=>\s*')[^']+(')/";

    if (preg_match($pattern, $content)) {
        $replacement = '${1}' . $new_version . '${2}';
        $content = preg_replace($pattern, $replacement, $content, 1);
        file_put_contents($main_file, $content);
        return true;
    }

    return false;
}

/**
 * Update all htmx 4 extensions in src/Main.php when htmx4 changes.
 */
function bump_htmx4_extensions($new_version) {
    $main_file = 'src/Main.php';
    if (!file_exists($main_file)) {
        return false;
    }

    $content = file_get_contents($main_file);

    // Update URLs in htmx4_extensions block: htmx.org@<old>/dist/ext/ -> htmx.org@<new>/dist/ext/
    $pattern_url = "/('url'\s*=>\s*'https:\/\/cdn\.jsdelivr\.net\/npm\/htmx\.org@)[^\/]+(\/dist\/ext\/)/";
    $content = preg_replace($pattern_url, '${1}' . $new_version . '${2}', $content);

    // Update version strings in htmx4_extensions block
    if (preg_match("/'htmx4_extensions'\s*=>\s*\[(.*?)\]\s*,\s*\];/s", $content, $m)) {
        $block = $m[1];
        $updated_block = preg_replace("/('version'\s*=>\s*')[^']+(')/", '${1}' . $new_version . '${2}', $block);
        $content = str_replace($block, $updated_block, $content);
    }

    file_put_contents($main_file, $content);
    return true;
}

/**
 * Keep docs/hypermedia-libraries.md aligned with pinned versions in Main.php.
 */
function update_docs_version_reference() {
    $docs_file = 'docs/hypermedia-libraries.md';
    if (!file_exists($docs_file)) {
        return;
    }

    $cdn_urls = getCdnUrls();
    $htmx_ver = $cdn_urls['htmx']['version'] ?? '2.0.11';
    $htmx4_ver = $cdn_urls['htmx4']['version'] ?? '4.0.0';
    $ds_ver = $cdn_urls['datastar']['version'] ?? '1.0.4';

    $content = file_get_contents($docs_file);
    $pattern = '/\(htmx\s+[0-9\.]+\s*\/\s*[0-9\.]+\s*,\s*Datastar\s+[0-9\.]+\)/';
    $replacement = "(htmx {$htmx_ver} / {$htmx4_ver}, Datastar {$ds_ver})";

    $updated = preg_replace($pattern, $replacement, $content, 1);
    if ($updated && $updated !== $content) {
        file_put_contents($docs_file, $updated);
    }
}

/**
 * Compare pinned CDN URLs with upstream releases and display status.
 */
function check_libraries() {
    echo "🔍 Checking upstream versions against src/Main.php...\n\n";

    $cdn_urls = getCdnUrls();
    $upstream = get_upstream_latest_versions();

    printf("%-15s %-15s %-15s %s\n", "Library", "Pinned (Main)", "Latest (Upstream)", "Status");
    echo str_repeat("-", 65) . "\n";

    $has_updates = false;

    foreach ($upstream as $lib => $info) {
        $pinned = $cdn_urls[$lib]['version'] ?? 'N/A';
        $latest = $info['version'];
        $status = '✅ UP-TO-DATE';

        if ($pinned !== 'N/A' && version_compare($pinned, $latest, '<')) {
            $status = '⚠️  OUTDATED';
            $has_updates = true;
        }

        printf("%-15s %-15s %-15s %s\n", $lib, $pinned, $latest, $status);
    }

    echo "\n";
    if ($has_updates) {
        echo "💡 Updates available! Run 'npm run update-all' to bump all pins and download.\n";
    } else {
        echo "🎉 All checked libraries are up to date.\n";
    }
}

/**
 * Parse command line arguments
 */
function parse_args($argv) {
    $target_library = null;
    $action = 'download';

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if ($arg === '--all') {
            $target_library = 'all';
        } elseif ($arg === '--update-all') {
            $target_library = 'all';
            $action = 'update';
        } elseif ($arg === '--check') {
            $action = 'check';
        } elseif ($arg === '--latest' || $arg === '--update') {
            $action = 'update';
        } elseif (strpos($arg, '--library=') === 0) {
            $target_library = substr($arg, 10);
        } elseif ($arg === '--library' && isset($argv[$i + 1])) {
            $target_library = $argv[$i + 1];
            $i++; // Skip next argument
        }
    }

    return [$target_library, $action];
}

/**
 * Main download function
 */
function download_libraries($target_library = null) {
    try {
        echo "🔍 Getting CDN URLs...\n";

        $cdn_urls = getCdnUrls();
        $extension_map_keys = ['htmx_extensions', 'htmx4_extensions'];
        $core_count = count(array_diff(array_keys($cdn_urls), $extension_map_keys));
        $extensions_count = 0;
        foreach ($extension_map_keys as $map_key) {
            $extensions_count += isset($cdn_urls[$map_key]) ? count($cdn_urls[$map_key]) : 0;
        }

        echo "✅ Found $core_count core libraries and $extensions_count HTMX extensions\n";

        if ($target_library === 'htmx-extensions') {
            // Download all htmx 2 extensions
            echo "\n🚀 Downloading all htmx 2 extensions...\n";
            if (isset($cdn_urls['htmx_extensions'])) {
                foreach ($cdn_urls['htmx_extensions'] as $name => $config) {
                    download_extension($name, $config['url']);
                }
            }
        } elseif ($target_library === 'htmx4-extensions') {
            // Download all htmx 4 extensions
            echo "\n🚀 Downloading all htmx 4 extensions...\n";
            if (isset($cdn_urls['htmx4_extensions'])) {
                foreach ($cdn_urls['htmx4_extensions'] as $name => $config) {
                    download_htmx4_extension($name, $config['url']);
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

            // Download core libraries (both extension maps are not core)
            foreach ($cdn_urls as $name => $config) {
                if (!in_array($name, $extension_map_keys, true)) {
                    download_core_library($name, $config['url']);
                }
            }

            // Download htmx 2 extensions
            if (isset($cdn_urls['htmx_extensions'])) {
                foreach ($cdn_urls['htmx_extensions'] as $name => $config) {
                    download_extension($name, $config['url']);
                }
            }

            // Download htmx 4 extensions
            if (isset($cdn_urls['htmx4_extensions'])) {
                foreach ($cdn_urls['htmx4_extensions'] as $name => $config) {
                    download_htmx4_extension($name, $config['url']);
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
    echo "🔽 HTMX API WordPress Plugin - Library Manager (PHP)\n";
    echo "====================================================\n\n";

    [$target_library, $action] = parse_args($argv);

    if ($action === 'check') {
        check_libraries();
        exit(0);
    }

    if ($action === 'update') {
        echo "🔄 Checking and updating library versions from upstream...\n";
        $upstream = get_upstream_latest_versions();
        $cdn_urls = getCdnUrls();

        $libs_to_check = ($target_library && $target_library !== 'all')
            ? [$target_library]
            : array_keys($upstream);

        $bumped = 0;
        foreach ($libs_to_check as $lib) {
            if (isset($upstream[$lib])) {
                $current_ver = $cdn_urls[$lib]['version'] ?? null;
                $new_ver = $upstream[$lib]['version'];
                $new_url = $upstream[$lib]['url'];

                if ($current_ver === null || version_compare($current_ver, $new_ver, '<')) {
                    echo "⬆️  Bumping $lib: $current_ver -> $new_ver\n";
                    if (bump_main_php_version($lib, $new_ver, $new_url)) {
                        $bumped++;
                        if ($lib === 'htmx4') {
                            bump_htmx4_extensions($new_ver);
                            echo "🔌 Kept htmx4 extensions synced to $new_ver\n";
                        }
                    }
                } else {
                    echo "✅ $lib is already at latest ($current_ver)\n";
                }
            }
        }

        // When updating all, also check and bump htmx 2.x extensions
        if ($target_library === 'all' && isset($cdn_urls['htmx_extensions'])) {
            echo "\n🔌 Checking htmx 2.x extensions on npm...\n";
            $ext_bumped = 0;
            foreach ($cdn_urls['htmx_extensions'] as $ext_name => $config) {
                $pkg = "htmx-ext-{$ext_name}";
                $pkg_data = fetch_json("https://registry.npmjs.org/{$pkg}");
                if ($pkg_data && !empty($pkg_data['dist-tags']['latest'])) {
                    $latest_ext_ver = $pkg_data['dist-tags']['latest'];
                    $current_ext_ver = $config['version'] ?? '0.0.0';
                    if (version_compare($current_ext_ver, $latest_ext_ver, '<')) {
                        echo "⬆️  Bumping extension $ext_name: $current_ext_ver -> $latest_ext_ver\n";
                        if (bump_main_php_extension_version($ext_name, $latest_ext_ver)) {
                            $ext_bumped++;
                            $bumped++;
                        }
                    }
                }
            }
            if ($ext_bumped === 0) {
                echo "✅ All htmx 2.x extensions are already at latest\n";
            }
        }

        if ($bumped > 0) {
            echo "\n📝 Updated $bumped library/extension version(s) in src/Main.php\n";
        }
    }

    if ($target_library) {
        echo "🎯 Target: " . ($target_library === 'all' ? 'All libraries' : $target_library) . "\n";
    } else {
        echo "🎯 Target: All libraries (default)\n";
    }

    download_libraries($target_library);
}
