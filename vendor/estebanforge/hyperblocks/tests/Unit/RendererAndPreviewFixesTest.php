<?php

declare(strict_types=1);

use HyperBlocks\Renderer;
use HyperBlocks\RestApi;

/*
 * Finding 3.1: a template that throws \Error (TypeError, ParseError) escapes
 * every catch (\Exception) in Renderer, leaking the temp file, the error
 * handler, and bypassing graceful degradation. After the fix (finally +
 * catch \Throwable), render() returns error HTML instead of fataling.
 */
it('returns error HTML instead of fataling when a template throws a TypeError', function (): void {
    $renderer = new Renderer();

    $level = ob_get_level();
    $html = $renderer->render('<?php throw new \TypeError("template boom"); ?>', []);

    expect($html)->toContain('hyperblocks-error');
    expect(ob_get_level())->toBe($level);
});

it('preserves level attribute in template scope without overwriting', function (): void {
    $renderer = new Renderer();
    $html = $renderer->render('<?php echo "heading-level-" . $level; ?>', ['level' => 3]);

    expect($html)->toBe('heading-level-3');
});

/*
 * Finding 3.2: JSON block preview attributes are sanitized by their declared
 * block.json types before rendering. String attributes with source:html use
 * wp_kses_post (preserves safe markup); nested arrays recurse; numbers and
 * booleans are cast.
 */
it('sanitizes JSON block preview attributes by their declared types', function (): void {
    $api = new RestApi();
    $method = new \ReflectionMethod($api, 'sanitizeJsonBlockAttributes');

    $declared = [
        'heading' => ['type' => 'string'],
        'content' => ['type' => 'string', 'source' => 'html'],
        'count' => ['type' => 'number'],
        'visible' => ['type' => 'boolean'],
        'items' => ['type' => 'array'],
        'nested' => ['type' => 'array'],
    ];

    $result = $method->invoke($api, [
        'heading' => '<b>hello</b>',
        'content' => '<p>safe <script>alert(1)</script> text</p>',
        'count' => '42',
        'visible' => 1,
        'items' => ['<b>one</b>', '<i>two</i>'],
        'nested' => [['<b>x</b>'], ['<i>y</i>']],
    ], $declared);

    // Plain string: tags stripped.
    expect($result['heading'])->toBe('hello');
    // HTML source: wp_kses_post keeps safe tags, strips script.
    expect($result['content'])->toContain('<p>');
    expect($result['content'])->toContain('safe');
    expect($result['content'])->not->toContain('<script>');
    // Number: cast.
    expect($result['count'])->toBe(42);
    // Boolean: cast.
    expect($result['visible'])->toBeTrue();
    // Nested array: recursively sanitized (no TypeError).
    expect($result['items'])->toBe(['one', 'two']);
    // Multi-level nested array: recursion verified (flat array_map would fail this).
    expect($result['nested'])->toBe([['x'], ['y']]);
});
