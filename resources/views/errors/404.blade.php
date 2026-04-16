@extends('layouts.base')

@section('title', '404 - ' . config('app.name', 'LightSchool'))

@section('body')
<div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px">
    <h1 style="font-size: 5em; font-weight: 700; color: #1e6ad3; margin-bottom: 0">404</h1>
    <h2 style="margin-top: 8px; color: #333">{{ __('error-404-title') }}</h2>
    <p style="color: gray; margin-bottom: 32px">{{ __('error-404-description') }}</p>
    <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center">
        <a href="{{ url('/') }}" class="button accent-bkg-gradient box-shadow-1-all">
            {{ __('error-404-go-home') }}
        </a>
    </div>
</div>
@endsection
