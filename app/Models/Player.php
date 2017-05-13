<?php

namespace App\Models;

use App\Pinochle\AutoPlayer;
use App\Models\Hand;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public $fillable = ['seat', 'user_id'];

    protected $autoPlayer;

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hands()
    {
        return $this->hasMany(Hand::class, 'player_id');
    }

    public function getName()
    {
        return $this->isAuto() ? "Computer $this->seat" : $this->user->name;
    }

    public function isAuto()
    {
        return $this->user_id === null;
    }

    public function getAutoPlayer($round, $reset  = false)
    {
        if($reset || $this->autoPlayer === null)
            $this->autoPlayer = new AutoPlayer($this->getHandForRound($round));

        return $this->autoPlayer;
    }

    public function getCurrentHand()
    {
        return $this->hands->sortByDesc('round_id')->first();
    }

    public function getHandForRound($round)
    {
        return $this->hands->where('round_id', $round)->first();
    }
}
