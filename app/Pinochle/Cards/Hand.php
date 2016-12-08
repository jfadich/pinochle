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

    public function getMeld($trump, $cards = null)
    {
        if(is_int($trump))
            $trump = new Card($trump);

        $this->meld = [
            'total' => 0,
            'cards' => []
        ];

        $this->checkMeld($this->meldRules->pinochle(), 40, 300);
        if(!$this->checkMeld($this->meldRules->run($trump->getSuit()), 150)) {
            $this->checkMeld($this->meldRules->marriage($trump->getSuit()), 40, 80);
        }

        foreach(Card::getSuits() as $suit => $name) {
            if($suit === $trump->getSuit())
                continue;

            $this->checkMeld($this->meldRules->marriage($suit), 20, 40);
        }

        $this->checkMeld($this->meldRules->lowestTrump($trump->getSuit()), 10, 20);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_ACE), 100);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_KING), 80);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_QUEEN), 60);
        $this->checkMeld($this->meldRules->fourOfAKind(Card::RANK_JACK), 40);

        return $this->meld;
    }

    public function getMeldPotential($trump)
    {
        $this->meld = [
            'total' => 0,
            'cards' => []
        ];

        $hand = $this->cards;
        $wishList = $this->getMeldWishList($trump, true);

        if($wishList->count() < 4) {
            $allTrump = $this->meldRules->run($trump)->merge($this->meldRules->run($trump));
            $wishList->merge($allTrump->diff($hand)->chunk(4 - $wishList->count())->first());
        }

        $this->cards = $hand->merge($wishList);
        $this->cards = $this->cards->diff($this->getPass($trump));
        $potential = $this->getMeld($trump);

        $this->cards = $hand;

        return $potential;
    }

    public function getMeldWishList($trump, $fillTrump = false)
    {
        $wishList = collect([]);

        $this->checkMeldPotential($this->meldRules->fourOfAKind(Card::RANK_ACE), $wishList);
        $this->checkMeldPotential($this->meldRules->run($trump), $wishList);
        $this->checkMeldPotential($this->meldRules->pinochle()->merge($this->meldRules->pinochle()), $wishList, false);

        if($fillTrump && $wishList->count() < 4) {
            $allTrump = $this->meldRules->run($trump)->merge($this->meldRules->run($trump));
            $wishList = $wishList->merge($allTrump->diff($this->cards)->chunk(4 - $wishList->count())->first());
        }

        return $wishList;
    }

    protected function checkMeld($meldTemplate, $points, $doublePoints = null)
    {
        $remainder = false;

        if($meldTemplate->diff($this->cards)->count() === 0) {
            $found = collect([]);
            $remainder = $this->cards->filter(function($card, $key) use ($found, $meldTemplate) {
                if($meldTemplate->contains($card) && !$found->contains($card)) {
                    $found->push($card);
                    return false;
                }

                return true;
            });

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

    public function getPass($trump)
    {
        $suits = $this->getPlayingPower($trump, false)->sortBy('power');
        $cards = $this->cards->sort(function($card) use($suits, $trump) {
            return  $suits[$card->getSuit()]['power'] + $card->getRank();
        });

        $pass=collect([]);

        foreach($cards as $card) {
            if($card->isSuit($trump) || $card->isRank(Card::RANK_ACE))
                continue;

            $pass->push($card);

            if($pass->count() >= 4)
                return $pass;
        }
    }

    protected function checkMeldPotential($meldTemplate, &$wishList, $double = true)
    {
        $tolerance = 0.25;
        $hand = $this->cards->merge($wishList);
        $missing = $meldTemplate->diff($hand);

        if( ($missing->count() / $meldTemplate->count()) <= $tolerance && ($wishList->count() + $missing->count()) <= 4) {
            $wishList = $wishList->merge($missing);

            if($double)
                return $this->checkMeldPotential($meldTemplate->merge($meldTemplate), $wishList, false);
        }

        return $wishList;
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
        $wishList = $this->getMeldWishList($trump);
        $pass = 0;
        $trumpPower = 10;
        $acePower = 5;
        $suitStats = [];
        $cards = $this->cards->merge($wishList);

        foreach(Card::getSuits() as $id => $name) {
            $suitStats[$id] = [
                'aces' => 0,
                'consecutive' => 0,
                'power' => 0
            ];;
        }

        foreach($cards as $card) {
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

        return $sum ? $suitStats->sum('power') : $suitStats;
    }

    public function jsonSerialize()
    {
        return $this->cards->values();
    }
}