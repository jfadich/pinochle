<?php

namespace App\Pinochle;


use App\Exceptions\PinochleRuleException;
use App\Pinochle\Cards\Deck;
use App\Pinochle\Cards\Hand;

class Pinochle
{
    protected $game;

    protected $currentRound;

    public static function make($game)
    {
        if(!$game instanceof Game) {
            $game = Game::create(['name' => $game]);
            $game->rounds()->create([]);

            // TODO Add 'addPlayer' methods
            $game->players()->create(['seat' => 0, 'user_id' => 1]);
            $game->players()->create(['seat' => 1, 'user_id' => 1]);
            $game->players()->create(['seat' => 2, 'user_id' => 1]);
            $game->players()->create(['seat' => 3, 'user_id' => 1]);
            // end todo
        }

        return (new static())->setGame($game);
    }

    public function setGame(Game $game)
    {
        $this->game = $game;

        return $this;
    }

    public function getGame()
    {
        return $this->game;
    }

    public function deal()
    {
        if($this->game->players->count() !== 4) {
            throw new PinochleRuleException('Not Enough Players');
        }

        $hands = Deck::make()->deal();
        $hands->each(function(Hand $hand, $key) {
            $this->game->currentRound->hands()->set("seat_$key", $hand);
        });

        $this->game->currentRound->phase = Round::PHASE_BIDDING;
        $this->setNextPlayer();

        $this->game->currentRound->save();

        return $this;
    }

    public function placeBid(Player $player, $bid)
    {
        if(!$this->game->currentRound->phase(Round::PHASE_BIDDING))
            throw new PinochleRuleException('Game is currently not bidding');

        if($this->game->getCurrentPlayer()->id !== $player->id)
            throw new PinochleRuleException('It\'s not your turn');

        if(in_array($player->seat, $this->game->currentRound->auction('passers', [])))
            throw new PinochleRuleException('You have already passed this round');

        if($bid % 10 !== 0)
            throw new PinochleRuleException('Bid must be a multiple of 10');

        $current_bid = $this->game->currentRound->getCurrentBid();

        if($bid !== 'pass' && $bid <= $current_bid['bid'])
            throw new PinochleRuleException('Bid must be larger than current bid');

        $this->game->currentRound->addBid($bid, $player->seat);

        $this->setNextBidder();

        return $bid;
    }

    protected function setNextBidder()
    {
        do {
            $this->setNextPlayer();
        } while (in_array($this->game->currentRound->active_seat, $this->game->currentRound->auction('passers', [])));

        if(count($this->game->currentRound->auction('passers')) === 3) {
            $this->game->currentRound->phase = Round::PHASE_CALLING;
            $this->game->currentRound->buy()->merge($this->game->currentRound->getCurrentBid(), ['seat', 'bid']);
            $this->game->currentRound->lead_seat = $this->game->currentRound->active_seat;

            $this->game->currentRound->save();
        }
    }

    protected function setNextPlayer()
    {
        $active_seat = $this->game->currentRound->active_seat;
        $this->game->currentRound->active_seat = $active_seat === 3 ? 0 : $active_seat + 1;

        return $this->game->currentRound->save();
    }
}