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
        <h1>Google API</h1>
        <a href="{{ route('login') }}">Iniciar sesión</a> |
        <a href="{{ route('register') }}">Register</a> |
        <a href="{{ route('gmail.connect') }}">Google</a>

    </body>
</html>
