<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\View\Helper;

use Eta\Core\Config;
use Eta\View\Helper;

class Lang extends Helper {

    protected static $definitions = [];
    protected static $langLoaded = false;
    protected static $currentLang = 'en';

    public function execute(...$params) {
        $langConst = $params[0];
        return self::get($langConst);
    }

    public static function get($langConst) {
        if(!$langConst) return "LANG: const empty";

        if(!isset(self::$langLoaded[self::$currentLang])) {
            self::loadDefinitions();
        }
        return !isset(self::$definitions[$langConst]) ? "LANG: $langConst" :  self::$definitions[$langConst];
    }

    public static function setLanguage($lang) {
        self::$currentLang = $lang;
        self::$langLoaded = false;
    }

    protected static function loadDefinitions() {
        $configPath = Config::getInstance()->get('langs','path');
        if(!$configPath) $configPath = "application" . DIRECTORY_SEPARATOR . "langs";

        $fh = @fopen($configPath . DIRECTORY_SEPARATOR . self::$currentLang . ".lang","r");
        if(!$fh) return;
        self::$definitions = [];
        while(!feof($fh)) {
            list($const,$data) = explode("=",fgets($fh),2);
            self::$definitions[trim($const)] = trim(trim(str_replace("{nl}","\n",$data),"\n"));
        }
        fclose($fh);
        self::$langLoaded = true;
    }
} 