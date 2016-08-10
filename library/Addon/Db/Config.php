<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon\Db;


class Config {

    private $configData = [];

    final public function __construct(Array $config) {
        if(!isset($config['host']) || !isset($config['port']) || !isset($config['user']) || !isset($config['password'])) {
            throw new Exception("Provided config array for adapter must have at least adapter, host, port, user, password.");
        }
        $this->configData = $config;
    }

    final public function getId() : string {
        if(!isset($this->configData['id'])) {
            $this->configData['id'] = uniqid();
        }
        return $this->configData['id'];
    }

    final public function getAdapterName() : string {
        return $this->configData['adapter'];
    }

    final public function getHost() : string {
        return $this->configData['host'];
    }

    final public function getPort() : string {
        return $this->configData['port'];
    }

    final public function getUser() : string {
        return $this->configData['user'];
    }

    final public function getPassword() : string {
        return $this->configData['password'];
    }

    final public function getCharset() : string {
        return isset($this->configData['charset']) ? $this->configData['charset'] : "utf8";
    }

    final public function isDefaultAdapter() : bool{
        return isset($this->configData['default']) ? $this->configData['default'] == true : false;
    }

    final public function getDatabase() : string {
        return isset($this->configData['database']) ? $this->configData['database'] : null;
    }

    final public function getDsn($withCredentials = false) : string {
        return
            strtolower($this->configData['adapter']).":"
            . "host=".$this->configData['host']
            . ";port=".$this->configData['port']
            . ($withCredentials ? ";user=".$this->configData['user'] . ";password=".$this->configData['password'] : "")
            . (isset($this->configData['database']) ? ";dbname=".$this->configData['database'] : "");
    }
} 