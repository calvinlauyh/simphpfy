<?php

/* 
 * Created by Hei
 */

$operationList = array('create');

// Include the database configuration
require(CONFIG . 'datasource.php');

$return = FALSE;

$datasourceConfig = new DataSourceConfig();
$config = $datasourceConfig->config;
$requiredKey = array('datasource', 'host', 'username', 'password', 'database', 'prefix');
foreach($requiredKey as $key) {
    if (!array_key_exists($key, $config)) {
        throw new CLIException("Missing `{$key}` in DataSource configuration");
    }
}
$dsn = "mysql:dbname={$config['database']};host={$config['host']}";
$username = $config['username'];
$password = $config['password'];
if (!class_exists('PDO')) {
    throw new CLIException('PDO is missing from the server');
}
try {
    $PDO = new PDO($dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch(PDOException $e) {
    throw new CLIException($e->getMessage());
}
        
if (!is_readable(TEMP)) {
    throw new CLIException(TEMP . ' is not readable');
}
$migrationFilePath = TEMP . 'db_migration';
if (!($migrationFile = fopen($migrationFilePath, 'rb'))) {
    throw new CLIException("Unable to open  `{$migrationFilePath}`");
}

if (filesize($migrationFilePath) > 0) {
    $migrationContent = fread($migrationFile, filesize($migrationFilePath));
    $migration = json_decode($migrationContent, TRUE);
    if (!$migration['migrated']) {
        foreach($migration['pendingFile'] as $schemaFile) {
            $output = "===== {$schemaFile}: migrating ";
            echo padding($output);
            $dbDirectory = APP . 'db' . DS;
            $path = $dbDirectory . $schemaFile;
            if (!is_readable($path)) {
                throw new CLIException($path . ' is not readable');
            }
            $handle = fopen($path, 'rb');
            // use fread to read file memory-safely
            $content = fread($handle, filesize($path));
            
            $dom = new DOMDocument();
            $dom->loadXML($content);
            $table = $dom->getElementsByTagName('table')->item(0);
            
            if (($operation = $table->getAttribute('operation')) == '') {
                throw new CLIException('Operation is not specified');
            }
            if (array_key_exists($operation, $operationList)) {
                throw new CLIException("Unrecognized operation `$operation`");
            }
            if (($tablename = $table->getAttribute('name')) == '') {
                throw new CLIException('Missing `name` in table');
            }
            if (($model = $table->getAttribute('model')) == '') {
                throw new CLIException('Missing `name` in table');
            }
            
            if ($operation == 'create') {
                $columnSQL = '';
                
                $columns = $table->getElementsByTagName('column');
                $columnSQL .= '`id` INTEGER PRIMARY KEY AUTO_INCREMENT';
                foreach($columns as $column) {
                    if (($name = $column->getAttribute('name')) == '') {
                        throw new CLIException('Missing `name` in column');
                    }
                    if ($name == 'id') {
                        throw new CLIException('`id` is a reserverd column name');
                    }
                    if (($type = $column->getAttribute('type')) == '') {
                        throw new CLIException('Missing `type` in column');
                    }
                    $default = $column->getAttribute('default');
                    
                    $columnSQL .= ", `{$name}`";
                    if ($type == 'integer') {
                        $columnSQL .= ' INTEGER';
                    } elseif ($type == 'string') {
                        if (($length = $column->getAttribute('length')) > 255) {
                            throw new CLIException('`string` type must have length less than 255');
                        }
                        $columnSQL .= ' VARCHAR(255)';
                    } elseif ($type == 'text') {
                        if (($length = $column->getAttribute('length')) < 65537) {
                            $columnSQL .= ' TEXT';
                        } elseif ($length < 16777217) {
                            $columnSQL .= ' MEDIUMTEXT';
                        } else {
                            $columnSQL .= ' LONGTEXT';
                        }
                    } elseif ($type == 'float') {
                        $columnSQL .= ' DECIMAL(';
                        if (($decimal = $column->getAttribute('decimal')) == '') {
                            $decimal = 65;
                        } else {
                            if ($decimal > 65) {
                                throw new CLIException('`decimal` should be less than 65');
                            }
                        }
                        if (($precision = $column->getAttribute('precision')) == '') {
                            $precision = 30;
                        } else {
                            if ($precision > 30) {
                                throw new CLIException('`precision` should be less than 65');
                            }
                        }
                        $columnSQL .= $decimal . ', ' . $precision . ')';
                    } elseif ($type == 'array' || $type == 'object') {
                        $columnSQL .= ' TEXT';
                    } elseif ($type == 'email') {
                        $columnSQL .= ' VARCHAR(255)';
                    } elseif ($type == 'date') {
                        $columnSQL .= ' INTEGER';
                    }
                    
                    if (($default = $column->getAttribute('default')) != '') {
                        $columnSQL .= " DEFAULT '{$default}'";
                    }
                }
                echo "    {$operation}: {$tablename}" . PHP_EOL;
                $sql = "CREATE TABLE {$tablename} ({$columnSQL}) ENGINE=InnoDB  DEFAULT CHARSET=utf8";
                echo "Execute SQL query: {$sql}" . PHP_EOL;
                try {
                    $PDO->query($sql);
                } catch(PDOException $e) {
                    throw new CLIException($e->getMessage());
                }
                $output = "===== {$schemaFile}: migrated ";
                echo padding($output);
                array_push($migration['migratedFile'], array_shift($migration['pendingFile']));
            }
            
            
            fclose($handle);
        }
    }
}
$migration['migrated'] = TRUE;

$schemaContent = json_encode($migration);

$dbDirectory = APP . 'db' . DS;
if (!is_writable($dbDirectory)) {
    throw new CLIException($dbDirectory . ' is not writable');
}

/*
 * Update the db_migration file
 */
if (!($migrationFile = fopen($migrationFilePath, 'wb'))) {
    throw new CLIException('Unable to open and truncate' . $migrationFilePath);
}
if (fwrite($migrationFile, $schemaContent) === false) {
    throw new CLIException("Unable to save `{$migrationFilePath}`");
} else {
    fclose($migrationFile);
}
$return = TRUE;
