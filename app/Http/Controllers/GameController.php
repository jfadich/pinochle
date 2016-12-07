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
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255'
        ]);

        $game = Pinochle::make($request->get('name'))->deal()->getGame();

        return redirect("/games/{$game->id}");
    }

    public function show(Game $game)
    {
        $game->load(['players', 'rounds']);
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

        $pinochle->placeBid($player, $new_bid);

        return redirect("/games/{$game->id}");
    }
}
