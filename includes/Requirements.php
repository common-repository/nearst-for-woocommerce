<?php

namespace Nearst;

/**
 * Class Requirements
 * @package Nearst
 */
class Requirements
{

	/**
	 * Do a requirements check. If it fails, we'll disable the plugin and show a message to the admin.
	 *
	 * @return bool
	 */
	public static function check()
	{
		$requirements = [];

		// Check if WooCommerce is installed & activated
		if (!defined('WC_VERSION')) {
			$requirements[] = 'You need WooCommerce installed and activated to use this plugin.';
		}

		// Check if the FTP extension is installed
		if (!function_exists('ftp_put')) {
			$requirements[] = 'The PHP FTP extension is required to upload stock to NearSt.';
		}


		if (!empty($requirements)) {
			add_action('all_admin_notices', function () use ($requirements) {
				View::render('notices.requirements', [
					'requirements' => $requirements,
					'plugins_page' => admin_url('plugins.php')
				]);
			});

			return false;
		}

		// Didn't meet the requirements
		return true;
	}

}
