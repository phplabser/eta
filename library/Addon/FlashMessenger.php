<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon;

use Eta\Core\Debug;

abstract class FlashMessenger {

    public static function addMessage($namespace, $message) {
        $msg = (array)Session::getInstance()->get('flashmessenger');
        if(!$msg || !isset($msg[$namespace]) ||!in_array($message,$msg[$namespace])) {
            $msg[$namespace][] = $message;
            Session::getInstance()->set('flashmessenger', $msg);
        }
    }

    public static function getStack() {
        $msg = (array)Session::getInstance()->get('flashmessenger');
        Session::getInstance()->set('flashmessenger',[]);
        return $msg;
    }

}