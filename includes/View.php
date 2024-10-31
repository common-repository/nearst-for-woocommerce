<?php

namespace Nearst;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

/**
 * Class View
 * @package Nearst
 */
class View
{

	/**
	 * @param $view
	 * @param array $data
	 */
	public static function render($view, $data = [])
	{
		$loader = new FilesystemLoader(trailingslashit(__DIR__) . 'views');
		$twig = new Environment($loader);

		$template = $twig->load(str_replace('.', '/', $view) . '.twig.html');
		echo $template->render($data);
	}

}
