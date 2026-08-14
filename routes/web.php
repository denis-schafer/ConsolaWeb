<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('emulator');
});

Route::get('/play', function () {
    return view('play');
});
