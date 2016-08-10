<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;


use Eta\Exception\RuntimeException;
use Eta\Model\BaseArrayObject;

abstract class ModelDataObjectFactory extends BaseArrayObject {

    protected static $count = 0;

    protected static function getChildrenClass() {
        return "\\StdClass";
    }

    protected static function buildObjects(Array $array) {
        $childrenClass = static::getChildrenClass();
        reset($array);
        while(list($k,$v) = each($array)) {
            $array[$k] = new $childrenClass($v);
        }
        return $array;
    }

    public static function removeChild($childId) {
        if(!static::getPrimaryKey()) {
            throw new RuntimeException("Factory not configured properly. Cannot retrive primary key name from getPrimaryKey function.", 500);
        }
        $sql = "DELETE FROM ". static::getTableName() . " WHERE " . static::getPrimaryKey() . " = :id";
        static::$_db->execDML($sql,['id'=>$childId]);
        return true;
    }


    public static function getAll() {
        $sql = "SELECT * FROM ".self::getTableName();
        $rows = static::$_db->getAll($sql);
        return self::buildObjects($rows);
    }

    public static function getById($ids) {
        $parameters = [];
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE " . static::getPrimaryKey();
        if(is_array($ids)) {
            $sql .= " IN ('".join("','",$ids)."')";
        } else {
            $sql .= " = :id";
            $parameters = ["id" => $ids];
        }
        $rows = static::$_db->getAll($sql,$parameters,"id");
        return self::buildObjects($rows);
    }

    public static function getTotalCount() {
        return self::$count;
    }

    public static function getByParameters(Array $parameters = [], Array $order = [], $limit = null, $offset = null) {
        $sql = "";

        $fields = static::getTableFields();

        if($limit) $limit = (int)$limit;
        if($offset) $offset = (int)$offset;

        if(count($parameters)) {
            if (!count($fields)) {
                throw new RuntimeException("In case using \$parameters parameter getTableFields() must return all of table fields", 500);
            }
            $keys = array_keys($parameters);
            foreach ($keys as $k => $v) {
                $keys[$k] = "$v = :$v";
            }
            $sql .= " WHERE ".join(" AND ", $keys);
        }

        if(count($order)) {
            $o = [];
            if(!count($fields)) {
                throw new RuntimeException("In case using \$order parameter getTableFields() must return all of table fields",500);
            }
            foreach($order as $k=>$v) {
                if(in_array($k,$fields)) {
                    $v = strtoupper($v);

                    if (in_array($v , ['ASC', 'DESC'])) {
                        $o[] = "$k ";
                    }
                }
            }
            if(count($o)) {
                $sql .= " ORDER BY ".join(',', $o);
            }
        }

        if(!$parameters && !$order && !$limit && !$offset) {
            throw new RuntimeException("No parameters nor order nor limit nor offset provided to getByParameters.",500);
        }

        $sqlCount = "SELECT count(*) FROM ".static::getTableName() ." ". $sql;

        self::$count = static::$_db->getOne($sqlCount, $parameters);

        if($limit) {
            $sql .= " LIMIT $limit";
        }
        if($offset) {
            $sql .= " OFFSET $offset";
        }

        $sqlReq = "SELECT * FROM ".static::getTableName() ." ". $sql;


        $rows = static::$_db->getAll($sqlReq,$parameters);
        return self::buildObjects($rows);
    }
} 