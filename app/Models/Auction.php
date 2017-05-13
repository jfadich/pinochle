<?php

namespace App\Models;


use Jfadich\JsonProperty\JsonProperty;

class Auction
{
    protected $auction;

    public function __construct(JsonProperty $auction)
    {
        $this->auction = $auction;
    }
}