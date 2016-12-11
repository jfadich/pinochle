<?php

namespace App\Pinochle\Models;


use App\Pinochle\Cards\Card;
use App\Pinochle\MeldRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Hand extends Model
{
    private $meldRules;

    private $meld = [
        'total' => 0,
        'cards' => []
    ];

    protected $casts = [
        'original' => 'array',
        'current'  => 'array'
    ];

    protected $fillable = [
        'original', 'current', 'player_id'
    ];

   // Model: as_dealt, current, analysis

    public function __construct($attributes = [])
    {
        $this->meldRules = new MeldRules;

        parent::__construct($attributes);
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function getCards()
    {
        return $this->validateCards($this->current ?? []);
    }

    public function getOriginalCards()
    {
        return $this->validateCards($this->original ?? []);
    }

    public function getCard(Card $card)
    {

    }

    public function addCards()
    {

    }

    protected function validateCards(array $cards)
    {
        $collection = collect([]);

        foreach($cards as $card) {
            if(!$card instanceof Card)
                $card = new Card($card);

            $collection->push($card);
        }

        return $collection;
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

        $hand = $this->getCards();
        $wishList = $this->getMeldWishList($trump, false);
        $idealHand = $hand->merge($wishList);

        $this->current = $idealHand;
        $this->current = $idealHand->diff($this->getPass($trump));// dd($this->original, $this->getMeld($trump));

        $potential = $this->getMeld($trump);

        $this->current = $hand;

        return $potential;
    }

    public function getMeldWishList($trump, $fillTrump = false)
    {
        $wishList = collect([]);

        $this->checkMeldPotential($this->meldRules->fourOfAKind(Card::RANK_ACE), $wishList);
        $this->checkMeldPotential($this->meldRules->run($trump), $wishList);

        if($trump === Card::SUIT_SPADES || $trump === Card::SUIT_DIAMONDS)
            $this->checkMeldPotential($this->meldRules->pinochle()->merge($this->meldRules->pinochle()), $wishList, false);

        if($fillTrump && $wishList->count() < 4) {
            $allTrump = $this->meldRules->run($trump)->merge($this->meldRules->run($trump));
            $wishList = $wishList->merge($allTrump->diff($this->getCards())->take(4 - $wishList->count()));
        }

        return $wishList;
    }

    public function getMaxBid()
    {
        $trump = $this->callTrump();

        $potential = $this->getMeldPotential($trump);
        $power = $this->getPlayingPower($trump);
        $wishlist = $this->getMeldWishList($trump, false);
        $safety = 2 - $wishlist->count();

        return $power + (($potential['total'] + ($safety * 12)) );
    }

    protected function checkMeld($meldTemplate, $points, $doublePoints = null)
    {
        $remainder = false;

        if($meldTemplate->diff($this->getCards())->count() === 0) {
            $found = collect([]);
            $remainder = $this->getCards()->filter(function($card, $key) use ($found, $meldTemplate) {
                if($meldTemplate->contains($card) && !$found->contains($card)) {
                    $found->push($card);
                    return false;
                }

                return true;
            });

            if($doublePoints !== false && $meldTemplate->diff($remainder)->count() === 0) {
                $double = $meldTemplate->merge($meldTemplate);
                $remainder = $this->getCards()->diff($remainder);
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
        $cards = $this->getCards()->sort(function($card) use($suits, $trump) {
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

        return $pass;
    }

    protected function checkMeldPotential($meldTemplate, &$wishList, $double = true)
    {
        $tolerance = 0.4;
        $hand = $this->getCards()->merge($wishList);
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
        $suits = $this->getCards()->groupBy(function(Card $card) {
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

    public function getCardPower($cards)
    {

    }

    public function getPlayingPower($trump, $sum = true)
    {
        $wishList = $this->getMeldWishList($trump);
        $needed = $wishList->sum(function($card) use($trump) { return $card->getSuit() === $trump ? $card->getRank() : 0; } );
        $pass = 0;
        $trumpPower = 15;
        $acePower = 10;
        $suitStats = [];
        $cards = $this->getCards()->merge($wishList);

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
                $suitStats[$suit]['power'] += $trumpPower + $rank + ($suitStats[$suit]['aces'] * $acePower) - $needed;
                continue;
            }

            switch($rank)
            {
                case Card::RANK_ACE:
                    $suitStats[$suit]['consecutive']++;
                    $suitStats[$suit]['power'] += ($suitStats[$suit]['aces'] * $acePower);
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