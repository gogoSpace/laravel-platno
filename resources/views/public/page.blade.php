<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $publication->title }}</title>
    @include('platno::public.styles')
</head>
<body class="platno-page">
    <main>
        @if ($preview ?? false)
            <aside class="platno-preview-notice" role="status">{{ __('platno::editor.private_preview') }} <a href="{{ route('platno.pages.index') }}">{{ __('platno::editor.back') }}</a></aside>
        @endif
        <h1>{{ $publication->title }}</h1>
        @foreach ($blocks as $block)
            {!! $block !!}
        @endforeach
    </main>
</body>
</html>
