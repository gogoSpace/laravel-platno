<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · Platno</title>
    @include('platno::editor.styles')
    @include('platno::editor.studio-styles')
</head>
<body class="platno-editor">
    <header class="platno-header">
        <a class="platno-brand" href="{{ route('platno.pages.index') }}">Platno<span> / {{ __('platno::editor.pages') }}</span></a>
        <nav class="platno-actions" aria-label="{{ __('platno::editor.workspace') }}">
            <a href="{{ route('platno.assets.index') }}">{{ __('platno::editor.media') }}</a>
        </nav>
    </header>
    <main class="platno-main" id="main">
        @if (session('platno.status'))
            <p class="platno-notice" role="status">{{ session('platno.status') }}</p>
        @endif
        @if ($errors->any())
            <section class="platno-errors" role="alert" aria-labelledby="error-title" tabindex="-1">
                <h2 id="error-title">{{ __('platno::editor.fix_errors') }}</h2>
                <ul>
                    @foreach ($errors->messages() as $field => $messages)
                        @foreach ($messages as $message)
                            <li><a href="#{{ $field }}">{{ $message }}</a></li>
                        @endforeach
                    @endforeach
                </ul>
            </section>
        @endif
        @yield('content')
    </main>
</body>
</html>
