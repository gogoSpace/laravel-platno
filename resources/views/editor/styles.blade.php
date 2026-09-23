<style>
    .platno-editor { --platno-ink: #202821; --platno-muted: #526054; --platno-line: #cdd5ca; --platno-accent: #285937; margin: 0; background: #f6f7f3; color: var(--platno-ink); font: 16px/1.55 system-ui, sans-serif; }
    .platno-editor * { box-sizing: border-box; }
    .platno-editor a { color: var(--platno-accent); text-underline-offset: .2em; }
    .platno-editor :is(a, button, input, textarea):focus-visible { outline: 3px solid #926214; outline-offset: 3px; }
    .platno-editor .platno-header { padding: 18px max(24px, calc((100vw - 1120px) / 2)); border-bottom: 1px solid var(--platno-line); display: flex; align-items: center; justify-content: space-between; gap: 20px; background: #fff; }
    .platno-editor .platno-brand { font-size: 22px; font-weight: 750; color: var(--platno-ink); text-decoration: none; }
    .platno-editor .platno-brand span { font-size: 14px; font-weight: 400; color: var(--platno-muted); }
    .platno-editor .platno-main { max-width: 1120px; margin: 0 auto; padding: 38px 24px 72px; }
    .platno-editor h1 { font-size: clamp(26px, 4vw, 34px); line-height: 1.2; margin: 12px 0 28px; letter-spacing: -.025em; overflow-wrap: anywhere; }
    .platno-editor h2 { font-size: 19px; line-height: 1.4; margin: 0 0 10px; }
    .platno-editor p { margin: 0 0 18px; }
    .platno-editor .platno-heading { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 20px; }
    .platno-editor .platno-heading h1 { margin: 0; }
    .platno-editor :is(button, .platno-button) { display: inline-block; min-height: 44px; padding: 10px 17px; border: 1px solid var(--platno-accent); border-radius: 5px; background: var(--platno-accent); color: #fff; font: inherit; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; }
    .platno-editor :is(button, .platno-button):hover { background: #1d4429; }
    .platno-editor .platno-quiet { background: transparent; border-color: var(--platno-line); color: var(--platno-ink); }
    .platno-editor .platno-quiet:hover { background: #e9eee6; }
    .platno-editor button:disabled { opacity: .55; cursor: not-allowed; }
    .platno-editor .platno-field { margin-bottom: 24px; }
    .platno-editor label { display: block; font-weight: 650; margin-bottom: 7px; }
    .platno-editor :is(input, textarea) { display: block; width: 100%; padding: 12px; border: 1px solid #889586; border-radius: 4px; color: var(--platno-ink); background: #fff; font: inherit; }
    .platno-editor textarea { min-height: 280px; resize: vertical; line-height: 1.7; }
    .platno-editor [aria-invalid="true"] { border: 2px solid #a53122; }
    .platno-editor .platno-help { display: block; color: var(--platno-muted); font-size: 14px; margin: 7px 0 0; }
    .platno-editor .platno-edit-grid { display: grid; grid-template-columns: minmax(0, 1fr) 260px; gap: 44px; align-items: start; }
    .platno-editor .platno-publication { border-left: 1px solid var(--platno-line); padding-left: 24px; }
    .platno-editor .platno-publication button { width: 100%; }
    .platno-editor .platno-publication a { display: inline-block; margin-top: 16px; overflow-wrap: anywhere; }
    .platno-editor .platno-notice { border-left: 4px solid var(--platno-accent); background: #e8f0e4; padding: 14px 18px; margin-bottom: 24px; }
    .platno-editor .platno-errors { border-left: 4px solid #a53122; background: #fff0ec; padding: 18px; margin-bottom: 28px; }
    .platno-editor .platno-errors a { color: #8d2418; }
    .platno-editor .platno-empty { border-top: 1px solid var(--platno-line); padding: 48px 0; max-width: 600px; }
    .platno-editor .platno-page-list { list-style: none; margin: 0; padding: 0; border-top: 1px solid var(--platno-line); }
    .platno-editor .platno-page-list li { display: flex; justify-content: space-between; align-items: center; gap: 24px; padding: 20px 0; border-bottom: 1px solid var(--platno-line); }
    .platno-editor .platno-page-list a { font-weight: 650; overflow-wrap: anywhere; }
    .platno-editor .platno-page-list small { display: block; font-weight: 400; color: var(--platno-muted); }
    .platno-editor .platno-status { font-size: 13px; background: #e6ece1; border-radius: 4px; padding: 4px 9px; white-space: nowrap; }
    .platno-editor .platno-pagination { display: flex; justify-content: space-between; gap: 24px; margin-top: 24px; }
    .platno-editor [hidden] { display: none !important; }
    .platno-editor .platno-actions, .platno-editor .platno-publication-bar, .platno-editor .platno-block-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
    .platno-editor .platno-heading { margin-top: 18px; }
    .platno-editor .platno-publication-bar { padding: 0 0 24px; }
    .platno-editor .platno-publication-bar .platno-help { margin: 0; }
    .platno-editor .platno-page-settings { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; border-bottom: 1px solid var(--platno-line); margin-bottom: 28px; }
    .platno-editor .platno-workspace { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 28px; align-items: start; }
    .platno-editor select { min-height: 44px; max-width: 100%; padding: 8px 12px; border: 1px solid #889586; border-radius: 4px; background: #fff; color: var(--platno-ink); font: inherit; }
    .platno-editor select:focus-visible { outline: 3px solid #926214; outline-offset: 3px; }
    .platno-editor .platno-block-toolbar h2 { margin: 0 auto 0 0; }
    .platno-editor .platno-blocks { list-style: none; margin: 18px 0 0; padding: 0; }
    .platno-editor .platno-block { border: 1px solid var(--platno-line); border-radius: 5px; background: #fff; margin-bottom: 14px; padding: 16px; }
    .platno-editor .platno-block[data-selected="true"] { border-color: var(--platno-accent); box-shadow: inset 3px 0 var(--platno-accent); }
    .platno-editor .platno-block-select { padding: 0; min-height: 32px; border: 0; background: transparent; color: var(--platno-accent); text-align: left; }
    .platno-editor .platno-block-select:hover { background: #e9eee6; }
    .platno-editor .platno-block-summary { white-space: pre-wrap; overflow-wrap: anywhere; margin: 12px 0; }
    .platno-editor .platno-block-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .platno-editor .platno-block-actions button { min-height: 36px; font-size: 13px; padding: 6px 10px; }
    .platno-editor .platno-inspector { border-left: 1px solid var(--platno-line); padding-left: 24px; position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow: auto; }
    .platno-editor .platno-inspector input[type="checkbox"] { width: 22px; height: 22px; }
    .platno-editor .platno-inspector select { width: 100%; }
    .platno-editor .platno-inspector textarea { min-height: 200px; }
    .platno-editor .platno-empty-blocks { border: 1px dashed var(--platno-line); padding: 32px; color: var(--platno-muted); }
    .platno-editor .platno-visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
    .platno-editor .platno-breadcrumbs { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px; }
    .platno-editor .platno-breadcrumbs button { font-size: 13px; min-height: 36px; padding: 5px 8px; }
    .platno-editor .platno-rich-editor fieldset { min-width: 0; margin: 12px 0; padding: 12px; border: 1px solid var(--platno-line); border-radius: 5px; }
    .platno-editor .platno-rich-editor :is(input, textarea, select) { margin-bottom: 10px; }
    .platno-editor .platno-rich-editor textarea { min-height: 120px; }
    .platno-editor .platno-rich-editor .platno-block-actions { margin: 10px 0; }
    .platno-editor .platno-rich-preview { margin: 16px 0; padding: 12px; background: #fff; white-space: pre-wrap; overflow-wrap: anywhere; border-left: 2px solid var(--platno-line); }
    .platno-editor .platno-media-dialog { width: min(850px, calc(100vw - 32px)); max-height: calc(100vh - 40px); padding: 24px; border: 1px solid var(--platno-line); border-radius: 8px; color: var(--platno-ink); }
    .platno-editor .platno-media-dialog::backdrop { background: #101c18a6; }
    .platno-editor .platno-media-dialog input { margin-bottom: 14px; }
    .platno-editor .platno-media-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin: 18px 0; }
    .platno-editor .platno-media-choice { display: flex; flex-direction: column; gap: 12px; align-items: center; background: #fff; color: var(--platno-ink); border-color: var(--platno-line); overflow-wrap: anywhere; font-weight: 400; }
    .platno-editor .platno-media-choice:hover { background: #e9eee6; }
    .platno-editor .platno-media-choice img { max-width: 100%; width: 160px; height: 120px; object-fit: contain; }
    .platno-editor .platno-asset-preview { display: block; max-width: 100%; max-height: 180px; margin-bottom: 12px; }
    .platno-editor .platno-upload-form, .platno-editor .platno-search { margin-bottom: 28px; }
    .platno-editor .platno-upload-form button, .platno-editor .platno-search button { margin-top: 12px; }
    .platno-editor .platno-page-management { margin-top: 36px; padding-top: 20px; border-top: 1px solid var(--platno-line); }
    .platno-editor summary { cursor: pointer; font-weight: 650; min-height: 44px; }
    .platno-editor .platno-page-management .platno-actions { margin-top: 16px; }
    .platno-editor .platno-block-image { width: 100%; height: 180px; object-fit: contain; background: #f6f7f3; display: block; margin: 10px 0; }
    .platno-editor .platno-page-filter { display: flex; flex-wrap: wrap; gap: 16px; align-items: end; margin: 28px 0; }
    .platno-editor .platno-page-filter > div:first-child { flex: 1; min-width: 180px; }
    @media (max-width: 720px) {
        .platno-editor .platno-workspace, .platno-editor .platno-page-settings { grid-template-columns: minmax(0, 1fr); }
        .platno-editor .platno-inspector { position: static; max-height: none; border-left: 0; border-top: 1px solid var(--platno-line); padding: 24px 0 0; }
        .platno-editor .platno-page-settings { gap: 0; }
        .platno-editor .platno-edit-grid { grid-template-columns: minmax(0, 1fr); gap: 36px; }
        .platno-editor .platno-publication { border-left: 0; border-top: 1px solid var(--platno-line); padding: 24px 0 0; }
        .platno-editor .platno-heading { flex-wrap: wrap; }
        .platno-editor .platno-main { padding: 28px 20px 48px; }
        .platno-editor .platno-page-list li { align-items: flex-start; flex-direction: column; gap: 10px; }
        .platno-editor .platno-brand span { display: none; }
    }
</style>
