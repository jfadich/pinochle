<?php

namespace App\Pinochle\Cards;

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

    public function deal()
    {
        $hands = collect();

        $this->cards->shuffle()->chunk(12)->each(function($hand) use($hands) {
            $hands->push($hand->sort()->reverse());
        });

        return $hands;
    }
}