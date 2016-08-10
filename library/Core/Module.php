<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;

use Eta\Interfaces\ModuleInterface;

class Module implements ModuleInterface {
	
	public function onInitialize() {
		return;
	}
	
	public function getRouting() {
		return [];
	}
	
	public function getNamespace() {
		return __NAMESPACE__;
	}
}