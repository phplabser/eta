<?php
/**
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @version 1.0
 */

namespace Eta\Model;


use Eta\Interfaces\PluginInterface;

abstract class Plugin extends Singleton implements PluginInterface {

    public function execute() {

    }

} 