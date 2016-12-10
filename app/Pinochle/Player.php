<?php

namespace App\Pinochle;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public $fillable = ['seat', 'user_id'];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getName()
    {
        return $this->isUser() ? $this->user->name : "Computer $this->seat";
    }

    public function isUser()
    {
        return $this->user_id !== null;
    }
}
