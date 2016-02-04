<?php

/*
 * !IMPORTANT!
 * NEVER MODIFY THE FOLLOWING UNTIL YOU ARE ABSOLUELY SURE WHAT YOU ARE DOING
 */

/* 
 * Check the execution environment is at the root
 */
if (end(explode(DIRECTORY_SEPARATOR, getcwd())) == 'bin'){
    exit('Please execute bin file in the root directory' . PHP_EOL);
}

require('custom.php');

// define constants

// the directory separator
define('DS', DIRECTORY_SEPARATOR);

// the path to the SimPHPfy directory
define('SIMPHPFY', 'Core' . DS);

// the path to the app directory
define('APP', 'app' . DS);

// the patht to the Exception directory
define('EXCEPTION', SIMPHPFY . 'Exception' . DS);

// the patht to the Router directory
define('ROUTER', SIMPHPFY . 'Router' . DS);

// the path to the Controller directory
define('SIMPHPFY_CONTROLLER', SIMPHPFY . 'Controller' . DS);

// the path to the Model directory
define('SIMPHPFY_MODEL', SIMPHPFY . 'Model' . DS);

// the path to the View directory
define('SIMPHPFY_VIEW', SIMPHPFY . 'View' . DS);

// the patht to the Library directory
define('SIMPHPFY_LIBRARY', SIMPHPFY . 'Library' . DS);

// the patht to the Utility directory
define('SIMPHPFY_UTILITY', SIMPHPFY . 'Utility' . DS);

// the path to the App Controller directory
define('CONTROLLER', APP . 'Controller' . DS);

// the path to the App Model directory
define('MODEL', APP . 'Model' . DS);

// the path to the App View directory
define('VIEW', APP . 'View' . DS);

// the path to the public directory
define('APP_PUBLIC', APP . 'public' . DS);

// the path to the public directory
define('CONFIG', APP . 'Config' . DS . rtrim($configProfile, '/') . DS);

// the path to the temp directory
define('TEMP', APP . 'temp' . DS);

// the path to the temp directory
define('TEMP_VIEW', TEMP . 'View' . DS);

// the path to the bin directory
define('BIN', 'bin' . DS);

// the path to the bin scaffold directory
define('BIN_SCAFFOLD', BIN . 'Scaffold' . DS);

require(BIN . 'CLIException.php');

function exception_handler($exception) {
    echo 'ERROR: ' . $exception->getMessage() . PHP_EOL;
    $trace = $exception->getTrace();
    if (count($trace) > 0) {
        var_dump($exception->getTrace());
    }
}
set_exception_handler('exception_handler');

function padding(&$content) {
    $paddingSize = 80-strlen($content);
    for ($i=0; $i<$paddingSize; $i++) {
        $content .= '=';
    }
    return $content. PHP_EOL;
}
