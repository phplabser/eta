<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon\Db\Adapter;

use Eta\Addon\Db\Tunnel;
use Eta\Core\Debug;
use Eta\Exception\RuntimeException;

class Sqlite3 extends Mysql {

    /**
     * @return \PDO
     */
    protected function getDb($newUnbuffered = false) {
        if(!$newUnbuffered) {
            if ($this->pdo && $this->checkConnection($this->pdo)) {
                return $this->pdo;
            }
        }

        $pdo = null;

        if($this->getConfig()->hasTunneledConnection()) {
            try {
                $pdo = new \PDO(
                    $this->getConfig()->getDsn()
                );
            } catch (\PDOException $e) {
                $tunnel = new Tunnel($this-getConfig()->getTunnelName());
                $tunnel->connect();
            }
        }
        if(!$pdo) {
            $pdo = new \PDO(
                $this->getConfig()->getDsn()
            );
        }

        if($newUnbuffered) {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


        if(!$newUnbuffered) {
            $this->pdo = $pdo;
            return $this->pdo;
        } else {
            $this->unbufferedPdo = $pdo;
            return $this->unbufferedPdo;
        }
    }

    public function describe($tableName) {
        $hash = md5("sqlite_".$tableName);
        $cacheFile = "application/data/cache/".$hash;
        if(file_exists($cacheFile)) {
            return unserialize(file_get_contents($cacheFile));
        }
        $isOk = preg_match("/[a-z0-9_-]/i",$tableName);
        if(!$isOk) {
            throw new RuntimeException("Trying to describe table which name is not well-formated");
        }
        $result = $this->_exec("PRAGMA table_info($tableName)", []);
        $fields = $result->fetchAll(\PDO::FETCH_ASSOC);

        $fields['Field'] = $fields['name'];
        unset($fields['name']);

        file_put_contents($cacheFile,serialize($fields));
        return $fields;
    }

    public function lastInsertId() {
        return $this->getOne("SELECT last_insert_rowid()");
    }

    public function getAffectedRows() {
        throw new RuntimeException("Not supported!");
    }

}
