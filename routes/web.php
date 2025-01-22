<?php

/*
|--------------------------------------------------------------------------
| EasyCore Route
|--------------------------------------------------------------------------
|
| The Route class is responsible for defining routes and their associated
| handlers in the application. It provides methods for specifying various
| types of HTTP routes such as GET, POST, etc. and mapping them to
| corresponding controller actions or closures.
|
*/

use Core\Route;
use Core\Json;
use App\Middleware\AuthMiddleware;

// Public routes
Route::get('/', function () {
    Json::clean([
        'status' => 'success',
        'message' => 'Welcome to the API',
        'version' => '1.0'
    ]);
});

// Auth routes
Route::group('/api/v1/auth', function () {
    Route::post('/register', 'AuthController@register');
    Route::post('/login', 'AuthController@login');
    Route::get('/logout', 'AuthController@logout');
});

// Protected member routes
Route::group('/api/v1/member', [
    function () {
        Route::get('/profile', 'MemberController@profile');
    },
    'middleware' => AuthMiddleware::class
]);