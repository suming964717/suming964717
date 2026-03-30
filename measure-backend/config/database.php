<?php
// 数据库配置
return [
    'type'            => 'mysql',
    'hostname'        => env('DB_HOST', '127.0.0.1'),
    'database'        => env('DB_NAME', 'measure_master'),
    'username'        => env('DB_USER', 'root'),
    'password'        => env('DB_PASS', ''),
    'hostport'        => env('DB_PORT', '3306'),
    'charset'         => 'utf8mb4',
    'prefix'          => '',
    'debug'           => false,
    'deploy'          => 0,
    'rw_separate'     => false,
    'fields_strict'   => true,
    'result_type'     => PDO::FETCH_ASSOC,
    'auto_timestamp'  => false,
];
