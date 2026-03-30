<?php
// 支付配置（实际值从数据库configs表读取，此处为默认结构）
return [
    'wechat' => [
        'appid'      => '',
        'mch_id'     => '',
        'api_key'    => '',
        'notify_url' => '',
        'cert_path'  => '',   // 微信支付证书路径（退款时用）
    ],
    'alipay' => [
        'app_id'      => '',
        'private_key' => '',
        'public_key'  => '',
        'notify_url'  => '',
        'return_url'  => '',
        'sandbox'     => false,
    ],
];
