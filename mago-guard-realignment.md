# Guard Rule Realignment

Procedure for a consumer dropping its local `mago.toml` guard overrides in favour of the central
rules in `vendor/webware/webware-tools/mago.toml`.

Boundary guard rules are enforced from the moment they land in the centre. A consumer updating its
`webware/webware-tools` dependency therefore goes red where it is non-compliant, and the expected
response is to make the package compliant, never to disable, weaken, or copy a central rule into
the consumer file.

The rules address the package and the application it serves through the same `{App,Webware}` roots,
so application code is held to the same conventions as a package's. Extending the centre is the
opt-in: a repository built on webware-tools takes the rules, component or app.

## When this applies

- After `composer update webware/webware-tools` picks up a new or changed central rule.
- When aligning an existing package, or when a package's local `mago.toml` has grown beyond the
  stub (`extends`, `php-version`, baseline paths, source paths).
- When a general-purpose rule is found duplicated in a consumer file. See Principle I of the
  package constitution.

## Procedure

1. **Update and take the red.** Run `composer update webware/webware-tools`, then `mago guard`.
   Every finding is either work to do or a rule that belongs elsewhere (step 3).

2. **Restore the stub.** Replace the consumer `mago.toml` with the preset artifact
   (`presets/webware-alignment/artifacts/mago.toml`: `extends`, `php-version`, baselines, source
   paths) before classifying anything. This makes every local rule explicit rather than implicit,
   and the diff shows exactly what the package was relying on.

3. **Classify each local rule that was removed.** There are only three outcomes:

   | Classification | Action |
   |---|---|
   | General: applies ecosystem-wide | Promote to the centre: PR to `webinertia/webware-tools`, remove from the consumer |
   | Duplicate: the centre already covers it | Delete. Do not re-add it because the wording differs |
   | Domain-specific: covers this package's own domain only | Re-add locally, verbatim from the centre's style, with a `reason` |

   The third bucket should shrink over time. A package that ends up with no local guard rules is the
   normal outcome, not a regression.

4. **Make the package compliant.** Fix the findings in source. Do not add `@mago-expect` for a
   guard finding, and do not lower `mode` or narrow a rule to get green.

5. **Verify.** `mago guard` clean, then the full gate: `mago format --check`, `mago lint`,
   `mago analyze`, unit + integration tests. Guard findings are not baselineable in the consumer
   file; if a rule genuinely cannot be satisfied yet, the migration ordering is wrong. Move the
   code first (step 6).

6. **Sequence the work so the rule never has to be suppressed.** A rule describes the post-move
   layout. Land the move (e.g. relocating PSR-15 classes under `Http\`), then let the rule go
   green. Where a cohort of packages shares the same migration, do the cheapest one first to
   validate the centre before the expensive ones.

## PSR-14 events and listeners

The centre's only PSR-14 rules are the two naming rules in Principle V:

| Namespace | Name |
|---|---|
| `Event\` | `*Event` |
| `Listener\` | `*Listener` |

Both carry the `{App,Webware}` root, so they bind application code exactly as they bind a package:
an application's `App\Event\SendWelcomeEvent` and `App\Listener\…` are held to the same convention,
whether the repository is a component or an app built on webware-tools. A consumer that extends the
centre has opted in by doing so — there is no consumer-side disable and no App carve-out.

They are deliberately narrow. Nothing is enforced about behaviour: a listener may implement
`Webware\Event\ListenerInterface` directly, extend a package base class, or be registered by its own
provider. There is no `must-implement`, no `must-be-final`, and no dependency restriction on any
event library, so the mechanism can be adopted incrementally and a package is never forced into a
particular dispatch style. The rules exist so the event graph is readable, not so the mechanism is
uniform — narrow scope means the rules are applied everywhere, not that the convention is optional.

Three consequences of the patterns, all verified against the pinned mago 1.50.0:

- `target = "class"` scopes both rules to classes. `EventInterface` and `ListenerInterface` are
  never reported, so the contract packages stay out of scope even where they occupy a matching
  namespace (webware-event keeps its contracts at the root namespace, `Webware\Event\EventInterface`).
- `\Event\*` and `\Listener\*` match direct children only. A listener's DI factory therefore belongs
  in `Listener\Container\`, the same nesting the Http boundary requires for
  `Http\Middleware\Container\`, because a factory is named for the class it builds and cannot
  satisfy `*Listener` otherwise. Every package sits this way now — webware-log's factory moved to
  `Listener\Container\`.
- A class whose name repeats its namespace segment is accepted, because `*` matches an empty run:
  `Webware\Event\Event` is not a finding.
- An App-side fixture set behaves identically to a package's: `App\Event\MisnamedThing` and
  `App\Listener\SomeFactory` are reported, while `App\Event\SendWelcomeEvent`,
  `App\Listener\SendWelcomeListener` and both interfaces are not.

`not-on` excludes two roots. `Webware\MessageBus\**` keeps the bus integration package out of
scope while its event classes are bus types under the bus root
(`Webware\MessageBus\Event\...`); reconciling that package against webware-event is tracked in
webinertia/webware-tools#21; the parent boundary doctrine landed as constitution Principle VIII.
`Webware\Event\**` excludes the
contract package itself, because its ROOT namespace is `Webware\Event` — the `\**\Event\*` pattern
matches its root classes as though they were events, and `ConfigProvider` does not end in `Event`.
Excluding the package is the fix; renaming its `ConfigProvider` to satisfy the rule is not. That is a
namespace collision, not an exemption: an application's `App\Event\` really is its event bucket and
stays fully in scope.

## Moving a class to satisfy a rule

Three things go wrong in this order. Plan for all of them before the first commit.

1. **The factory follows the class.** `Http\Middleware\*` and `Listener\*` require every class
   directly in the namespace to carry the role name and, for middleware, the PSR-15 contract, so a
   DI factory nests one level deeper, in `Http\Middleware\Container\` or `Listener\Container\`.
   Move the factory in the same commit as the class it builds, not afterwards.
2. **Baseline entries are keyed by file path.** A move does not carry suppressions with it: the
   moved file's entries stop matching, its findings reappear, and the entries left at the old path
   go stale (mago reports them as issues that no longer exist). Regenerate both baselines with the
   files in their new location, then diff against the committed files — the expected diff is the
   moved paths and nothing else. Do not reach for `--remove-outdated-baseline-entries` to tidy up
   after a move: it prunes the orphaned entries but cannot add the findings the moved file now
   carries, so the move would silently lose its suppressions.
3. **Decouple rather than relocate when the class is not an implementation.** A class that merely
   names a PSR interface as an event-identifier string is not an implementation of it, and moving it
   under the boundary is the wrong fix. Leave the class where it belongs and move the class-strings
   into a holder the boundary owns — the `Webware\Log\Http\PipelineIdentifiers` pattern.

## Rule semantics (verified against the pinned mago 1.50.0)

- **Matching is on the declared namespace, never the directory path.** A file under `src/Command/`
  whose namespace is `Webware\Thing\Domain\Command` matches `Webware\**\Domain\Command\*`. Matching
  on paths produces false positives; audit on the namespace.
- `*` matches exactly one namespace segment. `**` matches ZERO OR MORE segments, so
  `Webware\**\Command\*` matches `Webware\Command\SendEmailCommand`,
  `Webware\Mailer\Command\SendEmailCommand` and `Webware\Acl\Admin\Command\SaveRoleCommand`; it
  does not match `Webware\Mailer\CommandHandler\SendEmailHandler`. An earlier revision of this file
  claimed `**` was one-or-more, which would leave a top-level `App\Command\X` or
  `Webware\Command\X` unreachable by an `**` rule and silently enforce nothing there.
- **`on` accepts brace alternation.** `on = "{App,Webware}\**\Command\*"` is one rule covering
  both roots, and it composes with `not-on`: a `Webware\MessageBus\Command\*` fixture stayed
  excluded while the `App\` fixtures were reported. Prefer it over duplicating a rule per root.
- **Layer aliases are perimeter-only.** `@layer:<name>` from `[guard.perimeter.layers]` is
  referenced only by `permit` in `[[guard.perimeter.rules]]`. A layer reference in a structural
  `on` is accepted by the configuration and then matches nothing, with no error. A rule that
  enforces nothing is worse than a missing rule, so never write one.
- `Webware\*` matches exactly a two-segment root namespace (`Webware\Acl`), which is the composition
  root, and does not open up `Webware\Acl\Anything\Else`.
- `allow-from` matches the source **namespace** and prefix-matches sub-namespaces. Use the
  namespace form (`Webware\**\Http\**`), not a vendor-wide wildcard (`*\Http\**`), which does not
  match.
- `not-on` removes a namespace subtree from a rule's scope. It is load-bearing wherever a rule
  pattern would otherwise capture the contract package it is named after. For example,
  `Webware\**\Command\*` also matches `Webware\MessageBus\Command\CommandInterface`.
- **A rule pattern matches a package whose root namespace collides with the namespace segment it
  looks for.** `{App,Webware}\**\Event\*` matches every class directly in `Webware\Event`, because
  that is a real namespace and `**` matches zero segments — so the contract package's own
  `ConfigProvider` was reported the first time the Event rule ran against webware-event, while the
  committed rules reported nothing. The fix belongs in the `not-on`, not in the package's class
  names. Expect the same shape wherever a rule keys on a segment a package uses as its root
  (`Webware\Event`, and any future `Webware\Listener`).
- `not-on` takes a **single string**. A list is rejected outright (`invalid type: sequence,
  expected a string`), which is invisible until the config is loaded, so an exclusion set has to be
  expressed as brace alternation — `not-on = "{Webware\MessageBus,Webware\Event}\**"` — or the
  more specific of the two has to be dropped.
- `must-be-final` inspects classes only. Interfaces and traits are never reported, with or without
  `target = "class"`. Keep `target = "class"` for consistency with the centre.
- **`allow-from` entries select source namespaces, not symbols.** An exact symbol entry
  (`App\ConfigProvider`) is accepted and then permits nothing, so it can never be used to carve out a
  false positive. This also makes a one-segment root behave differently from a two-segment root,
  verified against the pinned mago 1.50.0: `Webware\*` matches the namespace `Webware\Acl` alone, so
  `Webware\Acl\Http\RequestHandler\X` is still flagged, while `App\*` matches every first-level
  namespace under `App` (`App\Repository`, `App\RequestHandler`, `App\Middleware`) and therefore
  permits the whole application. Bare `App` and `App\` are broader still, permitting everything
  beneath the root. There is no narrow App composition-root selector, so the App mirror omits it.
  Never use `App\*` in an `allow-from` list; it silently disarms the restriction the list exists to
  enforce.
- **An empty `allow-from` restricts nothing; a non-matching one bans everything.** Omitting the key
  and writing `allow-from = []` are the same: no restriction at all, so an "empty list, to be filled
  in later" silently enforces nothing. A list whose every entry fails to match (a placeholder
  namespace, say) is the opposite extreme — it excludes every source, which is how a total ban has
  to be expressed. Verified by probing `Psr\Http\Server\**` on 1.48.1 — 13 findings
  and 3 `disallowed-use` under a non-matching list, none under an empty one; re-confirmed on the
  pinned 1.50.0, where an empty `allow-from` flags nothing and a non-matching one flags the
  dependency. When a restriction is
  meant to ban a dependency outright, say so in the comment above it, because the TOML on its own
  does not read like a ban.
- Rules are **additive**: a consumer rule layers on top of the centre's rules; neither replaces the
  other. A consumer can strengthen a rule, never weaken it, and there is no consumer-side disable.
