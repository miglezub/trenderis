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
    Route::get('/import', 'App\Http\Controllers\TextController@import')->name('import');
    Route::get('/find_synonyms', 'App\Http\Controllers\TextController@find_synonyms');
    Route::get('/dayResults', 'App\Http\Controllers\GraphFilterController@dayResults');
    Route::get('/filterGraph', 'App\Http\Controllers\GraphFilterController@filter');
    Route::get('/recalculate', 'App\Http\Controllers\TextController@recalculate');

    Route::resource('/keys', App\Http\Controllers\ApiKeyController::class);
});

Route::prefix('portal')->group(function () {
    Route::get('/graph', 'App\Http\Controllers\ApiRequestController@graph');
    Route::post('/text', 'App\Http\Controllers\ApiRequestController@addTexts');
    Route::post('/callback', 'App\Http\Controllers\ApiRequestController@callback');
    Route::get('/text/{text_id}', 'App\Http\Controllers\ApiRequestController@text');
});

Route::get('{any}', function () {
    return view('layouts.vue');
})->where('any', '.*');
