<?php

namespace App\Models;


use App\Exceptions\PinochleRuleException;
use App\Pinochle\Cards\Card;
use App\Pinochle\MeldRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jfadich\JsonProperty\JsonPropertyInterface;
use Jfadich\JsonProperty\JsonPropertyTrait;

class Hand extends Model implements JsonPropertyInterface
{
    use JsonPropertyTrait;

    protected $casts = [
        'dealt' => 'array',
        'current'  => 'array'
    ];

    protected $fillable = [
        'dealt', 'current', 'player_id'
    ];

    protected $jsonProperty = [
        'analysis'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function getCards()
    {
        return $this->validateCards($this->current ?? []);
    }

    public function getDealtCards()
    {
        return $this->validateCards($this->dealt ?? []);
    }

    public function takeCards($cards)
    {
        $hand = $this->getCards();
        $found = collect([]);

        foreach ($cards as $card) {
            if(!$card instanceof Card)
                $card = new Card($card);

            if(!$hand->contains($card)) {

                throw new PinochleRuleException("{$card->friendlyName()} is not in the current players hand");
            }

            $hand = $hand->forget(array_search($card, $hand->toArray()));
            $found->push($card);
        }

        $this->current = $hand->values()->sort()->reverse()->values();
        $this->save();

        return $found;
    }

    public function addCards($cards)
    {
        $this->current = $this->getCards()->merge($cards)->sort()->reverse()->values();
        $this->save();
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