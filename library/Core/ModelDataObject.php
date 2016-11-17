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
        return '';
    }

    protected static function getTableFields() : array{
        return [];
    }

    protected static function getDataForNegativeIds(int $id) {
        return false;
    }

    public function getObjectId() {
        return $this->objectData[static::getPrimaryKey()] ?? null;
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
        $newId = static::$_db->lastInsertId();
        $this->objectData[static::getPrimaryKey()] = $newId;
        $this->changedFields = [];
        return $newId;
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
} 