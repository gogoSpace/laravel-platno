<div class="platno-columns">
    @foreach (['left', 'right'] as $column)
        <div>
            {!! $renderer->children($data[$column], $preview, $canvasPath ?? null, $column) !!}
        </div>
    @endforeach
</div>
