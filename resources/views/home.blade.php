@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    You are logged in!
                    <br>
                    <a href="{{ route('gmail.connect') }}">Google</a>
                    <a href="{{ route('gmail.mailbox') }}">Bandeja</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
