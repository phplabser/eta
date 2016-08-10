<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Model;

use Eta\Exception\RuntimeException;

abstract class Singleton extends Base {

    protected function __construct() {}
    final private function __clone(){}

    /**
     * @return static
     */
    final public static function getInstance ()
    {
        static $instance = [];

        $caller = get_called_class();
        if($caller == __CLASS__) {
            throw new RuntimeException("Cannot instantinate Singleton class itself!");
        }

        return (!isset($instance[$caller]) || $instance[$caller] === null) ? $instance[$caller] = new static() : $instance[$caller];
    }
} 