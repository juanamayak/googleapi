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
        <p>{{ $data }}</p>

        @if (count($attachments) > 0)
            @foreach ($attachments as $attachment)
                {{-- <a href="{{ route('gmail.attachment', ['id' => $attachment['data']]) }}">{{ $attachment['filename'] }}</a> --}}
                <a href="data:{{ $attachment['mimeType'] }};base64,{{ $attachment['data'] }}" download="{{ $attachment['filename'] }}">{{ $attachment['filename'] }}</a>
                <br>
            @endforeach
        @else
            <p>No hay archivos</p>
        @endif
    </body>
</html>
