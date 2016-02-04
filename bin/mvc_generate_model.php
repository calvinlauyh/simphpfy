<?php

/* 
 * Created by Hei
 */
if (isset($argv[1])) {
    $controller = $argv[1];
} else {
    throw new CLIException('Missing controller name for generation');
}
$modelFile = $controller . 'Model.php';
$modelContent = "<?php

class {$controller}Model{
    public \$schema = '{
        \"columns\": {
            \"id\": {
                \"type\": \"integer\"
            }
        }
    }';
}

";
$modelPath = MODEL . $modelFile;

if (file_exists($modelPath)) {
    throw new CLIException("Model file `{$modelPath}` already existed");
}
if (!$modelHandle = fopen($modelPath, 'wb')) {
    throw new CLIException("Unable to open nor create model file `{$modelPath}`");
}
if (fwrite($modelHandle, $modelContent) === false) {
    unlink($modelPath);
    throw new CLIException("Unable to write content to model file `{$modelPath}`");
}
$output = "===== Model file created: {$modelPath} ";
echo padding($output);

$return = $modelPath;
