import { clone } from './editor-dom.js';

export function createEditorStore(initialDocument, catalog) {
    let content = clone(initialDocument);
    let selectedPath = content.blocks.length ? 'document.blocks.0' : null;
    let previousKey = null;
    let previousTime = 0;
    const undo = [];
    const redo = [];
    const listeners = new Set();
    const fields = block => Object.entries(catalog.get(block.type)?.fields || {}).filter(([, field]) => field.type === 'blocks');
    const at = path => path?.split('.').slice(1).reduce((value, part) => value?.[part], content);
    const snapshot = () => ({ document: clone(content), selectedPath });
    const emit = kind => listeners.forEach(listener => listener(kind));
    const entries = () => {
        const collection = [];
        const visit = (blocks, path, depth) => blocks.forEach((block, position) => {
            const blockPath = `${path}.${position}`;
            collection.push({ block, path: blockPath, listPath: path, position, depth });
            fields(block).forEach(([name]) => visit(block.data[name], `${blockPath}.data.${name}`, depth + 1));
        });
        visit(content.blocks, 'document.blocks', 1);
        return collection;
    };
    const change = (action, mergeKey = null) => {
        const before = snapshot();
        action();
        const all = entries();
        if (all.length > 100 || all.some(entry => entry.depth > 6)) {
            content = before.document;
            selectedPath = before.selectedPath;
            return false;
        }
        if (JSON.stringify(content) === JSON.stringify(before.document)) return true;
        const now = Date.now();
        if (!mergeKey || mergeKey !== previousKey || now - previousTime > 800) {
            undo.push(before);
            if (undo.length > 100) undo.shift();
        }
        previousKey = mergeKey;
        previousTime = now;
        redo.length = 0;
        emit('change');
        return true;
    };
    const travel = (source, destination) => {
        if (!source.length) return;
        destination.push(snapshot());
        const state = source.pop();
        content = state.document;
        selectedPath = state.selectedPath;
        previousKey = null;
        emit('history');
    };
    const identities = block => {
        block.id = crypto.randomUUID();
        fields(block).forEach(([name]) => block.data[name].forEach(identities));
    };
    return {
        get document() { return content; },
        get selectedPath() { return selectedPath; },
        get selected() { return at(selectedPath); },
        get canUndo() { return undo.length > 0; },
        get canRedo() { return redo.length > 0; },
        at, fields, entries, change,
        subscribe(listener) { listeners.add(listener); return () => listeners.delete(listener); },
        select(path) { selectedPath = path; previousKey = null; emit('selection'); },
        updateField(path, name, value) { return change(() => { at(path).data[name] = clone(value); }, `${path}.${name}`); },
        undo() { travel(undo, redo); },
        redo() { travel(redo, undo); },
        add(type, listPath = 'document.blocks', position = at(listPath).length) {
            const plugin = catalog.get(type);
            if (!plugin?.editable) return false;
            return change(() => {
                const block = { id: crypto.randomUUID(), type, version: plugin.version, data: clone(plugin.defaults) };
                identities(block);
                at(listPath).splice(position, 0, block);
                selectedPath = `${listPath}.${position}`;
            });
        },
        duplicate() {
            if (!selectedPath) return false;
            return change(() => {
                const listPath = selectedPath.slice(0, selectedPath.lastIndexOf('.'));
                const position = Number(selectedPath.split('.').at(-1)) + 1;
                const copy = clone(at(selectedPath));
                identities(copy);
                at(listPath).splice(position, 0, copy);
                selectedPath = `${listPath}.${position}`;
            });
        },
        remove() {
            if (!selectedPath) return;
            change(() => {
                const listPath = selectedPath.slice(0, selectedPath.lastIndexOf('.'));
                const position = Number(selectedPath.split('.').at(-1));
                const blocks = at(listPath);
                blocks.splice(position, 1);
                selectedPath = blocks.length ? `${listPath}.${Math.min(position, blocks.length - 1)}` : null;
            });
        },
        move(offset) {
            const entry = entries().find(entry => entry.path === selectedPath);
            if (entry) this.relocate(entry.path, entry.listPath, entry.position + offset);
        },
        relocate(sourcePath, targetListPath, targetPosition) {
            if (targetListPath.startsWith(`${sourcePath}.`)) return false;
            const sourceListPath = sourcePath.slice(0, sourcePath.lastIndexOf('.'));
            const sourcePosition = Number(sourcePath.split('.').at(-1));
            const source = at(sourceListPath);
            const target = at(targetListPath);
            if (!Array.isArray(source) || !Array.isArray(target) || !source[sourcePosition] || targetPosition < 0 || targetPosition > target.length) return false;
            const moving = source[sourcePosition];
            return change(() => {
                source.splice(sourcePosition, 1);
                target.splice(Math.min(targetPosition, target.length), 0, moving);
                selectedPath = entries().find(entry => entry.block === moving)?.path || null;
            });
        },
    };
}
