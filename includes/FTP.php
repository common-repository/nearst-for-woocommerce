<?php

namespace Nearst;

use Exception;

/**
 * Class FTP
 * @package Nearst
 */
class FTP
{
	/**
	 * @param string $upload_key
	 * @return bool
	 */
	public static function verify($upload_key = null)
	{
		return !!self::connect($upload_key);
	}

	/**
	 * @param string $upload_key
	 * @return resource|null
	 */
	public static function connect($upload_key = null)
	{
		if (!$upload_key) {
			$upload_key = get_option('nearst_upload_key');
		}
		if (!$upload_key) {
			return null;
		}

		// Connect
		$connection = ftp_connect('ftp.near.live');

		// Sign in
		if (!ftp_login($connection, 'apikey', $upload_key)) {
			return null;
		}

		// Enter passive mode
		ftp_pasv($connection, true);

		return $connection;
	}

	/**
	 * @param $error
	 */
	public static function fail($error)
	{
		update_option('nearst_last_error', $error);
	}

	public static function getCsvLine($input, $delimiter = ',', $enclosure = '"')
	{
		if (function_exists('str_putcsv')) {
			return str_putcsv($input, $delimiter, $enclosure);
		}

		$fp = fopen('php://temp', 'r+b');
		fputcsv($fp, $input, $delimiter, $enclosure);
		rewind($fp);
		$data = rtrim(stream_get_contents($fp), "\n");
		fclose($fp);
		return $data;
	}

	public static function getCsvString($array)
	{
		$string = self::getCsvLine(array_keys($array[0]));
		foreach ($array as $item) {
			$string .= "\n" . self::getCsvLine(array_values($item));
		}
		return $string;
	}

	/**
	 * @throws Exception
	 */
	public static function uploadFile($contents, $type)
	{
		if (function_exists('curl_version')) {
			$upload_key = get_option('nearst_upload_key');
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => 'https://stock.near.live/upload/' . $upload_key . '?type=' . $type,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => true
			]);
			$url = curl_exec($ch);
			curl_close($ch);

			$ch = curl_init();
			$csv = self::getCsvString($contents);
			curl_setopt_array($ch, [
				CURLOPT_URL => $url,
				CURLOPT_POST => 1,
				CURLOPT_CUSTOMREQUEST => 'PUT',
				CURLOPT_HEADER => 0,
				CURLOPT_POSTFIELDS => $csv,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 300,
				CURLOPT_INFILESIZE => strlen($csv),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => [
					'Content-Type: text/csv'
				]
			]);
			curl_exec($ch);

			$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($status_code != 200) {
				throw new Exception('Unexpected status code: ' . $status_code);
			}
			return;
		}

		// Fall back to uploading via FTP
		$connection = self::connect();
		if (!$connection) {
			throw new Exception('No valid stock records found');
		}
		$handle = fopen('php://temp', 'r+');
		ftruncate($handle, 0);
		fputcsv($handle, array_keys($contents[0]));
		foreach ($contents as $item) {
			fputcsv($handle, array_values($item));
		}
		rewind($handle);
		ftp_fput($connection, $type . '.csv', $handle, FTP_BINARY);
		fclose($handle);
	}

	public static function upload($force = false)
	{
		$tz = new \DateTimeZone(get_option('timezone_string') ?: 'Europe/London');
		$dt = new \DateTime('now', $tz);
		$stats = [
			'date' => $dt->format('d-m-Y H:i'),
			'inventory' => [
				'total' => 0,
				'sku' => 0,
				'tracked' => 0,
				'in_stock' => 0,
				'accepted' => 0
			]
		];

		$upload_type = get_option('nearst_upload_type');

		// Get products
		try {
			list($inventory, $products, $counts) = Products::get();
			$stats['inventory'] = $counts;
		} catch (Exception $exception) {
			self::fail('Error getting products: ' . $exception->getMessage());
			return;
		}
		if (empty($inventory)) {
			self::fail('No valid stock records found');
			return;
		}

		// Connect to FTP
		$connection = self::connect();
		if (!$connection) {
			self::fail('Could not sign into NearSt FTP');
			return;
		}

		// Upload products every day
		if ($upload_type != 'stock' && get_option('nearst_last_upload_products') < time() - 86400 && !$force) {
			try {
				self::uploadFile($products, 'products');
				update_option('nearst_last_upload_products', time());
			} catch (Exception $exception) {
				self::fail('Error uploading products to FTP: ' . $exception->getMessage());
				return;
			}
		}

		// Upload stock
		if($upload_type != 'products') {
			try {
				self::uploadFile($inventory, 'stock');
			} catch (Exception $exception) {
				self::fail('Error uploading stock to FTP: ' . $exception->getMessage());
				return;
			}
		}

		update_option('nearst_last_upload', $stats);
		delete_option('nearst_last_error');
	}
}
