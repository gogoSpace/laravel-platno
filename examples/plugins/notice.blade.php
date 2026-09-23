<aside class="platno-notice-block" role="note">
    @if ($data['tone'] === 'important')
        <strong>!</strong>
    @endif
    <p>{{ $data['message'] }}</p>
</aside>
