<?php

namespace jfadich\Pinochle;

use jfadich\Pinochle\Cards\Card;

class MeldRules
{
    protected $meldTable = [];

    public static function pinochle()
    {
        return collect([
            new Card(Card::RANK_JACK, Card::SUIT_DIAMONDS),
            new Card(Card::RANK_QUEEN, Card::SUIT_SPADES)
        ]);
    }

    public static function run($suit)
    {
        return collect([
            new Card(Card::RANK_ACE, $suit),
            new Card(Card::RANK_TEN, $suit),
            new Card(Card::RANK_KING, $suit),
            new Card(Card::RANK_QUEEN, $suit),
            new Card(Card::RANK_JACK, $suit),
        ]);
    }

    public static function marriage($suit)
    {
        return collect([
            new Card(Card::RANK_KING, $suit),
            new Card(Card::RANK_QUEEN, $suit)
        ]);
    }

    public static function fourOfAKind($rank)
    {
        return collect([
            new Card($rank, Card::SUIT_CLUBS),
            new Card($rank, Card::SUIT_DIAMONDS),
            new Card($rank, Card::SUIT_SPADES),
            new Card($rank, Card::SUIT_HEARTS),
        ]);
    }

    public static function lowestTrump($suit)
    {
        return collect([
            new Card(Card::RANK_NINE, $suit)
        ]);
    }

    public function availableMeld($suit = null)
    {
        if(count($this->meldTable) > 0)
            return $this->meldTable;

        if($suit !== null) {
            if(!$suit instanceof Card) {
                $suit = new Card($suit);
            }

            $suits = [$suit->getSuit() => $suit->getSuitName()];
        } else {
            $suits = Card::getSuits();
        }

        $this->addMeld($this->pinochle(), 'Pinochle', 40);
        $this->addMeld($this->pinochle()->merge($this->pinochle()), 'Double Pinochle', 300);
        $this->addMeld($this->fourOfAKind(Card::RANK_JACK), 'Forty Jacks', 40);
        $this->addMeld($this->fourOfAKind(Card::RANK_JACK)->merge($this->fourOfAKind(Card::RANK_JACK)), 'Four Hundred Jacks', 400);
        $this->addMeld($this->fourOfAKind(Card::RANK_QUEEN), 'Sixty Queens', 60);
        $this->addMeld($this->fourOfAKind(Card::RANK_QUEEN)->merge($this->fourOfAKind(Card::RANK_QUEEN)), 'Six Hundred Queens', 60);
        $this->addMeld($this->fourOfAKind(Card::RANK_KING), 'Eighty Kings', 80);
        $this->addMeld($this->fourOfAKind(Card::RANK_KING)->merge($this->fourOfAKind(Card::RANK_KING)), 'Eight Hundred Kings', 80);
        $this->addMeld($this->fourOfAKind(Card::RANK_ACE), 'Hundred Aces', 100);
        $this->addMeld($this->fourOfAKind(Card::RANK_ACE)->merge($this->fourOfAKind(Card::RANK_ACE)), 'Thousand Aces', 1000);

        foreach($suits as $suit => $name) {
            $this->addMeld($this->marriage($suit), "$name Marriage", 40);
            $this->addMeld($this->run($suit), 'Run in Trump', 150);
            $this->addMeld($this->run($suit)->merge($this->run($suit)), 'Double Run in Trump', 1500);
            $this->addMeld($this->lowestTrump($suit), "Lowest Trump", 10);
        }

        return $this->meldTable;
    }

    protected function addMeld($meld, $name, $points)
    {
        $id = $meld->sum(function($card) { return $card->getValue(); });

        if(array_key_exists($id, $this->meldTable))
            throw new \Exception('!!!!!!Duplicate Meld Value!!!!');

        $cards = [];
        foreach($meld as $card) {
            $cards[]= $card;
        }


        $this->meldTable["$name ($id)"] = [
            [$points, $cards]
        ];
    }
}