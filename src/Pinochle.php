<?php

namespace jfadich\Pinochle;

use App\Events\GameEvents\GameStarted;
use App\Events\GameEvents\HandDealt;
use App\Events\GameEvents\NewPhaseStarted;
use App\Events\GameEvents\PartnerPassed;
use App\Events\GameEvents\PlayerSeenMeld;
use App\Events\GameEvents\TrumpCalled;
use jfadich\Pinochle\Exceptions\PinochleRuleException;
use jfadich\Pinochle\Contracts\Player;
use jfadich\Pinochle\Contracts\Round;
use App\Events\GameEvents\BidPlaced;
use jfadich\Pinochle\Contracts\Game;
use jfadich\Pinochle\Contracts\Seat;
use jfadich\Pinochle\Cards\Card;
use jfadich\Pinochle\Cards\Deck;

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

    public function deal(Seat $seat)
    {
        if(count($this->game->getSeats()) !== 4) {
            throw new PinochleRuleException('Not Enough Players');
        }

        if(count($this->game->getRounds()) > 0)
            throw new PinochleRuleException('Game has already started.');

        $round = $this->game->nextRound();
        $round->setLeadSeat($seat);
        $this->game->setActiveSeat($seat);

        broadcast(new GameStarted(clone $this->game, $seat));

        $hands = Deck::make()->deal();
        $hands->each(function($cards, $key) use($round) {
            $seat = $this->game->getSeatAtPosition($key);

            if(!$seat)
                throw new \Exception('Seat not filled');

            $round->addHand($seat, $cards);

            broadcast(new HandDealt(clone $this->game, $seat, $cards)); // @todo why clone?
        });

        $round->setPhase(Round::PHASE_BIDDING);

        broadcast(new NewPhaseStarted(clone $this->game, Round::PHASE_BIDDING, $seat->getPlayer()->getName(). ' dealt'));

        $next = $this->game->setNextSeat();

        $round->getAuction()->open($next);

        $nextSeat = $this->game->setNextSeat();

        $round->save();

        if( $nextSeat->getPlayer()->isAuto() ) {
            $player = $round->getAutoPlayerForSeat($nextSeat);
            $nextBid = $player->getNextBid($round->getAuction(), $this->game->getNextSeat(1));

            $this->placeBid($nextSeat, $nextBid);
        }

        return $this;
    }

    public function placeBid(Seat $seat, $bid)
    {
        $this->validateGameState($seat, Round::PHASE_BIDDING);
        $round = $this->game->getCurrentRound();
        $auction = $round->getAuction();

        if($auction->seatHasPassed($seat))
            throw new PinochleRuleException('You have already passed this round');

        if(is_numeric($bid) && $bid % 10 !== 0)
            throw new PinochleRuleException('Bid must be a multiple of 10');

        $current_bid = $auction->getCurrentBid();

        if($bid !== 'pass' && $bid <= $current_bid)
            throw new PinochleRuleException('Bid must be larger than current bid');

        $auction->placeBid($seat, $bid);

        $this->setNextBidder();

        broadcast(new BidPlaced($this->game, $seat, $bid));

        $this->resolveBid();

        return $bid;
    }

    public function callTrump(Seat $seat, $trump)
    {
        if(is_int($trump))
            $trump = new Card($trump);

        $this->validateGameState($seat, Round::PHASE_CALLING);

        $round = $this->game->getCurrentRound();

        $round->setTrump($trump);

        $round->setPhase(Round::PHASE_PASSING);

        $nextSeat = $this->game->setNextSeat(1);

        broadcast(new TrumpCalled($this->game, $seat, $trump));

        if($nextSeat->getPlayer()->isAuto()) {
            $pass = $round->getAutoPlayerForSeat($nextSeat)->getPass($trump);

            $this->passCards($nextSeat, $pass);
        }
    }

    public function passCards(Seat $seat, array $pass)
    {
        $this->validateGameState($seat, Round::PHASE_PASSING);

        $round = $this->game->getCurrentRound();

        $isLeader = $seat->getPosition() === $round->getLeadPosition();

        // Determine if we are passing to the bid winner or the partner
        if($isLeader) {
            $partner = $this->game->getNextSeat(1);
        } else {
            $partner = $this->game->setNextSeat(1);
        }

        $pass = $round->getHandForSeat($seat)->takeCards($pass);
        $round->getHandForSeat($partner)->addCards($pass);

        broadcast(new PartnerPassed($this->game, $seat, $pass));

        if($isLeader) {
            $round->setPhase(Round::PHASE_MELDING);
            $trump = $round->getTrump();

            broadcast(new NewPhaseStarted($this->game, $seat, Round::PHASE_MELDING));

            foreach ($this->game->getSeats() as $meldSeat) {

                $analysis = $round->getAutoPlayerForSeat($partner);

                $round->addMeld($meldSeat, $analysis->getMeld($trump));

                if($meldSeat->getPlayer()->isAuto()) {
                    $this->acceptMeld($meldSeat);
                }
            };
        } else {
            if($partner->getPlayer()->isAuto()) {
                $pass = $round->getAutoPlayerForSeat($partner)->getPassBack($round->getTrump());
                $this->passCards($partner, $pass);
            }
        }

    }

    public function acceptMeld(Seat $seat)
    {
        $round = $this->game->getCurrentRound();

        if(!$round->isPhase(Round::PHASE_MELDING))
            throw new PinochleRuleException('Game is currently not melding');

        $round->setMeldSeen($seat);

        broadcast(new PlayerSeenMeld($this->game, $seat));

        $player = $seat->getPlayer();
        $this->game->addLog($player->id, "{$player->getName()} ready");

        if(count($round->getMeldedSeats()) === 4) {
            $round->setPhase(Round::PHASE_PLAYING);
            broadcast(new NewPhaseStarted($this->game, Round::PHASE_PLAYING));
        }
    }

    public function playTrick(Seat $seat, Card $play)
    {
        $this->validateGameState($seat, Round::PHASE_PLAYING);

        $round = $this->game->getCurrentRound();
        $hand = $round->getHandForSeat($seat);
        $tricks = new TrickRules($round->play_area(), collect($hand->getCurrentCards()), $round->getTrump());

        if( !$tricks->canPlayTrick($play) ) {
            throw new PinochleRuleException('Not a valid play');
        }

        $hand->takeCards([$play]);

        if($round->play_area()->isEmpty('active.plays') && $this->game->active_seat === $round->lead_seat) {
            $round->play_area()->set('active.lead.card', $play);
            $round->play_area()->set('active.lead.seat', $this->game->active_seat);
        }

        $round->play_area()->push('active.plays',[
            'seat'  => $seat->getPosition(),
            'card'  => $play,
            'order' => 1
        ]);
        $nextPlayer = $this->game->setNextSeat();

        // check if next player is auto

        // check if trick is complete and move to the next round of tricks5
    }

    protected function setNextBidder()
    {
        $game = $this->game;
        $auction = $game->getCurrentRound()->getAuction();
        $activeSeat = $game->setNextSeat();

        if($auction->seatHasPassed($activeSeat))
            $this->setNextBidder();
    }

    protected function resolveBid()
    {
        $game = $this->game;
        $round = $game->getCurrentRound();
        $auction = $round->getAuction();

        $activeSeat = $game->getActiveSeat();
        $nextPlayer = $activeSeat->getPlayer();

        if(count($auction->getPassedSeats()) === 3) {
            $round->setPhase(Round::PHASE_CALLING);

            $round->setLeadSeat($activeSeat);

            broadcast(new NewPhaseStarted($this->game,Round::PHASE_CALLING, "{$nextPlayer->getName()} wins the auction with a bid of {$auction->getCurrentBid()}"));

            if($nextPlayer->isAuto()) {
                $this->callTrump($activeSeat, $round->getAutoPlayerForSeat($activeSeat)->callTrump());
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
        $round = $this->game->getCurrentRound();

        if(!$round->isPhase($phase))
            throw new PinochleRuleException("Game is currently not $phase");

        if(!$this->game->seatIsActive($seat))
            throw new PinochleRuleException('It\'s not your turn');

        if($phase === Round::PHASE_CALLING && $round->getLeadPosition() !== $seat->getPosition())
            throw new PinochleRuleException('You must be the bid winner to call trump');
    }
}