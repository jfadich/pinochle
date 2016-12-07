<?php

namespace App\Pinochle\Cards;


use App\Pinochle\MeldRules;
use Illuminate\Support\Collection;

class Hand implements \JsonSerializable
{
    private $cards;

    private $meldRules;

    private $meld = [
        'total' => 0,
        'cards' => []
    ];

    public function __construct($cards)
    {
        $this->cards = collect([]);
        foreach($cards as $card) {
            if(!$card instanceof Card)
                $card = new Card($card);

            $this->cards->push($card);
        }

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
        $remainder = false;

        if($meldTemplate->diff($this->cards)->count() === 0) {
            $remainder = $this->cards->diff($meldTemplate);

            if($doublePoints !== false && $meldTemplate->diff($remainder)->count() === 0) {
                $double = $meldTemplate->merge($meldTemplate);
                $remainder = $this->cards->diff($remainder);
                $this->meld['total'] += $doublePoints ?? $points * 10;
                $this->meld['cards'][] = $double;
            } else {
                $this->meld['total'] += $points;
                $this->meld['cards'][] = $meldTemplate;
            }
        }

        return $remainder;
    }

    public function callTrump()
    {
        $suits = $this->cards->groupBy(function(Card $card) {
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

    public function getPlayingPower($trump, $sum = true)
    {
        $pass = 0;
        $trumpPower = 10;
        $acePower = 5;
        $suitStats = [];

        foreach(Card::getSuits() as $id => $name) {
            $suitStats[$id] = [
                'aces' => 0,
                'consecutive' => 0,
                'power' => 0
            ];;
        }

        foreach($this->cards as $card) {
            $suit = $card->getSuit();
            $rank = $card->getRank();

            if($card->isRank(Card::RANK_ACE))
                $suitStats[$suit]['aces']++;

            if($card->isSuit($trump)) {
                $suitStats[$suit]['power'] += $trumpPower + $rank + ($suitStats[$suit]['aces'] * $acePower);
                continue;
            }

            switch($rank)
            {
                case Card::RANK_ACE:
                    $suitStats[$suit]['consecutive']++;
                    $suitStats[$suit]['power'] += ($suitStats[$suit]['aces'] * $acePower);
                    break;
                case Card::RANK_TEN:
                    if($suitStats[$suit]['consecutive'] >= 1)
                        $suitStats[$suit]['consecutive']++;

                    $suitStats[$suit]['power'] += ($rank * $suitStats[$suit]['consecutive']);
                    break;
                case Card::RANK_KING:
                    if($suitStats[$suit]['consecutive'] >= 3) {
                        $suitStats[$suit]['consecutive']++;
                        $suitStats[$suit]['power'] += $rank + $suitStats[$suit]['consecutive'];
                    } else {
                        if($pass < 4)
                            $pass++;
                        else
                            $suitStats[$suit]['power'] -= (10 - $suitStats[$suit]['consecutive']);
                    }
                    break;
                case Card::RANK_NINE:
                    $suitStats[$suit]['power'] -= 5;
                    break;
                default:
                    if($pass < 4)
                        $pass++;
                    else
                        $suitStats[$suit]['power'] -= (3 - $rank) * (6 - $suitStats[$suit]['consecutive']);
                    break;
            }
        }

        $suitStats = collect($suitStats);

        return $suitStats->sum('power');
    }

    public function jsonSerialize()
    {
        return $this->cards->values();
    }
}