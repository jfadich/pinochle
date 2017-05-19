<?php

namespace jfadich\Pinochle;

use jfadich\Pinochle\Contracts\Seat;
use jfadich\Pinochle\Exceptions\PinochleRuleException;
use jfadich\Pinochle\Cards\Card;
use jfadich\Pinochle\Cards\Deck;
use jfadich\Pinochle\Contracts\Game;
use jfadich\Pinochle\Contracts\Player;
use jfadich\Pinochle\Contracts\Round;

class Pinochle
{
    /**
     * @var Game
     */
    protected $game;

    protected $currentRound;

    public function __construct(Game $game)
    {
        $this->setGame($game);
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
        if(count($this->game->getPlayers()) !== 4) {
            throw new PinochleRuleException('Not Enough Players');
        }

        if(count($this->game->getRounds()) > 0)
            throw new PinochleRuleException('Game has already started.');

        $round = $this->game->nextRound();

        $hands = Deck::make()->deal();
        $hands->each(function($cards, $key) use($round) {
            $round->addHand($key, [
                'dealt' => $cards,
                'current' => $cards,
            ]);
        });

        $round->setPhase(Round::PHASE_BIDDING);
        $this->game->setNextSeat();

        return $this;
    }

    public function placeBid(Seat $seat, int $bid)
    {
        $this->validateGameState($seat, Round::PHASE_BIDDING);
        $round = $this->game->getCurrentRound();
        $auction = $round->getAuction();

        if($auction->seatHasPassed($seat))
            throw new PinochleRuleException('You have already passed this round');

        if($bid % 10 !== 0)
            throw new PinochleRuleException('Bid must be a multiple of 10');

        $current_bid = $auction->getCurrentBid();

        if($bid !== 'pass' && $bid <= $current_bid['bid'])
            throw new PinochleRuleException('Bid must be larger than current bid');

        $this->game->addLog($seat->id, "{$seat->getPlayer()->getName()} bid $bid");
        $auction->placeBid($seat, $bid);

        $this->setNextBidder();

        return $bid;
    }

    public function callTrump(Player $player, $trump)
    {
        if(is_int($trump))
            $trump = new Card($trump);

        $this->validateGameState($player, Round::PHASE_CALLING);

        $round = $this->game->getCurrentRound();

        $round->setTrump($trump);

        $round->setPhase(Round::PHASE_PASSING);

        $this->game->addLog($player->id, "{$player->getName()} called {$trump->getSuitName()} for trump");

        $nextPlayer = $this->game->setNextPlayer(1);


        if($nextPlayer->isAuto()) {
            $pass = $nextPlayer->getAutoPlayer($this->game->currentRound->id)->getPass($trump);

            $this->passCards($nextPlayer, $pass);
        }
    }

    public function passCards(Player $player, $pass)
    {
        $this->validateGameState($player, Round::PHASE_PASSING);

        $round = $this->game->getCurrentRound();

        $isLeader = $round->lead_seat === $this->game->active_seat;

        if($isLeader) {
            $partner = $this->game->getNextSeat(1);
            $partner = $this->game->getPlayerAtSeat($partner);
        } else {
            $partner = $this->game->setNextPlayer(1);
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

    public function acceptMeld($seat)
    {
        if(!$this->game->currentRound->isPhase(Round::PHASE_MELDING))
            throw new PinochleRuleException('Game is currently not melding');

        $this->game->currentRound->meld()->push('ready', $seat);

        if(count($this->game->currentRound->meld('ready', [])) === 4) {
            $this->game->currentRound->phase = Round::PHASE_PLAYING;
        }

        $this->game->currentRound->save();
    }

    public function playTrick(Player $player, Card $play)
    {
        $this->validateGameState($player, Round::PHASE_PLAYING);

        if($this->game->getCurrentRound()->active_seat === $this->game->getCurrentRound()->lead_seat) {

            $this->game->getCurrentRound()->play_area('lease_suit', $play->getSuit());
            $this->game->getCurrentRound()->play_area('plays', [$play->getSuit()]);
            $this->game->getCurrentRound()->save();

            return $this->setNextPlayer();
        }


    }

    protected function setNextBidder()
    {
        $game = $this->game;
        $round = $game->getCurrentRound();
        $auction = $round->getAuction();

        $game->setNextSeat();

        $activeSeat = $game->getActiveSeat();
        $nextPlayer = $game->getCurrentPlayer();


        if($auction->seatHasPassed($activeSeat))
            return $this->setNextBidder();

        if(count($auction->getPassedSeats()) === 3) {
            $round->setPhase(Round::PHASE_CALLING);
            $auction->closeAuction();
            //$round->buy()->merge($round->getCurrentBid(), ['seat', 'bid']);
            $round->setLeadSeat($activeSeat);
            //$round->lead_seat = $game->active_seat;

            //$round->save();
            //$game->save();

            if($nextPlayer->isAuto()) {
                $this->callTrump($nextPlayer, $round->getAutoPlayerForSeat($activeSeat)->callTrump());
                return null;
            }

            return null;
        }

        if( $nextPlayer->isAuto() ) {
            $player = $round->getAutoPlayerForSeat($activeSeat);
            $nextBid = $player->getNextBid($auction, $this->game->getNextSeat(1));

            $this->placeBid($activeSeat, $nextBid);
        }
    }

    protected function validateGameState(Seat $seat, $phase)
    {
        if(!$this->game->getCurrentRound()->isPhase($phase))
            throw new PinochleRuleException("Game is currently not $phase");

        if(!$this->game->seatIsActive($seat))
            throw new PinochleRuleException('It\'s not your turn');
    }
}