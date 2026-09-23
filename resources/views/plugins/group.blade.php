<section class="platno-group platno-group-{{ $data['tone'] }}">
    {!! $renderer->children($data['children'], $preview, $canvasPath ?? null, 'children') !!}
</section>
