# Routing, design and storage

Platno is an **Experimental Preview**. Configuration and extension contracts may change.

## Route ownership

The provider registers no routes. The consuming application explicitly mounts editor and public routes:

```php
\Platno\Platno::editorRoutes('content-editor');
\Platno\Platno::publicRoutes('articles');
```

Mount editor routes inside the host's own access middleware. Mount each group once, using dedicated, non-root prefixes without an enclosing route-name prefix. Names remain `platno.*`. Namespace and route-name collisions fail clearly. Host catch-all routes belong after these mounts. Laravel route/configuration caches are supported.

Use named routes when integrating:

```php
route('platno.pages.index');
route('platno.pages.edit', ['page' => $page->getKey()]);
route('platno.editor.workspace');
route('platno.public.show', ['slug' => $page->slug]);
```

## Plugin configuration

`config/platno.php` contains the list of plugin class names. That list **replaces** package defaults. Keep the built-ins you want and append your own classes. There is one implementation per unique plugin type. See [custom plugins](plugins.md) for fields, validation and nested content.

Do not place objects or closures in cached configuration. Rebuild configuration caches after changing registration.

## Theme tokens

The `theme` configuration merges with these defaults:

```php
'theme' => [
    'background' => '#ffffff',
    'text' => '#202821',
    'accent' => '#285937',
    'surface' => '#eef1e9',
    'width' => 1080,
    'font' => 'system',
],
```

Colors accept six-digit hex values. Width is an integer from 640 to 1600. Font is `system` or `serif`. These tokens style the public page and matching canvas output. They do not change host-wide CSS.

## Views and plugin styling

Use Laravel's namespaced view overrides:

```bash
php artisan vendor:publish --tag=platno-views
```

Overrides live under `resources/views/vendor/platno`. Installation never overwrites them. Publish only what you intend to maintain; overriding many package views makes later preview updates more work.

The public layout and canvas share `platno::public.styles`. Put plugin CSS in that shared override so it reaches both contexts. The canvas uses Shadow DOM: styles on the outer host page do not automatically reach it. Keep custom CSS scoped to your plugin and escape user content in Blade.

The default views use inline CSS. Hosts with a strict Content Security Policy need to adapt these styles and their nonce policy. Clear/rebuild view caches after changing cached overrides.

## Translation

Editor strings use Laravel's `platno::editor` namespace. English is supplied. Add host overrides using Laravel's [package translation conventions](https://laravel.com/docs/13.x/localization#overriding-package-language-files). Custom plugin labels can use the host's normal `__()` translations.

## Media storage

Supported uploads are JPEG, PNG, WebP and PDF. Server-side checks enforce MIME, size and image dimensions. SVG, HTML and executable files are not accepted. The default limit is 10 MB, also subject to the host PHP limits. Images may be at most 12,000 pixels on each side and 40 megapixels.

Original bytes are preserved under generated storage keys. The default `platno` disk is private at `storage/app/platno`; no storage symlink is needed. Set `assets.disk` to another configured **private** Laravel disk. Remote disks require the consuming application's driver/configuration and are not yet verified.

Draft assets use editor routes with host middleware. Anonymous media is available only while a current published page references it. Draft and retained-history references prevent deletion. Unpublishing can revoke public media access without deleting the original needed by history.

A failed storage deletion retains a record and key for retry. An abruptly interrupted upload can leave an `uploading` record that needs host reconciliation after confirming the upload stopped. There is no built-in automatic expiry job, image derivative generation or cropping.

## Page lifecycle

Saving changes only the draft. Publication creates a complete immutable snapshot and changes the current publication pointer. History restoration creates a new draft revision; it does not publish automatically.

**Unpublish** removes public access. **Archive** also removes the page from the active editor list; restoring it keeps the draft and requires explicit publication. Page addresses remain stable. Permanent page/history deletion is not implemented.

All mutation flows use revision checks. A stale write returns HTTP 409 and preserves submitted content instead of silently overwriting someone else's work. This is conflict detection, not collaborative editing.
