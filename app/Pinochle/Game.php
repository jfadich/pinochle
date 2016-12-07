<?php

namespace App\Pinochle;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public function players()
    {
        return $this->hasMany(Player::class, 'game_id')->orderBy('seat', 'asc');
    }

    public function rounds()
    {
        return $this->hasMany(Round::class, 'game_id');
    }

    public function currentRound()
    {
        return $this->hasOne(Round::class, 'game_id')->orderBy('round_number', 'desc');
    }

    public function getCurrentPlayer()
    {
        return $this->players->where('seat', $this->currentRound->active_seat)->first();
    }
}