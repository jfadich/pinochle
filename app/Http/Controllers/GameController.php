<?php

namespace App\Http\Controllers;

use App\Exceptions\PinochleRuleException;
use App\Pinochle\AutoPlayer;
use App\Pinochle\Cards\Card;
use App\Pinochle\Models\Game;
use App\Pinochle\Models\Hand;
use App\Pinochle\Pinochle;
use App\Pinochle\Models\Player;
use App\Pinochle\Models\Round;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function analysis(Game $game)
    {
        $hands = collect([]);

        foreach($game->currentRound->hands as $key => $hand) {
            $analysis = new AutoPlayer($hand);
            $trump = $analysis->callTrump();

            $hands->push([
                'seat' => $key,
                'player' => $hand->player,
                'cards' => $hand->getDealtCards(),
                'current' => $hand->getCards(),
                'trump' => new Card($trump),
                'meld'  => $analysis->getMeld($trump),
                'potential' => $analysis->getMeldPotential($trump),
                'play_power' => $analysis->getPlayingPower($trump, false),
                'wishlist' => $analysis->getMeldWishList($trump),
                'pass' => $analysis->getPassBack($trump),
                'bid' => $analysis->getMaxBid()
            ]);
        }

        return view('analysis', compact('game', 'hands'));
    }

    public function play(Game $game)
    {
        $game->load(['players.hands']);


        return view('game', compact('game'));
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

        return redirect("/games/{$game->id}");
    }

    public function callTrump(Game $game, Request $request)
    {
        $this->validate($request, [
            'trump' => 'required'
        ]);

        $pinochle = Pinochle::make($game);

        $trump = $request->get('trump');

        $player = Player::findOrFail($request->get('player'));

        $pinochle->callTrump($player, (int)$trump);

        return redirect("/games/{$game->id}");
    }

    public function passCards(Game $game, Request $request)
    {
        $this->validate($request, [
            'cards' => 'required|array|max:4|min:4'
        ]);

        $pinochle = Pinochle::make($game);
        $cards = $request->get('cards');
        $player = Player::findOrFail($request->get('player'));

        $pinochle->passCards($player, $cards);


        return redirect("/games/{$game->id}");
    }
}
