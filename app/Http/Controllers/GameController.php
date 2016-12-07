<?php

namespace App\Http\Controllers;

use App\Exceptions\PinochleRuleException;
use App\Pinochle\Game;
use App\Pinochle\Pinochle;
use App\Pinochle\Player;
use App\Pinochle\Round;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function show(Game $game)
    {
        $game->load(['players', 'rounds.plays']);
        return $game;
    }

    public function placeBid(Game $game, Request $request)
    {
        $this->validate($request, [
            'bid' => 'required'
        ]);

        $pinochle = Pinochle::make($game);

        $new_bid = $request->get('bid');

        $player = Player::findOrFail($request->get('player'));

        dd($pinochle->placeBid($player, $new_bid));
    }
}
