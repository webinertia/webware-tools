# Guard fixture corpus

The centre `mago.toml` guard rules name no host root. This corpus is the proof that they still
enforce, and the proof that the change from enumerated roots to root-agnostic globs lost no boundary.

A clean guard result is ambiguous: it can mean the source was permitted, or that the rule never
matched the file at all. The corpus resolves that ambiguity by running the same boundary from
several namespace roots and by pairing every allow case with a deny case one namespace segment away.
The pair discriminates; a lone clean file does not.

## Layout

```
guard-fixtures/
  cases/<rule>/<case>.php   one small file per case, grouped by the rule under test
  manifest.json             the expected verdict for every case, before and after
  baseline.json             what the unmodified configuration actually produced
  check.php                 the verifier
```

`manifest.json` carries a `rules` preamble describing each rule's pattern before and after the
transformation, so the corpus is readable without the diff.

The matrix roots are `App\`, `Webware\`, `AppTest\`, `WebwareTest\`, `AppTestIntegration\`,
`WebwareTestIntegration\`, the fleet test roots the old enumeration missed (`PhpDbTest\`,
`PhpiidocTest\`, `PhpiidocTestIntegration\`), a root that does not exist in the fleet (`Acme\`), and
zero-segment namespaces (`Http\...`, `Event\...`) that prove `**` matches zero segments.

## Running it

From the tooling container. The host binary is a different mago version and a host run is not
evidence.

```
docker compose up -d
docker compose exec -T tooling php guard-fixtures/check.php
```

`check.php` runs `mago guard guard-fixtures/cases --reporting-format=json` and compares the result
against the manifest. A comparison run asserts three things:

1. The observed verdicts equal `expectAfter` for every case. This is the shipped configuration's
   contract.
2. `baseline.json` equals `expectBefore` for every case. That pins the record taken from the
   unmodified, enumerated configuration, so a later edit cannot quietly rewrite history to match a
   regression.
3. No fixture file is missing from the manifest and no manifest case is missing a file.

It also prints every verdict that changed against the baseline, with the case's note, so the diff is
reviewable rather than inferred. Exit code 1 means a case changed or a fixture is unaccounted for.

To re-record the baseline against a configuration you are deliberately testing:

```
docker compose exec -T tooling php guard-fixtures/check.php --record=guard-fixtures/baseline.json
```

That rewrites `baseline.json` only. `expectBefore` in the manifest is the historical record and is
edited by hand, deliberately, never by a script.

## Adding a case

1. Add `cases/<rule>/<case>.php` with the smallest file that expresses the boundary. A class, its
   namespace and, for a perimeter case, one constructor-promoted dependency is enough.
2. Add an entry to `manifest.json` `cases`: `id`, `file`, `namespace`, `rule`, `root`,
   `expectBefore`, `expectAfter`, `note`. `expectBefore` is what the enumerated configuration
   produced; if the case is new after the transformation, record what it would have produced.
3. Run `check.php --record=` against the unmodified configuration (`git stash` the `mago.toml` edit,
   or check out `HEAD:mago.toml`) and confirm `expectBefore` matches. A prediction is not a record.
4. Restore the current configuration and run `check.php`.
5. If the verdict differs from `expectAfter`, decide which is wrong: the fixture or the prediction.
   Fix the fixture when the intent was right, correct the prediction only when it misread which rule
   fires.

## Design rules, learned the hard way

- **Every allow case needs a paired deny case one namespace segment away.** Otherwise a clean result
  cannot be distinguished from a rule that never matched. `psr15/acme-http-permitted` is paired with
  `psr15/acme-nonhttp-denied`; `repository/acme-repository-permitted` with
  `repository/acme-service-denied`.
- **A fixture must satisfy the rule's own naming constraint.** A `Middleware` case whose class is
  named `WMwGood` fails `must-be-named`, and the failure looks like a config difference when it is
  only the fixture. Every class name here is chosen so that naming is never the thing being
  measured: `...Middleware`, `...Handler`, `...Command`, `...Event`, `...Listener`, `...Interface`,
  `...Trait`.
- **Depth is part of the case.** The repeated-segment rule only reached a pair immediately under an
  enumerated root, so the depth cases are written at two depths to make the depth gain attributable
  to the leading `**` and not to the new root.
- **No UTF-8 BOM.** Mago fails to parse a BOM-prefixed file and guard then reports nothing for it,
  silently. Write fixtures as UTF-8 without a BOM.
- **Explicit paths scope the run and still apply the repo-root `mago.toml`.** The corpus needs no
  `[source] paths` entry, no vendor install and no stub classes: perimeter rules resolve from the
  `use` statement and `must-implement` sees directly declared interfaces. Vendor is not visible to
  guard at all, so a leading `**` cannot sweep a vendor symbol.
- **A fixture under an excluded path reports `No files found`.** `check.php` treats that as a hard
  failure rather than an empty corpus, because an empty corpus would otherwise pass.

## What the transformation changed, as recorded

157 cases, 83 clean, 74 flagged, and 40 verdicts differ from the recorded baseline. The full list is
the `check.php` change report; the shape of it is:

- **18 structural gains.** Enforcement that did not exist now fires, on roots the enumeration never
  named (`Acme\`, `Phpiidoc\`), on real fleet test roots (`PhpDbTest\`, `PhpiidocTest\`,
  `PhpiidocTestIntegration\`), at a namespace depth the enumerated form could not reach, and on
  zero-segment namespaces (`Http\...`). No fleet-root case lost a finding.
- **2 gains of perimeter enforcement.** `repository/acme-service-denied` and
  `messagebus-repository/acme-repository-denied` were clean because no rule covered a third-party
  root. A host application had no persistence boundary over its own repositories.
- **19 permissions.** Each is a namespace that is a legitimate home for the dependency and was
  denied only because its root was not enumerated: `Acme\Http` and zero-segment `Http\` for PSR-15,
  `Acme\Console`, `Acme\Repository`, `Acme\Entity`, `Acme\InputFilter`, and the three fleet test
  roots across the PSR-15, repository, PhpDb, PhpDb validator and Symfony Console restrictions.
- **1 reclassification.** `messagebus-handler/acme-handler-denied` is still denied, but by the bus
  `deny-from` rather than by the PSR-15 perimeter that used to deny its namespace outright.

Two of the 40 are intended widenings rather than pure gains, and both are named here so they are not
mistaken for a loss:

- `repository/cross-root-permitted`. The two root-specific restrictions were ANDed, so a
  `Webware\...\Repository` importing an `App\...\Repository` was denied. Merging them into one
  `**\Repository\**` restriction permits it. The repository namespace is the persistence boundary;
  the pair only drew the line where the root enumeration happened to fall.
- `phpdb/app-validator-permitted`. The PhpDb allow-list had `Webware\**\Validator\**` with no `App\`
  counterpart, so an application validator was denied PhpDb while a package validator was permitted.
  `**\Validator\**` removes the asymmetry.

No boundary was deleted or widened beyond those two, no finding is baselined or suppressed, and the
entries that stay literal (`not-on`, `Webware\*`, and single-package permits such as
`Webware\Async\**`, `Webware\Router\**`, `Webware\Migration\**` and `Webware\Traccio\**`) are package
names, not host roots.
