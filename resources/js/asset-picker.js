export function assetField(value, field, identity, messages, settings, changed, root = document) {
    const create = (tag, text) => { const node = document.createElement(tag); if (text !== undefined) node.textContent = text; return node; };
    const wrapper = create('div');
    const current = create('p', value ? messages.asset_selected : messages.no_asset);
    current.className = 'platno-help';
    const trigger = create('button', messages.choose_asset);
    trigger.type = 'button'; trigger.id = identity;
    wrapper.append(current, trigger);
    if (value && field.kind === 'image') {
        const thumbnail = create('img'); thumbnail.src = `${settings.index}/${value}`; thumbnail.alt = ''; thumbnail.className = 'platno-asset-preview'; wrapper.prepend(thumbnail);
    }
    trigger.addEventListener('click', () => {
        const dialog = create('dialog');
        const requests = new AbortController();
        dialog.className = 'platno-media-dialog';
        dialog.setAttribute('aria-label', messages.media);
        const heading = create('h2', messages.media);
        const close = create('button', messages.close); close.type = 'button'; close.className = 'platno-quiet';
        close.addEventListener('click', () => dialog.close());
        const header = create('div'); header.className = 'platno-heading'; header.append(heading, close);
        const feedback = create('p'); feedback.setAttribute('role', 'status');
        const searchLabel = create('label', messages.search_media);
        const search = create('input'); search.type = 'search'; search.id = 'platno-media-search'; searchLabel.htmlFor = search.id;
        const searchButton = create('button', messages.search); searchButton.type = 'button';
        const grid = create('div'); grid.className = 'platno-media-grid';
        const navigation = create('div'); navigation.className = 'platno-actions';
        const selected = asset => { changed(asset.id); current.textContent = asset.name; dialog.close(); };
        const failure = error => { if (error.name === 'AbortError') return; feedback.textContent = error.message || messages.upload_failed; };
        const parseResponse = async response => {
            let result;
            try { result = await response.json(); } catch { throw new Error(messages.session_failed); }
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || messages.upload_failed);
            return result;
        };
        let sequence = 0;
        const load = async address => {
            const request = ++sequence;
            feedback.textContent = messages.loading;
            try {
                const result = await parseResponse(await fetch(address, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: requests.signal }));
                if (request !== sequence || !dialog.isConnected) return;
                grid.replaceChildren(); navigation.replaceChildren();
                result.assets.forEach(asset => {
                    const choice = create('button'); choice.type = 'button'; choice.className = 'platno-media-choice';
                    if (asset.image) { const image = create('img'); image.src = asset.url; image.alt = ''; image.loading = 'lazy'; choice.append(image); }
                    else choice.append(create('span', 'PDF'));
                    choice.append(create('span', asset.name));
                    choice.addEventListener('click', () => selected(asset)); grid.append(choice);
                });
                for (const [address, label] of [[result.previous, messages.previous], [result.next, messages.next]]) {
                    if (!address) continue;
                    const button = create('button', label); button.type = 'button'; button.className = 'platno-quiet'; button.addEventListener('click', () => load(address)); navigation.append(button);
                }
                feedback.textContent = result.assets.length ? messages.select_asset : messages.empty_media;
            } catch (error) { if (request === sequence) failure(error); }
        };
        const searchAssets = () => load(`${settings.index}?kind=${field.kind || 'file'}&search=${encodeURIComponent(search.value)}`);
        searchButton.addEventListener('click', searchAssets);
        search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); searchAssets(); } });
        const uploadLabel = create('label', messages.upload);
        const upload = create('input'); upload.type = 'file'; upload.accept = field.kind === 'image' ? 'image/jpeg,image/png,image/webp' : 'image/jpeg,image/png,image/webp,application/pdf'; upload.id = 'platno-media-upload'; uploadLabel.htmlFor = upload.id;
        upload.addEventListener('change', async () => {
            if (!upload.files.length) return;
            const data = new FormData(); data.append('file', upload.files[0]); data.append('_token', settings.token);
            upload.disabled = true; feedback.textContent = messages.uploading;
            try { selected(await parseResponse(await fetch(settings.index, { method: 'POST', headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: requests.signal, body: data }))); }
            catch (error) { failure(error); upload.disabled = false; }
        });
        dialog.append(header, uploadLabel, upload, create('p', messages.upload_help), searchLabel, search, searchButton, feedback, grid, navigation);
        dialog.addEventListener('close', () => { requests.abort(); dialog.remove(); (root.querySelector(`[id="${identity}"]`) || trigger).focus(); });
        (root.body || root.querySelector('.platno-editor') || root).append(dialog); dialog.showModal(); searchAssets();
    });
    return wrapper;
}
