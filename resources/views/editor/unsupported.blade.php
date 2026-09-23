@extends('platno::editor.layout')
@section('title', __('platno::editor.unsupported_title'))
@section('content')
    <a href="{{ route('platno.pages.index') }}">← {{ __('platno::editor.back') }}</a>
    <h1>{{ $page->title }}</h1>
    <p class="platno-errors" role="alert">{{ __('platno::editor.unsupported_document') }}</p>
@endsection
