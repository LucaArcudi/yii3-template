# Project Instructions for Codex

This is a Yii3 web application template with CI/CD, database migrations,
monitoring, deployment scripts, and production runbooks.

## Operating Rules

- Work on a dedicated branch. Never push directly to `main`.
- Keep changes small, focused, and reviewable.
- Read the existing code before editing and follow local patterns.
- Prefer existing Yii3 services, widgets, handlers, `Data/` classes, and DI
  conventions over new architecture.
- Do not modify production secrets, deploy credentials, or environment files
  containing real values.
- Do not run destructive database, filesystem, or Git commands without explicit
  approval.
- Do not weaken CI, static analysis, security scans, or test coverage to make a
  change pass.

## Project Structure

The codebase is organized in self-contained feature modules (vertical
slices): `Core` (users, RBAC, notifications, audit log, error pages) and
`Mes` (the reference business module). New domains must follow the module
layout. Shared infrastructure still lives in layer folders and will be
consolidated under a `Shared/` area in a future step.

Module layout (e.g. `src/Core/`, `src/Mes/`):

- Domain classes belong under `src/<Module>/<Domain>/` (Entity, Input,
  Repository, Reader, Filter, Policy, Presenter, Scope).
- Web actions belong under `src/<Module>/<Domain>/Actions/` and set
  `withViewPath('@src/<Module>/<Domain>/views')` on the view renderer.
- Views belong under `src/<Module>/<Domain>/views/`.
- Routes belong in `src/<Module>/routes.php` (array of `Route`/`Group`),
  DI definitions in `src/<Module>/di.php`; both are auto-discovered by
  `config/common/routes.php` and `config/common/di/modules.php`.

Shared infrastructure (pending consolidation under `Shared/`):

- Data primitives under `src/Data/` (`BaseEntity`, `InputValue`,
  `AccessPolicyInterface`, ownership scopes).
- Cross-module services under `src/Services/Core/` (authorization, mail,
  web action support).
- HTTP middleware under `src/Handlers/Middleware/Core/`.
- Params objects under `src/Params/Core/`, console command skeletons under
  `src/Commands/Core/`, layouts/translations/emails under `src/resources/`.
- Reusable UI belongs under `src/Widgets/`.
- Navigation is declared in `src/Navigation/NavigationProvider.php`.
- Dashboard components are declared in `src/Dashboard/`.
- Runtime visibility for menu items and dashboard components must go through a
  `policyClass` whose `canAccess()` method is the single source of truth.

## Development Rules

- Schema changes must use `yiisoft/db-migration` and the existing migration
  pattern. Do not add new `initdb.d` bootstrap scripts for schema evolution.
- Add or update tests for every functional change.
- Add a regression test when fixing a bug, when practical.
- Update documentation and `CHANGELOG.md` when behavior, commands, workflows,
  deployment, or visible user functionality changes.
- Keep `psalm-baseline.xml` stable. Remove obsolete entries when code cleanup
  makes them unnecessary; do not add new entries to hide fresh issues unless
  explicitly approved.

## Verification

Run the most relevant checks before finishing. Prefer the narrowest useful set
first, then broaden when the change touches shared behavior.

```bash
vendor/bin/codecept run Unit
vendor/bin/psalm --no-cache --threads=1 --output-format=console
vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --sequential
```

When Docker is available, use the project `make` targets as the final check:

```bash
make cs-fix
make psalm
make test
```

If a check cannot run because of the local environment, report the exact reason
and the command that failed.

## Commit And PR Rules

- Use concise imperative commit messages.
- Keep generated or unrelated local files out of commits.
- For Codex-authored work, add this trailer to the commit message:

```text
Co-Authored-By: Codex <noreply@openai.com>
```

- PR summaries must include what changed, why it changed, tests run, residual
  risks, and any manual verification steps.

## Safety Boundaries

- Do not perform production operations unless the user explicitly asks and the
  documented runbook allows it.
- Deploy logic lives in versioned scripts under `scripts/`; do not pipe complex
  remote deploy logic through heredocs.
- If CI fails, inspect the failing job and fix the root cause.
- If a task needs external credentials or unavailable infrastructure, stop with
  a concrete blocker and the safest next step.
