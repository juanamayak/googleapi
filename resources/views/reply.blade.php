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
        <form method="post" action="{{ route('gmail.sendReply') }}">
            @csrf

            <input type="hidden" name="to" value="{{ $to }}">

            <div>
                <label for="">Asunto:</label>
                <input type="text" name="asunto">
            </div>

            <div>
                <label for="">Texto:</label>
                <textarea name="mensaje" cols="30" rows="10"></textarea>
            </div>

            <div>
                <button type="submit">Enviar</button>
            </div>
        </form>
    </body>
</html>
