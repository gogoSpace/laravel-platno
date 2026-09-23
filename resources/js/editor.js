import { element, button, clone } from './editor-dom.js';
import { createEditorStore } from './editor-store.js';
import { createCanvas } from './editor-canvas.js';
import { createInspector } from './editor-inspector.js';
import { createOutline } from './editor-outline.js';

export function mountEditor(root) {
    const find = identity => root.querySelector(`[id="${identity}"]`);
    const form = find('platno-draft');
    if (!form) return null;
    const read = identity => JSON.parse(find(identity).textContent);
    const input = find('platno-document');
    const messages = read('platno-messages');
    const settings = read('platno-media-settings');
    const catalog = new Map(read('platno-catalog').map(plugin => [plugin.type, plugin]));
    const status = find('platno-composer-status');
    let content;
    try { content = JSON.parse(input.value); if (content.version !== 1 || !Array.isArray(content.blocks)) throw new Error(); }
    catch { status.textContent = messages.invalid_document; return null; }
    const store = createEditorStore(content, catalog);
    const controller = new AbortController();
    const eventOptions = { signal: controller.signal };
    let dirty = false;
    let submitting = false;
    const original = { document: JSON.stringify(content), title: find('title').value, slug: find('slug').value };
    const emit = (name, detail) => form.dispatchEvent(new CustomEvent(`platno:${name}`, { detail, bubbles: true, composed: true }));
    const markUnsaved = () => {
        input.value = JSON.stringify(store.document);
        dirty = input.value !== original.document || find('title').value !== original.title || find('slug').value !== original.slug;
        const publish = find('platno-publish');
        if (publish) publish.disabled = dirty;
        if (find('platno-unsaved')) find('platno-unsaved').hidden = !dirty;
        find('platno-undo').disabled = !store.canUndo;
        find('platno-redo').disabled = !store.canRedo;
        emit('change', { document: clone(store.document), dirty });
    };
    const focusInspector = () => { (find('platno-inspector').querySelector('input,textarea,select,[contenteditable],button') || find('platno-inspector')).focus(); };
    function insert(listPath = null) {
        const dialog = element('dialog', undefined, 'platno-insert-dialog');
        const heading = element('h2', messages.add_block);
        const close = button(messages.close, () => dialog.close());
        const header = element('div', undefined, 'platno-heading'); header.append(heading, close);
        const choices = element('div', undefined, 'platno-insert-grid');
        const selected = store.entries().find(entry => entry.path === store.selectedPath);
        const destination = listPath || selected?.listPath || 'document.blocks';
        const position = listPath ? store.at(listPath).length : selected ? selected.position + 1 : store.at(destination).length;
        catalog.forEach(plugin => {
            if (!plugin.editable) return;
            choices.append(button(plugin.label, () => {
                if (!store.add(plugin.type, destination, position)) { status.textContent = messages.nested_limit; return; }
                dialog.close(); refresh(); focusInspector();
            }));
        });
        dialog.append(header, choices);
        (root.body || root.querySelector('.platno-editor') || root).append(dialog);
        dialog.addEventListener('close', () => { dialog.remove(); find('platno-add').focus(); }, { once: true });
        dialog.showModal();
    }
    const inspector = createInspector(find('platno-inspector'), store, catalog, messages, settings, root, insert);
    const outline = createOutline(find('platno-outline'), store, catalog, messages, insert);
    const canvas = createCanvas(find('platno-canvas'), store, catalog, { ...settings, title: find('title').value }, messages, status, focusInspector);
    function refresh() { inspector.render(); outline.render(); updateActions(); }
    function updateActions() {
        const entry = store.entries().find(entry => entry.path === store.selectedPath);
        find('platno-selection-label').textContent = entry ? catalog.get(entry.block.type)?.label || entry.block.type : messages.select_block;
        ['edit', 'duplicate', 'remove'].forEach(action => { find(`platno-${action}`).disabled = !entry; });
        find('platno-up').disabled = !entry || entry.position === 0;
        find('platno-down').disabled = !entry || entry.position === store.at(entry.listPath).length - 1;
        find('platno-undo').disabled = !store.canUndo;
        find('platno-redo').disabled = !store.canRedo;
    }
    const actions = { add: () => insert(), undo: () => store.undo(), redo: () => store.redo(), edit: focusInspector,
        up: () => { store.move(-1); refresh(); }, down: () => { store.move(1); refresh(); },
        duplicate: () => { if (!store.duplicate()) status.textContent = messages.block_limit; refresh(); },
        remove: () => { store.remove(); refresh(); },
    };
    Object.entries(actions).forEach(([name, action]) => find(`platno-${name}`).addEventListener('click', action, eventOptions));
    const stop = store.subscribe(kind => { if (kind !== 'selection') { markUnsaved(); if (!find('platno-inspector').contains(root.activeElement)) inspector.render(); } updateActions(); if (kind === 'history') refresh(); });
    form.addEventListener('input', () => { canvas.title(find('title').value); markUnsaved(); }, eventOptions);
    form.addEventListener('submit', event => { input.value = JSON.stringify(store.document); queueMicrotask(() => { submitting = !event.defaultPrevented; }); }, eventOptions);
    root.addEventListener('keydown', event => {
        if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'z') return;
        event.preventDefault(); event.shiftKey ? store.redo() : store.undo();
    }, eventOptions);
    find('platno-width').addEventListener('change', event => { find('platno-canvas').dataset.width = event.target.value; }, eventOptions);
    window.addEventListener('beforeunload', event => { if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; } }, eventOptions);
    updateActions();
    const errorSummary = root.querySelector('.platno-errors');
    if (errorSummary) {
        markUnsaved();
        errorSummary.addEventListener('click', event => {
            const link = event.target.closest('a[href^="#document.blocks."]');
            if (!link) return;
            event.preventDefault();
            const path = link.getAttribute('href').slice(1);
            const entry = store.entries().filter(entry => path.startsWith(entry.path)).at(-1);
            if (entry) { store.select(entry.path); focusInspector(); }
        }, eventOptions);
    }
    return {
        getDocument: () => clone(store.document),
        get dirty() { return dirty; },
        destroy() { controller.abort(); stop(); canvas.destroy(); inspector.destroy(); outline.destroy(); root.querySelectorAll('dialog').forEach(dialog => { if (dialog.open) dialog.close(); dialog.remove(); }); },
    };
}
if (document.querySelector('script[data-platno-boot]') && document.getElementById('platno-draft')) mountEditor(document);
