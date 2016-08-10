<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon;

use Eta\Addon\Db\Adapter;
use Eta\Addon\Db\Exception;
use Eta\Core\Config;
use Eta\Model\Singleton;
use Eta\Addon\Db\Config as AdapterConfig;

class Db extends Singleton {

    protected $pdoHandle = [];
    protected $defaultAdapter = null;
    protected $adapter = [];

    protected function __construct() {
        $servers = Config::getInstance()->get("database","servers");
        if(!$servers) {
            throw new Exception("No database servers configured!");
        }
        foreach($servers as $config) {
            $adapterConfig = new AdapterConfig($config);
            $adapterClass = "\\Eta\\Addon\\Db\\Adapter\\".$adapterConfig->getAdapterName();
            $adapterObject = new $adapterClass;
            if(!($adapterObject instanceof Adapter)) {
                throw new Exception("Adapter {$adapterConfig->getAdapterName()} must be instance of \\Eta\\Addon\\Db\\Adapter!");
            }
            $adapterObject->setConfig($adapterConfig);
            $this->adapter[$adapterConfig->getAdapterName()][$adapterConfig->getId()] = $adapterObject;

            if($this->defaultAdapter===null || $adapterConfig->isDefaultAdapter()) {
                $this->defaultAdapter = $this->adapter[$adapterConfig->getAdapterName()][$adapterConfig->getId()];
            }
        }
    }

    /**
     * @param null $adapter - adapter type name
     * @param null $id - id of adapters of given type
     * @return \Eta\Addon\Db\Adapter
     */
    public function getAdapter(string $adapter = null, string $id = null): Adapter {
        if(!$adapter && !$id) {
            if(!$this->defaultAdapter) {
                throw new Exception("No adapters registered!");
            }
            return $this->defaultAdapter;
        }

        if(!$adapter) {
            throw new Exception("No adapter provided!");
        }

        if(!isset($this->adapter[$adapter])) {
            throw new Exception("No $adapter adapter registered!");
        }

        if($id) {
            if (!isset($this->adapter[$adapter][$id])) {
                throw new Exception("Adapter $adapter found, but there is no adapter associated with id: $id!");
            }
        }

        return $id ? $this->adapter[$adapter][$id] : reset($this->adapter[$adapter]);
    }

}