<?php
use think\Route;

// 全局CORS中间件
Route::middleware(['app\api\middleware\Cors']);

// ===== Auth =====
Route::post('api/auth/sendSms',      'api/Auth/sendSms');
Route::post('api/auth/login',         'api/Auth/login');
Route::post('api/auth/wechatLogin',   'api/Auth/wechatLogin');

// ===== User =====
Route::get('api/user/info',           'api/User/info');
Route::post('api/user/update',        'api/User/update');
Route::post('api/user/use',           'api/User/use');

// ===== Member =====
Route::get('api/member/plans',        'api/Member/plans');
Route::get('api/member/status',       'api/Member/status');

// ===== Payment =====
Route::post('api/payment/create',          'api/Payment/create');
Route::get('api/payment/status',           'api/Payment/status');
Route::post('api/payment/wechatNotify',    'api/Payment/wechatNotify');
Route::post('api/payment/alipayNotify',    'api/Payment/alipayNotify');

// ===== History =====
Route::get('api/history/list',        'api/History/list');
Route::post('api/history/save',       'api/History/save');
Route::delete('api/history/delete',   'api/History/delete');
Route::delete('api/history/clear',    'api/History/clear');

// ===== OPTIONS 预检 =====
Route::options(':path', function () { return ''; })->pattern(['path' => '.*']);
