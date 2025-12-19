<?php

return [

    'default' => env('FILESYSTEM_DISK', 'public'),

    'cloud' => env('FILESYSTEM_CLOUD', 'do_spaces'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'tmp' => [
            'driver' => 'local',
            'root'   => storage_path('app/tmp'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') ? env('APP_URL') . '/storage' : '',
            'visibility' => 'public',
            'asset_url' => 'https://closyflix.com',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'visibility' => 'public',
        ],

        'wasabi' => [
            'driver' => 's3',
            'key' => env('WASABI_KEY'),
            'secret' => env('WASABI_SECRET'),
            'region' => env('WASABI_REGION'),
            'bucket' => env('WASABI_BUCKET'),
            'root' => '/',
            'visibility' => 'public',
        ],

        'do_spaces' => [
            'driver' => 's3',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'endpoint' => env('DO_SPACES_ENDPOINT'),
            'visibility' => 'public',
        ],

        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_KEY'),
            'secret' => env('MINIO_SECRET'),
            'region' => env('MINIO_REGION'),
            'bucket' => env('MINIO_BUCKET'),
            'endpoint' => env('MINIO_ENDPOINT'),
            'url' => env('MINIO_URL'),
            'visibility' => 'public',
            'use_path_style_endpoint' => true,
        ],

        'pushr' => [
            'driver' => 's3',
            'key' => env('PUSHR_KEY'),
            'secret' => env('PUSHR_SECRET'),
            'region' => env('PUSHR_REGION', 'us-east-1'),
            'bucket' => env('PUSHR_BUCKET'),
            'url' => env('PUSHR_URL'),
            'endpoint' => env('PUSHR_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'visibility' => 'public',
        ],

    ],

];

