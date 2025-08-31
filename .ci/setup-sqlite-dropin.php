<?php
declare(strict_types=1);

$base = __DIR__ . '/..';
$vendorWpContent = $base . '/vendor/wp-phpunit/wp-phpunit/wordpress/wp-content';
$vendorDropin = $base . '/vendor/aaemnnosttv/wp-sqlite-db/db.php';
$altDropin = $base . '/wp-content/wp-sqlite-db/src/db.php'; // optional fallback if present locally

@mkdir($vendorWpContent, 0777, true);
@mkdir($vendorWpContent . '/database', 0777, true);

$src = is_file($vendorDropin) ? $vendorDropin : (is_file($altDropin) ? $altDropin : '');
if ($src) {
    @copy($src, $vendorWpContent . '/db.php');
    fwrite(STDOUT, "SQLite drop-in installed to vendor wp-content.\n");
} else {
    fwrite(STDERR, "Warning: SQLite drop-in source not found. Ensure aaemnnosttv/wp-sqlite-db is installed.\n");
}
