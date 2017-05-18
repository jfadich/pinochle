<?php

namespace jfadich\Pinochle;

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

    public function placeBid(Player $player, $bid)
    {
        $this->validateGameState($player, Round::PHASE_BIDDING);

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
        $round = $this->game->getCurrentRound();
        $nextPlayer = $this->game->setNextPlayer();

        if(in_array($this->game->active_seat, $round->auction('passers', [])))
            return $this->setNextBidder();

        if(count($round->auction('passers')) === 3) {
            $round->setPhase(Round::PHASE_CALLING);
            $round->buy()->merge($round->getCurrentBid(), ['seat', 'bid']);
            $round->lead_seat = $this->game->active_seat;

            $round->save();
            $this->game->save();

            if($nextPlayer->isAuto()) {
                $this->callTrump($nextPlayer, $nextPlayer->getAutoPlayer($round->id)->callTrump());
                return;
            }

            return;
        }

        if( $nextPlayer->isAuto() ) {
            $player = $nextPlayer->getAutoPlayer($this->game->currentRound->id);
            $nextBid = $player->getNextBid($this->game->currentRound->auction(), $this->game->getNextSeat(1));

            $this->placeBid($nextPlayer, $nextBid);
        }

    }

    protected function validateGameState(Player $player, $phase)
    {
        if(!$this->game->currentRound->isPhase($phase))
            throw new PinochleRuleException("Game is currently not $phase");

        if($this->game->getCurrentPlayer()->id !== $player->id)
            throw new PinochleRuleException('It\'s not your turn');
    }
}