<?php

/**
 * Moved to src/helpers.php.
 *
 * This file remains only as a transparent redirect so any stale
 * require_once of the legacy includes/helpers.php path loads the canonical
 * file under src/. The real implementation and its prefix-safe Config
 * references live there. Safe to delete this file once no consumer
 * references the includes/ path.
 */

require_once __DIR__ . '/../src/helpers.php';
