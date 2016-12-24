<?php

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

use App\Pinochle\Cards\Card;
use App\Pinochle\Game;

Route::get('games/{game}/analysis', 'GameController@analysis');
Route::get('games/{game}', 'GameController@play');

Route::get('/', function () {
    $hands = App\Pinochle\Cards\Deck::make()->deal();
    $hand = $hands[0];

    $trump = $hand->callTrump();
    $meld = $hand->getMeld($trump);

//dd((new App\Pinochle\Meld())->availableMeld(8),$hands);
    return view('welcome', compact('hand', 'meld', 'trump'));
});

Auth::routes();

Route::get('/home', 'HomeController@index');
