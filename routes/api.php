<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::group(['prefix' => 'games'], function($router) {
    Route::post('/', 'GameController@store');
    Route::get('/{game}/players', 'GameController@getPlayers');
    Route::post('/{game}/players', 'GameController@addPlayer');
    Route::post('/{game}/bids', 'GameController@placeBid');
    Route::post('/{game}/trump', 'GameController@callTrump');
    Route::post('/{game}/deal', 'GameController@deal');
});