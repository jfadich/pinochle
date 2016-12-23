<table class="table table-striped">
    <tr>
        <th>Total</th>
        <th>Meld</th>
    </tr>
    @foreach($game->currentRound->meld('players') as $k => $meld)

    <tr>
        <td>{{ $meld['total'] }}</td>
        <td>
            <div style="display: inline-flex">
                @foreach($meld['cards'] as $k => $set)
                    @foreach($set as $card)
                        <?php $card = new \App\Pinochle\Cards\Card($card); ?>
                        <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png" class="img-responsive">

                    @endforeach
                @endforeach
            </div>

        </td>
    </tr>
    @endforeach

</table>

<form method="post" action="/api/games/{{ $game->id }}/meld">
    {{ csrf_field() }}
    <input type="hidden" name="seat" value="{{ $game->currentRound->active_seat }}">
    <input type="submit" title="Ready!" value="Ready">
</form>