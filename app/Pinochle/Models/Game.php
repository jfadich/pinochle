<?php

namespace App\Pinochle\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public $fillable = ['name'];

    public $casts = ['log' => 'array'];

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
        return $this->getPlayerAtSeat($this->currentRound->active_seat);
    }

    public function getPlayerAtSeat($seat)
    {
        return $this->players->where('seat', $seat)->first();
    }

    public function getLog()
    {
        return $this->log ?? [];
    }

    public function addLog($name, $action)
    {
        $log = $this->log;

        $log[] = [
            'time' => time(),
            'player' => $name,
            'text' => $action
        ];

        $this->log = $log;
        $this->save();
    }
}