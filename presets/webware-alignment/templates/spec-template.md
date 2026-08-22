# Feature Specification: Webware-Tools Alignment

**Feature Branch**: `[###-feature-name]`

**Created**: [DATE]

**Status**: Draft

**Input**: User description: "Align [PACKAGE]'s CI/CD pipeline and dev tooling with the
webinertia/webware-tools reusable workflow"

## Purpose

Every Webware package runs the same CI/CD pipeline and dev tooling, owned centrally by
`webware/webware-tools` and consumed through a thin per-package wrapper. This spec defines the
consumer-side contract: which artifacts a package must provide, what shape they take, and which
values are package-specific parameters.

## Scope

**In scope:** CI/CD pipeline, tooling configs, composer metadata, baseline files, the minimum
test scaffolding required for a green pipeline, and a containerized development environment
(`Dockerfile`, `compose.yml`, `.dockerignore`, `.devcontainer/devcontainer.json`) so developers
can work inside the container — via VS Code or plain Docker Compose — identically on Windows,
WSL, Linux, and macOS without a native PHP install.

**Out of scope (explicit):**

- PHPStan: `phpstan.neon.dist`, `stubs/`, type-coverage packages. The reusable workflow runs no
  PHPStan job.
- Full test suite coverage; only scaffolding sufficient to keep the pipeline green is required.

## Package Parameters

Fill in before planning. All reference artifacts (configs, workflow wrapper, badge block) ship
in this preset's `artifacts/` directory — no other package needs to be consulted.

| Parameter | [PACKAGE] value |
|---|---|
| `php-versions` | `["8.4", "8.5"]` |
| `require.php` | `~8.4.1 \|\| ~8.5.0` |
| `config.platform.php` | `8.4.99` |
| `run-integration` | [true/false] |
| `enable-codecov` | `true` |
| `enable-infection` | `true` |
| `coverage-php-version` | highest supported PHP |
| `min-msi` / `min-covered-msi` | `95` / `95` |
| DB container (`db-image`) | [omitted unless package needs a database]; canonical MySQL value `mysql:9.7`, with `db-port: 3306`, `db-env-json` (seeds the DB), `db-health-cmd` (readiness probe), and `test-env-json` (overrides phpunit.xml.dist connection for CI) |
| Integration container | [e.g. Mailpit, MySQL, omitted] |
| Tooling `PHP_VERSION` (Docker) | `8.4.24` (latest 8.4 patch; keep in sync with `require.php`) |
| Tooling `MAGO_VERSION` (Docker) | `1.47.3` (keep in sync with central `mago.toml` pin) |
| Test autoload namespaces | `WebwareTest\<Package>\` → `test/unit/`, `WebwareTestIntegration\<Package>\` → `test/integration/` |

## User Scenarios & Testing

### User Story 1 - Maintainer opens a PR and gets a full pipeline (Priority: P1)

A maintainer opens a pull request against a release branch. The wrapper workflow triggers the
reusable workflow, which runs Mago checks, the test matrix (lowest/locked/latest dependency
strategies), integration tests, Codecov upload, and Infection mutation testing.

**Why this priority**: The pipeline is the deliverable; nothing else in this spec has value
without it.

**Independent Test**: Open a PR touching `src/`; all CI jobs are scheduled and pass on a clean
change.

**Acceptance Scenarios**:

1. **Given** a PR against a `X.Y.x` branch, **When** pushed, **Then** Mago, test, codecov, and
   mutation-test jobs all run.
2. **Given** a PR with a Mago lint finding, **When** pushed, **Then** the Mago job fails and
   reports the finding.
3. **Given** a change that drops a test suite to zero tests, **When** pushed, **Then** the test
   job fails (PHPUnit errors on zero executed tests).

### User Story 2 - Tooling updates propagate with a version bump (Priority: P2)

When `webware/webware-tools` releases a new workflow version, consumers update the pinned ref and
regenerate baselines, without rewriting per-package config.

**Why this priority**: Ongoing maintenance loop.

**Independent Test**: Bump the pinned workflow ref; pipeline still green after `mago` fix pass.

**Acceptance Scenarios**:

1. **Given** a new webware-tools version, **When** the wrapper ref is bumped, **Then** only the
   wrapper and possibly baselines change.

### Edge Cases

- No `db-image` set: both DB steps of the reusable workflow are skipped at zero cost.
- Integration tests do not exist yet: `test-integration` leg runs an empty suite; acceptable
  temporarily, but at least one test per suite is required for a green pipeline.
- Zero tests: PHPUnit 13 errors, and Infection cannot score an empty suite; pipeline is red until
  scaffolding tests exist.

## Requirements

### Functional Requirements

- **FR-001**: Repository MUST provide `.github/workflows/continuous-integration.yml` calling
  `webinertia/webware-tools/.github/workflows/continuous-integration.yml` with `secrets: inherit`
  and package-specific inputs.
- **FR-002**: `composer.json` MUST define scripts `test`, `test-coverage`, `test-integration`,
  `mutation-test`, and a `test-all` alias (`test` + `test-integration` + `mutation-test`).
  Legacy `php-cs-fixer` and `phpstan` scripts MUST be removed.
- **FR-003**: `phpunit.xml.dist` MUST use PHPUnit 13.1 schema, strict flags
  (`requireCoverageMetadata`, `failOnNotice`, `failOnDeprecation`, `failOnWarning`), and suites
  named `unit test` and `integration test`.
- **FR-004**: `mago.toml` MUST extend `vendor/webware/webware-tools/mago.toml` and define
  `php-version`, baseline paths, and source paths. The legacy `mago.json` MUST be removed.
- **FR-005**: `lint-baseline.toml` and `analysis-baseline.toml` MUST start empty; entries only for
  maintainer-approved intentional suppressions.
- **FR-006**: `infection.json5.dist` MUST configure `source.directories = ["src"]` and
  `staticAnalysisTool: "mago"`.
- **FR-007**: `codecov.yml` MUST be copied from the preset's `artifacts/codecov.yml` (targets
  `auto`, threshold `0%`).
- **FR-008**: `renovate.json` MUST extend `local>webinertia/.github:renovate-config`.
- **FR-009**: `phpbench.json.dist` MUST exist with runner config; a `benchmarks/` directory is not
  required.
- **FR-010**: `composer.lock` MUST be committed (locked matrix leg).
- **FR-011**: `.github/copilot-instructions.md` MUST carry PHPUnit 13 mock-vs-stub and coverage
  metadata rules.
- **FR-012**: Each test suite MUST contain at least one test.
- **FR-013**: `README.md` MUST carry the standard badge block from the preset's
  `artifacts/readme-badges.md` (PHP version, latest version, license, CI, codecov, mutation
  testing) with CI/codecov badges tracking the default branch and the Stryker badge updated
  whenever the default branch changes.
- **FR-014**: Repository MUST add spec-kit scaffolding directories (`/.specify/`, `/specs/`) to
  `.gitignore` — they are local dev tooling and must not be pushed to the remote.
- **FR-015**: Repository MUST remove legacy tooling: `phpstan/phpstan`,
  `phpstan/phpstan-phpunit`, and `webware/coding-standard` from `require-dev`; delete
  `.php-cs-fixer.dist.php`, `.php-cs-fixer.php`, `.php-cs-fixer.cache`, `phpstan.neon.dist`,
  `phpstan-baseline.neon`, `stubs/`, and `.laminas-ci.json`.
- **FR-016**: Repository MUST provide a containerized development environment: a `Dockerfile`
  (PHP CLI + Composer + Mago + Xdebug, `TARGETARCH`-aware), a `compose.yml` (a persistent,
  interactive `tooling` service that bind-mounts the repo at `/app` and keeps `vendor/` and the
  Composer cache in named volumes), and a `.dockerignore` excluding `vendor/`, `.git/`, and other
  non-source artifacts. All files MUST be committed with LF line endings.
- **FR-017**: The repository MUST provide `.devcontainer/devcontainer.json` that references
  `compose.yml` via `dockerComposeFile` + `service: tooling` (a thin wrapper, not a parallel
  environment), so VS Code and plain `docker compose` users share the same container.
- **FR-018**: All local tooling (`composer`, `phpunit`, `mago`, `infection`, `phpbench`,
  roave/backward-compatibility-check) MUST be runnable inside the container via
  `docker compose exec tooling ...` (or the Dev Container), with no native PHP toolchain required
  on the host. Xdebug MUST be available but disabled by default, enabled via `XDEBUG_MODE=debug`.
- **FR-019**: If the package needs a database (`db-image` set), `compose.yml` MUST provide an
  opt-in `mysql` service reachable from `tooling` as host `mysql` on port `3306`, mirroring the
  `db-image` / `db-env-json` Package Parameters, with a healthcheck gating the `tooling`
  `depends_on`. The package's local DB config (e.g. `config/autoload/mysql.local.php`) MUST point
  at that service. An opt-in `phpmyadmin` service MUST provide a web UI over the database.

### Key Entities

- **Wrapper workflow**: per-package file translating package parameters into reusable workflow
  inputs.
- **Reusable workflow**: `webinertia/webware-tools@X.Y.x`; owns job definitions (mago, test,
  codecov, mutation-test).
- **Baselines**: per-package TOML files holding approved Mago suppressions.

## Success Criteria

### Measurable Outcomes

- **SC-001**: All CI jobs green on the canonical branch: Mago (all versions), test matrix,
  codecov, mutation-test.
- **SC-002**: `mago format --check`, `mago lint`, `mago analyze`, `mago guard` report zero
  unbaselined issues.
- **SC-003**: Infection MSI and covered MSI at or above package thresholds (95 reference).
- **SC-004**: Codecov receives coverage upload from exactly one matrix leg (canonical:
  `coverage-php-version` + locked).
- **SC-005**: On a fresh Windows machine with only Docker installed, `docker compose up -d` and
  `docker compose exec tooling composer test` succeed with no native PHP toolchain present, and
  the repository opens in a VS Code Dev Container backed by `compose.yml`.

## Assumptions

- The reusable workflow keeps its documented inputs until a deliberate version bump.
- Test scaffolding (not full test coverage) is sufficient for alignment scope.
