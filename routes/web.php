<?php

use Illuminate\Support\Facades\Route;

// Redirect route '/' to '/admin'
Route::get('/', function () {
    return redirect('/admin');
});