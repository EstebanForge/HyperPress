<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pins the separator handling in hb_path_within_base().
 *
 * The containment check must compare normalized paths: realpath() returns
 * backslash separators on Windows, so a check that appends '/' to the raw
 * base never prefix-matches there and rejects every legitimate file:
 * template. The trailing-separator anchor must survive normalization so a
 * sibling directory whose name shares a prefix ("blocks" vs "blocks-evil")
 * is still rejected on every platform.
 */
final class PathWithinBaseTest extends TestCase
{
    public function testAcceptsExactBaseDirectory(): void
    {
        $this->assertTrue(hb_path_within_base('/srv/app/blocks', '/srv/app/blocks'));
    }

    public function testAcceptsFileInsideBase(): void
    {
        $this->assertTrue(hb_path_within_base('/srv/app/blocks/tpl.php', '/srv/app/blocks'));
    }

    public function testAcceptsWindowsStyleFileInsideBase(): void
    {
        // The Windows failure mode: realpath() output uses backslashes while
        // the containment anchor used to be built with a forward slash.
        $this->assertTrue(
            hb_path_within_base('C:\\www\\site\\blocks\\tpl.php', 'C:\\www\\site\\blocks'),
            'Windows-style realpath output must match its base directory'
        );
    }

    public function testRejectsSiblingDirectoryWithSharedPrefix(): void
    {
        $this->assertFalse(hb_path_within_base('/var/www/blocks-evil/x.php', '/var/www/blocks'));
    }

    public function testRejectsWindowsStyleSiblingDirectoryWithSharedPrefix(): void
    {
        $this->assertFalse(
            hb_path_within_base('C:\\www\\site\\blocks-evil\\x.php', 'C:\\www\\site\\blocks')
        );
    }

    public function testRejectsUnrelatedPath(): void
    {
        $this->assertFalse(hb_path_within_base('/srv/other/tpl.php', '/srv/app/blocks'));
    }

    public function testAcceptsMixedSeparatorsInsideBase(): void
    {
        // Registrations often arrive with mixed separators on Windows
        // (plugin_dir_path() backslashes + author-typed forward slashes).
        $this->assertTrue(
            hb_path_within_base('C:\\www\\site\\blocks/sub/tpl.php', 'C:\\www\\site\\blocks')
        );
    }

    public function testAcceptsLowercaseDriveLetterMismatch(): void
    {
        // wp_normalize_path() ucfirst()s the drive letter; both sides of the
        // comparison go through it, so a lowercase-drive realpath output
        // must still match an uppercase-drive base.
        $this->assertTrue(
            hb_path_within_base('c:/www/site/blocks/tpl.php', 'C:\\www\\site\\blocks')
        );
    }

    public function testCollapsesDoubledSeparators(): void
    {
        // Real wp_normalize_path() collapses redundant slashes; the helper
        // must not let a doubled separator break the containment match.
        $this->assertTrue(
            hb_path_within_base('C:\\www\\site\\blocks//tpl.php', 'C:/www/site/blocks')
        );
    }
}
