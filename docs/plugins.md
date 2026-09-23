# Custom plugins

The default editor builds native inspectors from PHP field declarations. A basic custom block needs one PHP class and one Blade view. It needs no JavaScript, framework or asset build. This is an **Experimental Preview**; plugin APIs and stored content contracts are provisional.

## Generate and register

Run in the consuming Laravel application:

```bash
php artisan platno:make-plugin Callout --type=example.callout
```

With the standard application namespace, this creates `app/Platno/Callout.php` and `resources/views/platno-custom/callout.blade.php`. The generator uses the host's configured namespace, refuses existing destinations and prints the class to register. It never changes configuration automatically.

Append that class to the **existing** `plugins` list in `config/platno.php`:

```php
\App\Platno\Callout::class,
```

The list replaces the package defaults; retain the built-ins you want. Each plugin type must be unique. Lowercase identifiers may contain segments separated by dots, hyphens or underscores, such as `example.callout`. If configuration is cached, rebuild it with `php artisan config:cache`. Reopen the editor, choose Callout, add a block, enter text, save and publish. The fresh-consumer acceptance script executes this exact generator/registration journey.

## Declarative example

For a two-field example, copy [Notice.php](../examples/plugins/Notice.php) into `app/Content/Notice.php` and [notice.blade.php](../examples/plugins/notice.blade.php) into `resources/views/content/notice.blade.php`, then append `\App\Content\Notice::class` to the plugin list. The class extends `Platno\Plugins\Plugin`:

```php
public function type(): string
{
    return 'example.notice';
}

public function label(): string
{
    return 'Notice';
}

public function fields(): array
{
    return [
        'message' => ['type' => 'textarea', 'label' => 'Message', 'required' => true, 'default' => 'Something worth sharing'],
        'tone' => ['type' => 'select', 'label' => 'Tone', 'options' => ['note' => 'Note', 'important' => 'Important'], 'default' => 'note'],
    ];
}

public function view(): string
{
    return 'content.notice';
}
```

Blade receives validated values as `$data`. Escape content and enumerate any markup choices:

```blade
<aside role="note">
    @if ($data['tone'] === 'important')
        <strong>!</strong>
    @endif
    <p>{{ $data['message'] }}</p>
</aside>
```

Labels and help text can use the host's normal Laravel translations. Keep custom CSS scoped to the plugin. Configuration stores class names, not instances or closures.

## Fields and validation

`EditablePlugin` extends `BlockPlugin` with `label()` and `fields()`. The `Plugin` base class derives defaults and Laravel validation rules and uses schema version 1. Override `defaults()`, `rules()` or `schemaVersion()` when the actual data contract needs it. Validation always runs on the server over the complete document, including unselected blocks.

Each field has a stable name, `type` and `label`. Names start with a letter and contain letters, digits or underscores. Optional `help`, `default` and `required` keys control the common inspector. Supported field types:

| Type | Data and options |
| --- | --- |
| `text`, `textarea` | String; optional `max` length, default limit 50,000 characters. |
| `number` | Numeric value; `min`, `max`, optional browser `step`. Default server range −1,000,000 to 1,000,000. |
| `checkbox` | Boolean; defaults to `false`. |
| `select` | Value from an `options` map of stored value to display label; defaults to the first option. Use string keys. |
| `url` | String; HTTP/HTTPS URL, root-relative path or fragment; rejects executable schemes, protocol-relative destinations, controls and backslashes. |
| `blocks` | Ordered child blocks; default `[]`; supplies a nested composer and shares the document's total/depth limits. |
| `richtext` | Structured paragraphs/list items and marked text runs; native formatting controls and server validation. |
| `asset` | Asset UUID; `kind: image` restricts to raster images, otherwise files; supplies the media picker and reference tracking. |

All declared keys must be present in stored data; optional text values use `''`, child lists use `[]`. Set `required: true` when an empty value is invalid. New blocks receive defaults, which may intentionally require editing before the first save. Browser constraints supplement server rules; for example, a custom numeric `step` needs a matching server rule if it is part of the data contract.

If custom rules allow nested objects, enumerate their allowed keys with Laravel's `array:key,...` rules. Do not return a permissive array rule for arbitrary untrusted HTML. Unknown document and block keys are rejected, rather than silently removed.

## Nested content and assets

Nested lists must be declared as `blocks` fields. The document validator walks them recursively, enforcing a total of 100 blocks, a maximum depth of six and unique optional UUIDs. The editor supplies a nested outline, movement, duplication, removal and undo/redo. Duplicating a subtree assigns new identities throughout.

The renderer passes `$renderer` and `$preview` to plugin views. Render children through that renderer, preserving preview context:

```blade
{!! $renderer->children($data['children'], $preview, $canvasPath ?? null, 'children') !!}
```

The raw expression above emits the trusted renderer's Blade output, never submitted HTML. Each plugin remains responsible for escaping its own content. The declared field name (`children`) preserves direct selection of nested blocks on the canvas. Follow the built-in Group/Columns views for composition and Image/File views for media URLs. Previews use editor routes; public pages use publication-checked media routes. Asset references are collected automatically only from declared `asset` fields and recursively declared `blocks` fields. Custom plugins must not hide managed asset identifiers in unrelated text fields or serialize public storage URLs.

An optional asset field stores `''` when unset; its view must handle that state. Required asset fields fail saving until an available asset is selected. Drafts and every retained publication protect their referenced assets against deletion. A public media URL is usable only while a current publication references that asset.

## Portable document and rich text

A document contains `version: 1` and an ordered `blocks` list. Each block contains `type`, `version`, `data` and optionally `id` (UUID). Existing identity-free blocks remain readable. Empty documents are valid. New editor blocks receive UUIDs; document order determines output order.

A `richtext` value has this shape:

```json
[
  {
    "type": "paragraph",
    "runs": [
      {"text": "Hello", "bold": true, "italic": false, "link": ""}
    ]
  }
]
```

Item types are `paragraph`, `bullet` or `numbered`; adjacent list items render in semantic lists. Every run declares all four keys. Limits: 100 items, 200 runs per item, 10,000 characters per run and 200 KB encoded per rich-text field. Complete HTTP documents are limited to approximately 2 MB. No raw HTML import, sanitization or automatic schema migration is supplied.

Changing a plugin's type, fields or schema version can make old drafts and retained publications unreadable. Keep the contract compatible or plan an explicit migration; the package does not silently upgrade data. Unknown types/versions prevent edits and publication with 422, prevent previews with 422 and produce an unavailable public response with 503. Stored data remains intact. There is one registered implementation per type, not a multi-version registry.

## Advanced boundaries

A plugin may implement `BlockPlugin` directly with `type()`, `schemaVersion()`, `defaults()`, `rules()` and `view()`. Such renderer-only blocks are preserved and can be moved or removed; their data has no editable inspector and they do not appear in the add menu. Implement `EditablePlugin` to expose editable fields. Arbitrary custom JavaScript inspector registration is not supplied in this version.

For trusted server rendering without persistence:

```php
app(\Platno\Rendering\BlockRenderer::class)
    ->render('example.notice', ['message' => 'Your content', 'tone' => 'note']);
```

That method applies defaults and validation and returns rendered HTML. Stored-document validation requires complete data; it does not fill missing defaults. `DraftManager`, `PagePublisher` and `PageLifecycle` are trusted server services. They preserve transactional/revision guarantees but do not decide who may call them. The host owns authorization before calling them or mounting the editor routes.

Plugin PHP and Blade files are trusted application code. Content is untrusted. Public rendering uses Blade regardless of whether the host uses a [Vue, React or Livewire workspace adapter](../examples/adapters/README.md). Direct database/query-builder writes can bypass model immutability and asset-reference guarantees and are outside the package contract.

## Visual canvas

The canvas uses your existing Blade view with `$preview = true`. It needs no second JavaScript renderer. Plugin output is trusted code and must escape user content exactly as public rendering does. Add plugin styling to the host override of `platno::public.styles`; that view styles both public pages and the isolated canvas.

For a nested `blocks` field, use the path-aware child renderer so its descendants can be selected directly on the canvas:

```blade
<section>
    {!! $renderer->children($data['children'], $preview, $canvasPath ?? null, 'children') !!}
</section>
```

The field name must match the declared field. `$canvasPath` is supplied in the canvas context and is null publicly. Existing `render(type, data, preview)` calls remain compatible, but without a child path only their containing block is selectable on the canvas; the outline/inspector still exposes declared descendants. No editor wrappers are emitted by the default public renderer.

The canvas accepts only valid documents. While a required field is incomplete (such as an image without a selected asset), the editor retains unsaved data, shows the validation reason and keeps the last valid preview. This never publishes the draft. Text/Heading built-ins additionally support direct text entry on the canvas; other plugins use their declarative inspectors.
