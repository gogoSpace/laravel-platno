@extends('platno::editor.layout')
@section('title', __('platno::editor.history'))
@section('content')
    <a href="{{ route('platno.pages.edit', $page->getKey()) }}">← {{ $page->title }}</a>
    <h1>{{ __('platno::editor.history') }}</h1>
    <p>{{ __('platno::editor.history_help') }}</p>
    <ul class="platno-page-list">
        @forelse ($publications as $publication)
            <li>
                <div>
                    <a href="{{ route('platno.pages.publication', [$page->getKey(), $publication->getKey()]) }}">{{ $publication->title }}</a>
                    <small>{{ $publication->created_at->format('Y-m-d H:i') }} · {{ __('platno::editor.revision', ['revision' => $publication->source_revision]) }}</small>
                </div>
                @if ($page->archived_at === null)
                    <form method="post" action="{{ route('platno.pages.restore', [$page->getKey(), $publication->getKey()]) }}">
                        @csrf
                        <input type="hidden" name="revision" value="{{ $page->revision }}">
                        <button class="platno-quiet" type="submit">{{ __('platno::editor.restore_draft') }}</button>
                    </form>
                @endif
            </li>
        @empty
            <li>{{ __('platno::editor.empty_history') }}</li>
        @endforelse
    </ul>
    <nav class="platno-pagination" aria-label="{{ __('platno::editor.history') }}">
        @if ($publications->previousPageUrl())<a href="{{ $publications->previousPageUrl() }}">{{ __('platno::editor.previous') }}</a>@endif
        @if ($publications->nextPageUrl())<a href="{{ $publications->nextPageUrl() }}">{{ __('platno::editor.next') }}</a>@endif
    </nav>
@endsection
