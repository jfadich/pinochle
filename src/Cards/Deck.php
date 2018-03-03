<?php

namespace jfadich\Pinochle\Cards;

class Deck
{
    protected $cards;

    public function __construct(array $cards)
    {
        $this->cards = collect($cards);
    }

    public static function make()
    {
        $cards = [];

        foreach(array_keys(Card::getSuits()) as $suit)
        {
            foreach(array_keys(Card::getRanks()) as $rank)
            {
                $cards[] = new Card($rank, $suit);
                $cards[] = new Card($rank, $suit);
            }
        }

        return new Deck($cards);
    }

    public function deal($numberOfHands = 4)
    {
        $hands = collect();
        $perHand = floor($this->cards / $numberOfHands);

        $this->cards->shuffle()->chunk($perHand)->each(function($hand) use($hands) {
            $hands->push($hand->sort()->reverse()->values());
        });

        return $hands;
    }
}