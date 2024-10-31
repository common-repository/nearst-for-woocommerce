<?php

namespace Nearst;

/**
 * Class Cron
 * @package Nearst
 */
class Cron
{
	/**
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Cron constructor.
	 * @param Plugin $plugin
	 */
	public function __construct($plugin)
	{
		$this->plugin = $plugin;

		// Add cron schedule type
		add_filter('cron_schedules', function ($schedules) {
			$schedules['15m'] = [
				'interval' => 15 * 60,
				'display' => 'Every 15 minutes'
			];
			return $schedules;
		});

		// Add cron handler
		add_action('nearst_regular_upload', function () {
			if (!get_option('nearst_upload_key')) {
				return;
			}

			FTP::upload();
		});

		// Enable cron
		$this->enable();
	}

	public function enable()
	{
		if (!wp_next_scheduled('nearst_regular_upload')) {
			wp_schedule_event(time(), '15m', 'nearst_regular_upload');
		}
	}
}
