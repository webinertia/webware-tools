# webware/webware-tools

Shared CI/CD and tooling configuration for the Webware package family. Consumed as a dev
dependency by every component; it ships no library code. The only PHP is the fixture verifier in
`guard-fixtures/`, which is never autoloaded.

## What ships

| Path | Purpose |
|---|---|
| `mago.toml` | The central Mago configuration every component `extends`. **The Mago version is pinned here, in `version =`, and nowhere else.** |
| `mago-analysis-types.md` | Docblock-only types Mago's analyzer understands. |
| `mago-guard-realignment.md` | Procedure for a consumer dropping local guard overrides in favour of the central rules. |
| `guard-fixtures/` | Cross-root corpus that pins every guard boundary to an expected verdict. See its [README](guard-fixtures/README.md). |
| `compose.yml`, `Dockerfile` | Tooling container for this repository: the pinned Mago, used to run the guard corpus. |
| `presets/webware-alignment/` | Spec-Kit preset: templates and ready-to-copy artifacts for aligning a package. See its [README](presets/webware-alignment/README.md). |

## Guard rules name no host root

The ecosystem-wide guard rules describe a boundary by shape, not by namespace root: a middleware
boundary is `**\Http\**\Middleware\*` and a persistence boundary is `**\Repository\**`. `**` matches
zero, one or many segments, so a package, an application and a third-party consumer are all held to
the same convention. Test roots are recognised by shape as well, as `*Test\**` and
`*TestIntegration\**`.

Two maintenance obligations follow.

- **Adding a host root to `mago.toml` is never the fix for a finding.** A host application whose root
  the centre does not name is not missing enforcement, and a finding that appears under a new root is
  a real finding. What stays literal in the config is a package name: `not-on` entries, `Webware\*`
  and the other single-package permits exempt one package from one rule and are not host roots.
- **A guard rule change is gated by `guard-fixtures/`.** The corpus is a cross-root matrix that
  records the expected verdict for every boundary alongside the verdicts the pre-root-agnostic
  configuration produced, so a rule that stops matching fails by case name. Run it with
  `docker compose exec -T tooling php guard-fixtures/check.php`.

## How a consumer uses it

- **Mago**: the package's own `mago.toml` is a stub that extends this one
  (`extends = "vendor/webware/webware-tools/mago.toml"`). General settings and the
  ecosystem-wide guard rules are central; only a domain-specific rule belongs locally.
- **CI**: the pipeline is the organization's required workflow, bound to every repository by the
  organization ruleset. A package varies it with `webware-ci.json` in its own root. There is no
  per-package workflow file and no workflow ref to keep in sync.
- **Containers**: the tooling `Dockerfile` derives the Mago version from this file's `version =`
  pin, resolved through the consumer's `composer.lock`, so the tool inside the container always
  matches the pin Mago itself enforces.

## The pin has exactly one home

`version =` in `mago.toml` is the only place a Mago version is written in the ecosystem. The
required workflow rejects a copy of it in any of these forms:

- a Dockerfile `ARG MAGO_VERSION=`
- a compose build argument
- a workflow `tools: mago:<version>`
- a `mago.toml` `version =`
- a version-pinned Mago schema URL (`#:schema …/x.y.z/schema.json`)

A Mago bump is therefore one line in one file, and every consumer container and CI job follows it
through the revision its own `composer.lock` resolves.

## Release and versioning policy

The prerelease line is finished. `1.0.0-alpha.*`, `1.0.0-beta.*` and `1.0.0-rc.*` are history, and
the package is on stable `1.0.0` and semantic versioning from here. A release number answers two
questions: did the toolset change, and how disruptive is that change to a consumer.

| Change | Release |
|---|---|
| Any tool releases a minor or patch, such as a Mago bump | Patch: `1.0.x` |
| A new tool is added, or an existing tool gains a new capability | Minor: `1.x.0` |
| One tool is replaced by another | Major: `x.0.0` |

**Consumers take a caret range.** A dependent requires `^1.0` and never an exact pin, so patch and
minor releases arrive on `composer update` without an edit in the consumer. Only a major release
asks the consumer to act.

**The pin has exactly one home.** A Mago version is written in `version =` in `mago.toml` in this
repository, and nowhere else. A consumer that restates `version =` in its own `mago.toml` fails the
reusable CI.

## License

BSD-3-Clause. See [LICENSE](LICENSE).
