@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email__container">
    <div class="verify-email__message">
        <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p>メール認証を完了してください。</p>
    </div>

    @if (session('message'))
    <p class="verify-email__success">{{ session('message') }}</p>
    @endif

    <div class="verify-email__main-action">
        <a href="http://localhost:8025" target="_blank" class="btn btn-primary">認証はこちらから</a>
    </div>

    <div class="verify-email__resend">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-link">認証メールを再送信する</button>
        </form>
    </div>
</div>
@endsection