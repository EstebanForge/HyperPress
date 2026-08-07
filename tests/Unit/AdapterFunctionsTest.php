<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/adapter-functions.php';

/*
 * Direct unit tests for the pure logic extracted from bootstrap.php. No Brain
 * Monkey, no WordPress, no adapter load: each function is a pure transform or
 * predicate, so we assert inputs and outputs directly. These pin the behavior
 * the closures in bootstrap.php rely on.
 */

describe('hyperpress_adapter_insert_library_versions', function (): void {
    it('inserts versions immediately after the target row', function (): void {
        $result = hyperpress_adapter_insert_library_versions(
            ['Plugin Version' => '3.5.7', 'PHP Version' => '8.3'],
            ['HyperFields Library' => '1.5.1', 'HyperBlocks Library' => '1.5.0'],
            'Plugin Version'
        );

        expect(array_keys($result))->toBe(['Plugin Version', 'HyperFields Library', 'HyperBlocks Library', 'PHP Version']);
    });

    it('appends at the end when the target row is absent', function (): void {
        $result = hyperpress_adapter_insert_library_versions(
            ['Some Other Row' => 'x'],
            ['Lib' => '1.0'],
            'Plugin Version'
        );

        expect(array_keys($result))->toBe(['Some Other Row', 'Lib']);
    });

    it('returns the input unchanged when there are no versions to insert', function (): void {
        $info = ['A' => 1, 'B' => 2];

        expect(hyperpress_adapter_insert_library_versions($info, [], 'A'))->toBe($info);
    });
});
