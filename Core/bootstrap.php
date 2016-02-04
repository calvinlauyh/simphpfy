<?php

/* 
 * Created by Hei
 */

/* 
 * !IMPORTANT!
 * NEVER MODIFY THIS FILE UNTIL YOU ARE ABSOLUELY SURE WHAT YOU ARE DOING
 * 
 * All request will be routed to this file which act as a staring point of
 * evetything. It will load and initialzie all the necessary components. 
 * Afterward it will finish the routing by executing the desired routing 
 * destination.
 */

// enable error display
ini_set("display_errors", 1);

// define constants

// the path to the app directory
define('APP', SIMPHPFY_PATH . 'app' . DS);

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

// the path to the Library directory
define('SIMPHPFY_LIBRARY', SIMPHPFY . 'Library' . DS);

// the path to the Utility directory
define('SIMPHPFY_UTILITY', SIMPHPFY . 'Utility' . DS);

// the path to the App Controller directory
define('CONTROLLER', APP . 'Controller' . DS);

// the path to the App Model directory
define('MODEL', APP . 'Model' . DS);

// the path to the App View directory
define('VIEW', APP . 'View' . DS);

// the path to the Assert directory
define('ASSERT', APP . 'Assert' . DS);

// the path to the public directory
define('APP_PUBLIC', APP . 'public' . DS);

// the path to the configuration file
define('CONFIG', APP . 'Config' . DS . rtrim($configProfile, '/') . DS);

// the path to the temp directory
define('TEMP', APP . 'temp' . DS);

// the path to the temp directory
define('TEMP_VIEW', TEMP . 'View' . DS);

// Load the SimPHPfy base class into the system
require(SIMPHPFY . 'SimPHPfy.php');

// define a automatic class loader
spl_autoload_register(array('SimPHPfy', 'load'));

// Setup the EXCEPTION package
SimPHPfy::package(EXCEPTION);

// TODO: implement an exception handler
function exception_handler($exception) {
    ob_clean();
    echo '<body style="margin: 0px; padding: 0px;">
        <h1 style="width: 100%; background-color: #D54; color: #FFF; padding: 15px">' . 
            $exception->getMessage() . 
        '</h1>
        <pre style="padding: 15px;">';
    print_r($exception->getTrace());
    echo '</pre></body>';
    define('EXCEPTION_THROWN', true);
}
set_exception_handler('exception_handler');

// Setup the SimPHPfy Core package
SimPHPfy::package(SIMPHPFY_MODEL);
SimPHPfy::package(SIMPHPFY_VIEW);
SimPHPfy::package(SIMPHPFY_CONTROLLER);
SimPHPfy::package(SIMPHPFY_LIBRARY);
SimPHPfy::package(SIMPHPFY_UTILITY);

// Setup the ROUTER package
SimPHPfy::package(ROUTER);

// Setup the app Core package
SimPHPfy::package(MODEL);
SimPHPfy::package(VIEW);
SimPHPfy::package(CONTROLLER);

// Include the database configuration
require(CONFIG . 'datasource.php');
$datasourceConfig = new DataSourceConfig();
SimPHPfy::setDatasource(new DataSource($datasourceConfig->config));

// Include the routes.php from the configuration
require(CONFIG . 'routes.php');

/*
 * shutdown function
 * 
 * The shutdown function accepts a controller object and is responsible to 
 * render a View if there is no specified render call in the 
 * :Controller->:Action()
 * 
 * @params View $controllerObj
 * 
 * @return void
 */
function _shutdown($controllerObj) {
    $error = error_get_last();
    if (!defined('EXCEPTION_THROWN') && $error['type'] !== E_ERROR) {
        /*
         * Try to render the template for the current contorller and action only
         * if the progam is not termainted unexpectedly
         */
        try {
            if (!$controllerObj->View->isRendered()) {
                throw new InvalidControllerException(array($controllerObj->getController(), 'No template is rendered'));
            }
        } catch(SimPHPfyException $e){
            ob_clean();
            echo '(Unhandlable ERROR) Uncaught Exception: ' . $e->getMessage();
        }
    }
}
