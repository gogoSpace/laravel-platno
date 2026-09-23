@extends('platno::editor.layout')
@section('title', $page->title)
@section('content')
    <a href="{{ route('platno.pages.index', ['status' => 'archived']) }}">← {{ __('platno::editor.archived') }}</a>
    <h1>{{ $page->title }}</h1>
    <p>{{ __('platno::editor.archived_help') }}</p>
    <div class="platno-actions">
        <form method="post" action="{{ route('platno.pages.unarchive', $page->getKey()) }}">
            @csrf
            <input type="hidden" name="revision" value="{{ $page->revision }}">
            <button type="submit">{{ __('platno::editor.unarchive') }}</button>
        </form>
        <a href="{{ route('platno.pages.history', $page->getKey()) }}">{{ __('platno::editor.history') }}</a>
    </div>
@endsection
