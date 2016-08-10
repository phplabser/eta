<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;


use Eta\Exception\RuntimeException;
use Eta\Model\Singleton;

class PluginManager extends Singleton {

    const INIT_PLUGIN = 'init';
    const FINALIZE_PLUGIN = 'finalize';

    public function executePlugins($pluginType) {
        $plugins = Config::getInstance()->get("plugins",$pluginType);

        if($plugins) {
            foreach($plugins as $path => $pluginClass) {
                @include_once($path);
                if(!class_exists($pluginClass)) {
                    throw new RuntimeException("Plugin $pluginClass initialization failed! Class not found!");
                }
                if(get_parent_class($pluginClass) != "Eta\\Model\\Plugin") {
                    throw new RuntimeException("Plugin $pluginClass must extend \\Eta\\Model\\Plugin!");
                }
                $pluginInstance = $pluginClass::getInstance();
                $pluginInstance->execute();
            }
        }

        return $this;
    }
} 