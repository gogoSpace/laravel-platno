# Development tooling

Development dependencies do not become requirements of consuming applications. Runtime dependencies are PHP and the explicitly declared Laravel components.

| Tool | Purpose |
| --- | --- |
| PHPUnit 12 | Important behavior contracts. |
| Orchestra Testbench 10/11 | Laravel 12/13 package integration. |
| Laravel Pint | Laravel formatting. |
| Python 3.9+ standard library | Fresh-consumer HTTP acceptance and disposable adapter setup. |
| Node 22+ | Native JavaScript behavior and syntax checks. |
| A browser | Actual authoring, keyboard and desktop/mobile visual checks. |

Pest, Telescope, Boost, Docker and Sail are not required. Add tooling only when it solves a demonstrated problem. Never use another application's connected tools, database or storage for package tests.

## Commands

From a Git checkout:

```bash
composer install
composer check
node --experimental-default-type=module --test tests/editor_store.test.mjs tests/adapter_bridge.test.mjs
python3 tests/consumer_smoke.py 12
python3 tests/consumer_smoke.py 13
```

`composer check` runs strict Composer validation, Pint's read-only check and PHPUnit. During development, run the narrow affected test first:

```bash
vendor/bin/phpunit --filter=CanvasTest
```

Use the full gate for a completed change. `composer format` changes formatting and is not a test. Do not add tests for exact labels, CSS, layout snapshots or source-text matches. Tests should prevent data loss, unsafe output, bypassed middleware or broken installation/extension.

Consumer acceptance needs network access for Composer and the selected PHP on `PATH`; it installs no consumer development tools or Node dependencies. Keep the package's `composer.lock` for repeatable development. Consumers resolve their own lockfile.

## Isolation

Testbench creates a new directory under ignored `.sandbox/` per test for writable configuration, migrations, views and storage. Persistence tests replace database configuration with SQLite `:memory:`, remove inherited database URLs and verify the actual connection before writes. Testbench directories are removed after each test.

`tests/consumer_smoke.py` accepts only Laravel major version `12` or `13`. It creates a random directory under `.sandbox/`, strips ambient service configuration, pins SQLite to that application, and never accepts an existing application path. It keeps generated applications and local evidence for inspection and stops only its own HTTP server.

The consumer gate checks installation without implicit routes/accounts, inherited host middleware, sessions/CSRF, configuration/route/view caches, reinstall preservation and pending host migration isolation. It also exercises plugin generation/overrides, nested canvas rendering without writes, publication, retained history, stale writes, private media, archive and recovery.

Do not copy an existing app's `.env` into tests. Do not use destructive Artisan commands against an existing application or stop a user-facing preview as test cleanup.

## Framework acceptance

```bash
python3 tests/prepare_adapter_demo.py
```

This creates another fresh consumer, runs the HTTP gate, installs Livewire only in that application and prepares React/Vue/Livewire host pages. Start its printed application on a loopback port to inspect the integrations. Pinned ESM distributions in these development fixtures are not a recommended production dependency strategy; use the host's normal framework setup.

Browser acceptance is separate. Verify real editing and saving, state callbacks, unmount cleanup, dirty navigation, host Livewire morphing and conflicting saves. A mounted component or a passing import check alone is not evidence that saving works.

## CI and compatibility

The workflow selects PHP 8.3/8.4 × Laravel 12/13. Testbench 10 maps to Laravel 12; Testbench 11 to Laravel 13. It resolves each combination independently and runs package, Node and fresh-consumer checks. All writes stay in a runner's disposable workspace.

[Compatibility](compatibility.md) distinguishes local execution evidence from declared constraints. A workflow file is not a passing remote run. Do not widen support from a successful dependency resolution alone.

## References

- [Laravel package development](https://laravel.com/docs/13.x/packages)
- [Orchestra Testbench](https://github.com/orchestral/testbench)
- [PHPUnit 12 requirements](https://docs.phpunit.de/en/12.5/installation.html)
- [Laravel Pint](https://laravel.com/docs/13.x/pint)
