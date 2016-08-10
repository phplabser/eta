<?php
/**
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @version 1.0
 */

namespace Eta\Interfaces;


interface PluginInterface {

    public function execute();
    public static function getInstance();
} 