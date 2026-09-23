<figure>
    <img src="{{ route($preview ? 'platno.assets.show' : 'platno.public.asset', $data['asset']) }}" alt="{{ $data['alt'] }}" loading="lazy">
    @if ($data['caption'] !== '')<figcaption>{{ $data['caption'] }}</figcaption>@endif
</figure>
