<?php

return [
    'title'      => env('APP_NAME', 'LightSchool'),
    'version'    => '1.1',
    'secure_dir' => env('SECURE_DIR', storage_path('secure')),
    'smtp_auth'          => (bool) env('MAIL_SMTP_AUTH', true),
    'allow_user_uploads' => (bool) env('ALLOW_USER_UPLOADS', true),
];
