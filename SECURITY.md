# Security

Platno is an **Experimental Preview**, not a supported production release. There is no stable security-maintenance window or response-time guarantee yet. Reports about the current `main` branch are welcome.

## Report privately

Use [GitHub private vulnerability reporting](https://github.com/gogoSpace/laravel-platno/security/advisories/new). Include:

- The affected commit, PHP/Laravel versions and relevant configuration.
- Minimal reproduction steps and the expected/actual behavior.
- The impact, especially draft/media disclosure, unauthorized mutations, unsafe output or data loss.

Do not include live credentials, private user content or attacks against the public playground. Reproduce against your own isolated local application. Avoid public issues or pull requests containing exploit details until the report has been assessed.

## Boundaries

The consuming application controls authentication, authorization and route access. Mounting editor routes without host access middleware makes them reachable to everyone who can access that application. Platno supplies no login or account system.

Custom PHP/Blade plugins are trusted server code. Plugin authors own their validation and escaping. Declared media fields and nested block fields are required for automatic asset reference tracking. Direct database writes can bypass package guarantees.

The separately hosted demo application owns visitor isolation, expiry, quotas and cleanup. A demo issue should be reported privately through the [demo repository](https://github.com/gogoSpace/platno-demo/security/advisories/new).
