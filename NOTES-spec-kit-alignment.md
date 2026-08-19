# Spec-Kit Alignment — Session Handoff Notes

Pick up from this repo next session. webware-tools is the hub for all work in flight.
Delete this file only after everything listed under "In flight" is merged.

---

## In flight — exact state (2026-08-18)

### webware-tools (this repo)

- Branch `0.1.x` tip: `50f0ccc` (merged PR #8). Clean tree except this NOTES file (untracked).
- Merged: PR #6 (preset scaffold, `d6541ef`), PR #7 (export-ignore task fix, `f3c5a5b`),
  PR #8 (mago 1.47.1 pin + guard structural rules, `a2fe3ac`).
- Unmerged branch: `mago-pin-1.47.1` (pushed, PR #8 already merged from it — branch is
  obsolete, can be deleted).
- Old branches around: `alignment-preset` (merged via #6/#7).

### webware-mailer

- PR #7 open: `specify-alignment-spec` → `1.0.x`.
  URL: https://github.com/webinertia/webware-mailer/pull/7
- Branch commits: `6ea9032` (spec-kit template + badges), `d55453f` (inherit mago pin),
  `3d2c3f9` (analyzer annotations), `16aab7b` (mago 1.47.1 formatter).
- Local state: all pushed. `mago analyze/fmt/lint/guard` + unit tests all green locally.
- Awaiting CI green + merge.

### webware-navigation

- PR #2 open: `specify-alignment-spec` → `0.1.x`.
  URL: https://github.com/webinertia/webware-navigation/pull/2
- Branch commits: `1286b3f` (spec-kit + badges), `fa5815e` (inherit mago pin).
- Uncommitted locally: user's own `docs/webware-tools-alignment.md` edit — do NOT touch.
- Deferred: `mago analyze` (strict ruleset) reports ~20 errors — mostly `Webware\Acl\*`
  and `Webware\UserManager\*` imports that only resolve once webware-acl and
  webware-usermanager are published/repointed. Plus 5-ish `missing-api-or-internal` /
  `mixed-assignment` warnings. Decide after webware-acl lands.

### webware-acl — NEW import in progress (started end of this session)

- Repo cloned: `/home/jsmith/github.com/webinertia/webware-acl`, branch `initial-import`
  (off `0.1.x`), NOT pushed, NOT committed.
- Contents copied straight from
  `/home/jsmith/github.com/tyrsson/inventory-management-system/src/webware-acl/` (the
  user's currently-checked-out branch there: `override-user-manager-update-user-via-ims-store`).
  Copied: `src/`, `test/`, `templates/`, `docs/`, `ui-mockup/`, `README.md`, `composer.json`.
- `specify init --here --force --integration copilot --script sh` already run (`.specify/` +
  `.github/skills/` present, uncommitted).
- NOT yet done: composer resolution, preset install, spec/plan/tasks generation, mago pass,
  badges, .gitattributes export-ignore, commits, PR.
- Composer blockers (verified): module composer.json name is `webware/acl` (target:
  `webware/webware-acl`); requires `axleus/axleus-log`, `axleus/axleus-message` (both
  unpublished), `webware/core` (registered, zero releases), `php-db/phpdb-mysql 0.4.x-dev`,
  plus mezzio/laminas stack. Repoint plan from user's migration plan
  (`~/github.com/tyrsson/inventory-management-system/docs/module/component-migration-plan.md`):
  axleus-log → `webware/webware-log`, axleus-message → `webware/webware-message`,
  webware/core → `webware/webware-core`. CommandBus→MessageBus migration in the module is
  already done by the user. User said: focus only on the module being worked on, they know
  the dependency picture.

### Deferred / user-driven

- Evening test of the preset on webware-acl — now underway in this session instead.
- webware-usermanager does not exist as a repo yet (navigation + acl reference
  `Webware\UserManager\`).

---

## Preset: `webware-alignment` (what it is, how to use)

Lives in this repo at `presets/webware-alignment/` (id `webware-alignment`, v0.1.0). Template
overrides for spec/plan/tasks/constitution. Ships in the composer dist of
`webware/webware-tools` (presets/ is intentionally NOT export-ignored — that is the composer
channel).

Install into an initialized spec-kit consumer:

```bash
specify preset add --dev vendor/webware/webware-tools/presets/webware-alignment
# or from a tag:
specify preset add --from https://github.com/webinertia/webware-tools/archive/refs/tags/vX.Y.Z.zip
```

Workflow: `specify init` → preset add → `/speckit-specify` (fill Package Parameters) →
`/speckit-plan` → `/speckit-tasks` → `/speckit-implement`. Tasks are T001–T020; T017 adds
`/.specify/` and `/specs/` to the consumer `.gitignore` (local dev tooling, never pushed);
T018 is README badges; T019/T020 verification.

Reference instance: `webware-mailer/specs/001-webware-tools-alignment/` (mailer values filled).

Preset update flow: edit templates here → branch → PR to `0.1.x` → merge → in each consumer:
`composer update webware/webware-tools`, then `specify preset remove webware-alignment` +
`specify preset add --dev vendor/webware/webware-tools/presets/webware-alignment`.

---

## Mago incident 2026-08-18 (resolved)

- Break: mago 1.47.0 release (14:45Z) shipped WITHOUT the
  `mago-1.47.0-x86_64-unknown-linux-gnu.tar.gz` asset. setup-php `tools: mago` follows
  `latest`, the download 404'd, all output suppressed, install logged success anyway →
  `mago: command not found` (exit 127) on every mago CI step + mutation-test.
  Last green CI: 2026-08-17 23:13Z (mago 1.46.0).
- Fixed upstream: 1.47.1 (15:30Z) restored the asset set.
- Our fix (PR #8): pin `tools: mago:1.47.1` in BOTH workflow jobs (mago + mutation-test);
  central `mago.toml`: schema URL + `version = "1.47.1"` exact pin.
- Post-fix local sweep on mailer produced new findings from the stricter ruleset:
  `missing-api-or-internal` ×5 (fixed: `@api` on MessageInterface, MailerInterface,
  AdapterInterface, MailerAwareInterface, MailerAwareInterfaceTrait) and one
  `unhandled-thrown-type` (fixed: `@throws InvalidArgumentException` on
  `SendEmailCommand::setEvent`). `mago fmt` reformatted 20 files to 1.47.1 ruleset.

## Central config doctrine (decided this session)

- Mago version pin lives ONLY in `webware-tools/mago.toml` (`version = "1.47.1"` + schema
  comment). Consumer `mago.toml` files are 3-line extends wrappers — NO schema line, NO
  version. Workflow `tools: mago:<ver>` pin is also central (reusable workflow).
- Guard structural rules in central config: `Webware\**` interfaces must be `*Interface`,
  traits `*Trait`. `mode = "structural"`. Both mailer and navigation pass.
- `/.specify/` and `/specs/` go in `.gitignore` — spec-kit scaffolding is local dev tooling,
  never pushed to the remote (doctrine changed 2026-08-18; earlier export-ignore stance revoked).

## Tooling / environment

- `gh` CLI installed, authed as `tyrsson`, scopes `repo` + `workflow`. Prefer gh for PR
  create/merge; the MCP merge tool was disabled at some point mid-session.
- `mago` at `/usr/local/bin/mago`, now 1.47.1 (user ran `sudo mago self-update`).
- `specify` CLI 0.16.4 installed.
- Global pattern: branch before work, never commit straight to a release branch. One
  mistake mid-session pushed to `0.1.x` directly; recovered via `--force-with-lease`.

## Repos inventory (local paths, for reference)

- webinertia: `/home/jsmith/github.com/webinertia/<repo>` — mailer, navigation, webware-tools,
  message-bus, webware-log, webware-acl (fresh clone), etc.
- tyrsson app: `/home/jsmith/github.com/tyrsson/inventory-management-system` — source of the
  modules being extracted; branch `override-user-manager-update-user-via-ims-store`;
  user's `docs/module/component-migration-plan.md` is the master migration doc (untracked
  there — do not touch).

