<?php

namespace App\Pinochle\Models;

use App\Pinochle\Cards\Card;
use Illuminate\Database\Eloquent\Model;
use Jfadich\JsonProperty\JsonPropertyInterface;
use Jfadich\JsonProperty\JsonPropertyTrait;

class Round extends Model implements JsonPropertyInterface
{
    use JsonPropertyTrait;

    const PHASE_DEALING = 'dealing';
    const PHASE_BIDDING = 'bidding';
    const PHASE_CALLING = 'calling';
    const PHASE_PASSING = 'passing';
    const PHASE_MELDING = 'melding';
    const PHASE_PLAYING = 'playing';
    const PHASE_COMPLETE = 'complete';

    protected $jsonProperty = ['auction', 'buy', 'meld'];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function hands()
    {
        return $this->hasMany(Hand::class, 'round_id');
    }
/*
    public function getHands($seat = null)
    {
        foreach($this->hands()->all() as $k => $cards) {
            if($seat !== null && $k = "seat_$seat")
                return new Hand($cards);

            if(!$cards instanceof Hand)
                $this->hands()->set($k, new Hand($cards));

        }

        return $this->hands()->all();
    }
*/
    public function isPhase($phase)
    {
        return $this->phase === $phase;
    }

    public function addBid($bid, $seat)
    {
        $currentBid = $this->getCurrentBid();

        if($bid === 'pass') {
            $this->auction()->push('passers', $seat);
        } else {
            $isJump =  $bid > ($currentBid['bid'] + 10);
            $this->auction()->push('bids', [
                'seat' => $seat,
                'bid' => $bid,
                'under' => false,
                'jump' => $isJump
            ]);
        }

        $this->save();
    }

    public function setTrump(Card $trump)
    {
        $this->trump = $trump->getValue();
    }

    public function getCurrentBid()
    {
       //if(empty($this->auction('bids')))
      //      $this->auction()->push('bids', ['seat' => $this->lead_seat, 'bid' => 250, 'under' => true]);

        return $this->getBids()->first();
    }

    public function getBids()
    {
        if(empty($this->auction('bids')))
            $this->auction()->push('bids', ['seat' => $this->lead_seat, 'bid' => 250, 'under' => true]);

        return collect($this->auction('bids'))->sortByDesc('bid');
    }
}
