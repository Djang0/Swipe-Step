<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';
//require_once 'lib/mysql.php';
session_start();
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);
// function getDB()
// {
//     $dbhost = "localhost";
//     $dbuser = "root";
//     $dbpass = "guliver511";
//     $dbname = "clicktrax";
//
//     $mysql_conn_string = "mysql:host=$dbhost;dbname=$dbname";
//     $dbConnection = new PDO($mysql_conn_string, $dbuser, $dbpass);
//     $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     return $dbConnection;
// }

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';



// Run app
$app->run();
