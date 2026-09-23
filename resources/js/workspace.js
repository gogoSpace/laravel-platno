import { mountEditor } from './editor.js';
import { editorAddress, element } from './editor-dom.js';

/** Mount an isolated editor DOM in the host page; no frame or framework runtime. */
export function mountWorkspace(container, options) {
    const shadow = container.shadowRoot || container.attachShadow({ mode: 'open' });
    const controller = new AbortController();
    let editor;
    let disposed = false;
    let busy = false;
    let address = editorAddress(options.editorAddress);
    const emit = (name, detail) => {
        container.dispatchEvent(new CustomEvent(`platno:${name}`, { detail, bubbles: true, composed: true }));
        if (name === 'state') options.onState?.(detail);
        if (name === 'error') options.onError?.(detail);
    };
    const error = message => {
        let notice = shadow.querySelector('[data-workspace-error]');
        if (!notice) {
            notice = element('p', undefined, 'platno-errors');
            notice.dataset.workspaceError = '';
            notice.setAttribute('role', 'alert');
            (shadow.querySelector('main') || shadow).prepend(notice);
        }
        notice.textContent = message;
        emit('error', { message });
    };
    async function load(destination, request = {}) {
        busy = true;
        container.setAttribute('aria-busy', 'true');
        const progress = element('p');
        progress.setAttribute('role', 'status');
        const labels = shadow.querySelector('#platno-messages');
        progress.textContent = labels ? JSON.parse(labels.textContent).loading : 'Loading…';
        shadow.prepend(progress);
        const pendingSurface = shadow.querySelector('.platno-editor');
        if (request.method && pendingSurface) pendingSurface.inert = true;
        try {
            const response = await fetch(editorAddress(destination), { credentials: 'same-origin', signal: controller.signal, ...request });
            if (disposed) return;
            editorAddress(response.url);
            const source = new DOMParser().parseFromString(await response.text(), 'text/html');
            const validation = source.querySelector('.platno-errors');
            if (editor && (validation || !response.ok)) {
                error(validation?.textContent.trim() || source.querySelector('h1')?.textContent || `Request failed (${response.status}). Your unsaved changes are retained.`);
                if (response.status === 409) {
                    const latest = source.querySelector('a.platno-button');
                    if (latest) {
                        const link = document.importNode(latest, true);
                        link.href = editorAddress(link.href); link.target = '_blank'; link.rel = 'noopener';
                        shadow.querySelector('[data-workspace-error]').append(document.createTextNode(' '), link);
                    }
                }
                return;
            }
            if (!source.querySelector('.platno-main') || !response.ok) {
                throw new Error(`The editor could not be loaded (${response.status}).`);
            }
            const script = source.querySelector('script[type="module"][src]');
            if (script && new URL(script.src).origin !== window.location.origin) throw new Error('Invalid editor source.');
            editor?.destroy();
            const wrapper = element('div', undefined, 'platno-editor');
            source.querySelectorAll('head style, head link[rel="stylesheet"]').forEach(style => wrapper.append(document.importNode(style, true)));
            source.querySelectorAll('.platno-header, .platno-main').forEach(node => wrapper.append(document.importNode(node, true)));
            wrapper.querySelectorAll('noscript, script:not([type="application/json"])').forEach(script => script.remove());
            shadow.replaceChildren(wrapper);
            address = response.url;
            editor = mountEditor(shadow);
            const state = source.querySelector('#platno-page-state');
            if (state) emit('state', JSON.parse(state.textContent));
        } catch (failure) {
            if (!disposed && failure.name !== 'AbortError') error(failure.message);
        } finally { busy = false; progress.remove(); container.removeAttribute('aria-busy'); if (pendingSurface?.isConnected) pendingSurface.inert = false; }
    }
    shadow.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
        event.preventDefault();
        if (busy) return;
        const data = new FormData(form);
        if (event.submitter?.name) data.append(event.submitter.name, event.submitter.value);
        load(form.action, { method: 'POST', body: data });
    }, { signal: controller.signal });
    shadow.addEventListener('platno:change', event => options.onChange?.(event.detail), { signal: controller.signal });
    const ready = load(address);
    return {
        ready,
        getDocument: () => editor?.getDocument(),
        get dirty() { return editor?.dirty || false; },
        destroy() { disposed = true; controller.abort(); editor?.destroy(); shadow.replaceChildren(); },
    };
}
