<?php

namespace App\Pinochle\Models;

use Illuminate\Database\Eloquent\Model;

class Play extends Model
{
    public $fillable = [
        'type', 'data'
        , 'player_id'
    ];

    public function player()
    {
        return $this->hasOne(Player::class, 'player_id');
    }

    public function scopeBid($query)
    {
        return $query->where('type', 'bid');
    }
}
