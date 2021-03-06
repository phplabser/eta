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

    const ETA_ERROR_NOTICE  = 'Notice';
    const ETA_ERROR_WARNING = 'Warning';
    const ETA_ERROR_FATAL   = 'Fatal Error';

    const APP_LOG_FILE      = 'application/data/logs/application.log';

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

    public static function putToLog($string, $logFile = null) {
        $tm = time();
        $logData = [
            'date' => $tm,
            'runtime' => $tm - self::$applicationStartTime,
            'caller' => debug_backtrace()[1]['function'],
            'information' => $string
        ];
        //self::$runtimeLog[] = $logData;

        @file_put_contents(self::APP_LOG_FILE,date("[Y-m-d H:i:s] ",$logData['date'])."({$logData['runtime']}) ".$logData['information']. PHP_EOL,FILE_APPEND);

        if($logFile) {
            @file_put_contents($logFile,date("[Y-m-d H:i:s] ",$logData['date'])."({$logData['runtime']}) ".$logData['information']. PHP_EOL,FILE_APPEND);
        }
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

    public static function raiseError($text, $errorType = self::ETA_ERROR_NOTICE) {
        $prepend = ini_get("error_prepend_string");
        $append = ini_get("error_append_string");
        $message = "<b>ETA ".$errorType.":</b>  ".$text;

        if(self::inDevelMode()) {
            echo $prepend . $message . $append;
        }
        
        error_log(strip_tags($message));

        if($errorType == self::ETA_ERROR_FATAL) {
            die(0);
        }
    }
} 