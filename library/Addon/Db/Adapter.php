<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon\Db;

use Eta\Interfaces\DbAdapterInterface;

abstract class Adapter implements DbAdapterInterface {

    protected $config;
    protected $pdo = null;

    public function setConfig(Config $config) {
        $this->config = $config;
    }

    /**
     * @return \Eta\Addon\Db\Config
     */
    public function getConfig(): Config {
        return $this->config;
    }
}