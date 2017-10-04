<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon\Db\Adapter;

use Eta\Addon\Db\Adapter;
use Eta\Addon\Db\Tunnel;
use Eta\Core\Debug;

class Mysql extends Adapter {

    /**
     * @var \PDO
     */
    protected $pdo = null;

    /**
     * @var \PDO
     */
    protected $unbufferedPdo = null;

    /**
     * @return \PDO
     */
    protected function getDb($newUnbuffered = false) {
        if(!$newUnbuffered) {
            if ($this->pdo) return $this->pdo;
        }

        $pdo = null;

        if($this->getConfig()->hasTunneledConnection()) {
            try {
                $pdo = new \PDO(
                    $this-getConfig()->getDsn(false, 'mysql'),
                    $this-getConfig()->getUser(),
                    $this-getConfig()->getPassword()
                );
            } catch (\PDOException $e) {
                $tunnel = new Tunnel($this-getConfig()->getTunnelName());
                $tunnel->connect();
            }
        }
        if(!$pdo) {
            $pdo = new \PDO(
                $this->getConfig()->getDsn(false, 'mysql'),
                $this->getConfig()->getUser(),
                $this->getConfig()->getPassword()
            );
        }

        if($newUnbuffered) {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if ($charset = $this->getConfig()->getCharset()) {
            $pdo->exec("SET NAMES " . $charset);
        }

        if(!$newUnbuffered) {
            $this->pdo = $pdo;
            return $this->pdo;
        } else {
            $this->unbufferedPdo = $pdo;
            return $this->unbufferedPdo;
        }
    }

    public function execDML($sql, $bind = []) {
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($bind);
    }

    public function describe($tableName) {
        $cacheFile = "application/data/cache/".md5($tableName);
        if(file_exists($cacheFile)) {
            return unserialize(file_get_contents($cacheFile));
        }
        $isOk = preg_match("/[a-z0-9_-]/i",$tableName);
        if(!$isOk) {
            throw new Exception("Trying to describe table which name is not well-formated");
        }
        $result = $this->_exec("DESCRIBE $tableName", []);
        $fields = $result->fetchAll(\PDO::FETCH_ASSOC);
        file_put_contents($cacheFile,serialize($fields));
        return $fields;
    }

    public function lastInsertId() {
        return $this->getDb()->lastInsertId();
    }

    public function getAffectedRows() {
        return $this->_exec("SELECT FOUND_ROWS() rws",[])->fetch(\PDO::FETCH_ASSOC)['rws'];
    }

    public function getOne($sql, $bind = []) {
        return $this->_exec($sql, $bind)->fetchColumn();
    }

    public function get($sql, $bind = []) {
        return $this->_exec($sql, $bind, true);
    }

    public function getRow($sql, $bind = [], $objectClassName = null) {
        $row = $this->_exec($sql, $bind)->fetch(\PDO::FETCH_ASSOC);
        return $objectClassName===null ? $row : new $objectClassName($row);
    }

    public function getColumn($sql, $bind = []) {
        $rows = $this->getAll($sql, $bind);
        while(list($k,$v) = each($rows)) {
            $rows[$k] = reset($v);
        }
        return $rows;
    }

    public function getAll($sql, $bind = [], $objectClassName = null, $assocKey = null) {
        $stmt = $this->_exec($sql, $bind);
        $result = [];
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if($assocKey) {
                $result[$row[$assocKey]] = $objectClassName===null ? $row : new $objectClassName($row);
            } else {
                $result[] = $objectClassName===null ? $row : new $objectClassName($row);
            }
        }
        return $result;
    }

    public function insert($tableName, $parameters)
    {
        $fields = array_keys($parameters);
        array_walk($parameters,function(&$item, $key) {
            if($item === 'null') $item = null;
        });
        $sql = "INSERT INTO $tableName ";
        $sql .= "(".join(",",$fields).") VALUE (:".join(",:",$fields).")";

        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($parameters);
    }

    public function update($tableName, $parameters, $primaryKey)
    {
        $sql = "UPDATE $tableName SET ";
        foreach($parameters as $k=>$v) {
            $fields[] = "`$k` = :$k";
        }

        $pk = key($primaryKey);
        $parameters['__primaryKeyValue'] = $primaryKey[$pk];

        $sql .= join(", ",$fields);
        $sql .= " WHERE $pk = :__primaryKeyValue";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($parameters);
    }

    /**
     * @param $sql
     * @param $bind
     * @param $useUnbuffered
     * @return \PDOStatement
     */
    protected function _exec($sql, $bind, $useUnbuffered = false) {
        Debug::putToLog($sql . " [ ". var_export($bind,true)."]");
        $stmt = $this->getDb($useUnbuffered)->prepare($sql);
        $stmt->execute($bind);
        return $stmt;
    }

}
