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
	    $tabSize = 4;
        $str = var_export($expression, true);
        $str = preg_replace('/([a-z0-9\_]+)::__set_state/i', '$1 Object ', $str);
        $str = preg_replace('/\(array\(/i', ' ( ', $str);
        $str = preg_replace('/^\s+/im', "", $str);
        $str = preg_replace('/\)\)/i', ")", $str);
        $str = preg_replace('/([^\s]+) =>/i', '[$1] =>', $str);

        $lines = explode("\n",$str);
        $tab = 0;
        foreach ($lines as &$line) {
            $line = trim($line);

            if(preg_match('/\),?$/m',$line)) {
                $tab--;
                $line = str_repeat(" ", $tab * $tabSize).$line;
                continue;
            }

            $line = str_repeat(" ", $tab * $tabSize).$line;
            if(substr($line,-1,1) == "(") $tab++;
        }

        $str = join("\n",$lines);
        $str = str_replace("array (", "<span style=\"color: blue;\">array</span> (", $str);
        $str = str_replace('=>', '<span style="color: #009900;">=></span>', $str);
        $str = preg_replace('/\[(\'[^\']+\')\]/','[<span style="color: red;">$1</span>]',$str);
        $str = preg_replace('/,$/m','',$str);

        $str = "<pre style='font-family:lucida console; font-size:11px; padding: 10px; border: 1px solid #ddd; background-color: white; line-height: 14px;'>" . $str . "</pre>\n";

        if ($return) {
            return $str;
        } else {
            echo $str;
        }
    }




}
