@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $game->name }} : {{ $game->currentRound->phase }}</h1>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            @foreach($hands as $hand)
                <li role="presentation" class="{{ "seat_{$game->currentRound->active_seat}" === $hand['seat'] ? ' active' : '' }}"><a href="#{{ $hand['seat'] }}" role="tab" data-toggle="tab">{{ $hand['seat'] }}</a></li>
            @endforeach
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            @foreach($hands as $hand)
            <div role="tabpanel" class="tab-pane{{ "seat_{$game->currentRound->active_seat}" === $hand['seat'] ? ' active' : '' }}" id="{{ $hand['seat'] }}">
                <div class="row">

                    @foreach($hand['cards'] as $k => $card)
                        <div class="col-md-1">
                            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                        </div>

                    @endforeach
                </div>
<hr>

                <div class="row">
                    <div class="col-md-8">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#{{ $hand['seat'] }}-potential" role="tab" data-toggle="tab">Meld Potential ({{ $hand['potential']['total'] }}) </a></li>
                            <li role="presentation"><a href="#{{ $hand['seat'] }}-meld" role="tab" data-toggle="tab">Meld Dealt ({{ $hand['meld']['total'] }}) </a></li>
                        </ul>
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane" id="{{ $hand['seat'] }}-meld">
                                @foreach($hand['meld']['cards'] as $set)
                                    <div class="row">
                                        @foreach($set as $k => $card)
                                            <div class="col-md-2">
                                                <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div role="tabpanel" class="tab-pane active" id="{{ $hand['seat'] }}-potential">
                                @foreach($hand['potential']['cards'] as $set)
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
                        @if($hand['seat'] === "seat_{$game->currentRound->active_seat}")
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
                                <td> {{ $hand['trump']->getSuitName() }}</td>
                            </tr>
                            <tr>
                                <td>Play Power</td>
                                <td>{{ $hand['play_power'] }}</td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    Wishlist
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    @foreach($hand['wishlist'] as $k => $card)
                                        <div class="col-md-3">
                                            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    Passback
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    @foreach($hand['pass'] as $k => $card)
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
