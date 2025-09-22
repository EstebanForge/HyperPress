<?php

use HyperPress\Tests\WordPressTestCase;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| Here you may define all of your pest configuration. Each option is 
| documented so feel free to look through what's available.
|
*/

uses(WordPressTestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet 
| certain conditions. The "expect()" function gives you access to a set 
| of "expectation" methods that you can use to assert different 
| conditions.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing 
| code specific to your project that you don't want to repeat in every 
| file. Here you can also expose helpers as globals to make them 
| available in your tests.
|
*/

function something()
{
    // ..
}