<?php

/**
 * Moved to src/deprecated.php.
 *
 * Transparent redirect for any stale require_once of the legacy
 * includes/deprecated.php path. The real implementation lives under src/.
 * Safe to delete once no consumer references the includes/ path.
 */

require_once __DIR__ . '/../src/deprecated.php';
