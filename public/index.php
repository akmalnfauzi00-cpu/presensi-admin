<?php
date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../app/Core/Session.php';
Session::start();

require __DIR__ . '/../app/Core/Db.php';
require __DIR__ . '/../app/Core/Request.php';
require __DIR__ . '/../app/Core/Response.php';
require __DIR__ . '/../app/Core/Auth.php';
require __DIR__ . '/../app/Core/Router.php';

/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../app/Models/User.php';

/*
|--------------------------------------------------------------------------
| WEB Controllers
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../app/Controllers/AuthController.php';
require __DIR__ . '/../app/Controllers/DashboardController.php';
require __DIR__ . '/../app/Controllers/UserController.php';
require __DIR__ . '/../app/Controllers/SettingController.php';
require __DIR__ . '/../app/Controllers/GuruController.php';
require __DIR__ . '/../app/Controllers/RewardSpController.php';
require __DIR__ . '/../app/Controllers/LaporanController.php';
require __DIR__ . '/../app/Controllers/PengajuanController.php';

/*
|--------------------------------------------------------------------------
| API Controllers
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../app/Controllers/ApiAuthController.php';
require __DIR__ . '/../app/Controllers/ApiPresensiController.php';
require __DIR__ . '/../app/Controllers/ApiSettingController.php';
require __DIR__ . '/../app/Controllers/ApiPengajuanController.php';
require __DIR__ . '/../app/Controllers/ApiUploadController.php';
require __DIR__ . '/../app/Controllers/ApiRewardSpController.php';

$router = new Router();
$routes = require __DIR__ . '/../app/Config/routes.php';

foreach ($routes as $r) {
  [$method, $path, $action] = $r;
  [$controllerName, $methodName] = explode('@', $action, 2);

  $router->add($method, $path, function ($req, $res) use ($controllerName, $methodName) {
    if (!class_exists($controllerName)) {
      throw new Exception("Controller {$controllerName} tidak ditemukan.");
    }

    $controller = new $controllerName();

    if (!method_exists($controller, $methodName)) {
      throw new Exception("Method {$controllerName}@{$methodName} tidak ditemukan.");
    }

    $controller->$methodName($req, $res);
  });
}

$router->dispatch(new Request());