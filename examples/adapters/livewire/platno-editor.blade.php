<div x-data="{
    workspace: null,
    disposed: false,
    async init() {
        const module = await import(@js(route('platno.editor.workspace')));
        if (this.disposed) return;
        this.workspace = module.mountWorkspace(this.$refs.workspace, { editorAddress: @js($editorAddress) });
    },
    destroy() {
        this.disposed = true;
        this.workspace?.destroy();
    }
}">
    <div wire:ignore x-ref="workspace"></div>
</div>
