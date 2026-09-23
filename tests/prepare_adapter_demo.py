#!/usr/bin/env python3
"""Create a fresh, isolated consumer for the optional framework browser checks."""

import json
import os
from pathlib import Path
import shutil
import subprocess

repository = Path(__file__).resolve().parents[1]
result = subprocess.run(['python3', str(repository / 'tests/consumer_smoke.py'), '13'], cwd=repository, text=True, stdout=subprocess.PIPE, check=True)
print(result.stdout, flush=True)
application = Path(json.loads(result.stdout[result.stdout.index('{'):])['application'])
assert application.parent == repository / '.sandbox'
assert application.name.startswith('consumer13-')
environment = {name: value for name, value in os.environ.items() if not name.startswith(('APP_', 'DB_', 'DATABASE_', 'REDIS_', 'CACHE_', 'QUEUE_', 'SESSION_', 'MAIL_', 'AWS_', 'PLATNO_', 'LOG_'))}
log_path = application / 'adapter-setup.log'
with log_path.open('w') as log:
    subprocess.run(['composer', 'require', 'livewire/livewire:^4.0', '--update-no-dev', '--no-interaction', '--no-progress'], cwd=application, env=environment, stdout=log, stderr=subprocess.STDOUT, check=True)

middleware = application / 'app/Http/Middleware/AllowContentEditing.php'
middleware.write_text('''<?php
namespace App\\Http\\Middleware;
final class AllowContentEditing {
    public function handle($request, $next) {
        abort_unless(app()->environment('local') && in_array($request->ip(), ['127.0.0.1','::1'], true), 403);
        return $next($request);
    }
}
''')
for name in ['bridge.js', 'react.js', 'vue.js']:
    destination = application / 'public/integrations' / name
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(repository / 'examples/adapters' / name, destination)
for source, destination in [('PlatnoEditor.php','app/Livewire/PlatnoEditor.php'),('platno-editor.blade.php','resources/views/livewire/platno-editor.blade.php')]:
    target = application / destination
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(repository / 'examples/adapters/livewire' / source, target)
views = application / 'resources/views/integrations'
views.mkdir(parents=True, exist_ok=True)
imports = {'imports': {'react': 'https://esm.sh/react@19.3.0', 'react-dom/client': 'https://esm.sh/react-dom@19.3.0/client?external=react', 'vue': 'https://cdn.jsdelivr.net/npm/vue@3.5.43/dist/vue.esm-browser.prod.js'}}
react_script = '''import { createElement, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { PlatnoEditor } from '/integrations/react.js';
function Application() {
    const [state, setState] = useState(null);
    const [visible, setVisible] = useState(true);
    return createElement('section', null,
        createElement('button', {onClick:() => setVisible(!visible)}, 'Toggle editor'),
        createElement('p', {role:'status'}, state ? 'Saved revision ' + state.revision : 'Loading editor'),
        visible ? createElement(PlatnoEditor, {editorAddress:'/platno/pages/2/edit', onState:setState}) : null);
}
createRoot(document.getElementById('application')).render(createElement(Application));'''
vue_script = '''import { createApp, h, ref } from 'vue';
import { PlatnoEditor } from '/integrations/vue.js';
createApp({setup() {
    const state = ref(null);
    const visible = ref(true);
    return () => h('section', [h('button', {onClick:() => visible.value = !visible.value}, 'Toggle editor'), h('p', {role:'status'}, state.value ? 'Saved revision ' + state.value.revision : 'Loading editor'),
        visible.value ? h(PlatnoEditor, {editorAddress:'/platno/pages/2/edit', onState:value => state.value = value}) : null]);
}}).mount('#application');'''
for framework, script in [('react',react_script),('vue',vue_script)]:
    (views / (framework + '.blade.php')).write_text(f'''<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{framework.title()} integration</title><script type="importmap">{json.dumps(imports)}</script></head>
<body style="margin:24px;font:16px system-ui"><h1>{framework.title()} integration</h1><div id="application"></div><script type="module">{script}</script></body></html>''')
(views / 'livewire.blade.php').write_text('''<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Livewire integration</title>@livewireStyles</head><body style="margin:24px;font:16px system-ui"><h1>Livewire integration</h1><livewire:platno-editor :page-identifier="2" />@livewireScripts</body></html>''')
routes = application / 'routes/web.php'
routes.write_text(routes.read_text() + "\nRoute::middleware(\\App\\Http\\Middleware\\AllowContentEditing::class)->group(function () {\n" + ''.join(f"    Route::view('/integrations/{framework}', 'integrations.{framework}');\n" for framework in ['react','vue','livewire']) + '});\n')
with log_path.open('a') as log:
    for arguments in [['route:cache'], ['view:clear']]:
        subprocess.run(['php', 'artisan', *arguments], cwd=application, env=environment, stdout=log, stderr=subprocess.STDOUT, check=True)
installed = json.loads((application / 'vendor/composer/installed.json').read_text())['packages']
livewire_version = next(package['version'] for package in installed if package['name'] == 'livewire/livewire')
evidence = {'application':str(application), 'react':'19.3.0', 'vue':'3.5.43', 'livewire':livewire_version, 'browser_status':'pending'}
(application / 'adapter-result.json').write_text(json.dumps(evidence,indent=2)+'\n')
print(json.dumps(evidence,indent=2),flush=True)
