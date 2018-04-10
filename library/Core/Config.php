<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;

use Eta\Core\Dispatch\Request;
use Eta\Model\Singleton;

class Config extends Singleton{

	private static $config;

    public function init() {
        $config = require_once("application/config/application.php");
        $common = $config['common'] ?? [];
        $env = $config[Debug::getAppEnv()] ?? [];
        self::$config = array_merge_recursive($common,$env);
    }

	public function get(...$names) {
        $config = self::$config;
        foreach($names as $name) {
            $config = isset($config[$name]) ? $config[$name] : null;
            if($config===null) return null;
        }
        return $config;
	}

    public static function getDocumentRoot() {
        return Request::getInstance()->getParam('document_root');
    }

    public static function getApplicationRoot() {
        $documentRoot = Request::getInstance()->getParam('document_root');
        $documentRoot = "/".trim($documentRoot,"/ ")."/../";
        return realpath($documentRoot);
    }
	
}