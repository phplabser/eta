<?php
/**
 * Tools v2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 */

namespace Eta\Addon\Db\Adapter;


use Eta\Addon\Db\Exception;
use Eta\Addon\Db\Tunnel;
use Eta\Core\Debug;
use Eta\Core\Config;

class MysqlMulti extends Mysql
{
    protected static $servers = [];
    protected static $tableMap = [];
    protected static $lastResolvedServer = null;

    protected $pdo = [];
    protected $unbufferedPdo = null;

    protected function getDb($newUnbuffered = false) {
        return $newUnbuffered ? $this->unbufferedPdo : $this->pdo[self::$lastResolvedServer];
    }

    public function disconnect() {
        $this->pdo = [];
    }

    protected function getDbServer($serverName, $useUnbuffered = false) {
        if(!$useUnbuffered) {
            if (isset($this->pdo[$serverName]) && $this->checkConnection($this->pdo[$serverName])) {
                return $this->pdo[$serverName];
            }
        }

        $configData = Config::getInstance()->get('database','servers',$serverName);
        $config = new \Eta\Addon\Db\Config($configData);
        $pdo = null;

        if($config->hasTunneledConnection()) {
            try {
                $pdo = new \PDO(
                    $config->getDsn(false, 'mysql'),
                    $config->getUser(),
                    $config->getPassword()
                );
            } catch (\PDOException $e) {
                $tunnel = new Tunnel($config->getTunnelName());
                $tunnel->connect();
            }
        }
        if(!$pdo) {
            $pdo = new \PDO(
                $config->getDsn(false, 'mysql'),
                $config->getUser(),
                $config->getPassword()
            );
        }

        if($useUnbuffered) {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if($charset = $config->getCharset()) {
            $pdo->exec("SET NAMES ".$charset);
        }

        if($useUnbuffered) {
            $this->unbufferedPdo = $pdo;
            return $this->unbufferedPdo;
        } else {
            $this->pdo[$serverName] = $pdo;
            return $this->pdo[$serverName];
        }
    }

    public function execDML($sql, $bind = []) {
        $server = $this->resolveServer($sql);
        return $this->execOnServer($sql, $server, $bind);
    }

    public function execOnServer($sql, $server, $bind = [], $statementAsResult = false) {
        $stmt = $this->getDbServer($server)->prepare($sql);
        $resp = $stmt->execute($bind);
        return $statementAsResult ? $stmt : $resp;
    }

    public function insert($tableName, $parameters)
    {
        $fields = array_keys($parameters);
        array_walk($parameters,function(&$item, $key) {
            if($item === 'null') $item = null;
        });
        $sql = "INSERT INTO $tableName ";
        $sql .= "(".join(",",$fields).") VALUES (:".join(",:",$fields).")";

        $server = $this->resolveServer($sql);
        $stmt = $this->getDbServer($server)->prepare($sql);

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

        $server = $this->resolveServer($sql);
        $stmt = $this->getDbServer($server)->prepare($sql);

        return $stmt->execute($parameters);
    }

    protected function _exec($sql, $bind, $useUnbuffered = false) {
        $server = $this->resolveServer($sql);

        $stmt = $this->getDbServer($server, $useUnbuffered)->prepare($sql);
        $stmt->execute($bind);
        return $stmt;
    }

    protected function resolveServer(&$sql) {
        self::$servers = Config::getInstance()->get('database','servers');
        self::$tableMap = Config::getInstance()->get('database','table_map') ?? [];


        $query  = trim($sql);
        $preg = '/(\sfrom|\sinto|^update|\supdate|^truncate table|^describe)(\s+`?)([a-z_0-9]+)/i';

        preg_match($preg,$query,$sub);
	    $tbl  = isset($sub[3]) ? trim($sub[3]) : null;

        if ($tbl) {
            foreach (self::$tableMap as $server=>$tables) {
                if(isset($tables[$tbl]) && $tables[$tbl]) {
                    $host = $server;
                    if($tables[$tbl]!==true) {
                        $sql = preg_replace($preg,'\1\2'.$tables[$tbl],$sql);
                    }
                    break;
                }
            }

            if(isset($host) && isset(self::$servers[$host])) {
                self::$lastResolvedServer = $host;
                return $host;
            }

            foreach (self::$servers as $server => $config) {
                if(isset($config['default']) && $config['default']) {
                    self::$lastResolvedServer = $server;
                    return $server;
                }
            }

        } else {
            $keyWords = array('TRANSACTION','ROLLBACK','COMMIT','FOUND_ROWS()','LAST_INSERT_ID()');

            foreach ($keyWords as $kw) {
                if (strpos(strtoupper($query),$kw) !== false) {
                    return self::$lastResolvedServer;
                }
            }
        }

        throw new Exception("No server found to handle this query ($query).");
    }
}
