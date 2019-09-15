@extends('layouts.home')

@section('content')

@if (session('alert'))

<div class="notification is-primary" style="margin-bottom: 0;">
    <button class="delete"></button>
    {{ session('alert') }}
</div>

@endif

@if (session('error'))

<div class="notification is-error" style="margin-bottom: 0;">
    <button class="delete"></button>
    {{ session('error') }}
</div>

@endif

<section class="hero">
    <div class="hero-body">
        <form method="POST" action="{{ route('chat.newToken') }}">
            @csrf
            <div class="container">
                <h1 class="title">
                    Add new Tokens
                </h1>
                <h2 class="subtitle">
                    Here you can add new tokens
                </h2>

                <div class="field">
                    <label class="label">Value</label>
                    <div class="control">
                        <input name="value" class="input" type="text" placeholder="Token value...">
                    </div>
                </div>

                <div class="field">
                    <label class="label">Tag</label>
                    <div class="control has-icons-left">
                        <input name="tag" class="input is-success" type="text" placeholder="Token tag...">
                        <span class="icon is-small is-left">
                            <i class="fas fa-flag"></i>
                        </span>
                    </div>
                </div>

                <div class="field">
                    <label class="label">Synonyms</label>
                    <div class="control">
                        <textarea name="synonyms" class="textarea" placeholder="Token syn1#syn2#syn3"></textarea>
                    </div>
                </div>

                <div class="field is-grouped">
                    <div class="control">
                        <button class="button is-link">Continue</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="hero">
    <div class="hero-body">
        <div class="container">
            <h1 class="title">
                List of Tokens
            </h1>
            <table class="table">
                <thead>
                    <tr>
                        <th>Value</th>
                        <th>Tag</th>
                        <th>Synonyms</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tokens as $token)
                    <tr>
                        <td>{{ $token->value }}</td>
                        <td><span class="tag is-light">{{ $token->tag }}</span></td>
                        <td>{{ implode(" # ", $token->synonyms) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
$(document).on('click', '.delete', function(){ 
    $(this).parent().remove()
}); 
</script>


@endsection