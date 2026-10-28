<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Path;
use PHPUnit\Framework\TestCase;

/**
 * Pins the separator handling in Path::withinBase().
 *
 * The containment check lives on the class (not the hb_path_within_base()
 * procedural helper) so class consumers can never hit an undefined-function
 * fatal when a divergent copy's procedural helpers did not load; classes
 * autoload by name. The behavior matrix mirrors PathWithinBaseTest, which
 * keeps pinning the back-compat wrapper that delegates here.
 */
final class PathClassTest extends TestCase
{
    public function testAcceptsExactBaseDirectory(): void
    {
        $this->assertTrue(Path::withinBase('/srv/app/blocks', '/srv/app/blocks'));
    }

    public function testAcceptsFileInsideBase(): void
    {
        $this->assertTrue(Path::withinBase('/srv/app/blocks/tpl.php', '/srv/app/blocks'));
    }

    public function testAcceptsWindowsStyleFileInsideBase(): void
    {
        // The Windows failure mode: realpath() output uses backslashes while
        // the containment anchor used to be built with a forward slash.
        $this->assertTrue(
            Path::withinBase('C:\\www\\site\\blocks\\tpl.php', 'C:\\www\\site\\blocks'),
            'Windows-style realpath output must match its base directory'
        );
    }

    public function testRejectsSiblingDirectoryWithSharedPrefix(): void
    {
        $this->assertFalse(Path::withinBase('/var/www/blocks-evil/x.php', '/var/www/blocks'));
    }

    public function testRejectsWindowsStyleSiblingDirectoryWithSharedPrefix(): void
    {
        $this->assertFalse(
            Path::withinBase('C:\\www\\site\\blocks-evil\\x.php', 'C:\\www\\site\\blocks')
        );
    }

    public function testRejectsUnrelatedPath(): void
    {
        $this->assertFalse(Path::withinBase('/srv/other/tpl.php', '/srv/app/blocks'));
    }

    public function testAcceptsMixedSeparatorsInsideBase(): void
    {
        // Registrations often arrive with mixed separators on Windows
        // (plugin_dir_path() backslashes + author-typed forward slashes).
        $this->assertTrue(
            Path::withinBase('C:\\www\\site\\blocks/sub/tpl.php', 'C:\\www\\site\\blocks')
        );
    }

    public function testAcceptsLowercaseDriveLetterMismatch(): void
    {
        // wp_normalize_path() ucfirst()s the drive letter; both sides of the
        // comparison go through it, so a lowercase-drive realpath output
        // must still match an uppercase-drive base.
        $this->assertTrue(
            Path::withinBase('c:/www/site/blocks/tpl.php', 'C:\\www\\site\\blocks')
        );
    }

    public function testCollapsesDoubledSeparators(): void
    {
        // Real wp_normalize_path() collapses redundant slashes; the check
        // must not let a doubled separator break the containment match.
        $this->assertTrue(
            Path::withinBase('C:\\www\\site\\blocks//tpl.php', 'C:/www/site/blocks')
        );
    }
}
