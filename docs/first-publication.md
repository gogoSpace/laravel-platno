# Install and publish your first page

This guide uses a new, local Laravel application and the current **Experimental Preview** source checkout. Platno has not been published to Packagist. No frontend build is needed.

## 1. Prepare the two directories

Clone the package and create its sibling application in a directory of your choice:

```bash
git clone https://github.com/gogoSpace/laravel-platno.git
composer create-project laravel/laravel my-platno-site '^13.0' --no-dev
cd my-platno-site
composer config repositories.platno path ../laravel-platno
composer require 'platno/laravel:dev-main' --update-no-dev
php artisan platno:install --no-interaction
```

Use Laravel `'^12.0'` instead for a Laravel 12 application. The package's target minimum is PHP 8.3. Composer determines the actual framework version available for your PHP runtime. [Verified combinations](compatibility.md) are narrower than the declared constraints.

This uses a [Composer path repository](https://getcomposer.org/doc/05-repositories.md#path), usually symlinked to your checkout. Keep that directory in place. This is a development installation, not a production deployment recipe.

The normal `create-project` scripts create a Laravel application key and SQLite database and run the application's initial migrations. When installing in an existing app instead, configure its database, sessions and cache first. Platno does not create those host resources.

## 2. Explicitly mount routes

Add the following to the application's `routes/web.php`:

```php
use Platno\Platno;

if (app()->environment('local')) {
    Platno::editorRoutes();
}

Platno::publicRoutes();
```

No routes are mounted automatically. This example makes the editor available only in the `local` application environment. Keep Laravel's default `APP_ENV=local` in this new local project.

Start only a loopback development server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Visit [the page list](http://127.0.0.1:8000/platno). You start with no pages, no login screen and no demo content.

**For any hosted use**, replace the local mount with your application's own access middleware. A bare mount is open to everyone who can reach it. Authentication and authorization belong to the host; Platno supplies no identity system. Every editor endpoint inherits its enclosing route group.

## 3. Compose, save and publish

1. Choose **New page**, give it a title and a unique address such as `hello-platno`.
2. Add a **Heading** and **Text** block. Edit directly on the canvas or in the inspector.
3. Choose **Save draft**. The draft is private to editor access.
4. Choose **Preview saved draft** to inspect the saved version without publishing it.
5. Choose **Publish saved draft**. Visit `/pages/hello-platno` without editor access.

Public URLs return 404 until publication. Later draft edits do not alter the published snapshot. Publish again to replace the current public version. A publication always uses a saved revision; save browser changes first.

The rich-text inspector supports paragraphs, lists, bold, italic and links. Image/file blocks use the media library. Nested groups and columns share the same document history and undo/redo behavior. Required fields must be complete before saving.

## 4. Make it your own

Generate a [custom plugin](plugins.md), configure [theme tokens and route prefixes](customization.md), or embed the editor with a [framework adapter](../examples/adapters/README.md).

Platno manages its own pages and tables. It does not automatically attach content to existing application models. Demo templates, 24-hour playground expiry and visitor isolation are separate demo-application behavior.

## Installation behavior and upgrades

`platno:install` runs only package migrations and writes configuration only when `config/platno.php` is missing. It leaves host migrations, routes, authentication, `.env`, views and existing configuration unchanged. Running it again is supported; run installers sequentially. Laravel's production confirmation requires `--force` when installing in production.

There is no stable release or guaranteed upgrade path yet. Back up any content you need before switching preview revisions. Read the changes to schema and plugin contracts; automatic content migrations are not supplied.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| `/platno` returns 404 | The editor mount is present, the app environment is `local`, and no stale route cache exists. |
| Laravel reports no application key | Complete normal host setup with `php artisan key:generate`. |
| Session/cache table errors | Complete the **host application's** normal database setup; the package installer intentionally migrates only Platno tables. |
| A custom plugin is missing | Add its class to `config/platno.php`; clear or rebuild cached configuration. |
| Public page returns 404 | Save and publish it; an unpublished or archived page is not public. |
| Upload rejected | Check MIME, package size/dimension limits, PHP `upload_max_filesize` and `post_max_size`. |
| Save returns a conflict | Another editor saved a newer revision. Keep your submitted content and reconcile with the latest draft. Platno does not overwrite it automatically. |
