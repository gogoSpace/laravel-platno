# Experimental Preview: capabilities and verification

Platno is a working pre-alpha prototype. The current branch is for exploration and feedback, not production use. There are no stable API, plugin-schema or content-format compatibility promises, no supported stable release, and no Packagist publication yet.

## Current scope

| Area | Available now | Boundary |
| --- | --- | --- |
| Authoring | Visual Blade canvas, selection, outline, nesting, reorder, duplicate, undo/redo | Server-rendered preview requires a round trip; required incomplete fields retain the last valid preview. |
| Direct editing | Built-in text and headings | Other blocks use their inspector; custom inline editing API is not supplied. |
| Rich text | Structured paragraphs/lists with bold, italic and safe links | No raw HTML import, arbitrary embeds or HTML sanitization API. |
| Media | JPEG, PNG, WebP, PDF; original bytes; protected references | No cropping, derivatives, automatic stale-upload reconciliation or remote-disk acceptance evidence. |
| Publication | Private drafts, saved preview, immutable snapshots, history restore | No collaborative editing or permanent page/history deletion. |
| Plugins | PHP/Blade with declarative fields, nested content and managed assets | No automatic plugin-data migration or custom JavaScript inspector registration. |
| Frameworks | Direct Vue/React/Livewire mounts of the shared editor | Copyable examples, not npm packages; no separate framework renderers or arbitrary-model field binding. |
| Access | All editor routes inherit host middleware | No built-in authentication or authorization policy. |
| Installation | Explicit routes, package-only migrations, clean consumer without a frontend build | Database, sessions and cache remain host prerequisites. |

## Declared and exercised environments

Composer declares PHP `^8.3` and Laravel components `^12.0 || ^13.0`. Those ranges describe dependency resolution, not proof that every supported minor release or PHP runtime has been executed.

Local evidence from 2026-09-23, after the visual-canvas redesign:

| Environment | Executed checks |
| --- | --- |
| PHP 8.4.7 / Laravel 13.33.0 / SQLite | `composer check`: strict Composer validation, Pint, 30 PHPUnit tests / 330 assertions; fresh-consumer HTTP acceptance. |
| PHP 8.4.7 / Laravel 12.69.2 / SQLite | 30 PHPUnit tests / 330 assertions in a separate dependency environment; fresh-consumer HTTP acceptance. |
| PHP 8.3.33 / Laravel 13 / SQLite | Affected canvas, rich-content and host-access tests: 6 tests / 89 assertions. |
| Native JavaScript | Three Node behavior checks for document history/movement/identity and same-origin adapter imports. |

The earlier backend baseline passed PHP 8.3/8.4 × Laravel 12/13 before the new canvas test. That is not a claim that the entire latest suite was rerun locally on all four combinations. The [GitHub workflow](../.github/workflows/checks.yml) defines the full matrix; consult its actual runs for remote results.

The [first public CI run](https://github.com/gogoSpace/laravel-platno/actions/runs/35912592710) passed all four PHP 8.3/8.4 × Laravel 12/13 combinations, including each fresh-consumer check. A separate installation from the public repository also completed the README flow through first publication.

Only SQLite and the default private local disk were exercised. MySQL, PostgreSQL, remote storage, minimum Laravel minor releases, PHP versions beyond those listed and production load have not been verified.

## Browser journeys exercised

The real local applications were used for:

- Native create → compose → reorder → undo → save → publish → anonymous visit.
- React 19.3.0: direct entry, undo/redo, saving, publishing, unmount/remount.
- Vue 3.5.43: editing, saving, unmount/remount, incomplete image recovery and media selection.
- Livewire 4.4.6: direct entry, keyboard undo, saving, publishing and retained unsaved content across a host component refresh.
- Two editors with a stale-save conflict: unsaved content remained available and the newer saved revision was preserved.
- Formatted-text editing and retained emphasis in saved output.
- Desktop and 390 × 844 mobile authoring, including inspector navigation and visible keyboard focus.

These are specific acceptance journeys, not an exhaustive accessibility, cross-browser or device certification. Framework dependencies were installed only in the separate demo host.

## Data and extension guarantees

Version-1 documents use ordered blocks with optional UUIDs. Unsupported document/plugin types or versions fail instead of silently discarding data. Saved/public documents are validated with registered plugins. Plain text is escaped; custom PHP/Blade plugins are trusted code and must validate and escape their own content.

Draft, publication and retained-history references protect media against deletion. A media item becomes anonymous only while referenced by a current publication. Direct database writes bypass these service contracts.

Changing a plugin schema can make old content unreadable. There is no multi-version plugin registry or automatic migration system. Keep experiments disposable; preserve exports/backups externally before changing preview revisions if you need to retain content.

## How to reproduce checks

See [development tooling](tooling.md). Tests run against isolated SQLite and disposable storage. The fresh-consumer script always creates a new application; it does not accept an existing application path. Never aim tests or demo cleanup at a real application's content.
