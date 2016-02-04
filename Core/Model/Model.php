<?php

/* 
 * Created by Hei
 */
class Model{
    /*
     * Holds a reference to the DataSource object instance
     * 
     * @var DataSource
     */
    private $datasource;
    
    /*
     * The name of the Model
     * 
     * @var String
     */
    private $model;
    
    /*
     * The class name of the Model
     * 
     * @var String
     */
    private $modelClass;
    
    /*
     * The schema
     * 
     * @var Array
     */
    private $schema;
    
    /*
     * Last error in update/insertion
     * 
     * @var Array
     */
    public $lastError;
    
    /*
     * Constructor
     * 
     * @param DataSource $datasource The reference to the datasource object
     * @param String $model The name of the Model class without ending `Model`, 
     * i.e. Member but NOT MemberModel
     */
    function __construct($datasource, $model) {
        if (!is_a($datasource, 'DataSource')) {
            throw new InvalidModelException('$datasource is not an valid DataSource instance');
        }
        $this->setDatasource($datasource);
        $this->setSchema($this->_loadModel($model));
    }
    
    /*
     * Check and load the Model file
     * 
     * @param String $model The name of the Model, i.e. MemberModel
     * 
     * @return String the schema of the Model
     */
    private function _loadModel($model){
        $modelClass = $model . 'Model';
        $this->setModel($model);
        $this->setModelClass($modelClass);
        
        $path = MODEL . $modelClass . '.php';
        if (!class_exists($modelClass)) {
            if (!file_exists($path)) {
                echo $path;
                throw new MissingModelException($model);
            }
            require $path;
            if (!class_exists($modelClass)) {
                throw new MissingModelException($model);
            }
        }
        $modelObj = new $modelClass();
        if (!property_exists($modelObj, 'schema')) {
            throw new InvalidModelException("Model `{$model}` has no schema attribute");
        }
        $schema = json_decode($modelObj->schema, true);
        if ($schema === NULL) {
            throw new InvalidModelException("Invalid schema definition in Model `{$model}`");
        }
        return $schema;
    }
    
    /*
     * check if the current Model has `has` relationship with another Model
     * 
     * @params String $model The name of Model
     * @return Boolean Flag indicating the truthness
     */
    public function has_relationship($model) {
        if ($model == $this->getModel()) {
            return TRUE;
        } elseif (isset($this->getSchema()['hasMany']) && in_array($model, $this->getSchema()['hasMany'])) {
            return TRUE;
        } elseif (isset($this->getSchema()['hasOne']) && in_array($model, $this->getSchema()['hasOne'])) {
            return TRUE;
        } elseif (isset($this->getSchema()['belongsTo']) && in_array($model, $this->getSchema()['belongsTo'])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /*
     * Validate a ModelDataRow against the Model schema
     * 
     * @param ModelDataRow $datarow, The ModelDataRow which contains the values
     * to be validated
     * 
     * @return Boolean, Flag indicating whether all columns are validated
     */
    public function validateColumns($datarow) {
        if (!is_a($datarow, 'ModelDataRow')) {
            throw new InvalidModelException('Argument type mismatch (ModelDataRow expected, ' . gettype($datarow) . ' found)');
        }
        $error = array();
        foreach ($this->getSchema()['columns'] as $column => $columnSchema) {
            $value = $datarow->$column;
            $isRequired = FALSE;
            /*
             * Check if the field is required, i.e. must be filled
             */
            if (isset($columnSchema['rule'])) {
                if (in_array('required', $columnSchema['rule'])) {
                    $isRequired = TRUE;
                    if (!Validator::required($value)) {
                        $this->lastError[$column][] = 'required';
                        continue;
                    }
                }
            }
            if (!$isRequired) {
                /*
                 * If the column is not a required field, only performs 
                 * checking when the field is non-empty
                 */
                if (!Validator::nonEmpty($value)) {
                    continue;
                }
            }
            
            /*
             * Perform type checking
             */
            if ($columnSchema['type'] == 'array') {
                if (!Validator::arraytype($value)) {
                    $error[$column][] = 'array';
                }
                $datarow->$column = json_encode($datarow->$column);
            } elseif ($columnSchema['type'] == 'object'){
                if (!Validator::object($value)) {
                    $error[$column][] = 'object';
                }
                $datarow->$column = json_encode($datarow->$column);
            } elseif ($columnSchema['type'] == 'universial') {
                // no type checking needed
            } else {
                if (!call_user_func(array('Validator', $columnSchema['type']), $value)) {
                    $error[$column][] = $columnSchema['type'];
                }
            }
            /*
             * Perform rule checking
             */
            if (isset($columnSchema['rule'])) {
                foreach($columnSchema['rule'] as $rule) {
                    if (is_array($rule)) {
                        if (method_exists('Validator', $rule[0])) {
                            $parameters = array_slice($rule, 1);
                            array_unshift($parameters, $value);
                            if (!call_user_func_array(array('Validator', $rule[0]), $parameters)) {
                                $error[$column][] = $rule[0];
                            }
                        }
                    } else {
                        /*
                         * Special case, the rule is unique
                         */
                        if ($rule == 'unique') {
                            if ($datarow->id == NULL) {
                                if (!$this->unique($column, $value)) {
                                    $error[$column][] = 'unique';
                                }
                            } else {
                                if (!$this->unique($column, $value, $datarow->id)) {
                                    $error[$column][] = 'unique';
                                }
                            }
                        } else {
                            if (method_exists('Validator', $rule)) {
                                if (!call_user_func(array('Validator', $rule), $value)) {
                                    $error[$column][] = $rule;
                                }
                            }
                        }
                    }
                }
            }   
        }
        if (count($error) > 0) {
            $this->lastError = $error;
            return FALSE;
        }
        return TRUE;
    }
    
    /*
     * Update and save a ModelDataRow into the DataSource
     * 
     * @param ModelDataRow $datarow, The ModelDataRow which contains the updated
     * values
     * 
     * @return Boolean, save successful
     */
    public function edit($datarow) {
        if (!is_a($datarow, 'ModelDataRow')) {
            throw new InvalidModelException('Argument type mismatch (ModelDataRow expected, ' . gettype($datarow) . ' found)');
        }
        if (!$this->validateColumns($datarow)) {
            return FALSE;
        }
        
        if ($this->getDatasource()->getType() == DataSource::DATABASE) {
            $queryHelper = new DatabaseORM($this->getDatasource()->getConnector(), self::getTableFromModel($this->getModel()), $this->getSchema());
            return call_user_func(array($queryHelper, 'edit'), $datarow);
        } else {
            // TODO:
            throw new InvalidModelException('Non-Database type DataSource is not supported yet');
        }
    }
    
    /*
     * Insert an record into the data source
     * @param ModelDataRow $datarow, The ModelDataRow which contains the updated
     * values
     * 
     * @return Boolean, save successful
     */
    function insert($datarow) {
        if (!is_a($datarow, 'ModelDataRow')) {
            throw new InvalidModelException('Argument type mismatch (ModelDataRow expected, ' . gettype($datarow) . ' found)');
        }
        if (!$this->validateColumns($datarow)) {
            return FALSE;
        }
        
        if ($this->getDatasource()->getType() == DataSource::DATABASE) {
            $queryHelper = new DatabaseORM($this->getDatasource()->getConnector(), self::getTableFromModel($this->getModel()), $this->getSchema());
            return call_user_func(array($queryHelper, 'insert'), $datarow);
        } else {
            // TODO:
            throw new InvalidModelException('Non-Database type DataSource is not supported yet');
        }
    }
    
    /*
     * Call Query Helper function from Model
     */
    function __call($name, $arguments) {
        if ($this->getDatasource()->getType() == DataSource::DATABASE) {
            $queryHelper = new DatabaseORM($this->getDatasource()->getConnector(), self::getTableFromModel($this->getModel()), $this->getSchema());
            return call_user_func_array(array($queryHelper, $name), $arguments);
        } else {
            // TODO:
            throw new InvalidModelException('Non-Database type DataSource is not supported yet');
        }
    }
    
    /*
     * Get a Model object of another Model
     */
    function __get($model) {
        if ($this->has_relationship($model)) {
            return new Model($this->getDatasource(), $model);
        } else {
            throw new InvalidModelException("`{$model}` has no relationship with current schema");
        }
    }
   
    
    /*
     * Return the ModelDataRow for the current Model
     * 
     * @params Array $params, An array of parameters going to feed into the 
     * DataRow, the Array can be a POST/GET prepared by the MVC or a pure array
     * containing all the columns as array key and parameters as array value
     * 
     * @return ModelDataROw
     */
    public function create($params='') {
        $row = array();
        /*
         * The $params is a GET/POST parameters array
         */
        if (isset($params['Model'])) {
            $params = $params['Model'][$this->getModel()];
        }
        
        /*
         * Prepare the ModelDataRow by provding a column schema
         */
        foreach ($this->getSchema()['columns'] as $column => $schema) {
            $row[$column] = '';
        }
        $datarow = new ModelDataRow($row);
        
        /*
         * Feed in parameters if needed
         */
        if ($params != '') {
            foreach ($row as $column => $dummy) {
                if (isset($params[$column])) {
                    $datarow->$column = $params[$column];
                }
            }
        }
        return $datarow;
    }
    
    public static function getTableFromModel($model) {
        return strtolower($model);
    }
    
    public function getDatasource() {
        return $this->datasource;
    }

    public function getModel() {
        return $this->model;
    }
    
    public function getSchema() {
        return $this->schema;
    }
    
    public function getModelClass() {
        return $this->modelClass;
    }
    
    private function setDatasource($datasource) {
        $this->datasource = $datasource;
    }

    private function setModel($model) {
        $this->model = $model;
    }

    private function setSchema($schema) {
        $this->schema = $schema;
    }
    
    private function setModelClass($modelClass) {
        $this->modelClass = $modelClass;
    }

}
