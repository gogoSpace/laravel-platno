@extends('platno::editor.layout')
@section('title', __('platno::editor.media'))
@section('content')
    <a href="{{ route('platno.pages.index') }}">← {{ __('platno::editor.back') }}</a>
    <h1>{{ __('platno::editor.media') }}</h1>
    <form method="post" action="{{ route('platno.assets.store') }}" enctype="multipart/form-data" class="platno-upload-form">
        @csrf
        <label for="file">{{ __('platno::editor.upload') }}</label>
        <input type="file" id="file" name="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
        <p class="platno-help">{{ __('platno::editor.upload_help') }}</p>
        <button type="submit">{{ __('platno::editor.upload') }}</button>
    </form>
    <form method="get" class="platno-search">
        <label for="search">{{ __('platno::editor.search_media') }}</label>
        <input id="search" name="search" type="search" value="{{ request('search') }}" maxlength="100">
        <button type="submit">{{ __('platno::editor.search') }}</button>
    </form>
    <p class="platno-help">{{ __('platno::editor.media_retention') }}</p>
    <ul class="platno-page-list">
        @forelse ($assets as $asset)
            <li>
                <div>
                    @if ($asset->state === 'active')
                        <a href="{{ route('platno.assets.show', $asset->getKey()) }}">{{ $asset->name }}</a>
                    @else
                        <strong>{{ $asset->name }}</strong> <span>{{ $asset->state }}</span>
                    @endif
                    <small>{{ $asset->mime_type }} · {{ number_format($asset->size / 1024) }} KB</small>
                </div>
                @if ($asset->state !== 'uploading')
                    <form method="post" action="{{ route('platno.assets.destroy', $asset->getKey()) }}">
                        @csrf @method('DELETE')
                        <button class="platno-quiet" type="submit">{{ __('platno::editor.delete_asset') }}</button>
                    </form>
                @endif
            </li>
        @empty
            <li>{{ __('platno::editor.empty_media') }}</li>
        @endforelse
    </ul>
    <nav class="platno-pagination" aria-label="{{ __('platno::editor.media') }}">
        @if ($assets->previousPageUrl())<a href="{{ $assets->previousPageUrl() }}">{{ __('platno::editor.previous') }}</a>@endif
        @if ($assets->nextPageUrl())<a href="{{ $assets->nextPageUrl() }}">{{ __('platno::editor.next') }}</a>@endif
    </nav>
@endsection
