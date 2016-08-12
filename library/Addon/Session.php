<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon;


use Eta\Core\Config;
use Eta\Core\Debug;
use Eta\Exception\RuntimeException;
use Eta\Model\Singleton;

class Session extends Singleton implements \ArrayAccess {

    protected $backend = null;

    protected function __construct() {
        $backend = Config::getInstance()->get("session","backend");
        if($backend) {
            $backendClass = "Eta\\Addon\\Session\\".$backend;
            if (!is_subclass_of($backendClass,"\\SessionHandlerInterface")) {
                throw new RuntimeException("Session backend must implements SessionHandlerInterface");
            }
            $backendObject = new $backendClass;
            $this->backend = $backendObject;
        }
        session_set_save_handler($backendObject,true);
    }

    public function start() {
        $config = Config::getInstance()->get("session");
        session_set_cookie_params(
            $config['lifetime'] ?? ini_get("session.gc_maxliftime"),
            $config['path'] ?? ini_get("session.cookie_path"),
            $config['cookie_domain'] ?? ini_get("session.cookie_domain"),
            $config['cookie_secure'] ??ini_get("session.cookie_secure"),
            $config['cookie_httponly'] ??ini_get("session.cookie_httponly")
        );
        session_start();
        if(isset($_SESSION['_creationTime'])) $_SESSION['_creationTime'] = time();
    }

    public function destroy() {
        session_destroy();
    }

    public function abort() {
        session_abort();
    }

    public function restart() {
        session_reset();
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $_SESSION[] = $value;
        } else {
            $_SESSION[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($_SESSION[$offset]);
    }

    public function offsetUnset($offset) {
        unset($_SESSION[$offset]);
    }

    public function offsetGet($offset) {
        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    public function getBackend() {
        return $this->backend;
    }
}