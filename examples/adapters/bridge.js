/** Load the host's installed package module. Never load code from another origin. */
export function moduleAddress(address) {
    const parsed = new URL(address, window.location.href);
    if (parsed.origin !== window.location.origin || !['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) throw new TypeError('Supply a same-origin Platno module address.');
    return parsed.href;
}

export function connectWorkspace(container, options) {
    let disposed = false;
    let workspace;
    const ready = import(moduleAddress(options.moduleAddress)).then(module => {
        if (disposed) return;
        workspace = module.mountWorkspace(container, options);
        return workspace.ready;
    }).catch(error => {
        if (!disposed) { container.textContent = error.message; options.onError?.({ message: error.message }); }
    });
    return { ready, destroy() { disposed = true; workspace?.destroy(); } };
}
