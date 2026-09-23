export const clone = value => JSON.parse(JSON.stringify(value));
export function element(tag, text, className) {
    const node = document.createElement(tag);
    if (text !== undefined) node.textContent = text;
    if (className) node.className = className;
    return node;
}
export function button(label, action, disabled = false) {
    const node = element('button', label, 'platno-quiet');
    node.type = 'button';
    node.disabled = disabled;
    node.addEventListener('click', action);
    return node;
}
export function editorAddress(address) {
    const parsed = new URL(address, window.location.href);
    if (parsed.origin !== window.location.origin || !['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) {
        throw new TypeError('Platno requires a same-origin address.');
    }
    return parsed.href;
}
