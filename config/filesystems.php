<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Supabase Storage (S3-compatible)
        |----------------------------------------------------------------------
        |
        | Supabase Storage uses an S3-compatible API. We use the 's3' driver
        | with Supabase's endpoint. The bucket must be created manually in
        | the Supabase dashboard and set to "Public" for profile photos.
        |
        | Required env vars:
        |   SUPABASE_URL       = https://[project-id].supabase.co
        |   SUPABASE_KEY       = your service_role key (not anon key)
        |   SUPABASE_BUCKET    = profiles (or whatever you name it)
        |
        */
        'supabase' => [
            'driver'                  => 's3',
            'key'                     => env('SUPABASE_KEY'),
            'secret'                  => env('SUPABASE_KEY'),  // Supabase uses the same key for both
            'region'                  => 'ap-southeast-1',     // doesn't matter much for Supabase
            'bucket'                  => env('SUPABASE_BUCKET', 'profiles'),
            'endpoint'                => env('SUPABASE_URL') . '/storage/v1/s3',
            'use_path_style_endpoint' => true,
            'throw'                   => false,
            'report'                  => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
