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
use Eta\Model\BaseArrayObject;

abstract class ModelDataObject extends BaseArrayObject {

    protected $changedFields = [];

    public static function getInstanceById($id) : self {
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

    public function getObjectId() {
        return $this[static::getPrimaryKey()] ?? null;
    }

    public function get($tableField) {
        return isset($this[$tableField]) ? $this[$tableField] : null;
    }

    public function set($tableField, $value) {
        if(!in_array($tableField,static::getTableFields())) {
            throw new RuntimeException("Field $tableField not defined in array of getTableField method of " . __CLASS__ . " object!");
        }
        $this[$tableField] = $value;
        $this->changedFields[$tableField] = $value;
        return $this;
    }

    public function setMass(Array $data) {
        foreach($data as $field=>$value) {
            $this[$field] = $value;
            $this->changedFields[$field] = $value;
        }
        $this->sanitize();
    }

    public function update() {
        unset($this->changedFields[static::getPrimaryKey()]);

        if(!count($this->changedFields)) return false;
        $this->sanitize();

        $this->changedFields[static::getPrimaryKey()] = $this[static::getPrimaryKey()];

        static::$_db->update(static::getTableName(), $this->changedFields, [static::getPrimaryKey() => $this->getObjectId()]);
        $this->changedFields = [];
        return true;
    }

    public function add() {
        if(isset($this[static::getPrimaryKey()])) unset($this[static::getPrimaryKey()]);
        static::$_db->insert(static::getTableName(),$this->getArrayCopy());
        $newId = static::$_db->lastInsertId();
        $this[static::getPrimaryKey()] = $newId;
        $this->changedFields = [];
        return $newId;
    }

    public function sanitize() {
        $fields = static::getTableFields();
        $sanitized = 0;
        foreach($this as $k=>$v) {
            if(!in_array($k,$fields)) {
                $sanitized++;
                unset($this[$k]);
            }
        }
        return $sanitized;
    }
} 