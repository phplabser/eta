<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Core;


use Eta\Exception\RuntimeException;
use Eta\Interfaces\DataObjectInterface;
use Eta\Model\Base;

abstract class ModelDataObject extends Base implements \ArrayAccess, \JsonSerializable {

    const SELECT_ALL = 'all';
    const SELECT_ROW = 'row';
    const SELECT_ONE = 'one';

    protected $changedFields = [];
    protected $objectData = [];

    public function __construct(Array $data = []) {
        $this->setMass($data,true);
    }

    public static function getInstanceById($id) : self {
        if($id<0) {
            $resp = static::getDataForNegativeIds($id);
            if($resp !== false) {
                return new static($resp);
            }
        }
        $data = static::$_db->getRow("SELECT * FROM " . static::getTableName() . " WHERE " . static::getPrimaryKey() . " = :id",['id' => $id]);
        return new static($data ?: []);
    }

    protected static function getPrimaryKey() : string {
        return 'id';
    }

    protected static function getTableName() : string {
        $className = substr(static::class,strrpos(static::class,"\\")+1);
        $className = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));

        return $className;
    }

    protected static function getTableFields() : array {
        $fields = static::$_db->describe(static::getTableName());
        return array_column($fields,'Field');
    }

    protected static function getDataForNegativeIds(int $id) {
        return false;
    }

    public function getObjectId() {
        return $this->objectData[static::getPrimaryKey()] ?? null;
    }

    public function isValid() : bool {
        return $this->getObjectId() !== 0 && $this->getObjectId() !== null && $this->getObjectId() !== "";
    }

    public function get($tableField) {
        return isset($this->objectData[$tableField]) ? $this->objectData[$tableField] : null;
    }

    public function set($tableField, $value) {
        $availableFields = static::getTableFields();
        if(!count($availableFields)) {
            throw new RuntimeException("There are no fields defined for ".get_class($this)." object!");
        }

        if(!in_array($tableField,static::getTableFields())) {
            throw new RuntimeException("Field $tableField not defined in array of getTableField method of "  .get_class($this) . " object!");
        }
        $this->objectData[$tableField] = $value;
        $this->changedFields[$tableField] = $value;
        return $this;
    }

    public function setMass(Array $data,bool $init = false) {
        foreach($data as $field=>$value) {
            $this->objectData[$field] = $value;
            if(!$init) $this->changedFields[$field] = $value;
        }
        $this->sanitize();
    }

    public function offsetSet($tableField, $value) {
        throw new RuntimeException("Object has readonly ArrayAccess. Use ->set() instead.");
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function offsetUnset($offset)
    {
        unset($this->objectData[$offset]);
    }

    public function offsetExists($offset)
    {
        if(!in_array($offset,static::getTableFields())) return false;
        if(!isset($this->objectData[$offset])) return false;
        return true;
    }

    public function update() {
        unset($this->changedFields[static::getPrimaryKey()]);

        if(!count($this->changedFields)) return false;
        $this->sanitize();

        $this->changedFields[static::getPrimaryKey()] = $this->objectData[static::getPrimaryKey()];

        static::$_db->update(static::getTableName(), $this->changedFields, [static::getPrimaryKey() => $this->getObjectId()]);
        $this->changedFields = [];
        return true;
    }

    public function add($ignorePrimaryKeyField = true) {
        if($ignorePrimaryKeyField && isset($this->objectData[static::getPrimaryKey()])) unset($this->objectData[static::getPrimaryKey()]);
        static::$_db->insert(static::getTableName(),$this->getArrayCopy());
        if($ignorePrimaryKeyField) {
            $newId                                     = static::$_db->lastInsertId();
            $this->objectData[static::getPrimaryKey()] = $newId;
        }
        $this->changedFields = [];
        return $this->objectData[static::getPrimaryKey()];
    }

    public function sanitize() {
        $fields = static::getTableFields();
        if(!count($fields)) {
            throw new RuntimeException("There are no fields defined for ".get_class($this)." object!");
        }
        $sanitized = 0;
        foreach($this->objectData as $k=>$v) {
            if(!in_array($k,$fields)) {
                $sanitized++;
                unset($this->objectData[$k]);
                unset($this->changedFields[$k]);
            }
        }
        return $sanitized;
    }

    public function getArrayCopy() : array {
        return $this->objectData;
    }

    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }

    protected static function select($whereClause = null, $type = self::SELECT_ALL, $fields = null) {
        $types = ['all','row','one'];
        $type = in_array($type,$types) ? $type : self::SELECT_ALL;
        $w = [];
        if($fields) {
            foreach ($fields as &$field) {
                $field = "`".str_replace("`","",$field)."`";
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
        }
        if(count($w)) $sql .= " WHERE ".$w;
        $method = "get".ucfirst($type);
        return self::$_db->$method($sql,is_array($whereClause) ? $whereClause : []);
    }
} 