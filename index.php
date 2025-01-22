<?php
require_once "vendor/autoload.php";

use Core\Route;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


$debug = $_ENV['APP_DEBUG'];

if ($debug == "true") {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
} else {
    set_error_handler(function () {
        http_response_code(500);
        require_once("routes/errors/500.php");
        exit();
    });
    set_exception_handler(function () {
        http_response_code(500);
        require_once("routes/errors/500.php");
        exit();
    });
}

// Load Routes
require_once "routes/web.php";

// Run the Application
Route::run();