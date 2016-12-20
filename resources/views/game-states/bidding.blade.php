@if($game->getCurrentPlayer()->user_id == \Auth::id())
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
    @else
    Waiting for {{ $game->getCurrentPlayer()->getName() }}
@endif
