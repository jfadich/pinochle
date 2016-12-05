<?php

namespace App\Pinochle\Cards;


use App\Pinochle\MeldRules;
use Illuminate\Support\Collection;

class Hand
{
    private $cards;

    private $meldRules;

    private $meld = [
        'total' => 0,
        'cards' => []
    ];

    public function __construct($cards)
    {
        if($cards instanceof Collection)
            $this->cards = $cards;
        elseif(is_array($cards))
            $this->cards = collect($cards);

        $this->meldRules = new MeldRules;
    }

    public function getCards()
    {
        return $this->cards;
    }

    public function getMeld($trump)
    {
        if(is_int($trump))
            $trump = new Card($trump);

        $this->checkMeld($this->meldRules->pinochle(), 40, 300);
        if(!$this->checkMeld($this->meldRules->run($trump->getSuit()), 150)) {
            $this->checkMeld($this->meldRules->marriage($trump->getSuit()), 40, 80);
        }

        foreach(Card::getSuits() as $suit => $name) {
            if($suit === $trump->getSuit())
                continue;

            $this->checkMeld($this->meldRules->marriage($suit), 20, 40);
        }

        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_ACE), 100);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_KING), 80);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_QUEEN), 60);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_JACK), 40);

        return $this->meld;
    }

    public function checkMeld($meldTemplate, $points, $doublePoints = null)
    {
        $melded = false;

        if($meldTemplate->diff($this->cards)->count() === 0) {
            $double = $meldTemplate->merge($meldTemplate);

            if($double->diff($this->cards)->count() === 0 && $doublePoints !== false) {
                $this->meld['total'] += $doublePoints ?? $points * 10;
                $this->meld['cards'][] = $double;
            } else {
                $this->meld['total'] += $points;
                $this->meld['cards'][] = $meldTemplate;
            }

            $melded = true;
        }

        return $melded;
    }
}