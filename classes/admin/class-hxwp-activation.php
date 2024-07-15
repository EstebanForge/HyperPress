<?php

/**
 * Activation and Deactivation methods
 *
 * @package HXWP
 * @since   2023
 */

namespace HXWP;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Activation and Deactivation Class
 */
class HXWP_Activate_Deactivate
{
	/**
	 * Activation
	 *
	 * @since 2023-11-22
	 * @return void
	 */
	public static function activate()
	{
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Deactivation
	 *
	 * @since 2023-11-22
	 * @return void
	 */
	public static function deactivate()
	{
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
