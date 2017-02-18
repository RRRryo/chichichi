<?php


define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_ENABLE_EXCEPTION_HANDLER', false);
define('ROOTPATH', __DIR__);

define('GOOGLE_MAP_URL', 'maps.googleapis.com');
//define('GOOGLE_MAP_URL', 'ditu.gdgdocs.org');

ini_set("display_errors",true);
ini_set('error_log', ROOTPATH . "/php_error.log");
date_default_timezone_set('Europe/Paris');

//echo ini_get('error_log');
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

// include Yii bootstrap file
require_once(dirname(__FILE__).'/yiiframework/yii.php');
$config=dirname(__FILE__).'/protected/config/main.php';

// create a Web application instance and run
Yii::createWebApplication($config)->run();