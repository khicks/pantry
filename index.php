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
    'img' => "[a-z0-9-]{3,40}\\.[a-z0-9]+",
    'img_size' => "(md|sm)"
]);

try {
    // Pages
    $router->map('GET', '/install', 'PantryPage::install');
    $router->map('GET', '/update', 'PantryPage::update');

    $router->map('GET', '/', 'PantryPage::home');
    $router->map('GET', '/login', 'PantryPage::login');
    $router->map('GET', '/account', 'PantryPage::account');
    $router->map('GET', '/recipes', 'PantryPage::browseRecipes');
    $router->map('GET', '/recipes/create', 'PantryPage::createRecipe');
    $router->map('GET', '/recipe/[slug:slug]', 'PantryPage::viewRecipe');
    $router->map('GET', '/recipe/[slug:slug]/edit', 'PantryPage::editRecipe');
    $router->map('GET', '/image/[img:img]', 'PantryAPI::getImage');
    $router->map('GET', '/image/[img_size:size]/[img:img]', 'PantryAPI::getImageSize');

    // Admin pages
    $router->map('GET', '/admin', 'PantryAdminPage::dashboard');
    $router->map('GET', '/admin/courses-cuisines', 'PantryAdminPage::coursesCuisines');
    $router->map('GET', '/admin/users', 'PantryAdminPage::users');
    $router->map('GET', '/admin/users/create', 'PantryAdminPage::createUser');
    $router->map('GET', '/admin/user/[un:username]', 'PantryAdminPage::editUser');

    // API
    $router->map('POST', '/api/v1/install', 'PantryAPI::install');
    $router->map('GET', '/api/v1/install/databases', 'PantryAPI::getSupportedDatabases');
    $router->map('POST', '/api/v1/install/check_key', 'PantryAPI::checkInstallKey');
    $router->map('POST', '/api/v1/install/language', 'PantryAPI::setInstallLanguage');
    $router->map('POST', '/api/v1/update', 'PantryAPI::update');
    $router->map('GET', '/api/v1/update/version', 'PantryAPI::getUpdateVersion');

    $router->map('GET', '/api/v1/me', 'PantryAPI::me');
    $router->map('GET', '/api/v1/language', 'PantryAPI::language');
    $router->map('GET', '/api/v1/languages', 'PantryAPI::listLanguages');
    $router->map('POST', '/api/v1/login', 'PantryAPI::login');
    $router->map('POST', '/api/v1/logout', 'PantryAPI::logout');
    $router->map('POST', '/api/v1/account', 'PantryAPI::account');

    $router->map('GET', '/api/v1/recipes/featured', 'PantryAPI::getFeaturedRecipes');
    $router->map('GET', '/api/v1/recipes/new', 'PantryAPI::getNewRecipes');
    $router->map('GET', '/api/v1/recipes/all', 'PantryAPI::getAllRecipes');
    $router->map('POST', '/api/v1/recipes/create', 'PantryAPI::createRecipe');
    $router->map('GET', '/api/v1/recipe/[slug:slug]', 'PantryAPI::getRecipe');
    $router->map('POST', '/api/v1/recipes/edit', 'PantryAPI::editRecipe');
    $router->map('POST', '/api/v1/recipes/delete', 'PantryAPI::deleteRecipe');

    $router->map('GET', '/api/v1/courses', 'PantryAPI::listCourses');
    $router->map('GET', '/api/v1/cuisines', 'PantryAPI::listCuisines');
    $router->map('GET', '/api/v1/courses-cuisines', 'PantryAPI::listCoursesAndCuisines');

    // Admin API
    $router->map('GET', '/api/v1/admin/users', 'PantryAdminAPI::getUsers');
    $router->map('GET', '/api/v1/admin/users/count', 'PantryAdminAPI::getUserCounts');
    $router->map('GET', '/api/v1/admin/users/check-username', 'PantryAdminAPI::checkUsername');
    $router->map('GET', '/api/v1/admin/user', 'PantryAdminAPI::getUser');
    $router->map('POST', '/api/v1/admin/users/create', 'PantryAdminAPI::createUser');
    $router->map('POST', '/api/v1/admin/users/edit', 'PantryAdminAPI::editUser');
    $router->map('POST', '/api/v1/admin/users/delete', 'PantryAdminAPI::deleteUser');

    $router->map('POST', '/api/v1/admin/courses/create', 'PantryAdminAPI::createCourse');
    $router->map('POST', '/api/v1/admin/courses/edit', 'PantryAdminAPI::editCourse');
    $router->map('POST', '/api/v1/admin/courses/delete', 'PantryAdminAPI::deleteCourse');

    $router->map('POST', '/api/v1/admin/cuisines/create', 'PantryAdminAPI::createCuisine');
    $router->map('POST', '/api/v1/admin/cuisines/edit', 'PantryAdminAPI::editCuisine');
    $router->map('POST', '/api/v1/admin/cuisines/delete', 'PantryAdminAPI::deleteCuisine');

    // Temp
    $router->map('GET', '/test', 'PantryPage::test');
    $router->map('GET', '/uuid', function() {
        header("Content-Type: text/plain");
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
    call_user_func_array('PantryPage::error404', []);
}
