<?php

/**
 * Vercel Serverless Entrypoint for Laravel
 *
 * This file bootstraps Laravel for Vercel's serverless PHP runtime.
 * Since Vercel's filesystem is read-only, we redirect all writable
 * paths (views, cache, sessions, logs) to /tmp.
 */

// 1. Prepare writable storage directories in /tmp
$storageDirs = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 2. Copy Firebase service account to /tmp if it exists in project
$firebaseSource = __DIR__ . '/../storage/app/firebase/service-account.json';
$firebaseTarget = '/tmp/storage/app/firebase/service-account.json';
if (file_exists($firebaseSource) && !file_exists($firebaseTarget)) {
    @mkdir(dirname($firebaseTarget), 0755, true);
    copy($firebaseSource, $firebaseTarget);
}

// 3. Override environment for serverless compatibility
$_ENV['APP_STORAGE'] = '/tmp/storage';
$_SERVER['APP_STORAGE'] = '/tmp/storage';
putenv('APP_STORAGE=/tmp/storage');

// Override storage path in the .env loaded value
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

// 4. Forward to Laravel's public/index.php
require __DIR__ . '/../public/index.php';
