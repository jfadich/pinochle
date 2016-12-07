<?php

namespace App\Pinochle;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    const PHASE_DEALING = 'dealing';
    const PHASE_BIDDING = 'bidding';
    const PHASE_CALLING = 'calling';
    const PHASE_PASSING = 'passing';
    const PHASE_MELDING = 'melding';
    const PHASE_PLAYING = 'playing';

    protected $casts = ['auction' => 'json'];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function plays()
    {
        return $this->hasMany(Play::class, 'round_id');
    }
}
