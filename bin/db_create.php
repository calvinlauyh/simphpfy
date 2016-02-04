<?php

/* 
 * Created by Hei
 */
if (isset($argv[1])) {
    $modelName = $argv[1];
    $tableName = strtolower($modelName);
} else {
    throw new CLIException('Missing table name for creation');
}

// Append schema to db_migration
if (!is_readable(TEMP)) {
    throw new CLIException(TEMP . ' is not readable');
}
$migrationFilePath = TEMP . 'db_migration';
if (!($migrationFile = fopen($migrationFilePath, 'a+b'))) {
    throw new CLIException("Unable to open nor create `{$migrationFilePath}`");
}

$schemaFileName = time() . '_' . 'create_' . $tableName . '.schema';
/*
 * Check if the migration file is empty
 */
if (filesize($migrationFilePath) > 0) {
    $migrationContent = fread($migrationFile, filesize($migrationFilePath));
    $migration = json_decode($migrationContent, TRUE);
    $migration['migrated'] = FALSE;
    $migration['pendingFile'][] = $schemaFileName;
} else {
    $migration = array(
        'migrated' => FALSE, 
        'pendingFile' => array(
            $schemaFileName
        ), 
        'migratedFile' => array()
    );
}

$schemaContent = json_encode($migration);

$dbDirectory = APP . 'db' . DS;
if (!is_writable($dbDirectory)) {
    throw new CLIException($dbDirectory . ' is not writable');
}
$schemaFilePath = $dbDirectory . $schemaFileName;
/*
 * If the schema file exists, that means the user is executing the command too
 * frequently and thus yield the same unix timestamp
 */
if (file_exists($schemaFilePath)) {
    throw new CLIException('Please re-run this command');
}
if (!($schemaFile = fopen($schemaFilePath, 'w+'))) {
    throw new CLIException('Unable to create ' . $schemaFilePath);
}

// prepare a table schema template
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$table = $dom->createElement('table');
$table->setAttribute('operation', 'create');
$table->setAttribute('name', $tableName);
$table->setAttribute('model', $modelName);
$column = $dom->createElement('column');
$column->setAttribute('name', '');
$column->setAttribute('type', '');
$table->appendChild($column);
$dom->appendChild($table);
/*
 * The default indentation of DOMDocument is 2 spaces, replace them with 4 
 * spaces instead 
 */
$content = preg_replace_callback('@^( +)<@m', function($matches) { 
    return str_repeat(' ', ((int) strlen($matches[1]/2)*4)) .'<';  
}, $dom->saveXML());

/*
 * Guratee that both schema file is saved and the db_migration is updated 
 * atomically
 */
if (fwrite($schemaFile, $content) === false) {
    throw new CLIException('Unable to save ' . $schemaFilePath);
} else {
    fclose($schemaFile);

    if (!($migrationFile = fopen($migrationFilePath, 'wb'))) {
        throw new CLIException('Unable to open and truncate' . $migrationFilePath);
    }
    if (fwrite($migrationFile, $schemaContent) === false) {
        throw new CLIException("Unable to save `{$migrationFilePath}`");
        unlink($schemaFilePath);
    } else {
        fclose($migrationFile);
    }
}
$output = '===== Created schema file: ' . $schemaFilePath . ' ';
echo padding($output);
$return = array(
    'file' => $schemaFileName, 
    'path' => $schemaFilePath
);
