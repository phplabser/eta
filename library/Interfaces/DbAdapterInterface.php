<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Interfaces;

use Eta\Addon\Db\Config;

interface DbAdapterInterface {

    public function setConfig(Config $config);
    public function getConfig();
    public function execDML($sql, $bind = []);
    public function getOne($sql, $bind = []);
    public function getRow($sql, $bind = []);
    public function getAll($sql, $bind = []);
    public function getColumn($sql, $bind = []);
    public function update($tableName, $parameters, $primaryKey);
    public function insert($tableName, $parameters);
    public function lastInsertId();
    public function getAffectedRows();
} 