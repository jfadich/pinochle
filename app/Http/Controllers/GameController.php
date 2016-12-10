<?php

namespace App\Http\Controllers;

use App\Exceptions\PinochleRuleException;
use App\Pinochle\Cards\Card;
use App\Pinochle\Game;
use App\Pinochle\Pinochle;
use App\Pinochle\Player;
use App\Pinochle\Round;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function analysis(Game $game)
    {
        $hands = collect([]);

        foreach($game->currentRound->getHands() as $key => $hand) {
            $trump = $hand->callTrump();

            $hands->push([
                'seat' => $key,
                'cards' => $hand->getCards(),
                'trump' => new Card($trump),
                'meld'  => $hand->getMeld($trump),
                'potential' => $hand->getMeldPotential($trump),
                'play_power' => $hand->getPlayingPower($trump, false),
                'wishlist' => $hand->getMeldWishList($trump),
                'pass' => $hand->getPass($trump),
                'bid' => $hand->getMaxBid()
            ]);
        }

        return view('game', compact('game', 'hands'));
    }

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
        $pinochle->setNextBidder();

        return redirect("/games/{$game->id}");
    }
}
