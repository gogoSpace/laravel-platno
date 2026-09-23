export function richTextField(paragraphs, identity, messages, changed) {
    const create = (tag, text) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const wrapper = create('div');
    wrapper.className = 'platno-rich-editor';
    const plainRun = text => ({ text, bold: false, italic: false, link: '' });
    const mergeRuns = runs => runs.filter(run => run.text !== '').reduce((merged, run) => {
        const previous = merged.at(-1);
        if (previous && previous.bold === run.bold && previous.italic === run.italic && previous.link === run.link) previous.text += run.text;
        else merged.push({ ...run });
        return merged;
    }, []);
    const sliceRuns = (runs, start, end) => {
        let offset = 0;
        return runs.flatMap(run => {
            const from = Math.max(0, start - offset);
            const to = Math.min(run.text.length, end - offset);
            offset += run.text.length;
            return to > from ? [{ ...run, text: run.text.slice(from, to) }] : [];
        });
    };
    const button = (label, action) => {
        const control = create('button', label);
        control.className = 'platno-quiet';
        control.type = 'button';
        control.addEventListener('click', action);
        return control;
    };
    function render() {
        wrapper.replaceChildren();
        const help = create('p', messages.rich_help);
        help.className = 'platno-help';
        wrapper.append(help);
        paragraphs.forEach((paragraph, position) => {
            const section = create('fieldset');
            section.append(create('legend', `${messages.paragraph} ${position + 1}`));
            const typeLabel = create('label', messages.paragraph_type);
            const type = create('select');
            type.id = `${identity}-${position}-type`;
            typeLabel.htmlFor = type.id;
            ['paragraph', 'bullet', 'numbered'].forEach(value => {
                const option = create('option', messages[value]);
                option.value = value;
                type.append(option);
            });
            type.value = paragraph.type;
            type.addEventListener('change', () => { paragraph.type = type.value; changed(); });
            const textLabel = create('label', messages.paragraph_text);
            const textarea = create('div');
            textarea.contentEditable = 'true';
            textarea.className = 'platno-rich-editable';
            textarea.setAttribute('role', 'textbox');
            textarea.setAttribute('aria-label', messages.paragraph_text + ' ' + (position + 1));
            textarea.setAttribute('aria-multiline', 'true');
            let selection = { start: 0, end: 0 };
            const rememberSelection = () => {
                const browserSelection = textarea.getRootNode().getSelection?.() || window.getSelection();
                if (!browserSelection?.rangeCount) return;
                const range = browserSelection.getRangeAt(0);
                if (!textarea.contains(range.startContainer) || !textarea.contains(range.endContainer)) return;
                const before = range.cloneRange();
                before.selectNodeContents(textarea); before.setEnd(range.startContainer, range.startOffset);
                selection = { start: before.toString().length, end: before.toString().length + range.toString().length };
            };
            const restoreSelection = () => {
                const walker = document.createTreeWalker(textarea, NodeFilter.SHOW_TEXT);
                const nodes = []; let node;
                while ((node = walker.nextNode())) nodes.push(node);
                if (!nodes.length) { textarea.append(document.createTextNode('')); nodes.push(textarea.firstChild); }
                const locate = offset => {
                    for (const node of nodes) { if (offset <= node.length) return [node, offset]; offset -= node.length; }
                    const last = nodes.at(-1); return [last, last.length];
                };
                const range = document.createRange();
                range.setStart(...locate(selection.start)); range.setEnd(...locate(selection.end));
                const browserSelection = textarea.getRootNode().getSelection?.() || window.getSelection();
                browserSelection.removeAllRanges(); browserSelection.addRange(range);
            };
            ['keyup', 'mouseup', 'focus', 'input'].forEach(name => textarea.addEventListener(name, rememberSelection));
            textarea.id = `${identity}-${position}-text`;
            textLabel.htmlFor = textarea.id;
            textarea.textContent = paragraph.runs.map(run => run.text).join('');
            textarea.maxLength = 10000;
            textarea.rows = 4;
            const preview = textarea;


            const drawPreview = () => {
                preview.replaceChildren();
                paragraph.runs.forEach(run => {
                    let node = create('span', run.text);
                    if (run.italic) { const parent = create('em'); parent.append(node); node = parent; }
                    if (run.bold) { const parent = create('strong'); parent.append(node); node = parent; }
                    if (run.link) { const parent = create('u'); parent.append(node); node = parent; }
                    preview.append(node);
                });
            };
            const feedback = create('p');
            feedback.className = 'platno-help';
            feedback.setAttribute('role', 'status');
            const format = properties => {
                const start = selection.start;
                const end = selection.end;
                if (start === end) { feedback.textContent = messages.select_text; textarea.focus(); return; }
                const selected = sliceRuns(paragraph.runs, start, end).map(run => ({ ...run, ...properties }));
                paragraph.runs = mergeRuns([...sliceRuns(paragraph.runs, 0, start), ...selected, ...sliceRuns(paragraph.runs, end, textarea.innerText.length)]);
                feedback.textContent = '';
                changed();
                drawPreview();
                textarea.focus();
                selection = { start, end }; restoreSelection();
            };
            const toggle = property => {
                const selected = sliceRuns(paragraph.runs, selection.start, selection.end);
                format({ [property]: !selected.every(run => run[property]) });
            };
            textarea.addEventListener('beforeinput', event => {
                if (event.inputType.startsWith('format')) event.preventDefault();
                if (!['insertParagraph', 'insertLineBreak'].includes(event.inputType)) return;
                event.preventDefault();
                const browserSelection = textarea.getRootNode().getSelection?.() || window.getSelection();
                if (!browserSelection?.rangeCount) return;
                const range = browserSelection.getRangeAt(0);
                const newline = document.createTextNode('\n');
                range.deleteContents(); range.insertNode(newline); range.setStartAfter(newline); range.collapse(true);
                browserSelection.removeAllRanges(); browserSelection.addRange(range);
                textarea.dispatchEvent(new InputEvent('input', { bubbles: true }));
            });
            textarea.addEventListener('drop', event => event.preventDefault());
            textarea.addEventListener('paste', event => {
                event.preventDefault();
                const browserSelection = textarea.getRootNode().getSelection?.() || window.getSelection();
                if (!browserSelection?.rangeCount) return;
                const range = browserSelection.getRangeAt(0);
                if (!textarea.contains(range.commonAncestorContainer)) return;
                const text = document.createTextNode(event.clipboardData.getData('text/plain'));
                range.deleteContents(); range.insertNode(text); range.setStartAfter(text); range.collapse(true);
                browserSelection.removeAllRanges(); browserSelection.addRange(range);
                textarea.dispatchEvent(new InputEvent('input', { bubbles: true }));
            });
            textarea.addEventListener('keydown', event => {
                if ((event.ctrlKey || event.metaKey) && ['b', 'i'].includes(event.key.toLowerCase())) {
                    event.preventDefault(); toggle(event.key.toLowerCase() === 'b' ? 'bold' : 'italic');
                }
            });
            textarea.addEventListener('input', () => {
                const previous = paragraph.runs.map(run => run.text).join('');
                const current = textarea.innerText;
                let prefix = 0;
                while (prefix < previous.length && prefix < current.length && previous[prefix] === current[prefix]) prefix++;
                let suffix = 0;
                while (suffix < previous.length - prefix && suffix < current.length - prefix && previous.at(-suffix - 1) === current.at(-suffix - 1)) suffix++;
                const inherited = sliceRuns(paragraph.runs, Math.max(0, prefix - 1), Math.max(1, prefix))[0] || plainRun('');
                paragraph.runs = mergeRuns([
                    ...sliceRuns(paragraph.runs, 0, prefix),
                    { ...inherited, text: current.slice(prefix, current.length - suffix) },
                    ...sliceRuns(paragraph.runs, previous.length - suffix, previous.length),
                ]);
                changed();
            });
            const toolbar = create('div');
            toolbar.className = 'platno-block-actions';
            toolbar.addEventListener('mousedown', event => event.preventDefault());
            toolbar.append(button(messages.bold, () => toggle('bold')), button(messages.italic, () => toggle('italic')), button(messages.clear_format, () => format({ bold: false, italic: false, link: '' })));
            const linkLabel = create('label', messages.link_url);
            const link = create('input');
            link.type = 'text'; link.id = `${identity}-${position}-link`; link.maxLength = 2048; link.value = paragraph.runs[0]?.link || '';
            textarea.addEventListener('mouseup', () => {
                const selected = sliceRuns(paragraph.runs, selection.start, Math.max(selection.end, selection.start + 1));
                link.value = selected.length && selected.every(run => run.link === selected[0].link) ? selected[0].link : '';
            });
            linkLabel.htmlFor = link.id;
            const actions = create('div');
            actions.className = 'platno-block-actions';
            if (position > 0) actions.append(button('↑ ' + messages.move_up, () => {
                [paragraphs[position - 1], paragraphs[position]] = [paragraph, paragraphs[position - 1]]; changed(); render();
            }));
            actions.append(button(messages.remove, () => { paragraphs.splice(position, 1); changed(); render(); }));
            section.append(typeLabel, type, textLabel, toolbar, textarea, linkLabel, link, button(messages.apply_link, () => format({ link: link.value })), feedback, actions);
            wrapper.append(section);
            drawPreview();
        });
        if (paragraphs.length < 100) wrapper.append(button(messages.add_paragraph, () => {
            paragraphs.push({ type: 'paragraph', runs: [plainRun('')] }); changed(); render(); wrapper.querySelector('fieldset:last-of-type [contenteditable]')?.focus();
        }));
    }
    render();
    return wrapper;
}
