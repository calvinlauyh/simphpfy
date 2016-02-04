<?php

/* 
 * Created by Hei
 */
if (isset($argv[1])) {
    $controller = $argv[1];
} else {
    throw new CLIException('Missing controller name for generation');
}

$controllerFile = $controller . '.php';
$controllerContent = "<?php

class {$controller} extends Controller{
    public function Index() {
    }
    
    public function Create() {
    }
    
    public function Edit(\$id){
    }
    
    public function Show(\$id) {
    }
    
    public function Destroy(\$id) {
    }
}

";
$controllerPath = CONTROLLER . $controllerFile;
if (file_exists($controllerPath)) {
    throw new CLIException("Controller file `{$controllerPath}` already existed");
}
if (!$controllerHandle = fopen($controllerPath, 'wb')) {
    throw new CLIException("Unable to open nor create controller file `{$controllerPath}`");
}
if (fwrite($controllerHandle, $controllerContent) === false) {
    unlink($controllerPath);
    throw new CLIException("Unable to write content to controller file `{$controllerPath}`");
}
$output = "===== Controller file created: {$controllerPath} ";
echo padding($output);

$return = $controllerPath;
