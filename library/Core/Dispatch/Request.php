<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core\Dispatch;

use Eta\Model\Singleton;

class Request extends Singleton {
	
	protected $server   = [];
    protected $post     = [];
    protected $request  = [];
    protected $get      = [];
    protected $cookie   = [];
    protected $rawData  = null;
	
	protected function __construct() {
		$this->server = new \stdClass();
		foreach($_SERVER as $k=>$v) {
			$k = strtolower($k);
			$this->server->$k=$v;
		}

        $this->post     = $_POST;
        $this->request  = $_REQUEST;
        $this->get      = $_GET;
        $this->cookies  = $_COOKIE;

        $_POST = $_REQUEST = $_GET = [];
	}
	
	public function isPost() : bool {
		return $this->server->request_method == 'POST';
	}

	public function isXhr() : bool {
	    return isset($this->server->http_x_requested_with) ? $this->server->http_x_requested_with == 'XMLHttpRequest' : false;
    }

	public function getPostParam($key) {
        if(!$this->isPost()) return null;
        return $this->post[$key] ?? null;
    }

    public function getPostParams($buildQuery = false) {
        return  $buildQuery ? http_build_query($this->post) : $this->post;
    }

    public function getQueryParam($key) {
        return $this->get[$key] ?? null;
    }

    public function getQueryParams($buildQuery = false) {
        return  $buildQuery ? http_build_query($this->get) : $this->get;
    }

	public function getRequestParam($param) {
		return $this->request[$param] ?? null;
	}

    public function getRequestParams($buildQuery = false) {
        return $buildQuery ? http_build_query($this->request) : $this->request;
    }
	
	public function getParam($param) {
		return $this->server->$param ?? null;
	}

    public function getMethod() : string {
        return $this->server->request_method;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function redirect($url) {
        header("Location: ".$url,null,302);
        die();
    }

    public function getRawData($returnAsParamsArray = false) {
        if(!$this->rawData) {
            $fh  = fopen("php://input", "r");
            $raw = "";
            while (!feof($fh)) {
                $raw .= fgets($fh);
            }
            fclose($fh);
            $this->rawData = $raw;
        } else {
            $raw = $this->rawData;
        }

        if($returnAsParamsArray) {
            parse_str($raw,$raw);
        }

        return $raw;
    }

    public function getDataFromJson(bool $asArray=false) {
        $resp = $this->getRawData();
        return json_decode($resp,$asArray);
    }
}