<?php

namespace App\Pinochle\Models;


use App\Pinochle\Cards\Card;
use App\Pinochle\MeldRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Hand extends Model
{
    protected $casts = [
        'original' => 'array',
        'current'  => 'array'
    ];

    protected $fillable = [
        'original', 'current', 'player_id'
    ];

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
}