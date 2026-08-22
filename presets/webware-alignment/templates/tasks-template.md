# Tasks: Webware-Tools Alignment

**Input**: `specs/[###-feature]/spec.md`, `plan.md`

Replace package parameters (PHP versions, MSI thresholds, test namespaces) before executing.

## Phase 1 — composer.json

- [ ] T001 Align `composer.json` `require.php` to `~8.4.1 || ~8.5.0` (no `8.6.0-dev`), set
  `config.platform.php` to `8.4.99` in `composer.json`
- [ ] T002 Align `require-dev`: PHPUnit `^13.3.0`, add `infection/infection: ^0.35.0`,
  `phpbench/phpbench: ^1.7`, `roave/backward-compatibility-check: ^8.21.0`; keep package deps,
  `webware/webware-tools`, `roave/security-advisories`; remove legacy `phpstan/phpstan`,
  `phpstan/phpstan-phpunit`, `webware/coding-standard` in `composer.json`
- [ ] T003 Set `autoload-dev` namespaces: `WebwareTest\<Package>\` → `test/unit/`,
  `WebwareTestIntegration\<Package>\` → `test/integration/` in `composer.json`
- [ ] T004 Define scripts `test`, `test-coverage`, `test-integration`, `mutation-test`, and a
  `test-all` alias (`test` + `test-integration` + `mutation-test`); remove legacy
  `check-all`/`cs-check`/`cs-fix`/`sa` scripts in `composer.json`
- [ ] T005 Run `composer update` and commit `composer.lock`

## Phase 2 — phpunit.xml.dist

- [ ] T006 Create `phpunit.xml.dist` by copying the preset's
  `artifacts/phpunit.xml.dist` (PHPUnit 13.1 schema, strict flags, suites
  `unit test` and `integration test`, `<source>` including `src`); remove legacy tooling:
  `.php-cs-fixer.dist.php`, `.php-cs-fixer.php`, `.php-cs-fixer.cache`, `phpstan.neon.dist`,
  `phpstan-baseline.neon`, `stubs/`, `.laminas-ci.json`
- [ ] T007 Add test scaffolding: at least one test in `test/unit/` and one in
  `test/integration/` so PHPUnit and Infection do not error on empty suites

## Phase 3 — Mago tooling

- [ ] T008 Create `mago.toml` by copying the preset's `artifacts/mago.toml`
  (`extends = "vendor/webware/webware-tools/mago.toml"`, `php-version = "8.4.1"`, linter/analyzer
  baseline paths, source paths `["src", "test"]`); remove the legacy `mago.json`
- [ ] T009 Create empty `lint-baseline.toml` and `analysis-baseline.toml`
- [ ] T010 Fix pass: run `mago format`, `mago lint`, `mago analyze`, `mago guard`; fix all
  findings in `src/`; baseline only maintainer-approved remainder

## Phase 4 — Infection / Codecov / Renovate / PHPBench configs

- [ ] T011 Create `infection.json5.dist` by copying the preset's `artifacts/infection.json5.dist`
- [ ] T012 Create `codecov.yml` by copying the preset's `artifacts/codecov.yml` verbatim
- [ ] T013 Create `renovate.json` by copying the preset's `artifacts/renovate.json`
- [ ] T014 Create `phpbench.json.dist` by copying the preset's `artifacts/phpbench.json.dist`;
  no `benchmarks/` directory required

## Phase 5 — Workflow wrapper + agent instructions

- [ ] T015 Create `.github/workflows/continuous-integration.yml` from the preset's
  `artifacts/workflow.yml`, replacing the `{{PLACEHOLDER}}` values with the spec's Package
  Parameters (`php-versions`, `run-integration`, `enable-codecov`, `enable-infection`,
  `coverage-php-version`, `min-msi`, `min-covered-msi`), then move it to
  `.github/workflows/continuous-integration.yml`
- [ ] T016 Create `.github/copilot-instructions.md` from the preset's
  `artifacts/copilot-instructions.md`, replacing `{{PACKAGE_TITLE}}`
- [ ] T017 Add `/.specify/` and `/specs/` to `.gitignore` so spec-kit
  scaffolding stays local dev tooling and is never pushed to the remote

## Phase 6 — README badges

- [ ] T018 Add the standard badge block from the preset's `artifacts/readme-badges.md`,
  replacing `{{PACKAGE_NAME}}`, `{{ORG}}`, `{{REPO}}`, `{{DEFAULT_BRANCH}}` (PHP version,
  latest version, license, Continuous Integration, codecov, Mutation testing). CI and codecov
  badge URLs carry no `?branch=` parameter, so they always point at the default branch. The
  Stryker mutation badge URL embeds the branch segment; update that segment in both the badge
  URL and the dashboard link whenever the default branch changes. Update `README.md`

## Phase 7 — Containerized development environment

- [ ] T022 Create `Dockerfile` by copying the preset's `artifacts/Dockerfile` (PHP CLI +
  Composer + Mago + Xdebug, `TARGETARCH`-aware Mago asset selection,
  `intl`/`pcntl`/`zip`/`pcov` extensions, Xdebug installed but off by default, `WORKDIR /app`)
- [ ] T023 Create `compose.yml` by copying the preset's `artifacts/compose.yml` (a persistent,
  interactive `tooling` service: `sleep infinity` command, `stdin_open`/`tty`, named volumes for
  `vendor/` and the Composer cache, `host.docker.internal` host-gateway for Xdebug); add a `db`
  service here only if the package needs a database
- [ ] T024 Create `.dockerignore` by copying the preset's `artifacts/.dockerignore`
  (exclude `vendor/`, `.git/`, `.github/`, `.devcontainer/`, `.specify/`, `specs/`, caches,
  `*.log`)
- [ ] T025 Create `.devcontainer/devcontainer.json` by copying the preset's
  `artifacts/devcontainer.json` (references `compose.yml` via `dockerComposeFile` +
  `service: tooling`, `workspaceFolder /app`, PHP/Xdebug/EditorConfig extensions)
- [ ] T026 Verify the development environment: `docker compose up -d` then
  `docker compose exec tooling composer test` and `docker compose exec tooling mago format
  --check` succeed with no native PHP toolchain; `docker compose exec tooling php -m` lists
  `xdebug`; confirm "Reopen in Container" opens against the `tooling` service

## Verification

- [ ] T019 Run full local check: `mago format --check && mago lint && mago analyze && mago
  guard`, `composer test`, `composer test-coverage`, `composer test-integration`,
  `composer mutation-test`, and `composer test-all`
- [ ] T020 Verify spec-kit scaffolding is ignored:
  `grep -qxF '/.specify/' .gitignore && grep -qxF '/specs/' .gitignore`
- [ ] T021 Push branch, open PR, confirm all CI jobs green (mago, test matrix, codecov,
  mutation-test)
- [ ] T027 Confirm all committed files use LF line endings (no CRLF); verify via
  `git ls-files --eol | grep -v 'w/lf'` returns nothing
