<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    echo url(Storage::url('sfadsf'));
    return view('welcome');
});

Route::get('/phpinfo', function () {
    phpinfo();
});
