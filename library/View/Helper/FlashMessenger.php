<?php
/**
 * Eta Framework 2
 * 
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\View\Helper;


use Eta\Core\Config;
use Eta\Core\Debug;
use Eta\View\Helper;

class FlashMessenger extends Helper {

    public function execute(...$params) {
        $stack = \Eta\Addon\FlashMessenger::getStack();
        $tpl = Config::getInstance()->get("flashMessenger","templates");
        foreach($stack as $space=>$messages) {
            $message = join("<br/>",$messages);
            $stack[$space] = $tpl[$space] ? str_replace("{{message}}",$message, file_get_contents($tpl[$space])) : $message;
        }
        return $stack ? join("\n",$stack): "";
    }
}