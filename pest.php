<?php

declare(strict_types=1);

use Pest\Plugin\Plugins;

Plugins::usePHPUnit();

/*
|--------------------------------------------------------------------------
| Testsuite Configuration
|--------------------------------------------------------------------------
*/

$testSuites = [
    'Unit' => 'tests/Unit',
    'Integration' => 'tests/Integration',
    'Feature' => 'tests/Feature',
];

foreach ($testSuites as $name => $path) {
    if (is_dir($path)) {
        testSuite($name)->in($path);
    }
}

/*
|--------------------------------------------------------------------------
| Coverage Configuration
|--------------------------------------------------------------------------
*/

$coveragePaths = [
    'src' => true,
    'src/Blocks' => false,
    'src/Fields/templates' => false,
];

foreach ($coveragePaths as $path => $include) {
    if ($include) {
        cover($path);
    } else {
        cover()->exclude($path);
    }
}

// Exclude bootstrap file from coverage
cover()->exclude('bootstrap.php');

/*
|--------------------------------------------------------------------------
| Test Options
|--------------------------------------------------------------------------
*/

// Don't stop on failure
stopOnFailure(false);

// Don't fail on risky tests
failOnRisky(false);

// Don't fail on warnings
failOnWarning(false);
