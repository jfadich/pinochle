@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Dashboard</div>

                <div class="panel-body">
                    <form action="/api/games" method="post">
                        <input name="name" value="">
                        {{ csrf_field() }}

                        <input type="submit">
                    </form>
                    <hr>
                    <ul>
                    @foreach($games as $game)
                        <li><a href="/games/{{$game->id}}">{{ $game->name }}</a> </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
