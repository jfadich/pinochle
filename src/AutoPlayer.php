<?php

namespace jfadich\Pinochle;


use jfadich\Pinochle\Cards\Card;
use jfadich\Pinochle\Contracts\Auction;
use jfadich\Pinochle\Contracts\Hand;
use jfadich\Pinochle\Contracts\Seat;

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
        if($this->hand->analysis()->has('trump'))
            return $this->hand->analysis('trump');

        $suits = $this->hand->getCards()->groupBy(function(Card $card) {
            return $card->getSuit();
        });

        $suitValues= [];
        $suits->each(function($cards) use (&$suitValues) {
            $hasAce = $cards->first()->getRank() === Card::RANK_ACE;

            $suitPotential = ($hasAce ? 20 : 15) * $cards->count();

            $suitValues[$cards->first()->getSuit()] = $suitPotential;
        });

        $trump = collect($suitValues)->sort()->reverse()->flip()->first();

        $this->hand->analysis('trump', $trump);

        return $trump;
    }

    public function getNextBid(Auction $auction, Seat $partnerSeat)
    {
        //$bids = collect($currentAuction->get('bids', []));
        $partnerPassed = $auction->seatHasPassed($partnerSeat);

        $maxBid = $this->getMaxBid();
        //$currentBid = $bids->max('bid');
        $currentBid = $auction->getCurrentBid();
        $nextBid = $currentBid + 10;

        //$partnersBids = $bids->filter(function($bid) use($partnerSeat) {
        //    return $bid['seat'] == $partnerSeat;
        //})->sortByDesc('bid');

        $partnersBids = collect($auction->getBidsForSeat($partnerSeat));

        if($partnerPassed || $partnersBids->first()['under'] ?? false)
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