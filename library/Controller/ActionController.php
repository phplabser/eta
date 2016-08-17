<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Controller;

use \Eta;

class ActionController {
	
	public function __construct() {
		
	}
	
	public function onDispatch() {
		
	}

	public function onActionEnds() {
		return [];
	}
	
	protected function redirectBack() {
		$this->redirect($this->getRequest()->getParam('http_referer'));
	}
	
	protected function redirect($url) {
		Eta\Core\Dispatch\Request::getInstance()->redirect($url);
	}
	
	protected function getRequest() : Eta\Core\Dispatch\Request {
		return Eta\Core\Dispatch\Request::getInstance();
	}
	
}
