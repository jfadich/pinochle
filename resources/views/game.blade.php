@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $game->name }}</h1>
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

            </div>
            @endforeach

        </div>

    </div>

@endsection
