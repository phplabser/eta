<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon;

use Eta\Core\Config;
use Eta\Exception\RuntimeException;
use Eta\Model\Singleton;

class Memcached extends Singleton implements \ArrayAccess {

    protected $memHandle = null;
    protected $prefix = "";
    protected $lifetime = 900;

    protected function __construct() {
        if(!Config::getInstance()->get("memcached")) {
            throw new RuntimeException("There is no configuration entry for Memcached!");
        }

        $servers = Config::getInstance()->get("memcached","servers");
        $this->memHandle = new \Memcached();
        $this->memHandle->addServers($servers);

        $this->prefix = Config::getInstance()->get("memcached","prefix") ?: "";
        $this->lifetime = Config::getInstance()->get("memcached","lifetime") ?: "15m";
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
           throw new RuntimeException("Key must be set!");
        } else {
            $this->memHandle->set($this->prefix . $offset, $value, $this->lifetime);
        }
    }

    public function offsetExists($offset) {
        $this->memHandle->get($this->prefix . $offset);
        return $this->memHandle->getResultCode() != \Memcached::RES_NOTFOUND;
    }

    public function offsetUnset($offset) {
        $this->memHandle->delete($this->prefix . $offset);
    }

    public function offsetGet($offset) {
        $value = $this->memHandle->get($this->prefix . $offset);
        if($this->memHandle->getResultCode() == \Memcached::RES_NOTFOUND) return null;
        return $value;
    }

    public function getResultCode() {
        return $this->memHandle->getResultCode();
    }

    public function touch($offset, $lifetime = null) {
        return $this->memHandle->touch($this->prefix . $offset, $lifetime ?: $this->lifetime);
    }

    public function setWithLifeTime($offset, $value, $lifetime) {
        return $this->memHandle->set($this->prefix . $offset, $value, $lifetime);
    }

    public function getBackend() {
        return $this->memHandle;
    }

} 