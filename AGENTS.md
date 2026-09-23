# Platno engineering codex

## Start here

- Work from this repository's root. Keep implementation, tests and project notes here; do not continue development in another application's repository.
- Read `README.md`, `docs/architecture.md` and `docs/compatibility.md` before continuing work. Check `git status` and the files involved in the task.
- If a local `docs/status.md` exists, treat it as a factual handoff: current milestone, verified commands, limitations and next step. Keep local handoff notes outside the public repository.
- Inspect current code and version-specific official documentation before choosing APIs. Use this project's tools; never silently use another application's MCP server, database or environment.
- Distinguish implemented behavior from planned features. Do not describe a scaffold, stub or unrun check as a finished product.

## Product boundaries

- Platno is a standalone, public, open-source Laravel package. Its goal is a first published page within minutes of installing it in a clean Laravel application.
- Ship generic, configurable plugins. Application-specific integrations belong in plugins owned by the consuming application.
- No dependency on a host application's `App\\` classes, user model, roles, domain names, routing conventions or translation helpers.
- No PrimeVue, mandatory UI component library, CSS framework or frontend framework. Use semantic HTML, scoped CSS and native JavaScript for the default editor.
- Vue, React and Livewire integrations are optional adapters. They must not become requirements of the default editor or public renderer.
- Consumers must not need Node, npm, a frontend build pipeline, Redis, a queue worker or a paid service to publish their first basic page.

## Dependencies and compatibility

- Runtime requirements are PHP and the Laravel components actually used. Prefer facilities already provided by Laravel.
- Development tools belong in `require-dev` and never enter a consumer's installation through the package.
- Adding a runtime library beyond Laravel requires a concrete justification and an explicit user decision. Declaring the Laravel components actually used is already authorized. Evaluate maintenance, license, security and dependency tree, not only convenience.
- Do not reimplement security-sensitive parsers or sanitizers merely to advertise zero dependencies. Narrow the feature or present the specific dependency tradeoff first.
- The initial compatibility target is PHP 8.3+ and Laravel 12/13. Only claim combinations that are covered by verification; widen support deliberately.
- Keep Composer constraints, test matrix and documented support synchronized. Update dependencies intentionally, not as a side effect of unrelated work.

## Laravel and PHP practices

- Use Laravel service providers, package discovery, the container, configuration, Gates/Policies, validation, storage and queues through their supported APIs.
- Namespace package configuration, views, routes, translations and commands under `platno`.
- Use dependency injection. Introduce an interface for a real extension boundary, not for every implementation class.
- Each file has one coherent responsibility. Separate validation, authorization, persistence, publication and rendering when their behavior warrants it. Avoid both large catch-all services and trivial pass-through layers.
- Use `declare(strict_types=1)`, parameter and return types, constructor property promotion and explicit array shapes or generics where useful.
- Use complete, descriptive names. Avoid abbreviations in variables, parameters and methods.
- Follow the Laravel Pint preset. Use braces for all control structures and comments to explain non-obvious decisions rather than restating code.
- Use `env()` only in configuration. Published configuration must be cacheable: class names and scalar/array values, no closures or instantiated objects.
- Do not assume Laravel merges nested configuration deeply. Document whether a configurable list is replaced or extended.
- Keep service-provider boot free from application database queries, migrations and filesystem mutations.
- Use Eloquent relationships, eager loading and indexes appropriate to real access patterns. Do not query from templates or add repositories around Eloquent without a specific need.
- Never silently change host authentication, middleware, routes, global CSS or build configuration.

## Integrity and security

- Authentication and access belong exclusively to the host Laravel application. Never add package login/logout, accounts, passwords, keys, provisioning commands, guards, roles, fixed Gates or an optional built-in authentication mode.
- Register no routes automatically. The host explicitly mounts editor routes inside its own Laravel middleware/policy group. Preserve that middleware on every editor read and mutation. Platno owns CSRF, validation and publication integrity, not identities or access decisions. Installation must not ask for an account or credential.
- Validate structured content server-side with each plugin's rules. Browser validation is supplementary.
- Escape plain text in Blade. Raw HTML requires an explicit, reviewed sanitization strategy; `strip_tags()` and browser-only sanitization are not sufficient.
- Protect draft/publication separation, immutable publication snapshots and concurrent edits. A preview must not make a draft public.
- Use transactions and explicit conflict detection for multi-record publication and revision operations.
- Treat uploads as untrusted input. Validate size and content type, use generated storage keys, and keep originals distinct from derived files.
- Asset deletion must account for references from drafts, published content and retained history. Storage must use Laravel disks, not local-path assumptions.
- Never silently discard unknown plugin data or unsupported schema versions. Fail writes clearly; define read fallbacks before implementing them.
- No secrets, production data, real customer content or environment files in the repository, fixtures or logs.

## Tests: important behavior only

- Before adding a test, name the material failure it prevents: data loss, unauthorized access, unsafe output, incorrect publication, broken installation, incompatible plugin behavior or a confirmed regression.
- Prefer a few meaningful behavior tests that exercise public boundaries. Testbench is for integration with Laravel; plain PHPUnit is for independent logic.
- Do not add tests for exact labels, translated wording, CSS classes, colors, spacing, layout snapshots, arbitrary DOM shape, private method calls or strings occurring in source files.
- Do not duplicate the implementation in assertions or create a test merely because a file changed. No placeholder tests such as `true` being `true`.
- A formatter checks formatting. Visual checks assess presentation. Neither is a reason to create a PHP test.
- No coverage percentage target. Test value and failure consequences determine scope, not assertion count.
- Tests must never connect to a developer's, consumer's or production database/storage/services. Future persistence tests must use an explicitly isolated disposable environment, with safeguards before any writes.
- Never run destructive Artisan commands against an existing application. Do not copy a host `.env` into a test environment.
- Run the narrow affected tests during development. Run `composer check` for a completed milestone. Expand testing only when a change or unresolved risk justifies it.
- Do not weaken assertions, skip failures or widen mock behavior to obtain a passing result. Fix the cause or report the actual blocker.

## Frontend and plugins

- Use accessible native controls, keyboard interaction and semantic markup. Scope editor styles so they cannot affect the host page.
- Keep public rendering useful without JavaScript. Avoid shipping editor assets to public pages unless the rendered plugin needs them.
- Keep plugin data portable and versioned. Rendering must not require the editor framework.
- A basic custom plugin should need PHP, a Blade view and a declarative field definition, without a JavaScript build. Advanced custom inspectors may opt into JavaScript.
- Use Laravel's own namespaced translation mechanism for future editor UI; never copy a host application's translation implementation.
- The authoring surface is a visual page canvas using actual plugin output, with selection, contextual actions and inspectors. A list of textual block summaries is not an equivalent editor. Optional adapters mount directly into the host page; do not reintroduce iframe wrappers as the completed integration.
- Add optional adapters only after the default end-to-end publishing flow works and the underlying contract is clear.

## Git and collaboration

- Never create a commit or push without an explicit instruction. Preparing a project does not authorize an initial commit.
- Never amend an existing commit. Make subsequent changes in a new commit when a commit is requested.
- Do not rewrite published or existing history: no rebase, reset, squash or force push without an explicit request for that exact operation.
- Commit subjects are short, descriptive English sentences without prefixes, scopes, emojis or issue-number prefixes. Example: `Add publication conflict detection`.
- Do not use `feat:`, `fix:`, `chore:`, `docs:` or other Conventional Commit prefixes.
- Inspect the complete staged diff before a requested commit. Do not include unrelated work, secrets, generated caches or installed dependencies.
- Do not introduce hooks that create commits, rewrite messages, push or change user configuration.
- Use bounded tasks with clear file ownership when delegation is authorized. Do not launch competing writers or new work sessions without a clear purpose.

## Delivery

- Prefer a working vertical slice to many placeholder abstractions. The first product milestone is install → create a page → publish → anonymous visit.
- A milestone is a checkpoint, not permission to stop an authorized project. Read a local `docs/delivery-plan.md` if present, complete the agreed scope, record evidence and continue with the next unfinished item. Do not require a new instruction for work already in the agreed scope.
- Keep a running local preview for UI work. Verify its URL, provide usable access and update the preview as features land. Do not stop the user-facing preview during test cleanup or at the end of a work session; report genuine failures and recover it. Disposable test servers are separate.
- Keep the isolated demonstration directly accessible through its host-owned loopback-only middleware. No package login or credentials belong in the demonstration.
- Keep installation and extension documentation executable and honest about current capability.
- Keep changes local unless publication, remote creation or deployment is explicitly requested.
- Report what works, what was verified and what remains. A project foundation is not a completed editor.
