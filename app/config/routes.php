<?php

/**
 * Struktur: [METHOD, PATH, CONTROLLER@METHOD]
 * Pastikan PATH sesuai dengan yang dipanggil oleh Axios/Fetch di Mobile.
 */

return [
    // --- AUTH WEB (ADMIN) ---
    ['GET',  '/login',           'AuthController@loginForm'],
    ['POST', '/login',           'AuthController@login'],
    ['POST', '/logout',          'AuthController@logout'],

    // TAMBAHAN: Rute untuk Halaman Lupa Password di Web Admin
    ['GET',  '/forgot-password', 'AuthController@forgotPasswordView'],

    ['GET',  '/',               'AuthController@redirectHome'],
    ['GET',  '/dashboard',      'DashboardController@index'],

    // --- SETUP AWAL ADMIN (Halaman Form) ---
    ['GET',  '/setup/create-admin', 'SetupController@index'],       // Menampilkan Form HTML
    ['POST', '/setup/create-admin', 'SetupController@createAdmin'], // Memproses data Form

    // --- USER ADMIN ---
    ['GET',  '/users',          'UserController@index'],
    ['GET',  '/users/create',   'UserController@createForm'],
    ['POST', '/users/create',   'UserController@create'],
    ['GET',  '/users/edit',     'UserController@editForm'],
    ['POST', '/users/edit',     'UserController@update'],
    ['POST', '/users/delete',   'UserController@delete'],

    // --- SETTING WEB ---
    ['GET',  '/setting',             'SettingController@index'],
    ['POST', '/setting/save-jam',     'SettingController@saveJam'],
    ['POST', '/setting/save-reward',  'SettingController@saveReward'],
    ['POST', '/setting/save-lokasi',  'SettingController@saveLokasi'],
    ['POST', '/setting/save-libur',   'SettingController@saveLibur'],
    ['POST', '/setting/delete-libur', 'SettingController@deleteLibur'],
    ['POST', '/setting/save-batas',   'SettingController@saveBatas'],

    // --- API SETTINGS (MOBILE) ---
    ['OPTIONS', '/api/settings', 'ApiSettingController@index'],
    ['GET',     '/api/settings', 'ApiSettingController@index'],

    // --- GURU ADMIN ---
    ['GET',  '/guru',           'GuruController@index'],
    ['GET',  '/guru/create',    'GuruController@createForm'],
    ['POST', '/guru/create',    'GuruController@create'],
    ['GET',  '/guru/edit',      'GuruController@editForm'],
    ['POST', '/guru/edit',      'GuruController@update'],
    ['POST', '/guru/delete',    'GuruController@delete'],
    ['GET',  '/guru/account',   'GuruController@accountForm'],
    ['POST', '/guru/account',   'GuruController@accountSave'],
    ['GET',  '/guru/setujui',   'GuruController@setujui'],
    ['GET',  '/guru/tolak',     'GuruController@tolak'],
    
    // --- REWARD SP (ADMIN WEB) ---
    ['GET',  '/rewardsp',           'RewardSpController@index'],
    ['POST', '/rewardsp/create',    'RewardSpController@create'],
    ['GET',  '/rewardsp/download',  'RewardSpController@download'],
    ['GET',  '/rewardsp/generate',  'RewardSpController@generatePDF'],
    ['POST', '/rewardsp/reset',     'RewardSpController@reset'],

    // --- LAPORAN WEB ---
    ['GET', '/laporan',               'LaporanController@index'],
    ['GET', '/laporan/print',         'LaporanController@print'],
    ['GET', '/laporan/export-excel',  'LaporanController@exportExcel'],
    ['GET', '/laporan/export-pdf',    'LaporanController@exportPdf'],
    ['GET', '/laporan/pdf',           'LaporanController@pdf'],

    // --- API AUTH (MOBILE) ---
    ['OPTIONS', '/api/login',    'ApiAuthController@login'],
    ['POST',     '/api/login',    'ApiAuthController@login'],

    ['OPTIONS', '/api/register', 'ApiAuthController@register'],
    ['POST',     '/api/register', 'ApiAuthController@register'],

    ['OPTIONS', '/api/me',       'ApiAuthController@me'],
    ['GET',      '/api/me',       'ApiAuthController@me'],

    ['OPTIONS', '/api/logout',   'ApiAuthController@logout'],
    ['POST',     '/api/logout',   'ApiAuthController@logout'],

    // --- API FORGOT & RESET (MOBILE & WEB AJAX) ---
    ['OPTIONS', '/api/forgot-password', 'ApiAuthController@forgotPassword'],
    ['POST',     '/api/forgot-password', 'ApiAuthController@forgotPassword'],
    ['OPTIONS', '/api/reset-password',  'ApiAuthController@resetPassword'],
    ['POST',     '/api/reset-password',  'ApiAuthController@resetPassword'],

    // --- API UPLOAD (FITUR UTAMA PRESENSI) ---
    ['OPTIONS', '/api/upload/image', 'ApiUploadController@presensi'],
    ['POST',     '/api/upload/image', 'ApiUploadController@presensi'],

    // --- API PRESENSI (MOBILE) ---
    ['OPTIONS', '/api/presensi/today',     'ApiPresensiController@today'],
    ['GET',      '/api/presensi/today',     'ApiPresensiController@today'],
    
    ['OPTIONS', '/api/presensi/masuk',     'ApiPresensiController@masuk'],
    ['POST',     '/api/presensi/masuk',     'ApiPresensiController@masuk'],
    
    ['OPTIONS', '/api/presensi/pulang',    'ApiPresensiController@pulang'],
    ['POST',     '/api/presensi/pulang',    'ApiPresensiController@pulang'],
    
    ['OPTIONS', '/api/presensi/riwayat',   'ApiPresensiController@riwayat'],
    ['GET',      '/api/presensi/riwayat',   'ApiPresensiController@riwayat'],
    
    ['OPTIONS', '/api/presensi/reset-riwayat', 'ApiPresensiController@resetRiwayat'],
    ['POST',     '/api/presensi/reset-riwayat', 'ApiPresensiController@resetRiwayat'],

    // --- API PENGAJUAN (MOBILE) ---
    ['OPTIONS', '/api/pengajuan',         'ApiPengajuanController@index'],
    ['GET',      '/api/pengajuan',         'ApiPengajuanController@index'],
    ['OPTIONS', '/api/pengajuan/store',   'ApiPengajuanController@store'],
    ['POST',     '/api/pengajuan/store',   'ApiPengajuanController@store'],

    // --- API PENGUMUMAN (MOBILE) ---
    ['OPTIONS', '/api/pengumuman',       'ApiPengumumanController@index'],
    ['GET',      '/api/pengumuman',       'ApiPengumumanController@index'],
    
    // --- PENGUMUMAN WEB ADMIN ---
    ['GET',  '/pengumuman',         'PengumumanController@index'],
    ['POST', '/pengumuman/create',  'PengumumanController@create'],
    ['POST', '/pengumuman/store',   'PengumumanController@store'],
    ['POST', '/pengumuman/delete',  'PengumumanController@delete'],

    // --- API REWARD & SP (MOBILE) ---
    ['OPTIONS', '/api/rewardsp/me',       'ApiRewardSpController@me'],
    ['GET',      '/api/rewardsp/me',       'ApiRewardSpController@me'],
    ['OPTIONS', '/api/rewardsp/download', 'ApiRewardSpController@download'],
    ['GET',      '/api/rewardsp/download', 'ApiRewardSpController@download'],

    // --- PENGAJUAN ADMIN (WEB) ---
    ['GET',  '/pengajuan',             'PengajuanController@index'],
    ['POST', '/pengajuan/verifikasi',  'PengajuanController@verifikasi'],
    ['POST', '/pengajuan/delete',      'PengajuanController@delete'],

    // Endpoint Web Admin untuk Reset Manual
    ['GET', '/guru/reset-password/(:any)', 'GuruController@resetPasswordManual'],
];