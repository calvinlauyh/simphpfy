<?php

/* 
 * Created by Hei
 */
/*
 * The ModelDatabaseORM is a Obejct Relational Model framework 
 * The nature of the ModelDatabaseORM is to work with the Model to validate for
 * column existence in a table according to the schema defined.
 */
class DatabaseORM{
    /*
     * List of possible join types
     * 
     * @var Array
     */
    private $_JOIN_TYPES = array('INNER JOIN', 'LEFT JOIN', 'LEFT OUTER JOIN', 'RIGHT JOIN', 'RIGHT OUTER JOIN');
    /*
     * The PDO object
     */
    private $PDO;
    /*
     * The table this ModelDatabaseORM is assigned with
     * 
     * @var String
     */
    private $currentTable;
    /*
     * List of schemas
     * 
     * @var Array
     */
    private $schemas = array();
    /*
     * List of table with relationships
     */
    private $relationships = array();
    /*
     * List of foreign key
     */
    private $foreignKeys = array();
    /*
     * SQL related operation
     * 
     * @var Mixed
     */
    private $distinct = FALSE;
    private $select = array();
    public $selectedColumns = array();
    private $from = array();
    private $jointTableName = array();
    private $where = array();
    private $orderby = array();
    private $limit = null;
    private $offset = null;
    private $groupby = array();
    private $having = array();
    private $union = array();
    /*
     * List of bihnded parameters
     * 
     * @var Array
     */
    private $parameters = array();
    
    // TODO: aggregate, groupby, having
    
    /*
     * Constructor
     * 
     * @param PDO $PDO A reference to the PDO object
     * @param Model $model The name of the model class, i.e. MemberModel
     */
    function __construct($PDO, $table, $schema){
        if (!is_a($PDO, 'PDO')) {
            throw new DatabaseORMException('$PDO supplied is not an instance of PDO');
        }
        $this->setPDO($PDO);
        $this->setCurrentTable($table);
        $this->addSchemas($table, '', $schema);
        $this->from[] = $table;
        $this->jointTableName[$table] = $table;
    }
    
    /*
     * Check and load the Model file
     * 
     * @param String $model The name of the Model without ending `Model`, i.e.
     * Member but NOT MemberModel
     * @param String $alais The alias of the table
     * 
     * @return String the schema of the Model
     */
    private function _loadModel($model, $alias=''){
        $modelClass = $model . 'Model';
        if (!class_exists($modelClass)) {
            $path = MODEL . $modelClass . '.php';
            if (!file_exists($path)) {
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
        if ($alias == '') {
            $this->addSchemas(Model::getTableFromModel($model), '', $schema);
        } else {
            $this->addSchemas(Model::getTableFromModel($model), $alias, $schema);
        }
        return $schema;
    }
    
    /*
     * Import schema from a ModelDatabaseORM object instance
     * 
     * @param ModelDatabaseORM $queryObj The ModelDatabaseORM object instance
     * 
     * @return String the schema of the ModelDatabaseORM
     */
    private function _importSchema($alias, $queryObj) {
        if (!is_a($queryObj, 'DatabaseORM')) {
            throw new DatabaseORMException(array('Argument type mismatch for `$queryObj`. (DatabaseORM expected, ' . gettype($queryObj) . ' found)', '_importSchema'));
        }
        $schema = array();
        foreach ($queryObj->selectedColumns as $column) {
            $schema[$column] = array();
        }
        $this->addSchemas('', $alias, array('columns' => $schema));
    }
    
    /*
     * Check if a specified column is defined in a schema(i.e. the table 
     * contains such a column)
     * 
     * @param String $column The column
     * @param String $table The table/alias of table
     * 
     * @return Mixed The schema in array if the column exists or FALSE if the 
     * column does not exist
     */
    private function in_table($column, $table) {
        if ($schema = $this->getSchemas($table)) {
            return (isset($schema['columns'][$column]))? $schema: FALSE;
        } else {
            return FALSE;
        }
    }
    
    /* 
     * Add Distinct keyword
     * 
     * @param Boolean $distinct Flag indicating whether the select distinct 
     * rows only
     * 
     * @return DatabaseORM instance
     */
    public function distinct($distinct=TRUE) {
        if (is_bool($distinct)) {
            $this->distinct = $distinct;
        } else {
            throw new DatabaseORMException(array('Argument type mismatch for `$distinct`. (Boolean expected, ' . gettype($distinct) . ' found)', 'distinct'));
        }
        return $this;
    }
    
    /*
     * Check if a particular column/column alias can be added to select clause
     * 
     * @param String $table The name of the table
     * @param String $column The name of the column/column alias
     * 
     * @return Boolean Flag indicating whether the columan is selected
     */
    private function is_selectable($table, $column) {
        if ($column != '*') {
            if (!$this->in_table($column, $table)) {
                throw new DatabaseORMException(array("Unrecognized column `{$column}` in Model ` ". ucfirst($table) . '`', 'select'));
            }
        }
        return true;
    }
    
    /*
     * Add select
     * 
     * @params Mixed $column[], the field or an array descriping the field
     * 
     * @return ModelDatabaseORM instance
     */
    public function select() {
        if (func_num_args() == 0) {
            $this->select[] = '';
        } else {
            foreach(func_get_args() as $column) {
                $this->select[] = $column;
            }
        }
        return $this;
    }
    
    /*
     * Add select clause
     * 
     * @return String query of select cluase
     */
    private function selectClause() {
        $select = '';
        $i = 0;
        $end = count($this->select);
        foreach($this->select as $column) {
            if ($column == '') {
                if ($this->is_selectable($this->getCurrentTable(), '*')) {
                    $select .= "{$this->getCurrentTable()}.*";
                    $schema = $this->getSchemas($this->getCurrentTable());
                    foreach ($schema['columns'] as $columnName => $schema) {
                        $this->selectedColumns[] = $columnName;
                    }
                }
            } elseif (is_array($column)) {
                /*
                 * $columns in the form array(model, column)
                 */
                if (count($column) == 2) {
                    $table = Model::getTableFromModel($column[0]);
                    if ($this->is_selectable($table, $column[1])) {
                        $select .= "{$table}.{$column[1]}";
                        $this->selectedColumns[] = $column[1];
                    }
                /*
                 * $columns in the form array(model, column, alias)
                 */
                } elseif (count($column) == 3) {
                    $table = Model::getTableFromModel($column[0]);
                    if ($this->is_selectable($table, $column[1], $column[2])) {
                        $select .= "{$table}.{$column[1]} AS {$column[2]}";
                        $this->selectedColumns[] = $column[2];
                    }
                } else {
                    throw new DatabaseORMException(array('Unrecognized $column array format, array with ' . count($column) . ' indices found', 'select'));
                }
            } else {
                if ($this->is_selectable($this->getCurrentTable(), $column)) {
                    $select .= "{$this->getCurrentTable()}.{$column}";
                    if ($column == '*') {
                        $schema = $this->getSchemas($this->getCurrentTable());
                        foreach ($schema['columns'] as $columnName => $schema) {
                            $this->selectedColumns[] = $columnName;
                        }
                    } else {
                       $this->selectedColumns[] = $column;
                    }
                }
            }
            $select .= ((++$i == $end)? ' ': ', ');
        }
        return $select;
    }
    
    /*
     * Check if a particular column/column alias can be added to where clause
     * 
     * @param String $table The name of the table
     * @param String $column The name of the column/column alias
     * 
     * @return Boolean Flag indicating whether the columan can be added
     */
    private function is_conditionable($table, $column) {
        if (!$this->in_table($column, $table)) {
            throw new DatabaseORMException(array("Unrecognized column `{$column}` in Model ` ". ucfirst($table) . '`', 'where'));
        }
        return true;
    }
    
    /* 
     * Add where
     * 
     * @params logical operator Optional logical operator
     * @params expression The expression
     * @params params List of parameters to be binded
     */
    public function where() {
        if (func_num_args() < 2) {
            throw new DatabaseORMException(array('Argument number mismatch', 'where'));
        } else {
            /*
             * The first argument should be a logical operator, the default 
             * operator is AND
             */
            $args = func_get_args();
            if ((strtoupper($args[0]) != 'AND') && (strtoupper($args[0]) != 'OR')) {
                array_unshift($args, 'AND');
            }
            $this->where[] = $args;
        }
        return $this;
    }
    
    /*
     * Add where clause
     * 
     * @return String The where clause
     */
    private function whereClause() {
        $where = '';
        $i = 0;
        foreach($this->where as $condition) {
            if ($i++ != 0) {
                $where .= ' ' . $condition[0];
            }
            $pointer = 2;
            $where .= preg_replace_callback('@:(query|column|value)@', function($matches) use($condition, &$pointer, $where){
                if ($matches[1] == 'column') {
                    // columan in current table
                    if (is_string($condition[$pointer])) {
                        if ($this->is_conditionable($this->getCurrentTable(), $condition[$pointer])) {
                            $expr = "{$this->getCurrentTable()}.{$condition[$pointer]}";
                        }
                    // column with specified table
                    } elseif (is_array($condition[$pointer])) {
                        $table = Model::getTableFromModel($condition[$pointer][0]);
                        if ($this->is_conditionable($table, $condition[$pointer][1])) {
                            $expr = "{$table}.{$condition[$pointer][1]}";
                        }
                    }
                } elseif ($matches[1] == 'query') {
                    if (is_a($condition[$pointer], 'DatabaseORM')) {
                        $expr = ' (' . $condition[$pointer]->toString() . ')';
                    }
                } else {
                    $expr = ':' . count($this->parameters);
                    $this->parameters[$expr] = $condition[$pointer];
                }
                $pointer++;
                return ' ' . $expr;
            }, $condition[1]);
        }
        return $where;
    }
    
    /*
     * Find the join condition for two tables by searching the foreign key in 
     * schemas
     * 
     * @param String $table, The name of the table
     * @param String $foreignTable, The name of the table to refererence
     * 
     * @return Array, the name of the local key and the reference key
     */
    private function _joinCondition($table, $foreignTable) {
        if (isset($this->foreignKeys[$table])) {
            foreach ($this->foreignKeys[$table] as $foreignKey) {
                $foreignModel = $this->getSchemas($foreignTable)['table'];
                if (Model::getTableFromModel($foreignKey['reference']) != $foreignTable) {
                    continue;
                }
                return array(
                    $foreignKey['localKey'], 
                    $foreignKey['referenceKey']
                );
            }
        }
        throw new DatabaseORMException(array("No foreign key for table `$table` is specified in schema", 'join'));
    }
    
    /*
     * Join a table
     * 
     * $alias, $foreignKey and $referenceKey are optional arguments. However, 
     * $foreignKey and $referenceKey work as a pair and must both be presented
     * when you specific them
     * 
     * @param Mixed $model The name of the model or a ModelDatabaseORM instance
     * @param String $alias The alias of the table
     * @param String $foreignKey The name of the local column of the join 
     * @param String $referenceKey The name of the referenced column of the join
     * 
     * @return ModelDatabaseORM instance
     */
    public function join() {
        if (func_num_args() == 0) {
            throw new DatabaseORMException(array('Argument number mismatch', 'join'));
        } else {
            /* 
             * Allow multiple tables-joint at once
             */
            foreach(func_get_args() as $join) {
                $joinType = ' INNER JOIN';
                $model = $alias = $foreignModel = $from = '';
            
                /*
                 * join in pure string
                 */
                if (is_string($join)) {
                    $model = $join;
                } elseif (is_array($join)) {
                    if (isset($join['model'])) {
                        /* 
                         * The array is an associative array
                         */
                        $model = $join['model'];
                        if (isset($join['joinType'])) {
                            if (!in_array(strtoupper($join['joinType']), $this->_JOIN_TYPES)) {
                                throw new DatabaseORMException(array("Unrecognized table join type", 'join'));
                            }
                            $joinType = strtoupper($join['joinType']);
                        }
                        if (isset($join['alias'])) {
                            $alias = $join['alias'];
                        }
                        if (isset($join['foreignModel'])) {
                            $foreignModel = $join['foreignModel'];
                        }
                    } elseif (count($join) == 2) {
                        /*
                         * join in the form array(model, alias)
                         */
                        $model = $join[0];
                        $alias = $join[1];
                    } else {
                        throw new DatabaseORMException(array("Unrecognized array format", 'join'));
                    }
                }
                
                if (is_string($model)) {
                    $table = Model::getTableFromModel($model);
                    if (!in_array($table, $this->relationships)) {
                        throw new DatabaseORMException(array("`$table` has no relationship with the current schema", 'join'));
                    }
                    $this->_loadModel($model, $alias);
                    
                    if ($foreignModel == '') {
                        $from = ', ' .$table;
                    } else {
                        $foreignTable = Model::getTableFromModel($foreignModel);
                        $keys = $this->_joinCondition($table, $this->jointTableName[$foreignTable]);
                        $join_condition = ' ' . (($alias=='')? $table: $alias) . ".{$keys[0]} = {$foreignTable}.{$keys[1]}";
                        $from = $joinType . ' ' . $table . (($alias=='')?'':' AS ' . $alias) . ' ON' . $join_condition;
                    }
                    
                    $this->jointTableName[($alias=='')? $table: $alias] = $table;
                } elseif (is_a($model, 'DatabaseORM')) {
                    if ($alias == '') {
                        throw new DatabaseORMException(array("Subquery must have alias", 'join'));
                    }
                    $from = ', (' . $model->toString() . ')';
                    // The schema is not ready until we flush the object
                    $model->flush();
                    $this->_importSchema($alias, $model);
                }
                
                if (($alias != '') && ($foreignModel == '')) {
                    $from .= (($alias == '')? '': ' AS ' . $alias);
                }
//                if (in_array($table, $this->from)) {
//                    throw new DatabaseORMException(array("`$table` has already joint to query", 'join'));
//                }
                $this->from[] = ' ' . $from;
            }
        }
        
        return $this;
    }
    
    /*
     * Check if a particular column/column alias can be added to where clause
     * 
     * @param String $table The name of the table
     * @param String $column The name of the column/column alias
     * 
     * @return Boolean Flag indicating whether the columan can be added
     */
    private function is_orderable($table, $column) {
        if (!$this->in_table($column, $table)) {
           throw new DatabaseORMException(array("Unrecognized column `{$column}` in table {$table}`", 'order'));
        }
        return true;
    }
    
    /*
     * Add order
     * 
     * @params Array The order information
     * 
     * @return ModelDatabaseORM instance
     */
    public function order(){
        if (func_num_args() == 0) {
            throw new DatabaseORMException(array('Argument number mismatch', 'order'));
        } else {
            foreach(func_get_args() as $column) {
                $this->orderby[] = $column;
            }
        }
        return $this;
    }
    
    /*
     * Add order clause
     * 
     * @return String The order clause
     */
    private function orderClause() {
        $orderby = '';
        $i = 0;
        foreach($this->orderby as $ordering) {
            if ($i++ != 0) {
                $orderby .= ',';
            }
            if (is_string($ordering)) {
                /*
                 * in the form (column)
                 */
                if ($this->is_orderable($this->getCurrentTable(), $ordering)) {
                    $orderby .= " {$this->getCurrentTable()}.{$ordering}";
                }
            } elseif (is_array($ordering)) {
                if (count($ordering) == 2) {
                    /* 
                     * in the form (column, ordering) or (model, column)
                     */
                    if (strtoupper($ordering[1]) == 'ASC' || strtolower($ordering[1]) == 'DESC') {
                        if ($this->is_orderable($this->getCurrentTable(), $ordering[0])) {
                            $orderby .= " {$this->getCurrentTable()}.{$ordering[0]} {$ordering[1]}";
                        }
                    } else {
                        $table = Model::getTableFromModel($ordering[0]);
                        if ($this->is_orderable($table, $ordering[1])) {
                            $orderby .= " {$table}.{$ordering[1]}";
                        }
                    }
                }elseif (count($ordering) == 3) {
                    /*
                     * in the form (model, column, ordering)
                     */
                    $table = Model::getTableFromModel($ordering[0]);
                    if ($this->is_orderable($table, $ordering[1])) {
                        if (strtoupper($ordering[2]) != 'ASC' && strtoupper($ordering[2]) != 'DESC') {
                            throw new DatabaseORMException(array("Unrecognized ordering. (ASC|DESC expected, {$ordering[2]} found)" , 'order'));
                        }
                        $orderby .= " {$table}.{$ordering[1]} {$ordering[2]}";
                    }
                }
            }
        }
        return ' ' . $orderby;
    }
    
    /*
     * Convert the clauses into a SQL Query
     * 
     * Performs all necessary checking on all the calauses, including a 
     * validation of field against the schema
     * 
     * @return String SQL Query
     */
    public function flush() {
        $this->parameters = [];
        
        $select = 'SELECT';
        if ($this->distinct) {
            $select .= ' DISTINCT';
        }
        if (count($this->select) == 0) {
            $select .= ' *';
        } else {
            $select .= ' ' . $this->selectClause();
        }
        $where = ' WHERE' . $this->whereClause();
        
        $order = ' ORDER BY' . $this->orderClause();
        
        $from = ' FROM';
        $i = 0;
        $end = count($this->from);
        foreach($this->from as $fromClause) {
            $from .= ' '. $fromClause;
        }
        
        $union = '';
        foreach($this->union as $unionClause) {
            $union .= ' UNION' . $unionClause;
        }
        
        $query = ((count($this->select) > 0)? $select: '') . 
                ((count($this->from) > 0)? $from: '') . 
                ((count($this->where) > 0)? $where: '') . 
                ((count($this->orderby) > 0)? $order: '') . 
                ((count($this->union) > 0)? $union: '');
        return $query;
    }
    
    /*
     * Alias of flush
     * 
     * @return String SQL Query
     */
    public function toString() {
        return $this->flush();
    }
    
    /*
     * Execute a SELECT query and return the PDOStatement
     */
    public function fetch() {
        $sql = $this->toString();
        $statement = $this->getPDO()->prepare($sql);
        try{
            $statement->execute($this->parameters);
            return $statement;
        }catch(PDOException $e){
            throw new DatabaseORMException(array($e->getMessage(), 'fetch'));
        }
    }
    
    /*
     * Exectute a SELECT query and return an array containing all of the result 
     * set rows
     * 
     * @return Array an array containing all of the result
     */
    public function fetchAll() {
        $statment = $this->fetch();
        return $statment->fetchAll();
    }
    
    /*
     * Execute a SELECT query and return an array containing all of the result
     * set rows in associative array format
     * 
     * @return Array, an array containing all of the result
     */
    public function fetchAssoc() {
        $statment = $this->fetch();
        return $statment->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /*
     * Execute a SELECT query and return an array containing all of the result 
     * set rows in DatabaseDataRow format
     * 
     * The DatabaseDataRow restricts that the data row fetch must be from a 
     * single table and the columns name and number match exactly with the 
     * schema. The function is a mean to provide an editable DatabaseDataRow
     * for update
     * 
     * @return Array, an array containing all of the result
     */
    //TODO
    
    /*
     * Union function
     * 
     * @params DatabaseORM|Array $query, The DatabaseORM query to Union or an
     * array containing the DatabaseORM and the Union type
     * 
     * @return ModelDatabaseORM instance
     */
    public function union($query) {
        $unionClause = '';
        if (is_array($query)) {
            if (count($query) != 2) {
                throw new DatabaseORMException(array('Unrecognized array format, (2 indices expected, ' . count($query) . ' indices found)', 'union'));
            }
            if (!in_array(strtoupper($query[0]), array('ALL', 'DISTINCT'))) {
                 throw new DatabaseORMException(array('Unrecognized union type', 'union'));
            }
            $unionClause .= $query[0];
            $query = $query[1];
        }
        if (!is_a($query, 'DatabaseORM')) {
            throw new DatabaseORMException(array('Argument type mismatch, (DatabaseORM expected, ' . gettype($query) . ' found)', 'union'));
        }
        $query->flush();
        $unionClause .= ' (' . $query->toString() . ')';
        $this->union[] = ' ' . $unionClause;
        return $this;
    }
    
    /*
     * insert(), edit(), destroy() related operations
     */
    
    /*
     * Check if the found column-value is unique among all the rows, id is 
     * provided to prevent counting the row itself
     * 
     * @param String $column, The column
     * @param String $value, The value to check for uniqueness
     * @param Integer $id, The id of the current row
     * 
     * @return Boolean, Result
     */
    public function unique($column, $value, $id='') {
        if (!isset($this->getSchemas($this->getCurrentTable())['columns'][$column])) {
            throw new DatabaseORMException("Unrecongized column `$column` in table {$this->getCurrentTable()}", 'unique');
        }
        $sql = "SELECT COUNT(*) FROM {$this->getCurrentTable()} WHERE {$column} = ?";
        /*
         * If $id is provided, check if the row is not exactly the current row
         */
        if ($id == '') {
            $params = array($value);
        } else {
            $sql .= ' AND NOT (id = ?)';
            $params = array($value, $id);
        }
        $stmt = $this->getPDO()->prepare($sql);
        $stmt->execute($params);
        return ($stmt->fetchColumn()==0);
    }
    
    /* 
     * Insert a DataRow into the Model
     * 
     * @param ModelDataRow $datarow, The ModelDataRow object
     * 
     * @return Boolean, Successful insertion
     */
    public function insert($datarow) {
        $schema = $this->getSchemas($this->getCurrentTable());
        $fields = $values = '';
        $params = array();
        $i = 0;
        foreach($schema['columns'] as $column => $rule) {
            $fields .= (($i == 0)? ' ': ', ') . $column;
            $placeholder = ':' . $column;
            $values .= (($i++ == 0)? '': ', ') . $placeholder;
            $params[$placeholder] = $datarow->$column;
        }
        try {
            $sql = 'INSERT INTO ' . $this->getCurrentTable() . ' (' . $fields . ') VALUES(' . $values . ')';
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute($params);
            return TRUE;
        } catch (PDOException $e) {
            throw new DatabaseORMException(array($e->getMessage(), 'insert'));
        }
    }
    
    /*
     * Destory a row from the Mode
     * 
     * @param Integer $id, The `id` of the row to destroy
     * 
     * @return Boolean, Successful destroy
     */
    public function destroy($id) {
        try {
            $sql = "DELETE FROM {$this->getCurrentTable()} WHERE id=:id";
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute(array(':id' => $id));
            return TRUE;
        } catch (PDOException $e) {
            throw new DatabaseORMException($e->getMessage());
        }
    }
    
    /*
     * Get the last insert id
     * 
     * @return Integer, The insert id
     */
    public function lastInsertId() {
        return $this->PDO->lastInsertId();
    }
    
    /* 
     * Update a DataRow in the Model
     * 
     * @param ModelDataRow $datarow, The ModelDataRow object
     */
    public function edit($datarow) {
        $schema = $this->getSchemas($this->getCurrentTable());
        $sets = '';
        $params = array();
        $i = 0;
        foreach($schema['columns'] as $column => $rule) {
            if ($column == 'id') {
                continue;
            }
            $placeholder = ':' . $column;
            $sets .= (($i++ == 0)? ' ': ', ') . $column . ' = ' . $placeholder;
            $params[$placeholder] = $datarow->$column;
        }
        try {
            $params[':id'] = $datarow->id;
            $sql = 'UPDATE ' . $this->getCurrentTable() . ' SET' . $sets . ' WHERE id = :id';
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute($params);
            return TRUE;
        } catch (PDOException $e) {
            throw new DatabaseORMException($e->getMessage());
        }
    }
    
    // auto-generated getter and setter
    private function getPDO() {
        return $this->PDO;
    }

    private function getCurrentTable() {
        return $this->currentTable;
    }
    
    private function getSchemas($table = '') {
        if ($table == '') {
            return $this->schemas;
        } else {
            if (isset($this->schemas[$table])) {
                return $this->schemas[$table];
            } else {
                return false;
            }
        }
    }
    
    private function setPDO($PDO) {
        $this->PDO = $PDO;
    }
    
    private function setCurrentTable($currentTable) {
        $this->currentTable = $currentTable;
    }

    private function setSchemas($schemas) {
        $this->schemas = $schemas;
    }
    
    private function addSchemas($table, $alias, $schema) {
        $schemaKey = ($alias == '')? $table: $alias;
        $this->schemas[$schemaKey] = $schema;
        $this->schemas[$schemaKey]['table'] = $table;
        if (isset($schema['hasMany'])) {
            foreach($schema['hasMany'] as $model) {
                $this->relationships[] = Model::getTableFromModel($model);
            }
        }
        if (isset($schema['hasOne'])) {
            foreach($schema['hasOne'] as $model) {
                $this->relationships[] = Model::getTableFromModel($model);
            }
        }
        if (isset($schema['belongsTo'])) {
            foreach($schema['belongsTo'] as $model) {
                $this->relationships[] = Model::getTableFromModel($model);
            }
        }
        if ($table != '') {
            if (isset($schema['foreignKey'])) {
                foreach($schema['foreignKey'] as $foreignKey => $reference) {
                    $this->foreignKeys[$table][] = array(
                        'reference' => Model::getTableFromModel($reference[0]), 
                        'localKey' => $foreignKey, 
                        'referenceKey' => $reference[1]
                    );
                    $this->foreignKeys[Model::getTableFromModel($reference[0])][] = array(
                        'reference' => $table, 
                        'localKey' => $reference[1], 
                        'referenceKey' => $foreignKey
                    );
                }
            }
        }
            
    }

}
