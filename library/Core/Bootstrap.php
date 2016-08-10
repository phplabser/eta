<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

declare(strict_types=1);

namespace Eta\Core;

class Bootstrap {

    protected $applicationPath;

	public function __construct() {
        $this->applicationPath = getcwd();
        $this->registerAutoLoad();

        Debug::setApplicationStartTime(time());
        Debug::configureErrors();

        Config::getInstance()->init();

        PluginManager::getInstance()->executePlugins(PluginManager::INIT_PLUGIN);
	}

    private function getSearchPathStack(): array {
        $searchPath = [
            'application'. DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR
        ];

        return $searchPath;
    }

    protected function registerAutoLoad() {
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

	public function loadClass(string $class)
    {
        $className = $class;
        $class = str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";
        foreach ($this->getSearchPathStack() as $path) {
            if (file_exists($path . $class)) {
                include $path . $class;
                if (!class_exists($className) && !interface_exists($className)) {
                    throw new \Eta\Exception\AutoloadException("File loaded but class not found! ($class)", 100);
                }
                return;
            }
        }
        throw new \Eta\Exception\AutoloadException("Could not load class file! ($class - last try in $path)", 100);
    }

    public function getDispatcher(): Dispatcher {
        return Dispatcher::getInstance();
    }

    public function __destruct() {
        chdir($this->applicationPath);
        PluginManager::getInstance()->executePlugins(PluginManager::FINALIZE_PLUGIN);
    }
}