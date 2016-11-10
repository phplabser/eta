<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon\Session;

class Memcached implements \SessionHandlerInterface {

    /**
     * @var \Eta\Addon\Memcached
     */
    protected $memBackend = null;
    protected $lifetime = 900;

    public function open($savePath, $sessionName) {
        $this->memBackend = \Eta\Addon\Memcached::getInstance();
        $this->lifetime = \Eta\Core\Config::getInstance()->get("session","lifetime") ?: $this->lifetime;
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
        $value = $this->memBackend["session_".$id];
        $this->memBackend->touch("session_".$id,$this->lifetime);
        return $value;
    }

    public function write($id, $data) {
        $this->memBackend->setWithLifetime("session_".$id,$data,$this->lifetime);
        return $this->memBackend->getResultCode() == \Memcached::RES_STORED || $this->memBackend->getResultCode() == \Memcached::RES_SUCCESS;
    }

    public function destroy($id) {
        unset($this->memBackend["session_".$id]);
        return true;
    }

    public function gc($maxlifetime) {
        return true;
    }
} 