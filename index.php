<?php

require_once("vendor/autoload.php");
require_once("class/Pantry.php");

$router = new AltoRouter();
$base_path = dirname($_SERVER['SCRIPT_NAME']) == "/" ? "" : dirname($_SERVER['SCRIPT_NAME']);
$router->setBasePath($base_path);

try {
    $router->map('GET', '/', 'PantryPage::home');
    $router->map('GET', '/test', 'PantryPage::home');
    $router->map('GET', '/login', 'PantryPage::login');

    $router->map('GET', '/api/v1/test', 'PantryAPI::test');
    $router->map('GET', '/api/v1/me', 'PantryAPI::me');
    $router->map('POST', '/api/v1/login', 'PantryAPI::login');
    $router->map('POST', '/api/v1/logout', 'PantryAPI::logout');
}
catch (Exception $e) {
    die();
}

$match = $router->match();

if ($match && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
}
else {
    header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
    echo "Error 404: Not found";
}