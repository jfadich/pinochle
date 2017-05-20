<?php

namespace jfadich\Pinochle;

use jfadich\Pinochle\Cards\Card;
use Illuminate\Support\Collection;

class HandAnalyser
{
    protected $cards;

    protected $meld = [
        'total' => 0,
        'cards' => []
    ];

    protected $tolerance = 0.4;
    protected $trumpPower = 15;
    protected $acePower = 10;

    public function __construct($cards)
    {
        if(is_array($cards))
            $cards = collect($cards);

        if(!$cards instanceof Collection)
            throw new \Exception('Invalid hand provided');

        $this->cards = $cards;
    }

    public function getMeld($trump)
    {
        if(is_int($trump))
            $trump = new Card($trump);

        $trumpSuit = $trump->getSuit();

        $this->checkMeld(MeldRules::pinochle(), 40, 300);

        if(!$this->checkMeld(MeldRules::run($trumpSuit), 150)) {
            $this->checkMeld(MeldRules::marriage($trumpSuit), 40, 80);
        }

        foreach(Card::getSuits() as $suit => $name) {
            if($suit === $trumpSuit)
                continue;

            $this->checkMeld(MeldRules::marriage($suit), 20, 40);
        }

        $this->checkMeld(MeldRules::lowestTrump($trumpSuit), 10, 20);
        $this->checkMeld(MeldRules::fourOfAKind(Card::RANK_ACE), 100);
        $this->checkMeld(MeldRules::fourOfAKind(Card::RANK_KING), 80);
        $this->checkMeld(MeldRules::fourOfAKind(Card::RANK_QUEEN), 60);
        $this->checkMeld(MeldRules::fourOfAKind(Card::RANK_JACK), 40);

        return $this->meld;
    }

    public function getMeldWishList($trump, $fillTrump = false)
    {
        $wishList = collect([]);

        $this->checkMeldPotential(MeldRules::run($trump), $wishList);
        $this->checkMeldPotential(MeldRules::fourOfAKind(Card::RANK_ACE), $wishList);

        if($trump === Card::SUIT_SPADES || $trump === Card::SUIT_DIAMONDS)
            $this->checkMeldPotential(MeldRules::pinochle()->merge(MeldRules::pinochle()), $wishList, false);

        if($fillTrump && $wishList->count() < 4) {
            $allTrump = MeldRules::run($trump)->merge(MeldRules::run($trump));
            $wishList = $wishList->merge($allTrump->diff($this->cards)->take(4 - $wishList->count()));
        }

        return $wishList;
    }

    public function getMeldPotential($trump)
    {
        $this->meld = [
            'total' => 0,
            'cards' => []
        ];

        $hand = $this->cards;
        $wishList = $this->getMeldWishList($trump, false);
        $idealHand = $hand->merge($wishList);

        $this->cards = $idealHand;
        $this->cards = $idealHand->diff($this->getPassBack($trump));

        $potential = $this->getMeld($trump);

        $this->cards = $hand;

        return $potential;
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

    protected function checkMeldPotential($meldTemplate, &$wishList, $double = true)
    {
        $hand = $this->cards->merge($wishList);
        $missing = $meldTemplate->diff($hand);

        if( ($missing->count() / $meldTemplate->count()) <= $this->tolerance && ($wishList->count() + $missing->count()) <= 4) {
            $wishList = $wishList->merge($missing);

            if($double)
                return $this->checkMeldPotential($meldTemplate->merge($meldTemplate), $wishList, false);
        }

        return $wishList;
    }

    public function getPass($trump)
    {
        $pass = collect([]);
        $step = 0;
        $cards = $this->cards;

        if($trump instanceof Card)
            $trump = $trump->getSuit();

        do {
            $step++;

            foreach ($cards as $card) {
                if($pass->count() >= 4)
                    return $pass->take(4);

                $add = false;
                /** @var $card Card */
                switch($step) {
                    case 1: $add = $card->isSuit($trump) && !$card->isRank(Card::RANK_NINE);
                        break;
                    case 2: $add = $card->isRank(Card::RANK_ACE);
                        break;
                    case 3: $add = $card->isSuit($trump) && $card->isRank(Card::RANK_NINE);
                        break;
                    case 4: if($trump === Card::SUIT_SPADES || $trump === Card::SUIT_DIAMONDS) {
                        $add = $card->isLegOfPinochle();
                    } break;
                    default:
                        $add = true;
                        break;
                }

                if($add) {
                    $pass->push($card);
                }
            }

            $cards = $cards->diff($pass);
        } while($pass->count() < 4);

        return $pass->take(4);
    }

    public function getPassBack($trump)
    {
        $suits = $this->getPlayingPower($trump, false)->sortBy('power');
        $cards = $this->cards->sort(function(Card $card) use($suits, $trump) {
            return  $suits[$card->getSuit()]['power'] + $card->getRank();
        });

        $pass=collect([]);

        foreach($cards as $card) {
            if($trump === Card::SUIT_SPADES || $trump === Card::SUIT_DIAMONDS) {
                if( $card->isLegOfPinochle() )
                    continue;
            }

            if($card->isSuit($trump) || $card->isRank(Card::RANK_ACE))
                continue;

            $pass->push($card);

            if($pass->count() >= 4)
                return $pass;
        }

        return $pass; // TODO fix edge case for perfect hand with only trump and aces
    }

    public function getPlayingPower($trump, $sum = true)
    {
        $wishList = $this->getMeldWishList($trump);
        $needed = $wishList->sum(function($card) use($trump) { return $card->getSuit() === $trump ? $card->getRank() : 0; } );
        $pass = 0;
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
                $suitStats[$suit]['power'] += $this->trumpPower + $rank + ($suitStats[$suit]['aces'] * $this->acePower) - $needed;
                continue;
            }

            switch($rank)
            {
                case Card::RANK_ACE:
                    $suitStats[$suit]['consecutive']++;
                    $suitStats[$suit]['power'] += ($suitStats[$suit]['aces'] * $this->acePower);
                    break;
                case Card::RANK_TEN:
                    $suitStats[$suit]['power'] += ($rank * $suitStats[$suit]['consecutive']);

                    if($suitStats[$suit]['consecutive'] >= 1)
                        $suitStats[$suit]['consecutive']++;
                    break;
                case Card::RANK_KING:
                    if($suitStats[$suit]['consecutive'] >= 3) {
                        $suitStats[$suit]['consecutive']++;
                        $suitStats[$suit]['power'] += $rank + $suitStats[$suit]['consecutive'];
                    } else {
                        if($pass < 4)
                            $pass++;
                        else
                            $suitStats[$suit]['power'] -= (6 - $suitStats[$suit]['consecutive']);
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

}