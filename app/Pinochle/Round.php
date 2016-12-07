<?php

namespace App\Pinochle;

use App\Pinochle\Cards\Hand;
use Illuminate\Database\Eloquent\Model;
use Jfadich\JsonProperty\JsonPropertyInterface;
use Jfadich\JsonProperty\JsonPropertyTrait;

class Round extends Model implements JsonPropertyInterface
{
    use JsonPropertyTrait;

    const PHASE_DEALING = 0;
    const PHASE_BIDDING = 1;
    const PHASE_CALLING = 2;
    const PHASE_PASSING = 3;
    const PHASE_MELDING = 4;
    const PHASE_PLAYING = 5;

    protected $jsonProperty = ['auction', 'buy', 'hands', 'meld'];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function plays()
    {
        return $this->hasMany(Play::class, 'round_id');
    }

    public function getHands()
    {
        foreach($this->hands()->all() as $k => $cards) {
            if(!$cards instanceof Hand)
                $this->hands()->set($k, new Hand($cards));
        }

        return $this->hands()->all();
    }

    public function phase(int $phase)
    {
        return $this->phase === $phase;
    }

    public function addBid($bid, $seat)
    {
        if($bid === 'pass') {
            $this->auction()->push('passers', $seat);
        } else {
            $this->auction()->push('bids', ['seat' => $seat, 'bid' => $bid]);
        }

        $this->save();
    }

    public function getCurrentBid()
    {
        if(empty($this->auction('bids')))
            $this->auction()->push('bids', ['seat' => $this->lead_seat, 'bid' => 250]);

        return collect($this->auction('bids'))->sortByDesc('bid')->first();
    }
}
