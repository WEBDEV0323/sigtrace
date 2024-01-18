<?php

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */

//session_start();
ini_set('session.gc_maxlifetime', 9*60*60);
$_SESSION['timestamp'] = time(); //set new timestamp
define('IP',(isset(filter_input_array(INPUT_SERVER)['REMOTE_ADDR']))?((filter_input_array(INPUT_SERVER)['REMOTE_ADDR'] != "::1")?filter_input_array(INPUT_SERVER)['REMOTE_ADDR']:getHostByName(getHostName())):'');
define('files','./public/attachment/');
define('files_fetch_path','/attachment/');
chdir(dirname(__DIR__));
// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';
require 'public/library/Classes/PHPExcel.php';
require 'public/library/Classes/PHPExcel/IOFactory.php';
require 'public/library/mpdf60/mpdf.php';


// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
//define('BASE_PATH', realpath(dirname(__DIR__)));
//define('PUBLIC_PATH', BASE_PATH.'/public');
