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
use Eta\Interfaces\PostRendererInterface;
use Eta\Model\Singleton;

class Renderer extends Singleton {
	
	protected $params = [];
	protected static $layout = null;

    protected $pageTitle = "";
    protected $pageDescription = "";

    protected $helpers = [];
    protected $postRenderer = [];
    protected $useLayout = true;

    protected $forcedTemplate = null;

    protected $generatedTplVars = [];

    public function addParams(Array $array) {
        $this->params = array_merge($this->params,$array);
        return $this;
    }

    public function setTemplate($template) {
        $this->forcedTemplate = $template;
    }

    public function renderError($module) {

        $layouts = Config::getInstance()->get('layouts');
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

        $tpl = $this->includeTpl($templatePath);
        $this->initLayout($module);
        $this->params['templateContent'] = $tpl;
        $tpl = $this->includeTpl(self::$layout . DIRECTORY_SEPARATOR . "layout.phtml");
        $tpl = $this->postRender($tpl);
        echo $tpl;
    }

	public function render($route,$module,$params = [],$return=false) {
        if (Response::getInstance()->getResponseType() != Response::STATUS_OK && Response::getInstance()->getResponseType() != Response::STATUS_REDIRECTION) {
            $this->renderError($module);
            return null;
        }

        $this->addParams($params);

        if($this->forcedTemplate !== null) {
            $templatePath = "application" . DIRECTORY_SEPARATOR . "module". DIRECTORY_SEPARATOR . $this->forcedTemplate . ".phtml";
        } else {
            $templatePath = "application" . DIRECTORY_SEPARATOR . "module". DIRECTORY_SEPARATOR . $module. DIRECTORY_SEPARATOR . "views". DIRECTORY_SEPARATOR . strtolower($route['route']['controller']). DIRECTORY_SEPARATOR .strtolower($route['route']['action']).".phtml";
        }

        $tpl = $this->includeTpl($templatePath);
		$this->initLayout($module);

        if(!$this->useLayout) {
            self::$layout = null;
            $this->useLayout = true;
        }
		
		if(self::$layout) {
			$this->params['templateContent'] = $tpl;
            $tpl = $this->includeTpl(self::$layout . DIRECTORY_SEPARATOR . "layout.phtml");
		}

        $tpl = $this->postRender($tpl);
        if($return) return $tpl;
        echo $tpl;
    }

    protected function postRender($tpl) {
        if(count($this->postRenderer)) {
            foreach ($this->postRenderer as $renderer) {
                $renderer = new $renderer();
                if(!($renderer instanceof PostRendererInterface)) {
                    throw new \RuntimeException("PostRenderer must implements PostRendererInterface");
                }
                $tpl = $renderer->render($tpl);
            }
        }
        return $tpl;
    }

    protected function includeTpl($tpl) {
        ob_start();
        $resp = include($tpl);
        $tpl = ob_get_clean();
        if(!$resp) {
            Debug::raiseError("Missing template $tpl",Debug::ETA_ERROR_WARNING);
            return "";
        }
        return $tpl;
    }

    public function load($templateName,$layout = null) {
        $layout = $layout ?? self::$layout;
        $tpl = $this->includeTpl($layout . DIRECTORY_SEPARATOR . $templateName . ".phtml");

        echo $tpl;
        return;

//        $result = @include($layout . DIRECTORY_SEPARATOR . $templateName . ".phtml");
//        if(false === $result) {
//            Debug::raiseError("Missing template $templateName (in $layout)",Debug::ETA_ERROR_WARNING);
//        }
    }

    public function registerHelper($name, $class) {
        $this->helpers[$name] = $class;
		return $this;
    }

    public function registerPostRenderer($name, $class) {
        $this->postRenderer[$name] = $class;
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