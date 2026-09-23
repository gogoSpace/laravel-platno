import { defineComponent, h, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { connectWorkspace } from './bridge.js';

export const PlatnoEditor = defineComponent({
    name: 'PlatnoEditor',
    props: {
        editorAddress: { type: String, required: true },
        moduleAddress: { type: String, default: '/platno/assets/workspace.js' },
    },
    emits: ['state', 'change', 'error'],
    setup(properties, { emit }) {
        const container = ref(null);
        let workspace;
        const mount = () => {
            workspace?.destroy();
            workspace = connectWorkspace(container.value, { editorAddress: properties.editorAddress, moduleAddress: properties.moduleAddress,
                onState: value => emit('state', value), onChange: value => emit('change', value), onError: value => emit('error', value),
            });
        };
        onMounted(mount);
        watch(() => [properties.editorAddress, properties.moduleAddress], () => { if (container.value) mount(); });
        onBeforeUnmount(() => workspace?.destroy());
        return () => h('div', { ref: container });
    },
});
