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

describe('hyperpress_adapter_should_record_review_milestone', function (): void {
    it('records on a wp-html route when not already recorded', function (): void {
        expect(hyperpress_adapter_should_record_review_milestone(false, '/wp-html/v1/render'))->toBeTrue();
    });

    it('does not record when already recorded', function (): void {
        expect(hyperpress_adapter_should_record_review_milestone(true, '/wp-html/v1/render'))->toBeFalse();
    });

    it('ignores non-wp-html routes', function (): void {
        expect(hyperpress_adapter_should_record_review_milestone(false, '/wp-json/wp/v2/posts'))->toBeFalse();
    });

    it('ignores a null route', function (): void {
        expect(hyperpress_adapter_should_record_review_milestone(false, null))->toBeFalse();
    });
});

describe('hyperpress_adapter_should_show_review_notice', function (): void {
    $show = static fn (... $a): bool => hyperpress_adapter_should_show_review_notice(...$a);
    $record = ['at' => 1];

    it('shows on a HyperPress settings screen when all conditions are met', function () use ($show, $record): void {
        expect($show($record, true, 5, 0, 100, 'toplevel_page_hyperpress'))->toBeTrue();
    });

    it('shows on the plugins screen', function () use ($show, $record): void {
        expect($show($record, true, 5, 0, 100, 'plugins'))->toBeTrue();
    });

    it('does not show when there is no milestone record', function () use ($show): void {
        expect($show(false, true, 5, 0, 100, 'plugins'))->toBeFalse();
    });

    it('does not show when the record is not an array', function () use ($show): void {
        expect($show('not-an-array', true, 5, 0, 100, 'plugins'))->toBeFalse();
    });

    it('does not show when the user cannot manage options', function () use ($show, $record): void {
        expect($show($record, false, 5, 0, 100, 'plugins'))->toBeFalse();
    });

    it('does not show for an anonymous user', function () use ($show, $record): void {
        expect($show($record, true, 0, 0, 100, 'plugins'))->toBeFalse();
    });

    it('does not show while snoozed', function () use ($show, $record): void {
        expect($show($record, true, 5, 200, 100, 'plugins'))->toBeFalse();
    });

    it('shows again after the snooze window passes', function () use ($show, $record): void {
        expect($show($record, true, 5, 200, 250, 'plugins'))->toBeTrue();
    });

    it('does not show on an unrelated screen', function () use ($show, $record): void {
        expect($show($record, true, 5, 0, 100, 'edit-post'))->toBeFalse();
    });

    it('does not show without a screen', function () use ($show, $record): void {
        expect($show($record, true, 5, 0, 100, null))->toBeFalse();
    });
});
