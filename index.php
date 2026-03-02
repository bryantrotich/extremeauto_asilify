<?php
/**
 * For the PHP built-in server: 
 * If the request is for a real file or directory, serve it as-is.
 */
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }
}

ini_set('display_errors', 'Off');
error_reporting(E_ALL);

include_once 'vendor/autoload.php';

use Simcify\Application;

$app = new Application();
$app->route();
