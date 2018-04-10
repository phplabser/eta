<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;


use Eta\Exception\RuntimeException;
use Eta\Model\Base;

abstract class ModelDataObjectFactory extends Base {

    const SELECT_ALL = 'all';
    const SELECT_ROW = 'row';
    const SELECT_ONE = 'one';

    protected static $count = 0;
    /**
     * @var \Eta\Addon\Db\Adapter
     */

    protected static function getChildrenClass() {
        return substr(static::class,0,strrpos(static::class,"\\"));
    }

    protected static function getPrimaryKey() : string {
        return 'id';
    }

    protected static function getTableName() : string {
        $childrenClass = static::getChildrenClass();
        $tableName = substr($childrenClass,strrpos($childrenClass,"\\")+1);
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $tableName));

        return $tableName;
    }

    protected static function getTableFields()  {
        $fields = static::$_db->describe(static::getTableName());
        return array_column($fields,'Field');
    }

    protected static function buildObjects(Array $array) {
        $childrenClass = static::getChildrenClass();
        reset($array);
        foreach($array as $k=>$v) {
            $array[$k] = new $childrenClass($v);
        }

        return $array;
    }

    public static function getCount() {
        return self::$_db->getOne("SELECT count(*) FROM ".static::getTableName());
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
        $sql = "SELECT * FROM ".static::getTableName();
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
        $rows = static::$_db->getAll($sql,$parameters);
        return self::buildObjects($rows);
    }

    public static function getTotalCount() {
        return self::$count;
    }

    /**
     * Find rows by parameters
     *
     * @param array $parameters
     * @param array $order
     * @param null $limit
     * @param null $offset
     * @return array
     *
     *
     */
    public static function find(Array $parameters, Array $order = [], $limit = null, $offset = null)
    {
        if (!$parameters && !$order && !$limit && !$offset) {
            throw new RuntimeException("No parameters nor order nor limit nor offset provided to getByParameters.", 500);
        }

        if ((count($parameters) || count($order)) && !count(static::getTableFields())) {
            throw new RuntimeException("In case using \$parameters or \$order parameter getTableFields() must return all of table fields", 500);
        }

        $traverse =
            function ($i, $parentKey = '$AND', $level = 1, &$iteration = 0) use (&$traverse) {
                $conjuctive = [
                        '$OR' => 'OR'
                    ][trim($parentKey)] ?? "AND";
                $conditions = [];
                $values     = [];
                while ($i->valid()) {
                    $iteration++;
                    if ($i->hasChildren()) {
                        $resp         = $traverse($i->getChildren(), $i->key(), $level + 1, $iteration);
                        $conditions[] = $resp['sql'];
                        $values       = array_merge($values, $resp['values']);
                    } else {
                        $sign = [
                                "?" => "LIKE",
                                "!" => "<>",
                                ">" => ">",
                                "<" => "<",
                                "=" => "="
                            ][substr($i->key(), -1, 1)]
                            ?? [
                                ">=" => ">=",
                                "<=" => "<=",
                            ][substr($i->key(), -2, 2)]
                            ?? "=";

                        $value          = "v" . $level . "i" . $iteration;
                        $conditions[]   = join(" ", [trim($i->key(), "?!><= "), $sign, ":" . $value]);
                        $values[$value] = $i->current();
                    }
                    $i->next();
                }

                $sql = join(" " . $conjuctive . " ", $conditions);

                if ($level != 1) $sql = "(" . $sql . ")";

                return [
                    'sql'    => $sql,
                    'values' => $values
                ];
            };

        $iterator = new \RecursiveArrayIterator($parameters);
        $result   = $traverse($iterator);

        $sql      = $result['sql'] ? " WHERE ".$result['sql'] : "";
        $limit    = (int)$limit;
        $offset   = (int)$offset;

        if (count($order)) {
            $o = [];
            foreach ($order as $k => $v) {
                if (in_array($k, static::getTableFields())) {
                    $v = strtoupper($v);
                    if (in_array($v, ['ASC', 'DESC'])) {
                        $o[] = "$k $v";
                    }
                }
            }
            if (count($o)) {
                $sql .= " ORDER BY " . join(',', $o);
            }
        }

        $sqlCount    = "SELECT count(*) FROM " . static::getTableName() . " " . $sql;
        self::$count = static::$_db->getOne($sqlCount, $result['values']);

        if ($limit) $sql .= " LIMIT $limit";
        if ($offset) $sql .= " OFFSET $offset";

        $sqlReq = "SELECT * FROM " . static::getTableName() . " " . $sql;
        $rows   = static::$_db->getAll($sqlReq, $result['values']);

        return self::buildObjects($rows);

    }


    /**
     * @deprecated
     *
     * @param array $parameters
     * @param array $order
     * @param null $limit
     * @param null $offset
     * @param array $searchParams
     * @return array
     */
    public static function getByParameters(Array $parameters = [], Array $order = [], $limit = null, $offset = null, Array $searchParams = []) {
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

        if(count($searchParams)) {
            $tmp = [];
            foreach ($searchParams as $field => $val) {
                $fieldBindName = "_search_" . preg_replace('/[^[:alnum:]]/', '_', $field);
                $tmp[] = $field . " LIKE :" . $fieldBindName;
                $params[$fieldBindName] = $val;
            }
            $sql .= (count($parameters) ? " AND " : " WHERE ") . " (" . implode(" OR ", $tmp) . ")";
            $parameters = array_merge($parameters, $params);
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
                        $o[] = "$k $v";
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

    protected static function select($whereClause = null, $type = self::SELECT_ALL, $fields = null) {
        $types = ['all','row','one'];
        $type = in_array($type,$types) ? $type : self::SELECT_ALL;
        $w = [];
        if($fields) {
            foreach ($fields as &$field) {
                if(!in_array($field,static::getTableFields())) {
                    throw new RuntimeException("No field in getTableFields().");
                }
            }
            $fields = join(", ",$fields);
        } else {
            $fields = "*";
        }

        $sql = "SELECT ".$fields." FROM " . static::getTableName();
        if($whereClause) {
            if(is_array($whereClause)){
                foreach ($whereClause as $k => $v) {
                    $w[] = "$k = :$k";
                }
                $w = join(" AND ", $w);
            } else {
                $w = $whereClause;
            }
            $sql .= " WHERE ".$w;
        }
        $method = "get".ucfirst($type);
        return self::$_db->$method($sql,$whereClause);
    }
} 