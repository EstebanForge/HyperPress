<?php

declare(strict_types=1);

namespace HyperPress\Tests\Unit;

use Brain\Monkey\Functions;
use HyperPress\Render;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the opt-in strict template mode (Render strict-mode gate).
 *
 * Strict mode is OFF by default: nothing changes for existing sites.
 * When ON, a template renders only if explicitly registered:
 *  - namespaced templates need their namespace registered via
 *    'hyperpress/render/register_template_path';
 *  - non-namespaced templates must be listed in
 *    'hyperpress/render/registered_templates'.
 *
 * The test bootstrap defines apply_filters() as a passthrough before
 * Brain Monkey loads (same constraint documented in OptionsResolverTest),
 * so filter interception is not possible. The decision logic is therefore
 * extracted into the pure Render::templateMatchesRegistration() and tested
 * directly; the filter-wiring contract is covered via the passthrough
 * defaults (no filters registered = strict off, unregistered = refused).
 */
class RenderStrictModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // sanitizePath() dependencies for the gate-feed tests below (same
        // stubbing approach as RenderSanitizationTest). apply_filters() is a
        // passthrough from tests/preload.php and cannot be intercepted.
        Functions\when('sanitize_key')->alias(static function ($key): string {
            $key = strtolower((string) $key);

            return preg_replace('/[^a-z0-9_-]/', '', $key);
        });
        Functions\when('remove_accents')->alias(static function ($string): string {
            return (string) $string;
        });
        Functions\when('sanitize_file_name')->alias(static function ($name): string {
            $name = preg_replace('/[\s]+/', '-', (string) $name);

            return preg_replace('/[^A-Za-z0-9._-]/', '', $name);
        });
    }

    /**
     * Invoke protected isStrictMode() on a Render instance.
     */
    private function isStrictMode(): bool
    {
        $render = new Render();
        $method = new \ReflectionMethod($render, 'isStrictMode');

        return (bool) $method->invoke($render);
    }

    /**
     * Invoke protected isTemplateAllowed() on a Render instance. Under the
     * passthrough apply_filters() both registration filters return [].
     */
    private function isTemplateAllowedWithDefaults(string $templateName): bool
    {
        $render = new Render();
        $method = new \ReflectionMethod($render, 'isTemplateAllowed');

        return (bool) $method->invoke($render, $templateName);
    }

    /**
     * Invoke the private sanitizePath() (the transform every request template
     * name goes through BEFORE the strict gate sees it).
     */
    private function sanitizePath(string $raw)
    {
        $render = new Render();
        $method = new \ReflectionMethod($render, 'sanitizePath');

        return $method->invoke($render, $raw);
    }

    /**
     * CONTRACT: strict mode must default to off. With no filter registered,
     * the passthrough returns the default false.
     */
    public function testStrictModeDefaultsToOff(): void
    {
        $this->assertFalse($this->isStrictMode());
    }

    /**
     * CONTRACT: with no registration filters registered (passthrough []),
     * no template is allowed — enabling strict mode without registering
     * anything refuses everything, which is the safe direction.
     */
    public function testNoRegistrationRefusesEverything(): void
    {
        $this->assertFalse($this->isTemplateAllowedWithDefaults('demo/swap'));
        $this->assertFalse($this->isTemplateAllowedWithDefaults('myplugin:header'));
    }

    /**
     * STRICT ON, non-namespaced template listed in the allowlist: allowed.
     */
    public function testListedTemplateIsAllowed(): void
    {
        $this->assertTrue(
            Render::templateMatchesRegistration('demo/swap', [], ['demo/swap', 'noswap/header-update'])
        );
    }

    /**
     * STRICT ON, non-namespaced template NOT listed: refused. This is the
     * fail-open hole strict mode closes: today any .hp.php shipped in the
     * theme's hypermedia directory is loadable by anyone who can guess its
     * name.
     */
    public function testUnlistedThemeTemplateIsRefused(): void
    {
        $this->assertFalse(
            Render::templateMatchesRegistration('admin/secret-panel', [], ['demo/swap'])
        );
    }

    /**
     * STRICT ON, namespaced template with a REGISTERED namespace: allowed.
     * Registering the namespace is itself the explicit opt-in.
     */
    public function testRegisteredNamespaceTemplateIsAllowed(): void
    {
        $this->assertTrue(
            Render::templateMatchesRegistration('myplugin:header', ['myplugin' => '/path/hypermedia/'], [])
        );
    }

    /**
     * STRICT ON, namespaced template with an UNREGISTERED namespace: refused.
     */
    public function testUnregisteredNamespaceTemplateIsRefused(): void
    {
        $this->assertFalse(
            Render::templateMatchesRegistration('otherplugin:header', ['myplugin' => '/path/hypermedia/'], [])
        );
    }

    /**
     * The allowlist must match exactly, not by prefix: a listed template
     * must not unlock sibling paths under the same directory prefix, and
     * sanitized-away traversal payloads must not match either.
     */
    public function testAllowlistDoesNotMatchByPrefixOrTraversal(): void
    {
        $this->assertFalse(
            Render::templateMatchesRegistration('demo/other', [], ['demo/swap'])
        );
        $this->assertFalse(
            Render::templateMatchesRegistration('demo/swap/../secret', [], ['demo/swap'])
        );
    }

    /**
     * An empty template name never matches any registration.
     */
    public function testEmptyTemplateNameIsRefused(): void
    {
        $this->assertFalse(Render::templateMatchesRegistration('', ['ns' => '/x/'], ['a', '']));
    }

    /**
     * Multi-colon names take the FIRST segment as the namespace (explode
     * limit 2), matching the resolver's parse: 'a:b:c' = namespace 'a',
     * template 'b:c'. Only namespace 'a' is checked.
     */
    public function testMultiColonTemplateUsesFirstSegmentAsNamespace(): void
    {
        $this->assertTrue(
            Render::templateMatchesRegistration('a:b:c', ['a' => '/x/'], [])
        );
        $this->assertFalse(
            Render::templateMatchesRegistration('a:b:c', ['b' => '/x/', 'c' => '/y/'], [])
        );
    }

    /**
     * A namespace registered with a NULL path must be REFUSED by the gate:
     * the resolver (getTemplateFile) uses isset(), so a null-path namespace
     * cannot render — the gate must not claim it can. Pins the isset vs
     * array_key_exists divergence caught in peer review.
     */
    public function testNullPathNamespaceIsRefused(): void
    {
        $this->assertFalse(
            Render::templateMatchesRegistration('ns:header', ['ns' => null], [])
        );
    }

    /**
     * GATE WIRING: when strict mode refuses, renderOrFail() must dispatch
     * the 'template-not-registered' error page and never attempt resolution.
     * renderOrFail()/showDeveloperInfoPage() die in production; a test
     * subclass intercepts both decision points (same seam style as
     * RenderNonceTest's reflection approach, but overridable methods so
     * control flow itself is asserted).
     */
    public function testStrictRefusalStopsBeforeResolution(): void
    {
        $render = new class () extends Render {
            public bool $strict = true;
            public array $allowed = [];
            /** @var array<int, array{0: string, 1: string}> */
            public array $pages = [];
            public bool $resolved = false;

            protected function isStrictMode(): bool
            {
                return $this->strict;
            }

            protected function isTemplateAllowed(string $template_name): bool
            {
                return in_array($template_name, $this->allowed, true);
            }

            protected function getTemplateFile($templateName = '')
            {
                $this->resolved = true;

                return false;
            }

            protected function showDeveloperInfoPage($error_type = 'endpoint-info', $template_name = '', $template_path = ''): void
            {
                $this->pages[] = [$error_type, $template_name];
            }

            public function runRenderOrFail(string $templateName, $hpVals = false): void
            {
                $this->renderOrFail($templateName, $hpVals);
            }
        };

        $render->runRenderOrFail('admin/secret', false);

        $this->assertSame([['template-not-registered', 'admin/secret']], $render->pages);
        $this->assertFalse($render->resolved, 'refused template must not reach template resolution');
    }

    /**
     * GATE WIRING: when strict mode allows, resolution proceeds (here it
     * finds nothing → 'invalid-route'), proving the gate did not fire.
     */
    public function testStrictAllowedProceedsToResolution(): void
    {
        $render = new class () extends Render {
            public array $pages = [];
            public bool $resolved = false;

            protected function isStrictMode(): bool
            {
                return true;
            }

            protected function isTemplateAllowed(string $template_name): bool
            {
                return true;
            }

            protected function getTemplateFile($templateName = '')
            {
                $this->resolved = true;

                return false;
            }

            protected function showDeveloperInfoPage($error_type = 'endpoint-info', $template_name = '', $template_path = ''): void
            {
                $this->pages[] = [$error_type, $template_name];
            }

            public function runRenderOrFail(string $templateName, $hpVals = false): void
            {
                $this->renderOrFail($templateName, $hpVals);
            }
        };

        $render->runRenderOrFail('demo/swap', false);

        $this->assertSame([['invalid-route', 'demo/swap']], $render->pages);
        $this->assertTrue($render->resolved, 'allowed template must reach template resolution');
    }

    /**
     * BACKWARD COMPAT (the core no-regression contract): with strict mode
     * OFF — the shipped default — an UNREGISTERED template must still
     * proceed to normal resolution. If this test ever fails, the update
     * broke existing sites: their templates load without registration.
     */
    public function testStrictOffRendersUnregisteredTemplate(): void
    {
        $render = new class () extends Render {
            public array $pages = [];
            public bool $resolved = false;

            protected function isStrictMode(): bool
            {
                return false; // default
            }

            // Real gate behavior under defaults: nothing registered.
            protected function isTemplateAllowed(string $template_name): bool
            {
                return parent::isTemplateAllowed($template_name);
            }

            protected function getTemplateFile($templateName = '')
            {
                $this->resolved = true;

                return false;
            }

            protected function showDeveloperInfoPage($error_type = 'endpoint-info', $template_name = '', $template_path = ''): void
            {
                $this->pages[] = [$error_type, $template_name];
            }

            public function runRenderOrFail(string $templateName, $hpVals = false): void
            {
                $this->renderOrFail($templateName, $hpVals);
            }
        };

        $render->runRenderOrFail('totally/unregistered-template', false);

        $this->assertTrue($render->resolved, 'strict off: unregistered template must still resolve');
        $this->assertNotContains(
            ['template-not-registered', 'totally/unregistered-template'],
            $render->pages,
            'strict off must never produce the template-not-registered page'
        );
    }

    /**
     * GATE FEED: the name the gate compares is sanitizePath()'s output, not
     * the raw request. A traversal-crafted request must sanitize to a name
     * that does NOT equal the allowlisted entry, so the second defense layer
     * (exact match) holds even though sanitizePath already stripped '..'.
     */
    public function testTraversalSanitizesToNonAllowlistedName(): void
    {
        $sanitized = $this->sanitizePath('demo/swap/../secret');

        $this->assertNotSame('demo/swap', $sanitized);
        $this->assertSame('demo/swap/secret', $sanitized);
        $this->assertFalse(
            Render::templateMatchesRegistration((string) $sanitized, [], ['demo/swap']),
            'sanitized traversal output must not match the allowlisted entry'
        );
    }

    /**
     * GATE FEED (docs contract): directory segments normalize to lowercase,
     * the filename segment keeps its case. Pins the docs/security.md
     * allowlist guidance so a change in sanitizePath that alters casing
     * semantics cannot silently invalidate documented allowlist entries.
     */
    public function testSanitizePathLowercasesDirsPreservesFilenameCase(): void
    {
        $this->assertSame('demo/Swap', $this->sanitizePath('Demo/Swap'));
    }
}
