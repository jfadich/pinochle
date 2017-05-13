@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $game->name }} : {{ $game->currentRound->phase }}</h1>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            @foreach($hands as $hand)
                <li role="presentation" class="{{ $game->currentRound->active_seat === $hand['player']->seat ? ' active' : '' }}"><a href="#{{ $hand['seat'] }}" role="tab" data-toggle="tab">{{ $hand['seat'] }}</a></li>
            @endforeach
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            @foreach($hands as $hand)
            <div role="tabpanel" class="tab-pane{{ $game->currentRound->active_seat === $hand['player']->seat ? ' active' : '' }}" id="{{ $hand['seat'] }}">
                <div class="row">

                    @foreach($hand['cards'] as $k => $card)
                        <div class="col-md-1">
                            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                        </div>

                    @endforeach
                </div>

                <hr>
                <div class="row">

                    @foreach($hand['current'] as $k => $card)
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
                        @if($game->currentRound->phase === \App\Pinochle\Contracts\Round::PHASE_BIDDING && $game->currentRound->active_seat === $hand['player']->seat)
                            <div class="row">
                                <form action="/api/games/{{ $game->id }}/bids" method="post">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="player" value="{{ $game->getCurrentPlayer()->id }}">
                                    <input name="bid" value="{{ $game->currentRound->getCurrentBid()['bid'] + 10 }}">
                                    <input type="submit">
                                </form>
                                <form action="/api/games/{{ $game->id }}/bids" method="POST">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="player" value="{{ $game->getCurrentPlayer()->id }}">
                                    <input type="submit" name="bid" value="pass">
                                </form>
                            </div>
                        @endif

                        <table class="table table-bordered">
                            <tr>
                                <th colspan="2">Bid</th>
                            </tr>
                            <tr>
                                <td>Preferred Trump</td>
                                <td> {{ $hand['trump']->getSuitName() }}</td>
                            </tr>
                            <tr>
                                <td>Estimated Bid</td>
                                <td>{{ $hand['bid'] }}</td>
                            </tr>
                            <tr>
                                <th colspan="2">Play Power</th>
                            </tr>
                            <tr>
                                <td>
                                    @foreach($hand['play_power'] as $suit => $stats)
                                        <strong>{{ (new \App\Pinochle\Cards\Card($suit))->getSuitName(true) }}</strong> {{ $stats['power'] }}
                                    @endforeach
                                </td>
                                <td>{{ $hand['play_power']->sum('power') }}
                                </td>
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
                                    Pass
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <ul class="nav nav-tabs" role="tablist">
                                            <li role="presentation" class="active"><a href="#{{$hand['seat']}}pass-suit0" role="tab" data-toggle="tab">Hearts</a></li>
                                            <li role="presentation"><a href="#{{$hand['seat']}}pass-suit8" role="tab" data-toggle="tab">Spades</a></li>
                                            <li role="presentation"><a href="#{{$hand['seat']}}pass-suit16" role="tab" data-toggle="tab">Diamonds</a></li>
                                            <li role="presentation"><a href="#{{$hand['seat']}}pass-suit24" role="tab" data-toggle="tab">Clubs</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        @foreach($hand['pass'] as $suit => $pass)
                                        <div role="tabpanel" class="tab-pane {{ $loop->first ? 'active' : '' }}" id="{{$hand['seat']}}pass-suit{{ $suit }}">
                                            @foreach($pass as $k => $card)
                                                <div class="col-md-3">
                                                    <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                                                </div>
                                            @endforeach
                                        </div>
                                        @endforeach
                                    </div>

                                </td>
                            </tr>
                        </table>

                        <ul>
                            @foreach($game->getLog() as $log)
                                <li>{{ $log['text'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>



            </div>
            @endforeach

        </div>

    </div>

@endsection
