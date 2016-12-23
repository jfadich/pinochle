<?php

namespace App\Pinochle;


use App\Exceptions\PinochleRuleException;
use App\Pinochle\Cards\Card;
use App\Pinochle\Cards\Deck;
use App\Pinochle\Models\Game;
use App\Pinochle\Models\Player;
use App\Pinochle\Models\Round;

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
            $game->players()->create(['seat' => 0, 'user_id' => null]);
            $game->players()->create(['seat' => 1, 'user_id' => 1]);
            $game->players()->create(['seat' => 2, 'user_id' => null]);
            $game->players()->create(['seat' => 3, 'user_id' => null]);
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
        $hands->each(function($cards, $key) {
            $this->game->currentRound->hands()->create([
                'dealt' => $cards,
                'current' => $cards,
                'player_id' => $this->game->players[$key]->id
            ]);
        });

        $this->game->currentRound->phase = Round::PHASE_BIDDING;
        $this->setNextPlayer();

        return $this;
    }

    public function placeBid(Player $player, $bid)
    {
        if(!$this->game->currentRound->isPhase(Round::PHASE_BIDDING))
            throw new PinochleRuleException('Game is currently not bidding');
        if($this->game->getCurrentPlayer()->id !== $player->id) {
            //dd($this->game->getCurrentPlayer(), $player);

            throw new PinochleRuleException('It\'s not your turn');
        }

        if(in_array($player->seat, $this->game->currentRound->auction('passers', [])))
            throw new PinochleRuleException('You have already passed this round');

        if($bid % 10 !== 0)
            throw new PinochleRuleException('Bid must be a multiple of 10');

        $current_bid = $this->game->currentRound->getCurrentBid();

        if($bid !== 'pass' && $bid <= $current_bid['bid'])
            throw new PinochleRuleException('Bid must be larger than current bid');

        $this->game->addLog($player->id, "{$player->getName()} bid $bid");
        $this->game->currentRound->addBid($bid, $player->seat);

        $this->setNextBidder();

        return $bid;
    }

    public function callTrump(Player $player, $trump)
    {
        if(is_int($trump))
            $trump = new Card($trump);

        if(!$this->game->currentRound->isPhase(Round::PHASE_CALLING))
            throw new PinochleRuleException('Game is currently not calling');

        if($this->game->getCurrentPlayer()->id !== $player->id)
            throw new PinochleRuleException('It\'s not your turn');

        $this->game->currentRound->setTrump($trump);

        $this->game->currentRound->phase = Round::PHASE_PASSING;

        $this->game->addLog($player->id, "{$player->getName()} called {$trump->getSuitName()} for trump");

        $nextPlayer = $this->setNextPlayer(1);


        if($nextPlayer->isAuto()) {
            $pass = $nextPlayer->getAutoPlayer($this->game->currentRound->id)->getPass($trump);

            $this->passCards($nextPlayer, $pass);
        }
    }

    public function passCards(Player $player, $pass)
    {
        if(!$this->game->currentRound->isPhase(Round::PHASE_PASSING))
            throw new PinochleRuleException('Game is currently not bidding');

        $isLeader = $this->game->currentRound->lead_seat === $this->game->currentRound->active_seat;

        if($isLeader) {
            $partner = $this->getNextSeat(1);
            $partner = $this->game->getPlayerAtSeat($partner);
        } else {
            $partner = $this->setNextPlayer(1);
        }

        $pass = $player->getCurrentHand()->takeCards($pass);
        $partner->getCurrentHand()->addCards($pass);

        $this->game->addLog($player->id, "{$player->getName()} passed cards to {$partner->getName()}");

        if($isLeader) {
            $this->game->currentRound->phase = Round::PHASE_MELDING;

            $this->game->players->each(function($player) {

                $analysis = new HandAnalyser($player->getCurrentHand()->getCards());
                $meld = $analysis->getMeld($this->game->currentRound->trump);

                $this->game->currentRound->meld()->set("players.{$player->seat}", $meld);

                if($player->isAuto()) {
                    $this->game->currentRound->meld()->push('ready', $player->seat);
                }
            });

            $this->game->currentRound->save();
        } else {
            if($partner->isAuto()) {
                $pass = $partner->getAutoPlayer($this->game->currentRound->id)->getPassBack($this->game->currentRound->trump);
                $this->passCards($partner, $pass);
            }
        }
    }

    public function setMeldReady($seat)
    {
        if(!$this->game->currentRound->isPhase(Round::PHASE_MELDING))
            throw new PinochleRuleException('Game is currently not melding');

        $this->game->currentRound->meld()->push('ready', $seat);

        if(count($this->game->currentRound->meld('ready', [])) === 4) {
            $this->game->currentRound->phase = Round::PHASE_PLAYING;
        }

        $this->game->currentRound->save();
    }

    protected function setNextBidder()
    {
        $nextPlayer = $this->setNextPlayer();

        if(in_array($this->game->currentRound->active_seat, $this->game->currentRound->auction('passers', [])))
            return $this->setNextBidder();

        if(count($this->game->currentRound->auction('passers')) === 3) {
            $this->game->currentRound->phase = Round::PHASE_CALLING;
            $this->game->currentRound->buy()->merge($this->game->currentRound->getCurrentBid(), ['seat', 'bid']);
            $this->game->currentRound->lead_seat = $this->game->currentRound->active_seat;

            $this->game->currentRound->save();

            if($nextPlayer->isAuto()) {
                $this->callTrump($nextPlayer, $nextPlayer->getAutoPlayer($this->game->currentRound->id)->callTrump());
                return;
            }

            return;
        }

        if( $nextPlayer->isAuto() ) {
            $player = $nextPlayer->getAutoPlayer($this->game->currentRound->id);
            $nextBid = $player->getNextBid($this->game->currentRound->auction(), $this->getNextSeat(1));

            $this->placeBid($nextPlayer, $nextBid);
        }

    }

    protected function setNextPlayer(int $sameTeam = 0)
    {
        $this->game->currentRound->active_seat = $this->getNextSeat($sameTeam);
        $this->game->currentRound->save();

        return $this->game->getCurrentPlayer();
    }

    protected function getNextSeat( int $sameTeam = 0)
    {
        $activeSeat = $this->game->currentRound->active_seat;

        return ($activeSeat + 1 + $sameTeam ) & 3;
    }

}