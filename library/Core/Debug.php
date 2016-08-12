<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;

class Debug {

    protected static $applicationStartTime = 0;
    protected static $runtimeLog = [];

    public static function setApplicationStartTime($time) {
        self::$applicationStartTime = $time;
    }

    public static function stop($variable = null) {
        if(self::inDevelMode()) {
            Output::dump($variable);
            die();
        }
    }

    public static function putToLog($string) {
        $tm = time();
        self::$runtimeLog[] = [
            'date' => $tm,
            'runtime' => $tm - self::$applicationStartTime,
            'caller' => debug_backtrace()[1]['function'],
            'information' => $string
        ];
    }

    public static function getLog() {
        return self::$runtimeLog;
    }

    public static function getLogAsString() {
        $log="Debug Log: " . PHP_EOL;
        foreach(self::$runtimeLog as $k=>$v) {
            $log.=date("[Y-m-d H:i:s] ",$v['date'])."({$v['runtime']}) ".$v['information'] . PHP_EOL;
        }
        return $log;
    }

    public static function configureErrors()
    {
        if (php_sapi_name() == 'cli') return;
        ini_set("error_prepend_string", "<pre style=\"font-family: 'Courier New' !important; font-size: 11px; padding: 0 10px 10px 10px; border: 1px solid #f00; margin: 0 10px 10px 10px; color: #fff; background: #f00\">");
        ini_set("error_append_string", "</pre>");
        ini_set("display_errors", self::inDevelMode());
        error_reporting(E_ALL);
    }

    public static function getExceptionForDisplay() {
        if(!self::inDevelMode()) return "";
        $exception = "";
        if(Dispatcher::getInstance()->getDispatchException()) {
            $message = Dispatcher::getInstance()->getDispatchException()->getMessage();
            $trace = Dispatcher::getInstance()->getDispatchException()->getTraceAsString();
            $exception = <<<HTML
<pre class="debug">
Error description:
{$message}

{$trace}
</pre>
HTML;
        }
        return $exception;
    }

    public static function inDevelMode() {
        return getenv('ON_DEV') || getenv('APPLICATION_ENV')=='development' || getenv('APPLICATION_ENV')=='DEVELOPMENT';
    }

    public static function getAppEnv() {
        return self::inDevelMode() ? 'development' : 'production';
    }

} 