<?php

namespace Nearst;

use WP_Query;

/**
 * Class Redirect
 * @package Nearst
 */
class Redirect
{

	/**
	 * Runs on the 'template_redirect' hook, used to redirect requests
	 * that contain a nearst_barcode URL parameter to the appropriate
	 * product page.
	 */
	public static function redirect()
	{
		if (empty($_GET['nearst_barcode'])) {
			return;
		}

		$barcode = trim($_GET['nearst_barcode']);

		$args = [
			'post_type' => 'product',
			'meta_query' => [
				'relation' => 'or',
				[
					'key' => '_sku',
					'value' => $barcode,
					'compare' => 'LIKE',
				],
				[
					'key' => '_product_attributes',
					'value' => $barcode,
					'compare' => 'LIKE',
				],
			],
		];
		$query = new WP_Query($args);

		foreach ($query->get_posts() as $product) {
			$product = wc_get_product($product);
			if (trim(strtolower($product->get_sku())) == $barcode || trim(strtolower($product->get_attribute('UPC|EAN'))) == $barcode) {
				$url = $product->get_permalink();
				$url .= (strstr($url, '?') ? '&' : '?') . 'utm_source=nearst';
				if (!empty($_GET['nearst_location'])) {
					$url .= '&nearst_location=' . urlencode($_GET['nearst_location']);
				}
				if (!empty($_GET['nearst_attr'])) {
					$url .= '&' . $_GET['nearst_attr'];
				}

				wp_redirect($url);
			}
		}
	}

}
