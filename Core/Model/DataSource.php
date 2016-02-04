<?php

/* 
 * Created by Hei
 */

class DataSource{
    /*
     * Pre-defined constant
     * 
     * @constant int
     */
    // DataSource type
    const DATABASE = 0;
    // Database type
    const DATABASE_MYSQL = 0;
    const DATABASE_SQLITE = 1;
    
    /*
     * An array of information describing the datasource
     * 
     * @var Array
     */
    private $config = array();
    
    /*
     * Type of DataSource
     * 
     * @var int
     */
    private $type;
    
    /*
     * Type of Database if DataSource is DATABASE
     * 
     * @var int
     */
    private $databaseType;
    
    /*
     * Holds a datasource connector instance, i.e. PDO for database connection
     * 
     * @var Object
     */
    private $connector;
    
    /*
    
    /*
     * Constructor
     * 
     * Responsible to connect to the datasource according to the configurations
     * 
     * @param DataSourceConfig $config
     */
    function __construct($config) {
        $this->setConfig($config);
        if ($config['datasource'] == 'mysql') {
            $dsn = "mysql:dbname={$config['database']};host={$config['host']}";
            $this->connectDatabase($dsn, $config['username'], $config['password']);
            $this->setDatabaseType(self::DATABASE_MYSQL);
        } elseif ($config['datasource'] == 'sqlite') {
            // TODO:
            throw new InvalidDataSourceException('SQLite is not supported');
            $dsn = "sqlite:{$options['database']}";
            $this->setType(self::DATABASE_SQLITE);
        } else {
            // TODO:
        }
    }
    
    /*
     * Connect to a database
     */
    function connectDatabase($dsn, $username='', $password='') {
        if (!class_exists('PDO')) {
            throw new InvalidDataSourceException('PDO is missing from the server');
        }
        try {
            $PDO = new PDO($dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $this->setConnector($PDO);
            $this->setType(self::DATABASE);
        } catch(PDOException $e) {
            throw new DatabaseConnectionException($e->getMessage());
        }
    }
    
    /*
     * Get the datasource array
     * 
     * @return Array The datasource array
     */
    public function getConfig() {
        return $this->config;
    }

    /*
     * Set the datasource array
     * 
     * @param Array THe data source array
     * 
     * @return void
     */
    public function setConfig($config) {
        $requiredKey = array('datasource', 'host', 'username', 'password', 'database', 'prefix');
        foreach($requiredKey as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidDataSourceException("Missing `{$key}` in DataSource configuration");
            }
        }
        $this->config = $config;
    }
    
    // auto-generated getter and setter
    public function getConnector() {
        return $this->connector;
    }
    public function getType() {
        return $this->type;
    }

    public function getDatabaseType() {
        return $this->databaseType;
    }

    private function setConnector($connector) {
        $this->connector = $connector;
    }
    
    private function setType($type) {
        $this->type = $type;
    }

    private function setDatabaseType($databaseType) {
        $this->databaseType = $databaseType;
    }

}
