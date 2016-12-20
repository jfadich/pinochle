<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<style>
    body,html {
        height: 100%;
    }

    #content {
        display: flex;
        background: black;
        flex-basis:1366px;
        height: 100%;
    }

    #game-table {
        background: green;
        flex:8;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;

    }

    #play-area {
        background: gray;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        display: flex;
        flex:1;
    }

    #player-hand {
        display:flex;
        padding:10px;
    }

    #player-hand .held-card {
        margin-left:-50px;
        flex: 1;
    }

    #player-hand .held-card:first-of-type {
        margin-left:10px;
    }


    #info-pane {
        background: lightcyan;
        flex: 2;
    }
</style>

<div id="content">
    <div id="game-table">
        <div id="play-area">

            @if(!$errors->isEmpty())
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                <hr>
            @endif

            @include("game-states.{$game->currentRound->phase}")
            <i class="fa fa-5x fa-arrow-{{ ['right', 'down', 'left', 'up'][$game->currentRound->active_seat] }}"></i>
        </div>
        <div id="player-hand">

            @if($game->currentRound->phase === \App\Pinochle\Models\Round::PHASE_PASSING)
                <form method="post" action="/api/games/{{$game->id}}/pass" style="display: inherit">
                    {{ csrf_field() }}
                    <input type="hidden" name="player" value="{{ $game->getCurrentPlayer()->id }}">

                @foreach($game->getCurrentPlayer()->getCurrentHand()->getCards() as $k => $card)
                        <div class="held-card">

                            <input type="checkbox" id="hand-{{$card->getValue()}}" name="cards[]" value="{{$card->getValue()}}">
                            <label for="hand-{{$card->getValue()}}">
                            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                            </label>
                        </div>

                    @endforeach

                    <input type="submit" title="Submit">
                </form>
            @else



            @foreach($game->getCurrentPlayer()->getCurrentHand()->getCards() as $k => $card)
                <div class="held-card">
                    <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">
                </div>

            @endforeach
            @endif
        </div>
    </div>


    <div id="info-pane">
        <div id="scoreboard">
            <table class="table">
                <tr>
                    <th></th>
                    <th>Team Alpha</th>
                    <th>Team Bravo</th>
                </tr>
                @foreach($game->rounds as $round)
                    @if($loop->last)
                        <tr>
                            <th>Round {{ $round->number }}</th>
                            <td>
                                @if($game->currentRound->active_seat & $game->getCurrentPlayer()->seat & 0 )
                                {{ $game->currentRound->phase }}
                                @endif
                            </td>
                            <td>
                                @if($game->currentRound->active_seat & $game->getCurrentPlayer()->seat & 1 )
                                    {{ $game->currentRound->phase }}
                                @endif
                            </td>
                        </tr>
                    @else
                        <tr>
                            <th>Round {{ $round->number }}</th>
                            <td> {{ $round->score_team_0 }}</td>
                            <td> {{ $round->score_team_1 }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
        <div id="chat">
            <ul>
                @foreach($game->getLog() as $log)
                    <li>{{ $log['text'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>

</div>