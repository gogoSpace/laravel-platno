import assert from 'node:assert/strict';
import test from 'node:test';
import { createEditorStore } from '../resources/js/editor-store.js';

const catalog = new Map([
    ['text', { type: 'text', editable: true, version: 1, fields: { text: { type: 'text' } }, defaults: { text: '' } }],
    ['group', { type: 'group', editable: true, version: 1, fields: { children: { type: 'blocks' } }, defaults: { children: [] } }],
]);
const text = value => ({ id: crypto.randomUUID(), type: 'text', version: 1, data: { text: value } });

// Protect document content and identities through undo/redo, nested duplication and relocation.
test('history and block operations preserve portable data', () => {
    const initial = { version: 1, blocks: [{ id: crypto.randomUUID(), type: 'group', version: 1, data: { children: [text('original')] } }, text('outside')] };
    const store = createEditorStore(initial, catalog);
    store.select('document.blocks.0');
    store.duplicate();
    assert.equal(store.document.blocks[1].data.children[0].data.text, 'original');
    assert.notEqual(store.document.blocks[0].id, store.document.blocks[1].id);
    assert.notEqual(store.document.blocks[0].data.children[0].id, store.document.blocks[1].data.children[0].id);
    store.undo();
    assert.deepEqual(store.document, initial);
    store.redo();
    assert.equal(store.document.blocks.length, 3);
    assert.equal(store.relocate('document.blocks.0', 'document.blocks.0.data.children', 0), false);
    assert.equal(store.relocate('document.blocks.2', 'document.blocks.1.data.children', 1), true);
    assert.equal(store.selected.data.text, 'outside');
    store.updateField(store.selectedPath, 'text', 'first');
    store.updateField(store.selectedPath, 'text', 'second');
    store.undo();
    assert.equal(store.selected.data.text, 'outside');
    store.redo();
    assert.equal(store.selected.data.text, 'second');
    assert.equal(initial.blocks[1].data.text, 'outside');
});

// Prevent edits exceeding server limits from partially deleting or moving existing content.
test('rejected edits leave the document and history intact', () => {
    const initial = { version: 1, blocks: Array.from({ length: 100 }, (_, index) => text(String(index))) };
    const store = createEditorStore(initial, catalog);
    assert.equal(store.add('text'), false);
    assert.deepEqual(store.document, initial);
    assert.equal(store.canUndo, false);
});
