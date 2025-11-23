<?php

declare(strict_types=1);

// Modern PHP 8.3+ script to bump plugin version across multiple files
// Usage: php .ci/version-bump.php <new-version>

$files = [
    'package.json',
    'composer.json',
    'README.txt',
    'api-for-htmx.php',
    'SECURITY.md',
];

$pluginDir = __DIR__ . '/../';

function getCurrentVersion(string $file): ?string
{
    $content = file_get_contents($file);
    if (preg_match('/"version"\s*:\s*"([0-9]+\.[0-9]+\.[0-9]+)"/', $content, $m)) {
        return $m[1];
    }
    if (preg_match('/Stable tag:\s*([0-9]+\.[0-9]+\.[0-9]+)/', $content, $m)) {
        return $m[1];
    }
    if (preg_match('/Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/', $content, $m)) {
        return $m[1];
    }
    if (preg_match('/\|\s*([0-9]+\.[0-9]+\.[0-9]+)\s*\|/', $content, $m)) {
        return $m[1];
    }

    return null;
}

function bumpVersion(string $oldVersion, string $newVersion, string $file): void
{
    $content = file_get_contents($file);
    $content = preg_replace('/' . preg_quote($oldVersion, '/') . '(?!\d)/', $newVersion, $content);
    file_put_contents($file, $content);
}

// Find current version from any file
$currentVersion = null;
foreach ($files as $file) {
    $path = $pluginDir . $file;
    if (file_exists($path)) {
        $currentVersion = getCurrentVersion($path);
        if ($currentVersion) {
            break;
        }
    }
}
if (!$currentVersion) {
    fwrite(STDERR, "Could not determine current version.\n");
    exit(1);
}

if ($argc < 2) {
    echo "Current version: $currentVersion\n";
    echo "Enter new version: ";
    $input = fgets(STDIN);

    if ($input === false) {
        fwrite(STDERR, "Failed to read input.\n");
        exit(1);
    }

    $newVersion = trim($input);

    if (empty($newVersion)) {
        fwrite(STDERR, "No version provided.\n");
        exit(1);
    }

    if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $newVersion)) {
        fwrite(STDERR, "Invalid version format. Use semantic versioning (e.g., 1.2.3).\n");
        exit(1);
    }
} else {
    $newVersion = $argv[1];
}

foreach ($files as $file) {
    $path = $pluginDir . $file;
    if (file_exists($path)) {
        bumpVersion($currentVersion, $newVersion, $path);
        echo "Updated $file\n";
    }
}

echo "Bumped from $currentVersion to $newVersion\n";
