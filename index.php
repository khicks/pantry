<?php

require_once("vendor/autoload.php");
require_once("class/Pantry.php");

$router = new AltoRouter();
$base_path = dirname($_SERVER['SCRIPT_NAME']) == "/" ? "" : dirname($_SERVER['SCRIPT_NAME']);
$router->setBasePath($base_path);

$router->addMatchTypes([
    'un' => "[A-Za-z0-9-_]{3,32}"
]);

try {
    $router->map('GET', '/', 'PantryPage::home');
    $router->map('GET', '/login', 'PantryPage::login');

    $router->map('GET', '/admin', 'PantryAdminPage::dashboard');
    $router->map('GET', '/admin/users', 'PantryAdminPage::users');
    $router->map('GET', '/admin/users/create', 'PantryAdminPage::createUser');
    $router->map('GET', '/admin/users/edit/[un:username]', 'PantryAdminPage::editUser');

    $router->map('GET', '/api/v1/me', 'PantryAPI::me');
    $router->map('GET', '/api/v1/language', 'PantryAPI::language');
    $router->map('POST', '/api/v1/login', 'PantryAPI::login');
    $router->map('POST', '/api/v1/logout', 'PantryAPI::logout');

    $router->map('GET', '/api/v1/admin/users', 'PantryAdminAPI::getUsers');
    $router->map('GET', '/api/v1/admin/user', 'PantryAdminAPI::getUser');
    $router->map('GET', '/api/v1/admin/users/check', 'PantryAdminAPI::checkUsername');
    $router->map('POST', '/api/v1/admin/users/create', 'PantryAdminAPI::createUser');
    $router->map('POST', '/api/v1/admin/users/edit', 'PantryAdminAPI::editUser');
    $router->map('POST', '/api/v1/admin/users/delete', 'PantryAdminAPI::deleteUser');
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
