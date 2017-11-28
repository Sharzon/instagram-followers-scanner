<?php

namespace App\Model;

use App\DB;

abstract class Model implements \ArrayAccess
{
    static protected $id_field;
    static protected $table;
    static protected $fields = [];

    protected $container = [];

    public function __construct()
    {
        foreach (static::$fields as $field) {
            $this[$field] = null;
        }
    }

    public function save()
    {
        $pdo = DB::getPDO();

        $id_array = $this->getIdArray();


        if (static::checkIfExists($id_array)) {
            $container_without_id = $this->container;

            foreach ($id_array as $field => $value) {
                unset($container_without_id[$field]);
            }

            $query = 'UPDATE `'.static::$table.'` SET '.DB::pdoSetFromDict($container_without_id).' WHERE '.
                     DB::pdoAndSequenceFromDict($id_array);
        } else {
            $query = 'INSERT INTO `'.static::$table.'` SET '.DB::pdoSetFromDict($this->container);
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($this->container);
    }

    protected function getIdArray()
    {
        $id_array = [];

        if (is_array(static::$id_field)) {
            foreach (static::$id_field as $id_field) {
                $id_array[$id_field] = $this[$id_field];
            }
        } else {
            $id_array[static::$id_field] = $this[static::$id_field];
        }

        return $id_array;
    }

    static public function checkIfExists($id)
    {
        $pdo = DB::getPDO();

        $id_field = static::$id_field;
        if (!is_array($id_field)) {
            $id_field = [static::$id_field];
        }

        $query = 'SELECT COUNT(*) FROM `'.static::$table.'` WHERE '.DB::pdoAndSequence($id_field);

        $stmt = $pdo->prepare($query);
        $stmt->execute($id);

        return $stmt->fetchColumn();
    }

    static public function find($id)
    {
        $pdo = DB::getPDO();
        $query = 'SELECT * FROM `'.static::$table.'` WHERE ';

        $id_field = static::$id_field;
        if (is_array($id_field)) {
            $id = array_intersect_key($id, array_flip($id_field));
            if (count($id) != count($id_field)) {
                throw new \Exception('Wrong id array got');
            }
        } else {
            $id = [ $id_field => $id ];
            $id_field = [ $id_field ];
        }

        $query .= DB::pdoAndSequence($id_field);
        $stmt = $pdo->prepare($query);
        $stmt->execute($id);

        $raw_model = $stmt->fetch();
        if ($raw_model == null) {
            return null;
        }

        $model = new static();

        foreach (static::$fields as $key) {
            $model[$key] = $raw_model[$key];
        }

        return $model;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}