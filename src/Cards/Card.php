<?php

namespace jfadich\Pinochle\Cards;

class Card implements \JsonSerializable
{
    const RANK_ACE      = 0b101;
    const RANK_TEN      = 0b100;
    const RANK_KING     = 0b011;
    const RANK_QUEEN    = 0b010;
    const RANK_JACK     = 0b001;
    const RANK_NINE     = 0b000;

    const SUIT_CLUBS    = 0b11000; // 24
    const SUIT_DIAMONDS = 0b10000; // 16
    const SUIT_SPADES   = 0b01000; // 8
    const SUIT_HEARTS   = 0b00000; // 0

    const MASK_RANK     = 0b00111;
    const MASK_SUIT     = 0b11000;
    const MASK_COLOR    = 0b01000;

    private $value;

    /**
     * A cards value is a sum of the rank and suit.
     * It can be constructed with either the rank and suit separately or just the sum.
     * @param $rank
     * @param null $suit
     */
    public function __construct($rank, $suit = null)
    {
        // If the suit is not provided assume the rank represents a precalculated card value
        if($suit === null) {
            $suit = $rank & self::MASK_SUIT;
            $rank = $rank & self::MASK_RANK;
        }

        if(!array_key_exists($rank, static::getRanks()))
            throw new \InvalidArgumentException('Invalid Rank Provided');

        if(!array_key_exists($suit, static::getSuits()))
            throw new \InvalidArgumentException('Invalid Suit Provided');

        $this->value = $rank + $suit;
    }

    public static function getSuits($short = false) : array
    {
        return [
            self::SUIT_CLUBS => $short ? 'C' :  'Clubs',
            self::SUIT_DIAMONDS => $short ? 'D' : 'Diamonds',
            self::SUIT_SPADES => $short ? 'S' : 'Spades',
            self::SUIT_HEARTS => $short ? 'H' : 'Hearts'
        ];
    }

    public static function getRanks($short = false) : array
    {
        return [
            self::RANK_ACE => $short ? 'A' : 'Ace',
            self::RANK_TEN => $short ? '10' : 'Ten',
            self::RANK_KING => $short ? 'K' : 'King',
            self::RANK_QUEEN => $short ? 'Q' : 'Queen',
            self::RANK_JACK => $short ? 'J' : 'Jack',
            self::RANK_NINE => $short ? '9' : 'Nine'
        ];
    }

    public function friendlyName($short = false) : string
    {
        $preposition = !$short ? ' of ' : '';

        return $this->getRankName($short) .$preposition. $this->getSuitName($short);
    }

    public function isRank(int $rank) : bool
    {
        return $this->getRank() === $rank;
    }

    public function isSuit(int $suit) : bool
    {
        return $this->getSuit() === $suit;
    }

    public function isCard(Card $card) : bool
    {
        return $card->getValue() === $this->getValue();
    }

    public function isSameSuit($card)
    {
        if( !$card instanceof Card) {
            $card = new Card($card);
        }

        return $this->getSuit() === $card->getSuit();
    }

    public function isLegOfPinochle() : bool
    {
        return $this->isCard(new Card(Card::RANK_QUEEN, Card::SUIT_SPADES)) || $this->isCard(new Card(Card::RANK_JACK, Card::SUIT_DIAMONDS));
    }

    public function getSuitName($short = false) : string
    {
        $suits = static::getSuits($short);

        return $suits[$this->getSuit()];
    }

    public function getRankName($short = false) : string
    {
        $ranks = static::getRanks($short);

        return $ranks[$this->getRank()];
    }

    public function getRank() : int
    {
        return $this->value & self::MASK_RANK;
    }

    public function getSuit() : int
    {
        return $this->value & self::MASK_SUIT;
    }

    public function getValue() : int
    {
        return $this->value;
    }

    public function transform()
    {
        return [
            'id'    => $this->getValue(),
            'rank'  => $this->getRankName(),
            'suit'  => $this->getSuitName(),
            'shortRank' => $this->getRankName(true)
        ];
    }

    public function __toString() : string
    {
        return $this->friendlyName();
    }

    public function jsonSerialize()
    {
        return $this->value;
    }
}