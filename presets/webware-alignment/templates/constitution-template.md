# [PROJECT_NAME] Constitution

## Core Principles

### I. Webware-Tools CI Alignment

CI/CD pipeline and dev tooling follow the `webinertia/webware-tools` reusable workflow. The
repository provides a thin wrapper workflow with package-specific inputs and `secrets: inherit`.
Composer scripts `test`, `test-coverage`, `test-integration`, and `mutation-test` exist and match
what the reusable workflow invokes. `composer.lock` is committed (required by the `locked` matrix
leg).

`mago.toml` extends `vendor/webware/webware-tools/mago.toml`. That inheritance is what makes the
centre authoritative, so the division of responsibility is fixed:

- **General settings and general rules are central.** The guard `mode`, rule enablement, formatter
  and analyzer flags, and any rule that applies ecosystem-wide are defined once in the centre and
  MUST NOT be defined or overridden locally. A local copy of a general rule is drift, even when its
  content is identical today.
- **Domain rules may be local.** A package MAY add structural or perimeter rules that cover its own
  domain and are not covered by the centre. Local rules are additive — they layer on top of the
  centre and can strengthen it, never weaken it, and no central rule can be disabled from the
  consumer file. The target is the smallest possible set of local rules: the consumer file shrinks
  toward the stub (`extends`, `php-version`, baseline paths, source paths) as the centre grows.

The boundary rules the centre enforces are stated in Principle VIII. The procedure for dropping
local overrides is `vendor/webware/webware-tools/mago-guard-realignment.md`.

### II. PHPUnit 13 Strict Mode

- `requireCoverageMetadata="true"`: every test class declares `#[CoversClass]` for each source
  class and `#[CoversMethod]` for each method exercised.
- Mock vs stub separation: `createStub()` for value-returning test doubles; `createMock()` only
  when behavior is verified with `expects()`. Never `createMock()` without `expects()`.
- `failOnNotice="true"`, `failOnDeprecation="true"`, `failOnWarning="true"` — no loose PHPUnit
  notices in CI.

### III. Code Quality Gates

- Mago `format`, `lint`, `analyze`, `guard` run in CI; findings are fixed in source, never
  silently suppressed. Baseline entries require explicit maintainer approval per issue.
- Infection runs with Mago as `staticAnalysisTool`; `min-msi` and `min-covered-msi` start at 95
  and may only be lowered with justification.

### IV. PHP Compatibility

- Support current supported PHP versions only (`~8.4.1 || ~8.5.0`); `config.platform.php` pinned
  to `8.4.99` for dependency resolution.
- No `8.6.0-dev` in `require.php`.

### V. Naming

- Never repeat the namespace in class or interface names. `Webware\Input` namespace has
  `FilterInterface`, not `InputFilterInterface`. Applies to all new code.
- Message classes are named and located by type, in the domain they act on — never centralized
  into a `MessageBus\` bucket:

  | Sub-namespace | Name | Required contract |
  |---|---|---|
  | `Command\` | `*Command` | `Webware\MessageBus\Command\NamedCommandInterface` |
  | `CommandHandler\` | `*Handler` | `Webware\MessageBus\CommandHandlerInterface` |
  | `Query\` | `*Query` | `Webware\MessageBus\Query\QueryInterface` |
  | `QueryHandler\` | `*Handler` | `Webware\MessageBus\QueryHandlerInterface` |

  These are the enforced contracts. `NamedCommandInterface` extends `CommandInterface`, and it is
  the contract commands declare directly — `must-implement` matches the declared interface list, so
  an ancestor interface is not a substitute for declaring the contract itself.
- PSR-14 events and listeners are named and located by role, and nothing further: `Event\` holds
  `*Event`, `Listener\` holds `*Listener`. Behaviour is not constrained — a listener may implement
  `Webware\Event\ListenerInterface` directly or be registered by the package's own provider. A
  listener's DI factory nests one level deeper, in `Listener\Container\`, the same way
  `Http\Middleware\Container\` holds middleware factories.
- `MessageBus\` is reserved for the types that intersect the bus contract in order to support
  consumers — `AuthorizableCommandInterface`, `CommandResult`, `CommandStatus`, and middleware
  under `MessageBus\Middleware\`. It is not a namespace for ordinary message classes, and keeping
  bus middleware there is what distinguishes it from PSR middleware under `Http\Middleware\`.

### VI. Cross-Platform Development Environment

- Local development tooling (Composer, PHPUnit, Mago, Infection, PHPBench, BC-check, Xdebug)
  runs inside a persistent, interactive container (`docker compose up -d` +
  `docker compose exec tooling ...`, or the VS Code Dev Container). The host is never required to
  have a native PHP toolchain; behavior is identical on Windows, WSL, Linux, and macOS.
- `compose.yml` is the single source of truth for the environment. `Dockerfile`, `.dockerignore`,
  and `.devcontainer/devcontainer.json` live alongside it; the Dev Container is a thin wrapper
  over `compose.yml`, not a parallel environment, so non-VS Code users are never orphaned.
- These files are kept in sync with the central `webware/webware-tools` preset.
- The `MAGO_VERSION` build arg tracks the central `mago.toml` `version =` pin; `PHP_VERSION`
  tracks the package's latest supported 8.4 patch release.

### VII. Line Endings

- All text files are committed with LF line endings only. CRLF is never committed; the package
  `.gitattributes` (`* text eol=lf`) is authoritative and is not overridden.

### VIII. Boundary Rules

The following boundaries are enforced ecosystem-wide by the central
`vendor/webware/webware-tools/mago.toml`. The exact TOML lives there and is not restated here, so
the rule and its rationale cannot drift apart.

- **Http perimeter.** The PSR Http server contracts (`Psr\Http\Server\**` —
  `MiddlewareInterface`, `RequestHandlerInterface`) are usable only from
  `Webware\**\Http\**` — including the admin-nested `Http\Admin\Middleware\` and
  `Http\Admin\RequestHandler\` layout — plus `Webware\Async\**` (a runner must accept a PSR-15
  handler) and tests. Implementations live under `Http\` and carry the
  `*Middleware` / `*Handler` names.
- **Message bus.** Message classes conform by per-type sub-namespace (Principle V) rather than by
  relocating into a `MessageBus\` bucket. Bus middleware stays under `MessageBus\Middleware\`.
- **Events.** PSR-14 events live in `Event\` and listeners in `Listener\` (Principle V), with no
  further constraint: the mechanism may be adopted incrementally and no dependency boundary is
  enforced around it. Reconciling `messagebus-event` against `webware-event` is tracked in
  webinertia/webware-tools#21.
- **Persistence.** `Webware\**\Repository\**` is reachable only from the handlers that use it,
  the DI factories that wire it (`Container\`), the composition root, console commands, tests,
  and the one package that reaches repositories by its nature — the migration tooling
  (`Webware\Migration\**`), whose runner drives persistence directly. A boundary rule must not
  block a legitimate consumer; what it permits is recorded with its reason, in the TOML and here.
  `PhpDb\**` additionally stays behind the persistence boundary, so `ResultSet` and `RowPrototype`
  types never reach middleware, Http handlers, or query payloads.

## Quality Gates

Every pull request passes, on all CI matrix legs:

- Mago format check, lint, analyze, guard
- Unit tests under lowest/locked/latest dependency strategies
- Integration tests when `run-integration` is set, narrowed to a single leg by
  `integration-php-version` where the suite is expensive
- Codecov upload from the canonical coverage leg (report-only)
- Infection mutation score at or above configured MSI thresholds

## Governance

- Constitution supersedes other practices; conflicts are resolved in its favor or the
  constitution is amended via PR.
- Boundary guard rules take effect the moment they land in the central `mago.toml`; the expected
  response in a consumer is to become compliant, never to disable or locally weaken the rule.
  Realignment follows `vendor/webware/webware-tools/mago-guard-realignment.md`.
- Wrapper workflow inputs change only when the reusable workflow version bumps or a deliberate
  policy decision is recorded in a spec.
- `.github/copilot-instructions.md` carries the operational rules derived from this constitution.

**Version**: [CONSTITUTION_VERSION] | **Ratified**: [RATIFICATION_DATE] | **Last Amended**: [LAST_AMENDED_DATE]
