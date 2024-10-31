<?php

namespace Nearst;

use WC_Product;
use WC_Product_Variation;

/**
 * Class Products
 * @package Nearst
 */
class Products
{

	/**
	 * @param WC_Product|WC_Product_Variation $product
	 * @param string $product_identifier
	 * @return mixed|string
	 */
	public static function get_sku($product, $product_identifier)
	{
		if ($product_identifier == 'sku') {
			return $product->get_sku();
		} else if ($product_identifier == 'id') {
			return $product->get_id();
		}

		$sku = $product->get_sku();
		if (!$sku || !preg_match('/^\d{8,14}$/', $sku)) {
			$sku = '00000000' . $product->get_id();
		}
		return $sku;
	}

	/**
	 * @return array[]
	 */
	public static function get()
	{
		$product_identifier = get_option('nearst_product_identifier');
		$inventory = [];
		$products = [];
		$counts = [
			'total' => 0,
			'sku' => 0,
			'tracked' => 0,
			'accepted' => 0,
			'in_stock' => 0
		];

		$page = 1;

		while ($page) {
			$items = wc_get_products([
				'post_type' => 'product',
				'posts_per_page' => 10,
				'page' => $page,
				'status' => 'publish'
			]);
			foreach ($items as $product) {
				/** @var WC_Product $product */
				$counts['total']++;
				if ($product->get_sku()) {
					$counts['sku']++;
				}
				if ($product->managing_stock()) {
					$counts['tracked']++;
				}
				if ($product->get_manage_stock() && $product->get_status() == 'publish') {
					$counts['accepted']++;
					if ($product->is_in_stock()) {
						$counts['in_stock']++;
					}

					$sku = self::get_sku($product, $product_identifier);
					$inventory[] = [
						'barcode' => $sku,
						'price' => $product->get_price('edit'),
						'quantity' => $product->get_stock_quantity(),
						'currency' => get_woocommerce_currency()
					];
					$image_id = $product->get_image_id();
					$products[] = [
						'barcode' => $sku,
						'title' => $product->get_title() ?: '',
						'brand' => htmlspecialchars_decode(get_bloginfo('name')),
						'image_url' => $image_id ? wp_get_attachment_image_url($image_id, 'full') : '',
						'description' => wp_strip_all_tags($product->get_description(), true),
						'link' => $product->get_permalink()
					];
				}

				if ($product->is_type('variable') && is_callable([$product, 'get_available_variations'])) {
					$variations = $product->get_available_variations();
					foreach ($variations as $variation) {
						$variation = new WC_Product_Variation($variation['variation_id']);
						if ($variation->get_manage_stock() && $variation->get_status() == 'publish') {
							// Add inventory line
							$counts['total']++;
							$counts['sku']++;
							$counts['accepted']++;
							$counts['tracked']++;
							if ($variation->is_in_stock()) {
								$counts['in_stock']++;
							}
							$sku = self::get_sku($variation, $product_identifier);
							$inventory[] = [
								'barcode' => $sku,
								'price' => $variation->get_price('edit'),
								'quantity' => $variation->get_stock_quantity(),
								'currency' => get_woocommerce_currency()
							];

							// Get image, title and description
							$image_id = $variation->get_image_id();
							$formatted_variation_list = wc_get_formatted_variation($variation, true, false, false);
							$variant_description = trim(wp_strip_all_tags($variation->get_description(), true));
							$variant_title = $variation->get_title() ?: $product->get_title() ?: '';
							if ($formatted_variation_list) {
								$variant_title .= ' - ' . $formatted_variation_list;
							}

							// Get custom fields (size, color, gender, age_group)
							$custom_variant_fields = [];
							$supported_attribute_names = [
								'size' => 'size',
								'color' => 'color',
								'colour' => 'color',
								'gender' => 'gender',
								'sex' => 'gender',
								'age' => 'age_group',
								'agegroup' => 'age_group',
								'condition' => 'condition'
							];
							foreach ($variation->get_attributes() as $attribute => $value) {
								$normalized_attribute_name = strtolower(trim(str_replace(['attribute_', 'pa_', ' ', '-', '_'], '', $attribute)));
								if (!isset($supported_attribute_names[$normalized_attribute_name])) {
									continue;
								}
								$mapped_field_name = $supported_attribute_names[$normalized_attribute_name];
								$custom_variant_fields[$mapped_field_name] = $value;
							}

							// Build final product entry
							$products[] = array_merge([
								'barcode' => $sku,
								'title' => $variant_title,
								'brand' => htmlspecialchars_decode(get_bloginfo('name')),
								'image_url' => $image_id ? wp_get_attachment_image_url($image_id, 'full') : '',
								'description' => wp_strip_all_tags($variant_description ?: $product->get_description(), true),
								'link' => $variation->get_permalink(),
								'item_group_id' => $product->get_id()
							], $custom_variant_fields);
						}
					}
				}
			}
			if (count($items)) {
				$page++;
			} else {
				$page = null;
			}

			if ($page > 10 && !self::check_memory_limit()) {
				$page = null;
			}
		}

		return [$inventory, $products, $counts];
	}

	public static function get_memory_limit()
	{
		$val = ini_get('memory_limit');
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		$val = substr($val, 0, -1);
		switch ($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	private static function check_memory_limit()
	{
		$available = self::get_memory_limit();
		$used = memory_get_usage();

		if ($used > $available - 10 * 1024 * 1024) {
			return false;
		}

		return true;
	}

}
