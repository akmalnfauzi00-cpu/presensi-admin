<?php
return [
  ['GET',  '/login',     'AuthController@loginForm'],
  ['POST', '/login',     'AuthController@login'],
  ['POST', '/logout',    'AuthController@logout'],

  ['GET',  '/',          'AuthController@redirectHome'],
  ['GET',  '/dashboard', 'DashboardController@index'],

  // USER ADMIN
  ['GET',  '/users',        'UserController@index'],
  ['GET',  '/users/create', 'UserController@createForm'],
  ['POST', '/users/create', 'UserController@create'],
  ['GET',  '/users/edit',   'UserController@editForm'],
  ['POST', '/users/edit',   'UserController@update'],
  ['POST', '/users/delete', 'UserController@delete'],

  // SETTING
  ['GET',  '/setting',             'SettingController@index'],
  ['POST', '/setting/save-jam',    'SettingController@saveJam'],
  ['POST', '/setting/save-reward', 'SettingController@saveReward'],
  ['POST', '/setting/save-lokasi', 'SettingController@saveLokasi'],

  // GURU
  ['GET',  '/guru',            'GuruController@index'],
  ['GET',  '/guru/create',     'GuruController@createForm'],
  ['POST', '/guru/create',     'GuruController@create'],
  ['GET',  '/guru/edit',       'GuruController@editForm'],
  ['POST', '/guru/edit',       'GuruController@update'],
  ['POST', '/guru/delete',     'GuruController@delete'],
  ['GET',  '/guru/account',    'GuruController@accountForm'],
  ['POST', '/guru/account',    'GuruController@accountSave'],
  
// REWARD SP (ADMIN WEB)
['GET',  '/rewardsp',           'RewardSpController@index'],
['POST', '/rewardsp/create',    'RewardSpController@create'],
['GET',  '/rewardsp/download',  'RewardSpController@download'],
['GET',  '/rewardsp/generate',  'RewardSpController@generatePDF'], // Pastikan ini ada di file routes.php

  // LAPORAN
  ['GET', '/laporan',              'LaporanController@index'],
  ['GET', '/laporan/print',        'LaporanController@print'],
  ['GET', '/laporan/export-excel', 'LaporanController@exportExcel'],
  ['GET', '/laporan/export-pdf',   'LaporanController@exportPdf'],
  ['GET', '/laporan/pdf',          'LaporanController@pdf'],

  // API AUTH
  ['OPTIONS', '/api/login',  'ApiAuthController@login'],
  ['POST',    '/api/login',  'ApiAuthController@login'],

  ['OPTIONS', '/api/me',     'ApiAuthController@me'],
  ['GET',     '/api/me',     'ApiAuthController@me'],

  ['OPTIONS', '/api/logout', 'ApiAuthController@logout'],
  ['POST',    '/api/logout', 'ApiAuthController@logout'],

  // API PRESENSI
  ['OPTIONS', '/api/presensi/today',   'ApiPresensiController@today'],
  ['GET',     '/api/presensi/today',   'ApiPresensiController@today'],

  ['OPTIONS', '/api/presensi/masuk',   'ApiPresensiController@masuk'],
  ['POST',    '/api/presensi/masuk',   'ApiPresensiController@masuk'],

  ['OPTIONS', '/api/presensi/pulang',  'ApiPresensiController@pulang'],
  ['POST',    '/api/presensi/pulang',  'ApiPresensiController@pulang'],

  ['OPTIONS', '/api/presensi/riwayat', 'ApiPresensiController@riwayat'],
  ['GET',     '/api/presensi/riwayat', 'ApiPresensiController@riwayat'],

  ['OPTIONS', '/api/presensi/reset-riwayat', 'ApiPresensiController@resetRiwayat'],
  ['POST',    '/api/presensi/reset-riwayat', 'ApiPresensiController@resetRiwayat'],

  // API SETTINGS
  ['OPTIONS', '/api/settings', 'ApiSettingController@index'],
  ['GET',     '/api/settings', 'ApiSettingController@index'],

  // API REWARD / SP
  ['OPTIONS', '/api/rewardsp/me',       'ApiRewardSpController@me'],
  ['GET',     '/api/rewardsp/me',       'ApiRewardSpController@me'],

  ['OPTIONS', '/api/rewardsp/download', 'ApiRewardSpController@download'],
  ['GET',     '/api/rewardsp/download', 'ApiRewardSpController@download'],

  // API PENGAJUAN
  ['OPTIONS', '/api/pengajuan', 'ApiPengajuanController@index'],
  ['GET',     '/api/pengajuan', 'ApiPengajuanController@index'],

  ['OPTIONS', '/api/pengajuan/store', 'ApiPengajuanController@store'],
  ['POST',    '/api/pengajuan/store', 'ApiPengajuanController@store'],

  // API UPLOAD PENGAJUAN
  ['OPTIONS', '/api/upload/pengajuan', 'ApiUploadController@pengajuan'],
  ['POST',    '/api/upload/pengajuan', 'ApiUploadController@pengajuan'],

  // API UPLOAD PRESENSI SELFIE
  ['OPTIONS', '/api/upload/presensi', 'ApiUploadController@presensi'],
  ['POST',    '/api/upload/presensi', 'ApiUploadController@presensi'],

  // PENGAJUAN ADMIN
  ['GET',  '/pengajuan',            'PengajuanController@index'],
  ['POST', '/pengajuan/verifikasi', 'PengajuanController@verifikasi'],
];