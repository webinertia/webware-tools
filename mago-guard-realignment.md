# Guard Rule Realignment

Procedure for a consumer dropping its local `mago.toml` guard overrides in favour of the central
rules in `vendor/webware/webware-tools/mago.toml`.

Boundary guard rules are enforced from the moment they land in the centre. A consumer updating its
`webware/webware-tools` dependency therefore goes red where it is non-compliant, and the expected
response is to make the package compliant — never to disable, weaken, or copy a central rule into
the consumer file.

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
   (`presets/webware-alignment/artifacts/mago.toml` — `extends`, `php-version`, baselines, source
   paths) before classifying anything. This makes every local rule explicit rather than implicit,
   and the diff shows exactly what the package was relying on.

3. **Classify each local rule that was removed.** There are only three outcomes:

   | Classification | Action |
   |---|---|
   | General — applies ecosystem-wide | Promote to the centre: PR to `webinertia/webware-tools`, remove from the consumer |
   | Duplicate — the centre already covers it | Delete. Do not re-add it because the wording differs |
   | Domain-specific — covers this package's own domain only | Re-add locally, verbatim from the centre's style, with a `reason` |

   The third bucket should shrink over time. A package that ends up with no local guard rules is the
   normal outcome, not a regression.

4. **Make the package compliant.** Fix the findings in source. Do not add `@mago-expect` for a
   guard finding, and do not lower `mode` or narrow a rule to get green.

5. **Verify.** `mago guard` clean, then the full gate: `mago format --check`, `mago lint`,
   `mago analyze`, unit + integration tests. Guard findings are not baselineable in the consumer
   file; if a rule genuinely cannot be satisfied yet, the migration ordering is wrong — move the
   code first (step 6).

6. **Sequence the work so the rule never has to be suppressed.** A rule describes the post-move
   layout. Land the move (e.g. relocating PSR-15 classes under `Http\`), then let the rule go
   green. Where a cohort of packages shares the same migration, do the cheapest one first to
   validate the centre before the expensive ones.

## Rule semantics (verified against mago 1.47.x)

- **Matching is on the declared namespace, never the directory path.** A file under `src/Command/`
  whose namespace is `Webware\Thing\Domain\Command` matches `Webware\**\Domain\Command\*`. Matching
  on paths produces false positives — audit on the namespace.
- `*` matches a single namespace segment, `**` matches one or more. `Webware\**\Command\*` matches
  both `Webware\Mailer\Command\SendEmailCommand` and `Webware\Acl\Admin\Command\SaveRoleCommand`;
  it does not match `Webware\Mailer\CommandHandler\SendEmailHandler`.
- `Webware\*` matches exactly a two-segment root namespace (`Webware\Acl`) — the composition root —
  and does not open up `Webware\Acl\Anything\Else`.
- `allow-from` matches the source **namespace** and prefix-matches sub-namespaces. Use the
  namespace form (`Webware\**\Http\**`), not a vendor-wide wildcard (`*\Http\**`), which does not
  match.
- `not-on` removes a namespace subtree from a rule's scope. It is load-bearing wherever a rule
  pattern would otherwise capture the contract package it is named after — for example
  `Webware\**\Command\*` also matches `Webware\MessageBus\Command\CommandInterface`.
- Rules are **additive**: a consumer rule layers on top of the centre's rules; neither replaces the
  other. A consumer can strengthen a rule, never weaken it, and there is no consumer-side disable.
