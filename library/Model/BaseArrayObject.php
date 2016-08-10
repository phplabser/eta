<?php
/**
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @version 1.0
 */

namespace Eta\Model;

use Eta\Addon\Db\Adapter;

abstract class BaseArrayObject extends \ArrayObject{

    /**
     * @var \Eta\Addon\Db\Adapter
     */
    protected static $_db = null;

    public static function setDbAdapter(Adapter $dbAdapter) {
        static::$_db = $dbAdapter;
    }

}