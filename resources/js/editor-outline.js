import { element, button } from './editor-dom.js';

export function createOutline(container, store, catalog, messages, add) {
    let draggedPath;
    function render() {
        container.replaceChildren();
        const visit = (blocks, listPath, depth) => {
            blocks.forEach((block, position) => {
                const path = `${listPath}.${position}`;
                const row = element('div', undefined, 'platno-outline-row');
                row.style.setProperty('--depth', depth);
                const select = button(catalog.get(block.type)?.label || block.type, () => store.select(path));
                select.title = Object.values(block.data).find(value => typeof value === 'string') || block.type;
                select.setAttribute('aria-pressed', String(store.selectedPath === path));
                select.draggable = true;
                select.addEventListener('dragstart', event => { draggedPath = path; event.dataTransfer.setData('text/plain', path); event.dataTransfer.effectAllowed = 'move'; });
                row.addEventListener('dragover', event => { if (draggedPath) { event.preventDefault(); row.dataset.drop = 'true'; } });
                row.addEventListener('dragleave', () => { delete row.dataset.drop; });
                row.addEventListener('drop', event => { event.preventDefault(); store.relocate(draggedPath, listPath, position); draggedPath = null; });
                row.append(select); container.append(row);
                store.fields(block).forEach(([name, field]) => {
                    const childPath = `${path}.data.${name}`;
                    const group = element('div', undefined, 'platno-outline-group');
                    group.style.setProperty('--depth', depth + 1);
                    const insert = button(`+ ${field.label}`, () => add(childPath));
                    insert.addEventListener('dragover', event => { if (draggedPath) event.preventDefault(); });
                    insert.addEventListener('drop', event => { event.preventDefault(); store.relocate(draggedPath, childPath, block.data[name].length); draggedPath = null; });
                    group.append(insert); container.append(group);
                    visit(block.data[name], childPath, depth + 1);
                });
            });
        };
        visit(store.document.blocks, 'document.blocks', 0);
        if (!store.document.blocks.length) container.append(element('p', messages.empty_blocks, 'platno-help'));
    }
    render();
    const stop = store.subscribe(kind => { if (kind !== 'change' || !container.contains(container.getRootNode().activeElement)) render(); });
    return { render, destroy: stop };
}
