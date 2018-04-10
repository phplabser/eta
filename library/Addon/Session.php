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

class Session extends Singleton
{

    protected $backend = null;

    protected function __construct()
    {
        $backend = Config::getInstance()->get("session", "backend");
        $custom  = Config::getInstance()->get("session", "custom");
        if ($backend) {
            $backendClass = $custom ? $backend : "Eta\\Addon\\Session\\" . $backend;
            if (!is_subclass_of($backendClass, "\\SessionHandlerInterface")) {
                throw new RuntimeException("Session backend must implements SessionHandlerInterface");
            }
            $backendObject = new $backendClass;
            $this->backend = $backendObject;
        } else {
            throw new RuntimeException("Session backend not provided");
        }

        $path  = Config::getInstance()->get("session", "save_path");
        if($path) {
            session_save_path($path);
        }

        session_set_save_handler($backendObject, true);
    }

    public function start()
    {
        $config = Config::getInstance()->get("session");
        session_set_cookie_params(
            $config['lifetime'] ?? ini_get("session.gc_maxliftime"),
            $config['path'] ?? ini_get("session.cookie_path"),
            $config['cookie_domain'] ?? ini_get("session.cookie_domain"),
            $config['cookie_secure'] ?? ini_get("session.cookie_secure"),
            $config['cookie_httponly'] ?? ini_get("session.cookie_httponly")
        );
        if (isset($config['name']) && $config['name']) {
            session_name($config['name']);
        }
        session_start();
        self::prolongCookie();
        if (isset($_SESSION['_creationTime'])) $_SESSION['_creationTime'] = time();
    }

    protected static function prolongCookie()
    {
        $config = Config::getInstance()->get("session");
        setcookie(
            session_name(), self::getId(),
            time() + $config['lifetime'] ?? ini_get("session.gc_maxliftime"),
            $config['path'] ?? ini_get("session.cookie_path"),
            $config['cookie_domain'] ?? ini_get("session.cookie_domain"),
            $config['cookie_secure'] ?? ini_get("session.cookie_secure"),
            $config['cookie_httponly'] ?? ini_get("session.cookie_httponly")
        );
    }

    public static function getId()
    {
        return session_id();
    }

    public function destroy()
    {
        session_destroy();
    }

    public function abort()
    {
        session_abort();
    }

    public function restart()
    {
        session_reset();
    }

    public function getBackend()
    {
        return $this->backend;
    }

    public function getArrayCopy()
    {
        return $_SESSION;
    }

    public function set(...$params)
    {
        if (count($params) < 2) {
            throw new \RuntimeException("Set function must have at least two arguments.");
        }

        $data    = $_SESSION[$params[0]] ?? $_SESSION[$params[0]] = [];
        $pointer = [&$data];
        for ($i = 1; $i < count($params) - 1; $i++) {
            if (!isset($pointer[$i - 1][$params[$i]])) {
                $pointer[$i - 1][$params[$i]] = [];
            }
            $pointer[$i] = &$pointer[$i - 1][$params[$i]];
        }

        $pointer[$i - 1]      = $params[count($params) - 1];
        $_SESSION[$params[0]] = $data;
    }

    public function get(...$names)
    {
        $session = \App::$session->getArrayCopy();
        foreach ($names as $name) {
            $session = isset($session[$name]) ? $session[$name] : null;
            if ($session === null) return null;
        }

        return $session;
    }
}
