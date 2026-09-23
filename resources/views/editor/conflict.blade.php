@extends('platno::editor.layout')
@section('title', __('platno::editor.conflict_title'))
@section('content')
    <h1>{{ __('platno::editor.conflict_title') }}</h1>
    <p class="platno-errors" role="alert">{{ __($submitted ? 'platno::editor.conflict_description' : 'platno::editor.conflict_publish') }}</p>
    @if ($submitted)
        <div class="platno-field">
            <label for="submitted-title">{{ __('platno::editor.submitted_title') }}</label>
            <input id="submitted-title" readonly value="{{ $submitted['title'] }}">
        </div>
        <div class="platno-field">
            <label for="submitted-text">{{ __('platno::editor.submitted_text') }}</label>
            <textarea id="submitted-text" readonly>{{ $submitted['text'] ?? json_encode($submitted['document'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
        </div>
    @endif
    <a class="platno-button" href="{{ route('platno.pages.edit', $page->getKey()) }}">{{ __('platno::editor.latest') }}</a>
@endsection
