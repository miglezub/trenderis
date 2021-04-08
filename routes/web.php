<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('dashboard');
// })->middleware(['auth'])->name('dashboard');;

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth'])->name('dashboard');


// Route::get('/texts', 'App\Http\Controllers\TextController@view')->middleware('auth');

require __DIR__.'/auth.php';

Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/texts/filter', 'App\Http\Controllers\TextController@filter');
    Route::resource('texts', App\Http\Controllers\TextController::class);
    Route::post('/analyse/{id}', 'App\Http\Controllers\TextController@analyse');
    Route::get('/lemmatize/{word}', 'App\Http\Controllers\TextController@lemmatize');
    Route::get('/wordEndings', 'App\Http\Controllers\TextController@wordEndings');

    Route::resource('/keys', App\Http\Controllers\ApiKeyController::class);
});

Route::get('{any}', function () {
    return view('layouts.vue');
})->where('any', '.*');
