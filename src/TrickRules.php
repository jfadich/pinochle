<?php

namespace jfadich\Pinochle;

use Illuminate\Support\Collection;
use Jfadich\JsonProperty\JsonProperty;
use jfadich\Pinochle\Cards\Card;

class TrickRules
{
    /**
     * @var JsonPropertyInterface
     */
    protected $arena;
    /**
     * @var Collection
     */
    protected $cards;

    public function __construct(JsonProperty $arena, Collection $cards, int $trump)
    {
        $this->arena = $arena;
        $this->cards = $cards;
        $this->trump = $trump;
    }

    public function canPlayTrick(Card $trick)
    {
        if($this->cards->search($trick) === false) {
            return 'Card not in hand';
        }

        if($this->arena->isEmpty('active.plays')) {
            return true;
        }

        $leadCard = new Card( $this->arena->get('active.lead.card') );
        $winningCard = collect($this->arena->get('active.plays'))->reduce(function($carryPlay, $trickPlay) use($leadCard) {
            if($carryPlay === null) {
                return $trickPlay;
            }

            $carry = new Card($carryPlay['card']);
            $trick = new Card($trickPlay['card']);

            if($trick->isSameSuit($leadCard) || $trick->isSameSuit($this->trump)) {
                return $this->canPlayBeatCard($trick, $carry) ? $trickPlay : $carryPlay;
            } else {
                return $carryPlay;
            }
        });

        $suits = $this->cards->groupBy(function($card) {
            return $card->getSuit();
        });

        if($trick->isSameSuit($leadCard)) {
            $winningCard = new Card($winningCard['card']);

            if($this->canPlayBeatCard($trick, $winningCard)) {
                return true;
            }

            $handCanBeatWinningCard = $suits->get($leadCard->getSuit())->filter(function($card) use($winningCard) {
                return $this->canPlayBeatCard($card, $winningCard);
            })->count() > 0;

            if($handCanBeatWinningCard) {
                // If the players hand has a card that can win but it wasn't played
                return false;
            }

            return true;
        } else if($trick->isSameSuit($this->trump)) {
            if($suits->has($leadCard->getSuit()) && !$suits->get($leadCard->getSuit())->isEmpty()) {
                return false;//"Must follow lead suit";
            }

            $handCanBeatWinningCard = $suits->get($this->trump)->filter(function($card) use($winningCard) {
                    return $card->getSuit() > $this->trump;
                })->count() > 0;

            if($handCanBeatWinningCard) {
                // If the players hand has a card that can win but it wasn't played
                return false;
            }

            return true;
        } else {
            if($suits->has($leadCard->getSuit()) && !$suits->get($leadCard->getSuit())->isEmpty()) {
                return false;
            }

            if($suits->has($this->trump) && !$suits->get($this->trump)->isEmpty()) {
                return false;
            }

            return true;
        }
    }

    /** @todo move to a Arena class */
    public function getWinningPlay()
    {
        
    }

    public function canPlayBeatCard(Card $play, Card $card)
    {
        if($play->isSameSuit($card)) {
            return $play->getRank() > $card->getRank();
        }

        if($play->isSameSuit($this->trump)) {
            return true;
        }

        return false;
    }

    public function hasCardsInSuit($suit)
    {
        return $this->cards->filter(function ($card) use($suit) {
            return $card->getSuit() === $suit;
        })->count() > 0 ;
    }
}