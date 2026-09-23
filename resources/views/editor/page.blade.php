@extends('platno::editor.layout')
@section('title', $page ? $page->title : __('platno::editor.new_page'))
@section('content')
    @php($encodedDocument = is_string(old('document')) ? old('document') : json_encode(old('document', $document), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
    <a href="{{ route('platno.pages.index') }}">← {{ __('platno::editor.back') }}</a>
    <div class="platno-heading">
        <h1>{{ $page ? $page->title : __('platno::editor.new_page') }}</h1>
        <div class="platno-actions">
            <button type="submit" form="platno-draft">{{ __('platno::editor.save') }}</button>
            @if ($page)
                <form method="post" action="{{ route('platno.pages.publish', $page->getKey()) }}">
                    @csrf
                    <input type="hidden" name="revision" value="{{ old('revision', $page->revision) }}">
                    <button id="platno-publish" class="platno-quiet" type="submit">{{ __('platno::editor.publish') }}</button>
                </form>
            @endif
        </div>
    </div>
    @if ($page)
        <div class="platno-publication-bar">
            <span class="platno-status">{{ __(!$page->publication ? 'platno::editor.draft' : ($page->publication->source_revision + 1 === $page->revision ? 'platno::editor.live' : 'platno::editor.changes_pending')) }}</span>
            <span class="platno-help">{{ __('platno::editor.revision', ['revision' => $page->revision]) }}</span>
            <a href="{{ route('platno.pages.preview', $page->getKey()) }}" target="_blank" rel="noopener">{{ __('platno::editor.preview_saved') }} ↗</a>
            <a href="{{ route('platno.pages.history', $page->getKey()) }}">{{ __('platno::editor.history') }}</a>
            @if ($page->publication_id && Route::has('platno.public.show'))
                <a href="{{ route('platno.public.show', $page->slug) }}">{{ __('platno::editor.visit') }} ↗</a>
            @endif
            <span id="platno-unsaved" class="platno-help" role="status" hidden>{{ __('platno::editor.unsaved') }}</span>
        </div>
    @endif
    <form id="platno-draft" method="post" action="{{ $page ? route('platno.pages.update', $page->getKey()) : route('platno.pages.store') }}">
        @csrf
        @if ($page)
            @method('PUT')
            <input type="hidden" name="revision" value="{{ old('revision', $page->revision) }}">
        @endif
        <details class="platno-page-settings-panel" @if (!$page) open @endif>
            <summary>{{ __('platno::editor.page_settings') }}</summary>
            <div class="platno-page-settings">
            <div class="platno-field">
                <label for="title">{{ __('platno::editor.title') }}</label>
                <input id="title" name="title" required maxlength="200" value="{{ old('title', $page?->title) }}" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}">
            </div>
            <div class="platno-field">
                <label for="slug">{{ __('platno::editor.slug') }}</label>
                <input id="slug" name="slug" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $page?->slug) }}" @readonly($page) aria-describedby="slug-help" aria-invalid="{{ $errors->has('slug') ? 'true' : 'false' }}">
                <p class="platno-help" id="slug-help">{{ __('platno::editor.slug_help') }}</p>
            </div>
        </div>
            </details>
        <input type="hidden" id="platno-document" name="document" value="{{ $encodedDocument }}">
        <div id="document" class="platno-studio">
            <div class="platno-studio-tools">
                <button type="button" id="platno-add">+ {{ __('platno::editor.add_block') }}</button>
                <div class="platno-actions">
                    <button type="button" id="platno-undo" class="platno-quiet" disabled>↶ {{ __('platno::editor.undo') }}</button>
                    <button type="button" id="platno-redo" class="platno-quiet" disabled>↷ {{ __('platno::editor.redo') }}</button>
                </div>
                <label class="platno-width-label" for="platno-width">{{ __('platno::editor.canvas_width') }}</label>
                <select id="platno-width">
                    <option value="desktop">{{ __('platno::editor.desktop') }}</option>
                    <option value="mobile">{{ __('platno::editor.mobile') }}</option>
                </select>
            </div>
            <div class="platno-studio-body">
                <nav class="platno-outline" aria-label="{{ __('platno::editor.blocks') }}">
                    <h2>{{ __('platno::editor.blocks') }}</h2>
                    <div id="platno-outline"></div>
                </nav>
                <section class="platno-canvas-area" aria-label="{{ __('platno::editor.canvas') }}">
                    <div class="platno-selection-tools">
                        <span id="platno-selection-label"></span>
                        <button type="button" class="platno-quiet" id="platno-edit">{{ __('platno::editor.edit_block') }}</button>
                        <button type="button" class="platno-quiet" id="platno-up" title="{{ __('platno::editor.move_up') }}" aria-label="{{ __('platno::editor.move_up') }}">↑</button>
                        <button type="button" class="platno-quiet" id="platno-down" title="{{ __('platno::editor.move_down') }}" aria-label="{{ __('platno::editor.move_down') }}">↓</button>
                        <button type="button" class="platno-quiet" id="platno-duplicate">{{ __('platno::editor.duplicate') }}</button>
                        <button type="button" class="platno-quiet" id="platno-remove">{{ __('platno::editor.remove') }}</button>
                    </div>
                    <p id="platno-composer-status" class="platno-help platno-canvas-status" role="status" aria-live="polite"></p>
                    <div id="platno-canvas" class="platno-canvas" data-width="desktop"></div>
                </section>
                <aside id="platno-inspector" class="platno-inspector" aria-labelledby="inspector-title" tabindex="-1"></aside>
            </div>
        </div>
        <noscript><p>{{ __('platno::editor.javascript_needed') }}</p><textarea name="document" aria-label="{{ __('platno::editor.document') }}">{{ $encodedDocument }}</textarea></noscript>
    </form>
    @if ($page)
        <details class="platno-page-management">
            <summary>{{ __('platno::editor.manage_page') }}</summary>
            <p class="platno-help">{{ __('platno::editor.manage_help') }}</p>
            <div class="platno-actions">
                @if ($page->publication_id)
                    <form method="post" action="{{ route('platno.pages.unpublish', $page->getKey()) }}">
                        @csrf
                        <input type="hidden" name="revision" value="{{ $page->revision }}">
                        <button class="platno-quiet" type="submit">{{ __('platno::editor.unpublish') }}</button>
                    </form>
                @endif
                <form method="post" action="{{ route('platno.pages.archive', $page->getKey()) }}">
                    @csrf
                    <input type="hidden" name="revision" value="{{ $page->revision }}">
                    <button class="platno-quiet" type="submit">{{ __('platno::editor.archive') }}</button>
                </form>
            </div>
        </details>
    @endif
    <script type="application/json" id="platno-catalog">{!! json_encode($catalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    <script type="application/json" id="platno-messages">{!! json_encode(__('platno::editor'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    <script type="application/json" id="platno-page-state">{!! json_encode($page ? ['id' => $page->getKey(), 'revision' => $page->revision, 'publication_id' => $page->publication_id ? (int) $page->publication_id : null] : null, JSON_THROW_ON_ERROR) !!}</script>
    <script type="application/json" id="platno-media-settings">{!! json_encode(['index' => route('platno.assets.index'), 'token' => csrf_token(), 'canvas' => route('platno.editor.canvas')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    <script type="module" data-platno-boot src="{{ route('platno.editor.script') }}"></script>
@endsection
