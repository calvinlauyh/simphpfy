<?php

/* 
 * Created by Hei
 */
/*
 * Represents a row of a Model
 */
class ModelDataRow{
    private $_columns;
    /*
     * Cosntructor
     * 
     * Import a list of columns from a Model schema
     * 
     * @params Array $columns, an array ofclumns
     */
    public function __construct($columns) {
        $this->setColumns($columns);
        foreach ($columns as $column => $schema) {
            $this->$column = NULL;
        }
    }
    
    /*
     * Getter
     */
    public function __get($name) {
        if (array_key_exists($name, $this->getColumns())) {
            return $this->$name;
        } else {
            throw new DatabaseDataRowException("Unrecognized column `$name` in Model row when __get()");
        }
    }
    
    /*
     * Setter
     */
    public function __set($name, $value) {
        if (array_key_exists($name, $this->getColumns())) {
            $this->$name = $value;
        } else {
            throw new DatabaseDataRowException("Unrecognized column `$name` in Model row when __set()");
        }
    }
    
    // Auto-generatd getter and setter
    private function getColumns() {
        return $this->_columns;
    }

    private function setColumns($columns) {
        $this->_columns = $columns;
    }


}
