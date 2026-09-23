import { element } from './editor-dom.js';

export function createCanvas(host, store, catalog, settings, messages, status, editSettings) {
    const shadow = host.attachShadow({ mode: 'open' });
    const styles = element('div');
    const controls = element('style');
    controls.textContent = `
        :host { display:block; min-width:0; }
        .platno-page { min-height:520px; container-type:inline-size; }
        .platno-page main { padding:40px; }
        [data-platno-path] { position:relative; min-height:30px; outline:1px solid transparent; outline-offset:5px; cursor:pointer; }
        [data-platno-path]:hover { outline-color:#b8c7bf; }
        [data-platno-path][data-selected] { outline:2px solid #285937; }
        [data-platno-path]:focus-visible { outline:2px solid #926214; }
        [data-platno-path][data-selected]::before { content:attr(aria-label); position:absolute; top:-20px; left:-7px; padding:1px 6px; background:#285937; color:white; font:11px/18px system-ui,sans-serif; z-index:2; border-radius:3px 3px 0 0; pointer-events:none; }
        [contenteditable] { cursor:text; min-height:1.5em; }
        [contenteditable]:focus { outline:none; }
        [contenteditable]:empty::after { content:attr(data-placeholder); opacity:.45; }
        .platno-page a { cursor:inherit; }
        .platno-page main > [data-platno-path] { margin-block:20px; }
        @container (max-width:640px) { .platno-page .platno-columns { grid-template-columns:minmax(0,1fr); } .platno-page h1 { font-size:36px; } }
        @media (max-width:640px) { .platno-page main { padding:28px 20px; } }
    `;
    const page = element('div', undefined, 'platno-page');
    const main = element('main');
    const title = element('h1', settings.title || messages.new_page);
    const blocks = element('div');
    main.append(title, blocks); page.append(main); shadow.append(styles, controls, page);
    let timer;
    let sequence = 0;
    let pendingResponse;
    let request;
    let destroyed = false;
    let signature = '';
    const identities = new WeakMap();
    let identitySequence = 0;
    const structure = () => store.entries().map(entry => {
        if (!identities.has(entry.block)) identities.set(entry.block, ++identitySequence);
        return `${entry.path}:${entry.block.id || identities.get(entry.block)}`;
    }).join('|');
    function selection() {
        blocks.querySelectorAll('[data-platno-path]').forEach(node => {
            node.toggleAttribute('data-selected', node.dataset.platnoPath === store.selectedPath);
        });
    }
    function wire() {
        blocks.querySelectorAll('[data-platno-path]').forEach(node => {
            const path = node.dataset.platnoPath;
            const block = store.at(path);
            if (!block) return;
            node.setAttribute('aria-label', catalog.get(block.type)?.label || block.type);
            node.addEventListener('pointerdown', event => {
                if (event.target.closest('[data-platno-path]') === node && store.selectedPath !== path) store.select(path);
            });
            node.addEventListener('click', event => {
                if (event.target.closest('[data-platno-path]') !== node) return;
                if (event.target.closest('a')) event.preventDefault();
                event.stopPropagation();
                if (store.selectedPath !== path) store.select(path);
            });
            node.addEventListener('keydown', event => {
                if (event.target !== node) return;
                if (['Enter', ' '].includes(event.key)) { event.preventDefault(); store.select(path); editSettings(); }
                if (event.altKey && ['ArrowUp', 'ArrowDown'].includes(event.key)) { event.preventDefault(); store.select(path); store.move(event.key === 'ArrowUp' ? -1 : 1); }
            });
            const editable = block.type === 'text' ? node.querySelector('p') : block.type === 'heading' ? node.querySelector('h2,h3,h4') : null;
            if (editable) {
                editable.contentEditable = 'plaintext-only';
                editable.setAttribute('role', 'textbox');
                editable.setAttribute('aria-label', catalog.get(block.type).fields.text.label);
                editable.setAttribute('aria-multiline', String(block.type === 'text'));
                editable.dataset.placeholder = messages.empty_block;
                editable.addEventListener('focus', () => { if (store.selectedPath !== path) store.select(path); });
                editable.addEventListener('input', () => store.updateField(path, 'text', editable.innerText.replace(/\r/g, '')));
                editable.addEventListener('keydown', event => { if (event.key === 'Enter' && block.type === 'heading') event.preventDefault(); });
                editable.addEventListener('blur', () => { if (pendingResponse) { const pending = pendingResponse; pendingResponse = null; apply(pending); } });
            } else node.addEventListener('dblclick', event => { if (event.target.closest('[data-platno-path]') === node) { store.select(path); editSettings(); } });
        });
        selection();
    }
    function apply(result) {
        if (destroyed || result.sequence !== sequence) return;
        if (shadow.activeElement?.isContentEditable) { pendingResponse = result; return; }
        styles.innerHTML = result.styles;
        blocks.innerHTML = result.html;
        main.inert = false;
        signature = structure();
        wire();
        if (!store.document.blocks.length) blocks.append(element('p', messages.empty_blocks));
    }
    async function render(currentSequence) {
        request?.abort();
        request = new AbortController();
        status.textContent = messages.updating_canvas;
        try {
            const response = await fetch(settings.canvas, {
                method: 'POST', credentials: 'same-origin', signal: request.signal,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': settings.token },
                body: JSON.stringify({ document: store.document }),
            });
            const result = await response.json();
            if (currentSequence !== sequence || destroyed) return;
            if (!response.ok) {
                status.textContent = messages.canvas_incomplete + ' ' + (Object.values(result.errors || {}).flat().join(' ') || result.message || '');
                return;
            }
            status.textContent = '';
            apply({ ...result, sequence: currentSequence });
        } catch (error) {
            if (error.name !== 'AbortError' && !destroyed && currentSequence === sequence) status.textContent = messages.canvas_failed;
        }
    }
    function schedule(immediate = false) {
        sequence++;
        pendingResponse = null;
        clearTimeout(timer);
        if (signature !== structure()) main.inert = true;
        const currentSequence = sequence;
        timer = setTimeout(() => render(currentSequence), immediate ? 0 : 600);
    }
    const stop = store.subscribe(kind => {
        if (kind === 'selection') selection();
        else if (kind === 'history') { pendingResponse = null; shadow.activeElement?.blur(); main.inert = true; schedule(true); }
        else schedule();
    });
    schedule(true);
    return {
        title(value) { title.textContent = value || messages.new_page; },
        focusSelection() { [...blocks.querySelectorAll('[data-platno-path]')].find(node => node.dataset.platnoPath === store.selectedPath)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); },
        destroy() { destroyed = true; clearTimeout(timer); request?.abort(); stop(); },
    };
}
