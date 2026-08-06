<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/adapter-functions.php';

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Finding 1.1: the review-milestone option is non-autoloaded, so reading it on
 * every REST dispatch issues an extra get_option() SELECT. The record path
 * must gate on the route BEFORE touching the option, so non-/wp-html/ REST
 * traffic never triggers the read.
 *
 * The real production function (hyperpress_adapter_maybe_record_review_milestone,
 * used by the rest_pre_dispatch closure) is exercised directly with Brain
 * Monkey option stubs.
 */
final class ReviewMilestoneRecordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $GLOBALS['__hp_get_option_calls'] = [];
        $GLOBALS['__hp_test_options'] = [];

        Functions\when('get_option')->alias(function ($name, $default = false) {
            $GLOBALS['__hp_get_option_calls'][] = $name;

            return $GLOBALS['__hp_test_options'][$name] ?? $default;
        });
        Functions\when('update_option')->alias(function ($name, $value, $autoload = true): bool {
            $GLOBALS['__hp_test_options'][$name] = $value;

            return true;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testDoesNotReadMilestoneOptionForNonHypermediaRoute(): void
    {
        hyperpress_adapter_maybe_record_review_milestone('/wp-json/wp/v2/posts');

        $this->assertNotContains(
            'hyperpress_review_milestone',
            $GLOBALS['__hp_get_option_calls'],
            'non-hypermedia REST routes must not read the milestone option'
        );
    }

    public function testDoesNotReadMilestoneOptionForNullRoute(): void
    {
        hyperpress_adapter_maybe_record_review_milestone(null);

        $this->assertNotContains('hyperpress_review_milestone', $GLOBALS['__hp_get_option_calls']);
    }

    public function testReadsAndRecordsMilestoneForHypermediaRoute(): void
    {
        hyperpress_adapter_maybe_record_review_milestone('/wp-html/v1/render');

        $this->assertContains('hyperpress_review_milestone', $GLOBALS['__hp_get_option_calls']);
        $this->assertArrayHasKey('hyperpress_review_milestone', $GLOBALS['__hp_test_options']);
    }

    public function testDoesNotRecordTwiceOnceMilestoneExists(): void
    {
        $GLOBALS['__hp_test_options']['hyperpress_review_milestone'] = ['at' => 1];

        hyperpress_adapter_maybe_record_review_milestone('/wp-html/v1/render');

        $this->assertContains('hyperpress_review_milestone', $GLOBALS['__hp_get_option_calls']);
        $this->assertSame(['at' => 1], $GLOBALS['__hp_test_options']['hyperpress_review_milestone']);
    }
}
