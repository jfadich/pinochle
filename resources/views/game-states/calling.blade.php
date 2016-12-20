@if($game->getCurrentPlayer()->user_id == \Auth::id())
    <div class="row">
        <form action="/api/games/{{ $game->id }}/trump" method="post">
            <input type="hidden" name="player" value="{{ $game->getCurrentPlayer()->id }}">
            {{ csrf_field() }}
            <input name="trump" type="radio" value="0"> Hearts
            <input name="trump" type="radio" value="8"> Spades
            <input name="trump" type="radio" value="16"> Diamonds
            <input name="trump" type="radio" value="24"> Clubs
            <input type="submit">
        </form>
    </div>
@else
    Waiting for {{ $game->getCurrentPlayer()->getName() }}
@endif
