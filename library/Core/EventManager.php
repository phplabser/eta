<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;

use Eta\Exception\RuntimeException;

class EventManager extends \Eta\Model\Singleton {

    protected $events = [];

    public function setEventListener($eventName,$callback) {
        if(!isset($this->events[$eventName])) $this->events[$eventName] = [];
        $this->events[$eventName][] = $callback;
    }

    public function dispatchEvent($eventName) {
        if(!$eventName) throw new RuntimeException("Cannot dispatch unnamed event!");
        if(!$this->events || !$this->events[$eventName]) return;
        foreach($this->events[$eventName] as $event) {
            $event();
        }
    }

}