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
use Eta\Core\Dispatch\Response;
use Eta\Model\Singleton;
use Eta\View\Renderer;

class Dispatcher extends Singleton {

	protected $availableModules = [];
	protected $loadedModules = [];
    protected $exception;
	protected $resolvedRoute;
	protected $currentModule;

    protected function __construct() {
        parent::__construct();
		$this->loadModules();
	}

	protected function loadModules() {
		$this->availableModules = Config::getInstance()->get("modules") ?: ['Application'];

		foreach($this->availableModules as $moduleName) {
			$moduleObject = "\\".$moduleName."\\Module";
			$module = new $moduleObject;
			if(!$module instanceOf \Eta\Core\Module) {
				throw new \Eta\Exception\BootstrapException("Wrong class instance of instantinated Module class of $moduleName module!");
			}
			if(method_exists($module, "onInitialize")) {
				$module->onInitialize();
			}
			$this->loadedModules[$moduleName] = $module;
		}
	}

	public function getResolvedRoute() {
		return $this->resolvedRoute;
	}

	public function getCurrentModule() {
		return $this->currentModule;
	}
	
	public function dispatch() {
		$targetModule = null;
		foreach($this->loadedModules as $moduleName=>$module) {
			$route = $this->resolveRoutes($module->getRouting());
			if($route) {
				$this->currentModule = $moduleName;
				$this->resolvedRoute = $route;
				break;
			}
		}

		if(!$route) {
			$resp = $this->notFound();
		} else {
			EventManager::getInstance()->dispatchEvent("onPostDispatch");
            try {
                $resp = $this->execute($route, $this->currentModule);
            } catch (\Exception $e) {
                $trace = $e->getTrace()[0];
				error_log("ETA: ".$e->getMessage()." in ".($trace['file'] ?? "unknown")." at line ". ($trace['line'] ?? "null"));
                $this->exception = $e;
                $resp = $this->serverError($e);
            }
		}

        Response::getInstance()->sendHeaders();

        if(!$route)
        Renderer::getInstance()
            ->setPageTitle(Response::getInstance()->getResponse())
            ->setPageDescription(Response::getInstance()->getResponseReason());
		if($resp!==null) {
            Renderer::getInstance()
                ->render($route,$this->currentModule,$resp);
		}
	}
	
	protected function resolveRoutes(array $routes): array {
		foreach($routes as $routeName=>$rd) {
			$router = ucfirst(strtolower($rd['type']));
			if(!$router) {
				throw new \Eta\Exception\RouteException("Router type not set in route $routeName!");
			}
			$router = "\\Eta\\Route\\$router";
			$r = new $router($rd['route'],$rd['constraints'],isset($rd['spec']) ? $rd['spec'] : []);
			$resp = $r->match($this->getUri(true));
			if($resp) {
				return [
					'route' => $resp,
					'routeMatch' => $routeName
				];
			}
		}
		return [];
	}
	
	protected function getUri($trimQueryString = false): string {
        $uri = $_SERVER['REQUEST_URI'];
        if($trimQueryString) {
            $uri = str_replace("?".$_SERVER['QUERY_STRING'],"",$_SERVER['REQUEST_URI']);
        }
        $uri = rtrim(trim($uri),"/");
        return $uri;
	}
	
	protected function notFound(): array {
        Response::getInstance()->setResponse(404);
		return [
			'errorCode' => 404
			];
	}

    protected function serverError(\Exception $e): array {
        Response::getInstance()->setResponse(500);
        Renderer::getInstance()->addParams(["exception"=>$e]);

        return [
            'errorCode' => 500
        ];
    }

    public function getDispatchException(): \Exception {
        return $this->exception;
    }
	
	protected function execute(array $route,string $module) {
		$controllerString = "\\$module\\Controller\\".ucfirst(strtolower($route['route']['controller']))."Controller";
		$actionString = $this->buildActionString($route['route']['action']);
		
		$controller = new $controllerString;
		if(!($controller instanceof \Eta\Controller\ActionController)) {
			throw new \Eta\Exception\BootstrapException("Controller must extends \\Eta\\Controller\\ActionController");
		}
		$controller->onDispatch();
		
		if(!$actionString || !method_exists($controller, $actionString)) $this->notFound();
		$returnData =  $controller->$actionString();

		$endData = $controller->onActionEnds();
		if($returnData && is_array($returnData) && is_array($endData)) {
			$returnData = array_merge($returnData,$endData);
		}
		return $returnData;
	}
	
	protected function buildActionString(string $action): string {
		$parts = explode("-",strtolower($action));
		$action="";
		$cnt=0;
		foreach($parts as $part) {
			$cnt++;
			if($cnt>1) {
				$part = ucfirst($part);
			}
			$action .= $part;
		}
		if(!$action) {
			return false;
		}
		return $action."Action";
	}
}