<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/openapi.yaml', function () {
    return response()->file(public_path('openapi.yaml'), [
        'Content-Type' => 'application/x-yaml',
    ]);
});
