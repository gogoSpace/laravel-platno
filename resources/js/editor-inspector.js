import { element, button, clone } from './editor-dom.js';
import { assetField } from './asset-picker.js';
import { richTextField } from './rich-text.js';

export function createInspector(container, store, catalog, messages, media, root, add) {
    function render() {
        container.replaceChildren();
        const path = store.selectedPath;
        const block = store.selected;
        const plugin = block && catalog.get(block.type);
        const heading = element('h2', plugin?.label || messages.inspector);
        heading.id = 'inspector-title';
        container.append(heading);
        if (!block) { container.append(element('p', messages.select_block, 'platno-help')); return; }
        if (!plugin?.editable) { container.append(element('p', messages.no_inspector)); return; }
        const change = (name, value) => store.updateField(path, name, value);
        Object.entries(plugin.fields).forEach(([name, field]) => {
            const wrapper = element('div', undefined, 'platno-field');
            const identity = `${path}.data.${name}`;
            const label = element('label', field.label);
            label.htmlFor = identity;
            if (field.type === 'asset') {
                label.removeAttribute('for');
                wrapper.append(label, assetField(block.data[name], field, identity, messages, media, value => { change(name, value); render(); }, root));
            } else if (field.type === 'blocks') {
                wrapper.append(label, button(`${messages.add_block} · ${field.label}`, () => add(identity)));
            } else if (field.type === 'richtext') {
                const paragraphs = clone(block.data[name]);
                wrapper.append(label, richTextField(paragraphs, identity, messages, () => change(name, paragraphs)));
            } else {
                const control = element(field.type === 'textarea' ? 'textarea' : field.type === 'select' ? 'select' : 'input');
                control.id = identity;
                if (field.type === 'select') {
                    Object.entries(field.options).forEach(([value, label]) => {
                        const option = element('option', label); option.value = value; control.append(option);
                    });
                } else if (field.type === 'checkbox') {
                    control.type = 'checkbox'; control.checked = Boolean(block.data[name]);
                } else if (field.type !== 'textarea') control.type = field.type === 'number' ? 'number' : 'text';
                if (field.type !== 'checkbox') control.value = block.data[name] ?? '';
                if (field.type === 'textarea') control.rows = 5;
                if (field.required) control.required = true;
                if (field.max !== undefined) field.type === 'number' ? control.max = field.max : control.maxLength = field.max;
                if (field.min !== undefined && field.type === 'number') control.min = field.min;
                if (field.step !== undefined && field.type === 'number') control.step = field.step;
                control.addEventListener('input', () => change(name, field.type === 'checkbox' ? control.checked : field.type === 'number' ? Number(control.value) : control.value));
                wrapper.append(label, control);
                if (field.help) wrapper.append(element('p', field.help, 'platno-help'));
            }
            container.append(wrapper);
        });
        const destinationLabel = element('label', messages.move_to);
        const destination = element('select');
        destination.id = 'platno-move-target'; destinationLabel.htmlFor = destination.id;
        const prompt = element('option', messages.choose_location); prompt.value = ''; destination.append(prompt);
        const targets = [['document.blocks', messages.root_blocks]];
        store.entries().forEach(entry => store.fields(entry.block).forEach(([name, field]) => {
            const targetPath = `${entry.path}.data.${name}`;
            if (!targetPath.startsWith(`${path}.`)) targets.push([targetPath, `${catalog.get(entry.block.type)?.label} · ${field.label}`]);
        }));
        targets.forEach(([targetPath, text]) => { const option = element('option', text); option.value = targetPath; destination.append(option); });
        destination.addEventListener('change', () => {
            if (destination.value) { store.relocate(path, destination.value, store.at(destination.value).length); render(); }
        });
        const moving = element('div', undefined, 'platno-field'); moving.append(destinationLabel, destination); container.append(moving);
        if (!Object.keys(plugin.fields).length) container.append(element('p', messages.no_fields, 'platno-help'));
    }
    render();
    const stop = store.subscribe(kind => { if (kind !== 'change') render(); });
    return { render, destroy: stop };
}
