<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Pinochle</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @if (Auth::check())
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ url('/login') }}">Login</a>
                        <a href="{{ url('/register') }}">Register</a>
                    @endif
                </div>
            @endif

        </div>


        @foreach($hand->getCards() as $k => $card)
            <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png">

        @endforeach

    <hr><hr>
<h1>meld :{{ $meld['total'] }}. trump: {{ (new App\Pinochle\Cards\Card($trump))->getSuitName() }}</h1>
        @foreach($meld['cards'] as $set)
            @foreach($set as $k => $card)
                <img src="/images/cards/card{{ $card->getSuitName() }}{{ $card->getRankName(true) }}.png">

            @endforeach
            <br>
            @endforeach

        <img src="/images/cards/cardBack_blue5.png">

    </body>
</html>
