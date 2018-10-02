<?php

namespace jfadich\Pinochle;


use Jfadich\JsonProperty\JsonProperty;
use jfadich\Pinochle\Cards\Card;
use jfadich\Pinochle\Contracts\Auction;
use jfadich\Pinochle\Contracts\Hand;
use jfadich\Pinochle\Contracts\Seat;

class AutoPlayer
{
    protected $analyser;

    protected $hand;

    public function __construct(Hand $hand, JsonProperty $store)
    {
        $this->hand = $hand;
        $this->store = $store;
        $this->analyser = new HandAnalyser($hand->getCurrentCards());
    }

    public function getMaxBid()
    {
        if($this->store->has('maxBid'))
            return $this->store->get('maxBid');

        $trump = $this->callTrump();

        $potential = $this->analyser->getMeldPotential($trump);
        $power = $this->analyser->getPlayingPower($trump);
        $wishlist = $this->analyser->getMeldWishList($trump, false);
        $safety = 2 - $wishlist->count();

        $maxBid = $power + (($potential['total'] + ($safety * 12)) );
        $this->store->set('maxBid', $maxBid);

        return $maxBid;
    }

    public function callTrump()
    {
        if($this->store->has('trump'))
            return $this->store->get('trump');

        $suits = collect($this->hand->getDealtCards())->groupBy(function(Card $card) {
            return $card->getSuit();
        });

        $suitValues= [];
        $suits->each(function($cards) use (&$suitValues) {
            $hasAce = $cards->first()->getRank() === Card::RANK_ACE;

            $suitPotential = ($hasAce ? 20 : 15) * $cards->count();

            $suitValues[$cards->first()->getSuit()] = $suitPotential;
        });

        // Sort by the suits potential, flip keys (suits) and values (potential) to get best suit
        $trump = collect($suitValues)->sort()->flip()->pop();

        $this->store->set('trump', $trump);

        return $trump;
    }

    public function getNextBid(Auction $auction, Seat $partnerSeat)
    {
        $partnerPassed = $auction->seatHasPassed($partnerSeat);

        $maxBid = $this->getMaxBid();
        $currentBid = $auction->getCurrentBid();
        $nextBid = $currentBid + 10;

        $partnersBids = collect($auction->getBidsForSeat($partnerSeat));

        if($partnersBids->isEmpty() || $partnerPassed || $partnersBids->first()['under'] ?? false)
            return $maxBid >= $nextBid ? $nextBid : 'pass' ;

        if($partnersBids->first()['jump'] ?? false)
            return 'pass';

        return $maxBid - $currentBid > 250 ? $nextBid : 'pass';
    }

    public function __call($method, $parameters)
    {
        if(method_exists($this->analyser, $method))
            return call_user_func_array([$this->analyser, $method], $parameters);

        throw new \BadMethodCallException;
    }
}