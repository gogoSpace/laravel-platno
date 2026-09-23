<style>
    .platno-page { margin: 0; --platno-background: {{ $theme['background'] }}; --platno-text: {{ $theme['text'] }}; --platno-accent: {{ $theme['accent'] }}; --platno-surface: {{ $theme['surface'] }}; color: var(--platno-text); background: var(--platno-background); font: 18px/1.7 {{ $theme['font'] === 'serif' ? 'Georgia, serif' : 'system-ui, sans-serif' }}; }
    .platno-page * { box-sizing: border-box; }
    .platno-page main { max-width: {{ $theme['width'] }}px; margin: 0 auto; padding: clamp(24px, 5vw, 72px) 24px; overflow-wrap: anywhere; }
    .platno-page h1 { font-size: clamp(32px, 6vw, 56px); letter-spacing: -.035em; line-height: 1.1; margin: 0 0 36px; }
    .platno-page h2 { font-size: clamp(26px, 4vw, 36px); line-height: 1.2; }
    .platno-page h3 { font-size: 24px; }
    .platno-page h4 { font-size: 20px; }
    .platno-page a { color: var(--platno-accent); text-underline-offset: .2em; }
    .platno-page a:focus-visible { outline: 3px solid #926214; outline-offset: 4px; }
    .platno-page .platno-link-button { display: inline-block; background: var(--platno-accent); color: #fff; padding: 10px 22px; border-radius: 6px; text-decoration: none; }
    .platno-page .platno-group { padding: 24px 0; }
    .platno-page .platno-group-muted { background: var(--platno-surface); padding: 24px; border-radius: 8px; }
    .platno-page .platno-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 32px; }
    .platno-page .platno-rich-text :is(p, li) { white-space: pre-wrap; }
    .platno-page hr { border: 0; border-top: 1px solid #cdd5ca; margin: 32px 0; }
    .platno-page img { max-width: 100%; height: auto; }
    .platno-page figure { margin: 32px 0; }
    .platno-page figcaption { font-size: 14px; color: #526054; }
    .platno-page .platno-preview-notice { padding: 14px 18px; margin-bottom: 30px; background: var(--platno-surface); font-size: 14px; }
    @media (max-width: 640px) { .platno-page .platno-columns { grid-template-columns: minmax(0, 1fr); } }
</style>
