<?php

require_once("vendor/autoload.php");
require_once("class/Pantry.php");

$router = new AltoRouter();
$base_path = dirname($_SERVER['SCRIPT_NAME']) == "/" ? "" : dirname($_SERVER['SCRIPT_NAME']);
$router->setBasePath($base_path);

$router->addMatchTypes([
    'uuid' => "[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}",
    'un' => "[A-Za-z0-9-_]{3,32}",
    'slug' => "[a-z0-9-]{3,40}",
    'img' => "[a-z0-9-]{3,40}\\.(bmp|jpg|png)"
]);

try {
    // Pages
    $router->map('GET', '/', 'PantryPage::home');
    $router->map('GET', '/login', 'PantryPage::login');
    $router->map('GET', '/recipes', 'PantryPage::browseRecipes');
    $router->map('GET', '/recipes/create', 'PantryPage::createRecipe');
    $router->map('GET', '/recipe/[slug:slug]', 'PantryPage::viewRecipe');
    $router->map('GET', '/recipe/[slug:slug]/edit', 'PantryPage::editRecipe');
    $router->map('GET', '/image/[img:img]', 'PantryAPI::getImage');

    // Admin pages
    $router->map('GET', '/admin', 'PantryAdminPage::dashboard');
    $router->map('GET', '/admin/courses-cuisines', 'PantryAdminPage::coursesCuisines');
    $router->map('GET', '/admin/courses/[slug:slug]/edit', 'PantryAdminPage::editCourse');
    $router->map('GET', '/admin/cuisines/[slug:slug]/edit', 'PantryAdminPage::editCuisine');
    $router->map('GET', '/admin/users', 'PantryAdminPage::users');
    $router->map('GET', '/admin/users/create', 'PantryAdminPage::createUser');
    $router->map('GET', '/admin/users/edit/[un:username]', 'PantryAdminPage::editUser');

    // API
    $router->map('GET', '/api/v1/me', 'PantryAPI::me');
    $router->map('GET', '/api/v1/language', 'PantryAPI::language');
    $router->map('POST', '/api/v1/login', 'PantryAPI::login');
    $router->map('POST', '/api/v1/logout', 'PantryAPI::logout');

    $router->map('GET', '/api/v1/recipes/featured', 'PantryAPI::getFeaturedRecipes');
    $router->map('GET', '/api/v1/recipes/new', 'PantryAPI::getNewRecipes');
    $router->map('POST', '/api/v1/recipes/create', 'PantryAPI::createRecipe');
    $router->map('GET', '/api/v1/recipe/[slug:slug]', 'PantryAPI::getRecipe');
    $router->map('POST', '/api/v1/recipes/edit', 'PantryAPI::editRecipe');
    $router->map('POST', '/api/v1/recipes/delete', 'PantryAPI::deleteRecipe');

    $router->map('GET', '/api/v1/courses', 'PantryAPI::listCourses');
    $router->map('GET', '/api/v1/cuisines', 'PantryAPI::listCuisines');
    $router->map('GET', '/api/v1/courses-cuisines', 'PantryAPI::listCoursesAndCuisines');

    // Admin API
    $router->map('GET', '/api/v1/admin/users', 'PantryAdminAPI::getUsers');
    $router->map('GET', '/api/v1/admin/user', 'PantryAdminAPI::getUser');
    $router->map('GET', '/api/v1/admin/users/check', 'PantryAdminAPI::checkUsername');
    $router->map('POST', '/api/v1/admin/users/create', 'PantryAdminAPI::createUser');
    $router->map('POST', '/api/v1/admin/users/edit', 'PantryAdminAPI::editUser');
    $router->map('POST', '/api/v1/admin/users/delete', 'PantryAdminAPI::deleteUser');

    // Temp
    $router->map('GET', '/test', 'PantryPage::test');
    $router->map('GET', '/uuid', function() {
        echo Pantry::generateUUID();
    });
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
