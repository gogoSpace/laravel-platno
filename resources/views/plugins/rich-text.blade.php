<div class="platno-rich-text">
    @foreach ($data['content'] as $position => $paragraph)
        @php($listTag = $paragraph['type'] === 'bullet' ? 'ul' : 'ol')
        @if ($paragraph['type'] !== 'paragraph' && ($position === 0 || $data['content'][$position - 1]['type'] !== $paragraph['type']))
            <{{ $listTag }}>
        @endif
        <{{ $paragraph['type'] === 'paragraph' ? 'p' : 'li' }}><?php foreach ($paragraph['runs'] as $run) { ?><?php if ($run['link'] !== '') { ?><a href="{{ $run['link'] }}"><?php } ?><?php if ($run['bold']) { ?><strong><?php } ?><?php if ($run['italic']) { ?><em><?php } ?>{{ $run['text'] }}<?php if ($run['italic']) { ?></em><?php } ?><?php if ($run['bold']) { ?></strong><?php } ?><?php if ($run['link'] !== '') { ?></a><?php } ?><?php } ?></{{ $paragraph['type'] === 'paragraph' ? 'p' : 'li' }}>
        @if ($paragraph['type'] !== 'paragraph' && (!isset($data['content'][$position + 1]) || $data['content'][$position + 1]['type'] !== $paragraph['type']))
            </{{ $listTag }}>
        @endif
    @endforeach
</div>
