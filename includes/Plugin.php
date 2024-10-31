<?php

namespace Nearst;

/**
 * Class Plugin
 * @package Nearst
 */
class Plugin
{

	/**
	 * @var string
	 */
	public $path;

	/**
	 * @var string
	 */
	public $basename;

	/**
	 * @var Options
	 */
	public $options;

	/**
	 * @var Cron
	 */
	public $cron;

	/**
	 * Sets up our plugin.
	 */
	protected function __construct()
	{
		$plugin_file = trailingslashit(dirname(__DIR__)) . 'nearst-for-woocommerce.php';
		$this->basename = plugin_basename($plugin_file);
		$this->path = plugin_dir_path($plugin_file);

		if (is_admin()) {
			// Redirect to activation screen
			if (get_option('nearst_activated')) {
				delete_option('nearst_activated');
				header('Location: ' . admin_url('admin.php?page=nearst&welcome=1'));
			}

			// Enable auto updates (WP >=5.5)
			if (!get_option('nearst_auto_update_enabled')) {
				$autoupdate_plugins = (array)get_site_option('auto_update_plugins', []);
				$autoupdate_plugins = array_unique(array_merge($autoupdate_plugins, ['nearst-for-woocommerce/nearst-for-woocommerce.php']));
				update_site_option('auto_update_plugins', $autoupdate_plugins);
				update_option('nearst_auto_update_enabled', '1');
			}
		}
	}

	public static function activate()
	{
		update_option('nearst_activated', '1');
	}

	/**
	 * Add hooks and filters.
	 */
	public function hooks()
	{
		add_action('init', [$this, 'init'], 0);
		add_action('wp_head', [$this, 'metadata'], 9);
		add_action('template_redirect', [$this, 'redirect'], 0);
	}

	/**
	 * Init hooks
	 *
	 * @since  1.0.0
	 */
	public function init()
	{
		// Bail early if requirements aren't met
		if (!Requirements::check()) {
			return;
		}

		// Initialize plugin classes
		$this->options = new Options($this);
		$this->cron = new Cron($this);
	}

	/**
	 * Output metadata.
	 */
	public function metadata()
	{
		echo '<meta name="nearst" value="' . NEARST_VERSION . '" />';
	}

	/**
	 * Call redirect hook.
	 */
	public function redirect()
	{
		Redirect::redirect();
	}

	/**
	 * Creates or returns an instance of this class.
	 * @return Plugin A single instance of this class.
	 */
	public static function instance()
	{
		static $instance;
		if (!$instance) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Get an asset URL.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function url($path = '')
	{
		$plugin_file = trailingslashit(dirname(__DIR__)) . 'nearst-for-woocommerce.php';
		return trailingslashit(plugin_dir_url($plugin_file)) . 'assets/' . $path;
	}

}
