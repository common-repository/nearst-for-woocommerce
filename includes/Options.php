<?php

namespace Nearst;

/**
 * Class Options
 * @package Nearst
 */
class Options
{
	/**
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Options constructor.
	 * @param Plugin $plugin
	 */
	public function __construct($plugin)
	{
		$this->plugin = $plugin;

		// Add admin page
		add_action('admin_menu', function () {
			add_menu_page(
				'NearSt', // page title
				'NearSt', // menu title
				'manage_options', // role required to access menu page
				'nearst', // slug
				[$this, 'admin_page_display'], // callback
				Plugin::url('images/menu.svg'),
				58
			);
		});
	}

	public function admin_page_display()
	{
		if (!empty($_GET['nearst_reset'])) {
			delete_option('nearst_upload_key');
			delete_option('nearst_upload_type');
			delete_option('nearst_last_upload');
			delete_option('nearst_last_upload_products');
			delete_option('nearst_last_error');
			delete_option('nearst_product_identifier');
		}

		if (!empty($_POST['upload_type'])) {
			update_option('nearst_upload_type', $_POST['upload_type']);
		}
		if (!empty($_POST['product_identifier'])) {
			update_option('nearst_product_identifier', $_POST['product_identifier']);
		}

		if (!empty($_POST['upload_key'])) {
			$key = $_POST['upload_key'];
			if (!FTP::verify($key)) {
				View::render('notices.setup-error');
			} else {
				update_option('nearst_upload_key', $key);
				$this->plugin->cron->enable();
				FTP::upload(true);
			}
		}

		$upload_key = get_option('nearst_upload_key');

		if ($upload_key && !empty($_GET['nearst_upload'])) {
			FTP::upload(true);
		}

		if ($upload_key && !empty($_GET['nearst_debug'])) {
			echo '<pre>';
			print_r(Products::get());
			exit;
		}

		View::render($upload_key ? 'options.main' : 'options.install', [
			'uploadKey' => $upload_key,
			'memoryIssue' => Products::get_memory_limit() < 500 * 1024 * 1024,
			'lastUpload' => get_option('nearst_last_upload'),
			'stylesUrl' => Plugin::url('styles/index.css'),
			'scriptsUrl' => Plugin::url('scripts/index.js')
		]);
	}
}
