<?php
/**
 * Plugin Name: NearSt for WooCommerce
 * Description: Helping high street shops sell more locally.
 * Version:     1.2.5
 * Author:      NearSt
 * License:     GPLv2
 * Author URI:  https://near.st/
 * Plugin URI:  http://shopkeeper-support.near.st/en/articles/4242806-connecting-your-woocommerce-shop-to-nearst
 */

define('NEARST_VERSION', '1.2.5');

// Load Composer packages
include_once(trailingslashit(__DIR__) . 'vendor/autoload.php');

// Kick it off
add_action('plugins_loaded', [Nearst\Plugin::instance(), 'hooks']);
register_activation_hook(__FILE__, [Nearst\Plugin::instance(), 'activate']);
