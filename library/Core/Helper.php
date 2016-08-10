<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;

class Helper {

    public static function inDevelMode() {
        return (isset($_SERVER['ON_DEV']) && $_SERVER['ON_DEV']) || (isset($_SERVER['APPLICATION_ENV']) && $_SERVER['APPLICATION_ENV']=='DEVELOPMENT');
    }


} 