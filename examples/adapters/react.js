import { createElement, useEffect, useRef } from 'react';
import { connectWorkspace } from './bridge.js';

export function PlatnoEditor({ editorAddress, moduleAddress = '/platno/assets/workspace.js', onState, onChange, onError }) {
    const container = useRef(null);
    const callbacks = useRef({ onState, onChange, onError });
    callbacks.current = { onState, onChange, onError };
    useEffect(() => {
        const workspace = connectWorkspace(container.current, { editorAddress, moduleAddress,
            onState: value => callbacks.current.onState?.(value),
            onChange: value => callbacks.current.onChange?.(value),
            onError: value => callbacks.current.onError?.(value),
        });
        return () => workspace.destroy();
    }, [editorAddress, moduleAddress]);
    return createElement('div', { ref: container });
}
