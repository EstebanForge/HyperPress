<?php

namespace HyperFields\Tests;

use WP_Mock;

class WordPressTestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    protected function mockWordPressFunctions(): void
    {
        // Mock common WordPress functions
        WP_Mock::userFunction('__', [
            'return' => function ($text, $domain) {
                return $text; // Return the original text for simplicity
            },
        ]);

        WP_Mock::userFunction('esc_html', [
            'return' => function ($text) {
                return htmlspecialchars($text); // Basic HTML escaping
            },
        ]);

        WP_Mock::userFunction('esc_attr', [
            'return' => function ($text) {
                return htmlspecialchars($text, ENT_QUOTES); // Attribute escaping
            },
        ]);

        WP_Mock::userFunction('wp_json_encode', [
            'return' => function ($data) {
                return json_encode($data);
            },
        ]);

        // Add more mocks as needed for your tests
    }
}
