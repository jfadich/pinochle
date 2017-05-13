<?php

namespace App\Models;

use App\Models\Round;
use Illuminate\Database\Eloquent\Model;

class Game extends Model implements \App\Pinochle\Contracts\Game
{
    public $fillable = ['name', 'join_code'];

    public $with = ['players', 'rounds'];

    public $casts = ['log' => 'array'];

    public static function make($attributes)
    {
        $game = static::create($attributes);
        $game->rounds()->create([]);

        return $game;
    }

    public function players()
    {
        return $this->hasMany(Player::class, 'game_id')->orderBy('seat', 'asc');
    }

    public function addPlayer($attributes)
    {
        return $this->players()->create($attributes);
    }

    public function rounds()
    {
        return $this->hasMany(Round::class, 'game_id');
    }

    public function currentRound()
    {
        return $this->hasOne(Round::class, 'game_id')->orderBy('number', 'desc');
    }

    public function getCurrentPlayer()
    {
        return $this->getPlayerAtSeat($this->active_seat);
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

    /**
     * @return Round
     */
    public function getCurrentRound()
    {
        return $this->currentRound;
    }

    /**
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     */
    public function getRounds()
    {
        return $this->rounds;
    }

    /**
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * @return Round
     */
    public function nextRound()
    {
        $rounds = $this->rounds;

        if($rounds->isEmpty())
            return $this->rounds()->create([]);
        // TODO: Implement nextRound() method.
    }


    public function setNextPlayer(int $sameTeam = 0)
    {
        $this->active_seat = $this->getNextSeat($sameTeam);
        $this->save();

        return $this->getCurrentPlayer();
    }

    public function getNextSeat( int $sameTeam = 0 )
    {
        return ($this->active_seat + 1 + $sameTeam ) & 3;
    }
}