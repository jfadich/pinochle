@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $game->name }} : {{ $game->currentRound->phase }}</h1>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            @foreach($game->currentRound->getHands() as $key => $hand)
                <li role="presentation" class="{{ "seat_{$game->currentRound->active_seat}" === $key ? ' active' : '' }}"><a href="#{{ $key }}" role="tab" data-toggle="tab">{{ $key }}</a></li>
            @endforeach
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            @foreach($game->currentRound->getHands() as $key => $hand)
            <div role="tabpanel" class="tab-pane{{ "seat_{$game->currentRound->active_seat}" === $key ? ' active' : '' }}" id="{{ $key }}">
                <div class="row">

                    @foreach($hand->getCards() as $k => $card)
                        <div class="col-md-1">
                            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                        </div>

                    @endforeach
                </div>
<hr>

                <div class="row">
                    <?php $trump = $hand->callTrump(); $meld = $hand->getMeld($trump); $potential = $hand->getMeldPotential($trump) ?>
                    <div class="col-md-8">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#{{ $key }}-potential" role="tab" data-toggle="tab">Meld Potential ({{ $potential['total'] }}) </a></li>
                            <li role="presentation"><a href="#{{ $key }}-meld" role="tab" data-toggle="tab">Meld Dealt ({{ $meld['total'] }}) </a></li>
                        </ul>
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane" id="{{ $key }}-meld">
                                @foreach($meld['cards'] as $set)
                                    <div class="row">
                                        @foreach($set as $k => $card)
                                            <div class="col-md-2">
                                                <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div role="tabpanel" class="tab-pane active" id="{{ $key }}-potential">
                                @foreach($potential['cards'] as $set)
                                    <div class="row">
                                        @foreach($set as $k => $card)
                                            <div class="col-md-2">
                                                <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>




                    </div>
                    <div class="col-md-4">
                        @if($key === "seat_{$game->currentRound->active_seat}")
                            <div class="row">
                                <form action="/api/games/{{ $game->id }}/bids" method="post">
                                    <input type="hidden" name="player" value="{{ $game->getCurrentPlayer()->id }}">
                                    <input name="bid" value="{{ $game->currentRound->getCurrentBid()['bid'] + 10 }}">
                                    <input type="submit">
                                </form>
                                <form action="/api/games/{{ $game->id }}/bids" method="POST">
                                    <input type="hidden" name="player" value="{{ $game->getCurrentPlayer()->id }}">
                                    <input type="submit" name="bid" value="pass">
                                </form>
                            </div>
                        @endif

                        <table class="table table-bordered">
                            <tr>
                                <td>Prefered Trump</td>
                                <td> {{ (new App\Pinochle\Cards\Card($trump))->getSuitName() }}</td>
                            </tr>
                            <tr>
                                <td>Play Power</td>
                                <td>{{ $hand->getPlayingPower($trump) }}</td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    Wishlist
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    @foreach($hand->getMeldWishlist($trump) as $k => $card)
                                        <div class="col-md-3">
                                            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>



            </div>
            @endforeach

        </div>

    </div>

@endsection
