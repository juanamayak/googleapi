<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    </head>
    <body>
        @if ($listMessagesCollection->count() > 0)
        @foreach ($listMessagesCollection as $message)
            <ul>
                <li><a href="{{ route('gmail.show', ['id' => $message->id, 'from' => $message->from]) }}">{{ $message->from }} | {{$message->subject}} </a></li>
            </ul>
        @endforeach
        @else
        <p>No hay mensajes</p>
        @endif
    </body>
</html>
