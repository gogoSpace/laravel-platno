import assert from 'node:assert/strict';
import test from 'node:test';
import { moduleAddress } from '../examples/adapters/bridge.js';

// Prevent the optional adapters importing executable code from foreign origins.
test('workspace modules are restricted to the host origin', () => {
    globalThis.window = { location: { href: 'https://host.test/editor', origin: 'https://host.test' } };
    assert.equal(moduleAddress('/content/assets/workspace.js'), 'https://host.test/content/assets/workspace.js');
    for (const address of ['//other.test/module.js', 'https://other.test', 'javascript:alert(1)', '/\\other.test', 'https://user:secret@host.test/module.js']) {
        assert.throws(() => moduleAddress(address), TypeError);
    }
    delete globalThis.window;
});
