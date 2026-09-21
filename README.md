# webware/webware-tools

Shared CI/CD and tooling configuration for the Webware package family. Consumed as a dev
dependency by every component; it ships no PHP code.

## What ships

| Path | Purpose |
|---|---|
| `mago.toml` | The central Mago configuration every component `extends`. **The Mago version is pinned here, in `version =`, and nowhere else.** |
| `mago-analysis-types.md` | Docblock-only types Mago's analyzer understands. |
| `mago-guard-realignment.md` | Procedure for a consumer dropping local guard overrides in favour of the central rules. |
| `presets/webware-alignment/` | Spec-Kit preset: templates and ready-to-copy artifacts for aligning a package. See its [README](presets/webware-alignment/README.md). |

## How a consumer uses it

- **Mago** — the package's own `mago.toml` is a stub that extends this one
  (`extends = "vendor/webware/webware-tools/mago.toml"`). General settings and the
  ecosystem-wide guard rules are central; only a domain-specific rule belongs locally.
- **CI** — the pipeline is the organization's required workflow, bound to every repository by the
  organization ruleset. A package varies it with `webware-ci.json` in its own root. There is no
  per-package workflow file and no workflow ref to keep in sync.
- **Containers** — the tooling `Dockerfile` derives the Mago version from this file's `version =`
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

## License

BSD-3-Clause. See [LICENSE](LICENSE).
