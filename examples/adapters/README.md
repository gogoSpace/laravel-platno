# Direct framework integrations

**Experimental Preview.** These files are optional source examples to copy into a host application, not separately published npm packages. They edit pages owned by Platno; arbitrary Eloquent-model form fields are not implemented.

Vue, React and Livewire mount the same visual editor **directly in the host page**. No iframe, postMessage bridge or framework-specific copy of the document model is used. The package's native JavaScript owns selection, composition, history, inspectors and media. Its canvas renders the same Blade plugin views as public pages. Shadow DOM isolates editor styling and per-instance controls from the host page.

The framework is supplied by the consuming application. The Composer package adds no Vue, React, Livewire, PrimeVue or Node requirement. Every request uses the host-mounted editor routes, session and CSRF protection. Platno supplies no authentication.

## React

Copy `react.js` and `bridge.js` into your existing application:

```jsx
import { PlatnoEditor } from './platno/react.js';

<PlatnoEditor
    editorAddress="/platno/pages/42/edit"
    moduleAddress="/platno/assets/workspace.js"
    onState={page => console.log('Persisted revision', page?.revision)}
    onChange={({ document, dirty }) => console.log('Unsaved document', document, dirty)}
    onError={({ message }) => console.error(message)}
/>
```

Callbacks do not remount the editor when their function identities change. Unmounting aborts pending requests and releases listeners. Changing either address intentionally replaces the instance; the host should handle any unsaved-change confirmation before replacing or unmounting it.

## Vue

Copy `vue.js` and `bridge.js` into your existing Vue 3 application:

```vue
<script setup>
import { PlatnoEditor } from './platno/vue.js';
</script>

<template>
    <PlatnoEditor
        editor-address="/platno/pages/42/edit"
        module-address="/platno/assets/workspace.js"
        @state="page => console.log(page?.revision)"
        @change="change => console.log(change.document, change.dirty)"
        @error="error => console.error(error.message)"
    />
</template>
```

The adapter handles mounting, address changes and disposal. As in React, the host owns navigation or replacement while content is dirty.

## Livewire

For Livewire 4, copy `livewire/PlatnoEditor.php` to `app/Livewire/PlatnoEditor.php` and its Blade view to `resources/views/livewire/platno-editor.blade.php`:

```blade
<livewire:platno-editor :page-identifier="$page->getKey()" />
```

The component uses Livewire's bundled Alpine lifecycle (`init`/`destroy`) and `wire:ignore`. It does not install another Alpine copy. Host DOM morphing leaves the editor subtree intact; removal destroys its resources. Its locked page identifier selects a host-protected route. No content is saved through Livewire properties or automatic server events.

`platno:state`, `platno:change` and `platno:error` browser events bubble from the workspace. Listen in the host if needed; these events describe state and are not authorization evidence. Backend mutations always validate input and revision separately.

## Native mounting API

The host can also use the package directly without a framework:

```js
const { mountWorkspace } = await import('/platno/assets/workspace.js');
const editor = mountWorkspace(document.querySelector('#content-editor'), {
    editorAddress: '/platno/pages/42/edit',
    onState: page => console.log(page),
    onChange: ({ document, dirty }) => console.log(document, dirty),
});
await editor.ready;
const snapshot = editor.getDocument(); // Independent copy; does not mutate the editor.
editor.destroy(); // Call when the host removes this instance.
```

Both addresses must be on the current origin. Generate them using the named `platno.pages.edit` and `platno.editor.workspace` routes; adjust `moduleAddress` when using custom route prefixes. Each editor requires its own empty container. Multiple instances have separate documents, histories and event listeners.

Saving and publication stay inside the mounted workspace. During a pending mutation its controls are inert so later typing cannot be overwritten by the response. Validation and conflict responses retain the current document. Conflicts offer a separate tab with the latest draft; Platno never silently retries a stale revision. Normal page navigation warns about unsaved changes; host SPA unmounting must use the `dirty` event/state to apply the host's navigation policy.

Public output remains plain Blade without an editor runtime. These adapters expose one shared native editor; they do not provide separate React/Vue public renderers or framework-specific plugin inspectors. Basic plugins use PHP, Blade and declarative fields in every integration. Host styles for plugin output belong in the overridden `platno::public.styles` view so they reach both public output and the isolated canvas.

## Verification

`node --experimental-default-type=module --test tests/adapter_bridge.test.mjs tests/editor_store.test.mjs` checks same-origin module loading and document/history integrity. No DOM snapshots or label/style assertions are used.

`python3 tests/prepare_adapter_demo.py` creates a new isolated consumer and adds the optional framework demo routes. It installs Livewire only in that disposable application. React/Vue demo pages use pinned ESM distributions; production applications use their existing framework setup. Start the printed application on a loopback port to inspect `/integrations/react`, `/integrations/vue` and `/integrations/livewire`.

Executed browser journeys and runtime versions are recorded in [compatibility](../../docs/compatibility.md).
