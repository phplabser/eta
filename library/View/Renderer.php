<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\View;

use Eta\Core\Config;
use Eta\Core\Debug;
use Eta\Core\Dispatch\Request;
use Eta\Core\Dispatch\Response;
use Eta\Core\Output;
use Eta\Exception\RuntimeException;
use Eta\Model\Singleton;

class Renderer extends Singleton {
	
	protected $params = [];
	protected static $layout = null;

    protected $pageTitle = "";
    protected $pageDescription = "";

    protected $helpers = [];
    protected $useLayout = true;

    public function addParams(Array $array) {
        $this->params = array_merge($this->params,$array);
        return $this;
    }

	public function render($route,$module,$params = []) {
        $this->addParams($params);
        $layouts = Config::getInstance()->get('layouts');

		if (Response::getInstance()->getResponseType() == Response::STATUS_OK || Response::getInstance()->getResponseType() == Response::STATUS_REDIRECTION) {
            $templatePath = "application" . DIRECTORY_SEPARATOR . "module". DIRECTORY_SEPARATOR . $module. DIRECTORY_SEPARATOR . "views". DIRECTORY_SEPARATOR . strtolower($route['route']['controller']). DIRECTORY_SEPARATOR .strtolower($route['route']['action']).".phtml";

		} else {
            if(isset($layouts['error'])) {
                $module = 'error';
            }
            if (isset($layouts["error/".Response::getInstance()->getResponse()])) {
                $templatePath = $layouts["error/".Response::getInstance()->getResponse()];
            } else {
                if (isset($layouts["error"])) {
                    $templatePath = $layouts["error"];
                } else {
                    Debug::raiseError(
                        "Internal server error! "
                        . Response::getInstance()->getResponseType() . " - "
                        . Response::getInstance()->getResponse() . " - "
                        . Response::getInstance()->getResponseReason() . " - request: "
                        . Request::getInstance()->getParam('request_uri')
                        , Debug::ETA_ERROR_FATAL
                    );
                }
            }
		}
		
		ob_start();
        $resp = @include($templatePath);
        if(!$resp) {
            Debug::raiseError("Missing template $templatePath",Debug::ETA_ERROR_WARNING);
        }
		$tpl = ob_get_clean();
		
		$this->initLayout($module);

        if(!$this->useLayout) {
            self::$layout = null;
            $this->useLayout = true;
        }
		
		if(self::$layout) {
			$this->params['templateContent'] = $tpl;
            $resp = @include(self::$layout . DIRECTORY_SEPARATOR . "layout.phtml");
			if(!$resp) {
                Debug::raiseError("Missing layout template: ".self::$layout . DIRECTORY_SEPARATOR . "layout.phtml",Debug::ETA_ERROR_WARNING);
            }
		} else {
			echo $tpl;
		}
	}

    public function load($templateName,$layout = null) {
        $layout = $layout ?? self::$layout;
        $result = @include($layout . DIRECTORY_SEPARATOR . $templateName . ".phtml");
        if(false === $result) {
            Debug::raiseError("Missing template $templateName (in $layout)",Debug::ETA_ERROR_WARNING);
        }
    }

    public function registerHelper($name, $class) {
        $this->helpers[$name] = $class;
		return $this;
    }

    public function __call($helper, $params) {
        if(!isset($this->helpers[$helper])) {
            throw new RuntimeException("View helper $helper not registered!");
        }
        if(get_parent_class($this->helpers[$helper]) != "Eta\\View\\Helper") {
            throw new RuntimeException("Helper {$this->helpers[$helper]} must extends \\Eta\\View\\Helper!");
        }
        $helper = $this->helpers[$helper];
        $helper = $helper::getInstance();
        return $helper->execute(...$params);
    }

	public function __get($name) {
		if(isset($this->params[$name])) {
			return $this->params[$name];
		}
	}
	
	public function setLayout($layout) {
		self::$layout = $layout;
	}

    public function setNoLayout() {
        $this->useLayout = false;
    }
	
	protected function initLayout($module) {
		$module = strtolower($module);
        $layouts = Config::getInstance()->get("layouts");
		if(!$layouts) return $this;

		if(isset($layouts[$module])) {
			self::$layout = $layouts[$module];
		} else {
			self::$layout = isset($layouts['default']) ? $layouts['default'] : null;
		}

		return $this;
	}

    public function setPageTitle($title) {
        $this->pageTitle = $title;
        return $this;
    }

    public function setPageDescription($description) {
        $this->pageDescription = $description;
        return $this;
    }

    public function getPageTitle() {
        return $this->pageTitle;
    }

    public function getPageDescription() {
        return $this->pageDescription;
    }
	
}