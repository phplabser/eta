<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */


namespace Eta\Core;

class Output {
    
	/**
	 * Returns or put on output expression dump
	 *
	 * @param mixed $expression - expression to be dumped
	 * @param bool[optional] $return - if this parameter is set to TRUE, dump() will return its output, instead of printing it (which it does by default).
	 * @param string[optional] $IP - IP permitted to see expression dump
	 *
	 * @return mixed - true or dump. depends on $return parameter
	 *
	 * @see App::stop() for dump/die functionality
	 *
	 */
	public static function dump($expression, $return = false) {
		$str = var_export($expression, true);

		$str = preg_replace('/([a-z0-9\_]+)::__set_state/i', '$1 Object ', $str);
		$str = preg_replace('/\(array\(/i', ' ( ', $str);
		$str = preg_replace('/\)\)/i', ")", $str);
		$str = preg_replace('/([^\s]+) =>/i', '[$1] =>', $str);
		$str = preg_replace('/,\n/', "\n", $str);

		if (php_sapi_name()!='cli') {
			$str = str_replace('[', '[<span style="color: red;">', $str);
			$str = str_replace(']', '</span>]', $str);

			/* Turn the word Object Green */
			$str = str_replace("object", '<span style="color: #FF8000;">', $str);

			/* Turn the word Array blue */
			$str = str_replace('array', '<span style="color: blue;">array</span>', $str);

			/* Turn arrows green */
			$str = str_replace('=>', '<span style="color: #009900;">=></span>', $str);
//			$str = str_replace("    ", "  ", $str);
//			$str = str_replace(")\n", ")", $str);
//
			$str = "<pre style='font-family:lucida console; font-size:11px;'>" . $str . "</pre>\n";
		}

		if ($return) {
			return $str;
		} else {
			echo $str;
		}
	}




}
