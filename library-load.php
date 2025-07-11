<?php

declare(strict_types=1);

/**
 * Plugin Name: Hypermedia API for WordPress (Library Loader)
 * Description: Safe loader for embedding the Hypermedia API as a library.
 * Version: 2.0.0
 * Author: Esteban Cuevas
 */

// This file is intended to be used by developers who are embedding this plugin as a library in their own themes or plugins.
// It ensures that the main plugin file is loaded only once, even if multiple plugins are using it as a dependency.

// Load the shared bootstrap file.
require_once __DIR__ . '/bootstrap.php';
