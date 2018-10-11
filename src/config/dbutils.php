<?php

return [
    
    // db:backup utility config
    'backup' => [
        'path' => env('DB_UTILS_BACKUP_PATH', '/securetv/dbutils/backups/'),
        'compress' => false,
        'upload' => false
    ],

    // db:create utility config
    'create' => [
        'host' => env('DB_UTILS_CREATE_HOST', 'localhost'),
        'port' => env('DB_UTILS_CREATE_PORT', '3306')
    ],

];
