<?php
/**
 * Entry Point Aplikasi Presensi Admin
 */
// --- TAMBAHKAN KODE INI DI SINI ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Tangani permintaan OPTIONS (pre-flight) dari Axios/Fetch
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Pengaturan Environment & Error Reporting
date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Inisialisasi Session
require_once __DIR__ . '/../app/Core/Session.php';
Session::start();

// 3. Load Core Systems (Menggunakan require_once untuk keamanan)
require_once __DIR__ . '/../app/Core/Db.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Router.php';

/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Pengumuman.php';

/*
|--------------------------------------------------------------------------
| WEB Controllers[cite: 1]
|--------------------------------------------------------------------------
*/
// Tambahkan SetupController agar rute /setup/create-admin bisa diakses[cite: 1]
require_once __DIR__ . '/../app/Controllers/SetupController.php'; 

require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Controllers/SettingController.php';
require_once __DIR__ . '/../app/Controllers/GuruController.php';
require_once __DIR__ . '/../app/Controllers/RewardSpController.php';
require_once __DIR__ . '/../app/Controllers/LaporanController.php';
require_once __DIR__ . '/../app/Controllers/PengajuanController.php';
require_once __DIR__ . '/../app/Controllers/PengumumanController.php';

/*
|--------------------------------------------------------------------------
| API Controllers[cite: 1]
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../app/Controllers/ApiAuthController.php';
require_once __DIR__ . '/../app/Controllers/ApiPresensiController.php';
require_once __DIR__ . '/../app/Controllers/ApiSettingController.php';
require_once __DIR__ . '/../app/Controllers/ApiPengajuanController.php';
require_once __DIR__ . '/../app/Controllers/ApiUploadController.php';
require_once __DIR__ . '/../app/Controllers/ApiRewardSpController.php';
require_once __DIR__ . '/../app/Controllers/ApiPengumumanController.php';

/*
|--------------------------------------------------------------------------
| Routing Logic[cite: 1]
|--------------------------------------------------------------------------
*/
$router = new Router();
$routes = require __DIR__ . '/../app/Config/routes.php';

foreach ($routes as $r) {
    [$method, $path, $action] = $r;
    
    // Pastikan format action benar (Controller@Method)[cite: 1]
    if (strpos($action, '@') !== false) {
        [$controllerName, $methodName] = explode('@', $action, 2);

        $router->add($method, $path, function ($req, $res) use ($controllerName, $methodName) {
            if (!class_exists($controllerName)) {
                throw new Exception("Controller {$controllerName} tidak ditemukan. Pastikan file sudah di-require di index.php.");
            }

            $controller = new $controllerName();

            if (!method_exists($controller, $methodName)) {
                throw new Exception("Method {$controllerName}@{$methodName} tidak ditemukan di dalam class.");
            }

            $controller->$methodName($req, $res);
        });
    }
}

// Jalankan aplikasi[cite: 1]
try {
    $router->dispatch(new Request());
} catch (Exception $e) {
    echo "<h1>Terjadi Kesalahan Sistem</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}