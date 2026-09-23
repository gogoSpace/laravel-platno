<style>
    .platno-editor:has(.platno-studio) .platno-header { padding:10px 24px; }
    .platno-editor:has(.platno-studio) .platno-brand { font-size:19px; }
    .platno-editor .platno-main:has(.platno-studio) > .platno-heading { margin:8px 0 12px; }
    .platno-editor .platno-main:has(.platno-studio) > .platno-heading h1 { font-size:25px; }
    .platno-editor .platno-main:has(.platno-studio) > .platno-heading button { min-height:40px; font-size:13px; padding:8px 13px; }
    .platno-editor .platno-main:has(.platno-studio) > .platno-publication-bar { padding-bottom:10px; font-size:12px; }
    .platno-editor .platno-main:has(.platno-studio) > .platno-publication-bar .platno-help { font-size:12px; }
    .platno-editor .platno-main:has(.platno-studio) > a { font-size:12px; }

    .platno-editor .platno-page-settings-panel { margin:0 0 8px; }
    .platno-editor .platno-page-settings-panel summary { min-height:32px; font-size:13px; }
    .platno-editor .platno-page-settings-panel .platno-page-settings { margin:12px 0 0; }
    .platno-editor .platno-rich-editable { max-height:280px; overflow:auto; }
    .platno-editor .platno-main:has(.platno-studio) { max-width: 1800px; padding: 16px 20px 36px; }
    .platno-editor .platno-studio { container-type:inline-size; border: 1px solid var(--platno-line); border-radius: 8px; background: #fff; overflow: clip; }
    .platno-editor .platno-studio-tools { display:flex; align-items:center; flex-wrap:wrap; gap:12px; padding:12px 16px; border-bottom:1px solid var(--platno-line); }
    .platno-editor .platno-width-label { margin:0 0 0 auto; font-size:13px; }
    .platno-editor .platno-studio-tools :is(button, select) { font-size:13px; min-height:38px; }
    .platno-editor .platno-studio-body { display:grid; grid-template-columns:190px minmax(0,1fr) 300px; align-items:start; }
    .platno-editor .platno-outline { padding:20px 12px; position:sticky; top:0; max-height:85vh; overflow:auto; }
    .platno-editor .platno-outline h2 { font-size:12px; text-transform:uppercase; letter-spacing:.08em; margin:0 8px 16px; color:var(--platno-muted); }
    .platno-editor .platno-outline-row { padding-left:calc(var(--depth) * 12px); }
    .platno-editor .platno-outline-row button { display:block; width:100%; text-align:left; font-size:13px; min-height:38px; padding:7px 10px; border:0; font-weight:500; }
    .platno-editor .platno-outline-row button[aria-pressed="true"] { background:#e8eee9; color:var(--platno-accent); font-weight:700; }
    .platno-editor .platno-outline-row[data-drop] { border-top:2px solid var(--platno-accent); }
    .platno-editor .platno-outline-group { padding-left:calc(var(--depth) * 12px); }
    .platno-editor .platno-outline-group button { font-size:11px; padding:4px 8px; min-height:32px; border:0; color:var(--platno-muted); }
    .platno-editor .platno-canvas-area { min-width:0; background:#e9ece8; border-inline:1px solid var(--platno-line); padding:0 24px 36px; min-height:640px; }
    .platno-editor .platno-selection-tools { position:sticky; top:0; z-index:3; background:#e9ece8; display:flex; flex-wrap:wrap; align-items:center; gap:4px; padding:10px 0; min-height:54px; }
    .platno-editor .platno-selection-tools span { font-size:12px; font-weight:650; margin-right:auto; }
    .platno-editor .platno-selection-tools button { font-size:12px; padding:4px 8px; min-height:32px; background:transparent; border:0; }
    .platno-editor .platno-canvas { width:100%; margin:0 auto; background:white; box-shadow:0 3px 14px #23342512; transition:max-width .15s; }
    .platno-editor .platno-canvas[data-width="mobile"] { max-width:390px; }
    .platno-editor .platno-canvas-status { font-size:12px; margin:0 0 10px; }
    .platno-editor .platno-canvas-status:empty { display:none; }
    .platno-editor .platno-studio .platno-inspector { border:0; padding:20px; top:0; max-height:85vh; }
    .platno-editor .platno-studio .platno-inspector h2 { font-size:17px; margin:0 0 22px; }
    .platno-editor .platno-studio .platno-inspector :is(input,textarea,select) { font-size:14px; }
    .platno-editor .platno-studio .platno-inspector textarea { min-height:120px; }
    .platno-editor .platno-studio .platno-inspector label { font-size:13px; }
    .platno-editor .platno-insert-dialog { width:min(560px, calc(100vw - 32px)); max-height:80vh; padding:24px; border:1px solid var(--platno-line); border-radius:8px; color:var(--platno-ink); }
    .platno-editor .platno-insert-dialog::backdrop { background:#14241c70; }
    .platno-editor .platno-insert-dialog .platno-heading { margin:0 0 20px; }
    .platno-editor .platno-insert-dialog h2 { margin:0; }
    .platno-editor .platno-insert-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .platno-editor .platno-insert-grid button { text-align:left; padding:14px; }
    .platno-editor .platno-rich-editable { min-height:70px; padding:12px; border:1px solid #889586; border-radius:4px; background:#fff; white-space:pre-wrap; overflow-wrap:anywhere; line-height:1.7; }
    .platno-editor .platno-rich-editable:focus-visible { outline:3px solid #926214; outline-offset:2px; }
    .platno-editor .platno-rich-editor fieldset { padding:8px; }
    .platno-editor .platno-rich-editor .platno-block-actions { gap:4px; }
    .platno-editor .platno-rich-editor .platno-block-actions button { font-size:12px; }
    @media (max-width:1200px) { .platno-editor .platno-studio-body { grid-template-columns:150px minmax(0,1fr) 260px; } .platno-editor .platno-canvas-area { padding-inline:16px; } }
    @media (max-width:1000px) { .platno-editor .platno-studio-body { grid-template-columns:minmax(0,1fr) 280px; } .platno-editor .platno-outline { display:none; } }
    @media (max-width:720px) {
        .platno-editor .platno-main:has(.platno-studio) { padding:20px 12px 36px; }
        .platno-editor .platno-studio-body { grid-template-columns:minmax(0,1fr); }
        .platno-editor .platno-studio .platno-inspector { max-height:none; position:static; border-top:1px solid var(--platno-line); }
        .platno-editor .platno-canvas-area { padding:0 12px 20px; border:0; min-height:0; }
        .platno-editor .platno-studio-tools { gap:8px; padding:10px; }
        .platno-editor .platno-width-label { margin-left:0; }
        .platno-editor .platno-selection-tools { position:sticky; top:0; background:#e9ece8; z-index:3; }
    }
    @container (max-width:950px) { .platno-editor .platno-studio-body { grid-template-columns:minmax(0,1fr) 260px; } .platno-editor .platno-outline { display:none; } }
    @container (max-width:680px) { .platno-editor .platno-studio-body { grid-template-columns:minmax(0,1fr); } .platno-editor .platno-studio .platno-inspector { position:static; max-height:none; border-top:1px solid var(--platno-line); } }
    @media (prefers-reduced-motion:reduce) { .platno-editor .platno-canvas { transition:none; } }
</style>
