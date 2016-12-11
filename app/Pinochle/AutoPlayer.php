<?php
/**
 * Created by PhpStorm.
 * User: jfadi
 * Date: 12/9/2016
 * Time: 5:22 PM
 */

namespace App\Pinochle;


use App\Pinochle\Cards\Card;
use App\Pinochle\Models\Hand;

class AutoPlayer
{
    protected $analyser;

    protected $hand;

    public function __construct(Hand $hand)
    {
        $this->hand = $hand;
        $this->analyser = new HandAnalyser($hand->getCards());
    }

    public function getMaxBid()
    {
        $trump = $this->callTrump();

        $potential = $this->analyser->getMeldPotential($trump);
        $power = $this->analyser->getPlayingPower($trump);
        $wishlist = $this->analyser->getMeldWishList($trump, false);
        $safety = 2 - $wishlist->count();

        return $power + (($potential['total'] + ($safety * 12)) );
    }
    public function callTrump()
    {
        $suits = $this->hand->getCards()->groupBy(function(Card $card) {
            return $card->getSuit();
        });

        $suitValues= [];
        $suits->each(function($cards) use (&$suitValues) {
            $hasAce = $cards->first()->getRank() === Card::RANK_ACE;

            $suitPotential = ($hasAce ? 20 : 15) * $cards->count();

            $suitValues[$cards->first()->getSuit()] = $suitPotential;
        });

        return collect($suitValues)->sort()->reverse()->flip()->first();
    }

    public function __call($method, $parameters)
    {
        if(method_exists($this->analyser, $method))
            return call_user_func_array([$this->analyser, $method], $parameters);

        throw new \BadMethodCallException;
    }
}