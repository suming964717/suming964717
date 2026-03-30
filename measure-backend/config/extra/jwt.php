<?php
return [
    'secret'  => env('JWT_SECRET', 'MeasureMaster_JWT_Secret_2024_Change_This'),
    'expire'  => 2592000, // 30天(秒)
    'issuer'  => 'MeasureMaster',
];
