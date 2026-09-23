@extends('platno::editor.layout')
@section('title', __('platno::editor.pages'))
@section('content')
    <div class="platno-heading">
        <h1>{{ __('platno::editor.pages') }}</h1>
        <a class="platno-button" href="{{ route('platno.pages.create') }}">{{ __('platno::editor.new_page') }}</a>
    </div>
    <form method="get" class="platno-page-filter">
        <div>
            <label for="search">{{ __('platno::editor.search_pages') }}</label>
            <input id="search" name="search" type="search" maxlength="100" value="{{ request('search') }}">
        </div>
        <div>
            <label for="status">{{ __('platno::editor.page_status') }}</label>
            <select id="status" name="status">
                @foreach (['active' => 'active_pages', 'draft' => 'draft', 'published' => 'live', 'archived' => 'archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status', 'active') === $value)>{{ __('platno::editor.'.$label) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit">{{ __('platno::editor.search') }}</button>
    </form>
    @if ($pages->isEmpty())
        <div class="platno-empty">
            <h2>{{ __('platno::editor.empty_title') }}</h2>
            <p>{{ __('platno::editor.empty_description') }}</p>
        </div>
    @else
        <ul class="platno-page-list">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ route('platno.pages.edit', $page->getKey()) }}">{{ $page->title }}<small>{{ $page->slug }}</small></a>
                    <span class="platno-status">{{ __($page->archived_at !== null ? 'platno::editor.archived' : ($page->publication_id ? 'platno::editor.live' : 'platno::editor.draft')) }}</span>
                </li>
            @endforeach
        </ul>
        <nav class="platno-pagination" aria-label="{{ __('platno::editor.pages') }}">
            @if ($pages->previousPageUrl())
                <a href="{{ $pages->previousPageUrl() }}">{{ __('platno::editor.previous') }}</a>
            @endif
            @if ($pages->nextPageUrl())
                <a href="{{ $pages->nextPageUrl() }}">{{ __('platno::editor.next') }}</a>
            @endif
        </nav>
    @endif
@endsection
