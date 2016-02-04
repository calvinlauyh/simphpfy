<?php

use SimPHPfy;

/* 
 * Created by Hei
 */

/*
 * !IMPORTANT!
 * NEVER MODIFY THE FOLLOWING UNTIL YOU ARE ABSOLUELY SURE WHAT YOU ARE DOING
 */
require('custom.php');

// define constants

// the directory separator
define('DS', DIRECTORY_SEPARATOR);
// the SimPHPfy document root, i.e. directory of this file
define('SIMPHPFY_ROOT', dirname(__FILE__));
// the SimPHPfy path for include
define('SIMPHPFY_PATH', SIMPHPFY_ROOT . DS);
// the server path to the SimPHPfy core directory
define('SIMPHPFY', SIMPHPFY_PATH . 'Core' . DS);
// the SimPHPfy relative path
define('SIMPHPFY_RELATIVE_PATH', str_replace($_SERVER['DOCUMENT_ROOT'], '', SIMPHPFY_PATH));

/*
 * The directory prefix to the current directory
 * 
 * If the project is placed under a directory not exactly the root(public_html),
 * you have to specific the path to the project directory for the system to work
 * properly
 * e.g.
 * public_html
 * |
 * |---framework
 *     |
 *     |---index.php(current directory)
 * 
 * define('DIRECTORY_PREFIX', DS . 'framework' . DS);
 */
define('DIRECTORY_PREFIX', DS . 'simphpfy' . DS);

require(SIMPHPFY . 'bootstrap.php');

