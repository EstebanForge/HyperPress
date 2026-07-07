<?php

declare(strict_types=1);

namespace HyperBlocks\Tests\Unit;

use HyperBlocks\Config;
use HyperBlocks\Renderer;
use PHPUnit\Framework\TestCase;

/**
 * Pins the path-containment check in Renderer::validateTemplatePath().
 *
 * The validator must confirm a resolved template path is truly INSIDE an
 * allowed base directory. A naive str_starts_with($real, $base) without a
 * trailing-separator check treats a sibling directory whose name shares a
 * prefix (e.g. /var/www/blocks vs /var/www/blocks-evil) as "inside", letting
 * an absolute file: path escape into an unregistered sibling. These tests
 * prove that escape is rejected and that legitimate resolution still works.
 */
class RendererTemplatePathSecurityTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        Config::reset();
        $this->tmpRoot = rtrim(sys_get_temp_dir(), '/\\') . '/hb-render-sec-' . uniqid('', true);
        mkdir($this->tmpRoot, 0777, true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->rmrf($this->tmpRoot);
        Config::reset();
        parent::tearDown();
    }

    /**
     * A file: template whose realpath shares a string prefix with a registered
     * base (but lives in a SIBLING directory, not inside it) must be rejected.
     *
     * Vector: an absolute file: path skips Renderer's relative-resolution
     * loop and lands directly in the prefix check. Before the
     * trailing-separator fix, str_starts_with('/tmp/.../secure-evil/x.php',
     * '/tmp/.../secure') returns true and the evil file renders. After the
     * fix, the check requires the base plus a separator and the path is
     * rejected as outside the allowed directories.
     */
    public function testSiblingDirectoryPrefixAttackIsRejected(): void
    {
        $secure = realpath($this->tmpRoot) . '/secure';
        $evil = realpath($this->tmpRoot) . '/secure-evil';
        mkdir($secure, 0777, true);
        mkdir($evil, 0777, true);

        // Legit base is the "secure" directory.
        Config::registerBlockPath($secure);

        // Evil file lives in the SIBLING "secure-evil" directory (not registered).
        $evilFile = $evil . '/escape.hb.php';
        file_put_contents($evilFile, "<?php echo 'SECURITY_ESCAPE_MARKER';");

        $result = (new Renderer())->render('file:' . $evilFile, []);

        $this->assertStringNotContainsString(
            'SECURITY_ESCAPE_MARKER',
            $result,
            'Template in an unregistered sibling directory must not execute.'
        );
        $this->assertStringContainsString(
            'outside allowed directories',
            $result,
            'The sibling-prefix escape must be rejected as outside the allowed bases.'
        );
    }

    /**
     * Regression guard: legitimate relative file: templates inside a
     * registered base must still resolve and render after the
     * trailing-separator containment fix.
     */
    public function testLegitimateRelativeTemplateStillRenders(): void
    {
        $secure = realpath($this->tmpRoot) . '/secure';
        mkdir($secure, 0777, true);
        Config::registerBlockPath($secure);

        file_put_contents($secure . '/ok.hb.php', '<p class="hb"><?= esc_html($heading ?? "") ?></p>');

        $html = (new Renderer())->render('file:ok.hb.php', ['heading' => 'Hi']);

        $this->assertSame('<p class="hb">Hi</p>', $html);
    }

    /**
     * Recursive best-effort fixture cleanup.
     */
    private function rmrf(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->rmrf($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($path);
    }
}
