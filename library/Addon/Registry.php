<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon;


class Registry {

    protected static $registry = [];

    public static function add($name, $value) {
        if(isset(self::$registry[$name])) {
            throw new Exception\RegistryException("There is already registered object under name '$name'!");
        }
        self::$registry[$name] = $value;
    }

    public static function get($name) {
        if(!isset(self::$registry[$name])) {
            throw new Exception\RegistryException("There is no '$name' object registered");
        }
        return self::$registry[$name];
    }
} 