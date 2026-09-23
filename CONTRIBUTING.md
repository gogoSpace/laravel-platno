# Contributing to Platno

Thank you for helping make a small, understandable Laravel editor better. Platno is an **Experimental Preview**: both the product and extension contracts are still taking shape.

## Useful contributions

A minimal bug reproduction, an unclear installation step, an inaccessible interaction or a well-explained plugin use case is valuable. For larger features or dependencies, open an issue first so the implementation fits the package's direction. Do not post security vulnerabilities publicly; see [SECURITY.md](SECURITY.md).

The product boundaries are deliberate:

- Generic, configurable blocks; application-specific features belong in host plugins.
- No required frontend framework, UI library, Node build or paid service.
- Authentication and authorization stay in the host application.
- Public rendering works through Blade without an editor runtime.
- Honest documentation: clearly separate implemented behavior, examples and plans.

## Work locally

Fork and clone the repository, create a branch, then:

```bash
composer install
composer check
node --experimental-default-type=module --test tests/editor_store.test.mjs tests/adapter_bridge.test.mjs
```

Use PHP 8.3+, a compatible Composer environment and Node 22+ for the development checks. For a live consumer, follow [first publication](docs/first-publication.md); for disposable installation verification, follow [tooling](docs/tooling.md). The local page editor should stay available while reviewing UI work.

## Code and tests

Follow [the engineering codex](AGENTS.md) and the surrounding code. Keep classes and modules focused, use descriptive names, explicit PHP types and the Laravel Pint preset. New dependencies need a concrete justification.

Test important behavior, not incidental markup. Good regression tests protect document integrity, publication, upload handling, host access boundaries, installation or plugin compatibility. Do not add assertions for label wording, colors, CSS classes or private implementation details. Cosmetic work needs visual verification, not a new backend test.

Tests must use isolated disposable databases/storage. Never run against a developer's app or reuse a user-facing demo as a fixture. Run the affected checks during development and `composer check` when ready for review. Include the actual commands and results in your pull request.

## Pull requests

Explain the concrete problem, resulting behavior and any compatibility impact. Keep changes focused. Show before/after screenshots for visible changes, using real output without customer content. Update the relevant public documentation when a contract changes.

Commit subjects use plain descriptive English, such as `Preserve unsaved content after a conflict`, without `feat:`, `fix:` or other prefixes. Do not amend or rewrite existing commits; add a new commit for follow-up changes. Automated assistants must not commit or push without an explicit instruction.

Contributions are provided under the repository's [MIT license](LICENSE). Add only code and assets you have the right to contribute; document any non-code asset provenance.
