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

## The test-root shape residual

Test roots are recognised by shape, not by an exact list: `*Test\**`, `*TestAsset\**` and
`*TestIntegration\**`. The first matches any root whose first segment ends in `Test`, so the set is
not exact and the residual is accepted rather than closed. Recorded here so the shape rule is never
read as an exact set.

Measured, not inferred:

- `MyTest\Service` importing `PhpDb\Adapter\Adapter` is clean. `MyTest` ends in `Test`, so it is
  treated as a test root and permitted.
- `MyTestApp\Unit\Trap` importing the same dependency is denied. `MyTestApp` does not end in `Test`,
  so it is not a test root.

A single `*Test*\**` form was rejected for this reason: it matches any root whose segment merely
contains the substring, which permits a production root such as `MyTestApp\`. The split form narrows
that to a suffix and stops there. A production package whose root genuinely ends in `Test` would be
treated as a test root; that is the accepted residual, and it is not a reason to add a root to the
config.

## The structural naming exemption for test roots

The structural naming rules match on the namespace segment alone, so widening them to `**` made them
match test roots too, where the naming clause cannot be satisfied: a PHPUnit class must end in `Test`
and so cannot end in `Command`, `Query`, `Event`, `Listener`, `Middleware` or `Exception`. Every one
of those rules now carries the three suffix shapes in `not-on`, merged by brace alternation with any
exclusion it already had. `not-on` takes a single string, so the merge is alternation rather than a
second key.

Measured, not inferred:

- `WebwareTest\Fixtures\Command\SomethingTest` is clean.
- `WebwareTest\MessageBus\Exception\MessageExceptionTest` is clean. This is the shape reported on
  webinertia/message-bus and webinertia/messagebus-event.
- `WebwareTestAsset\Fixtures\Query\SomethingTest` is clean. `WebwareTestAsset` ends in `Asset`, so it
  needs `*TestAsset\**` rather than `*Test\**`.
- `MyTestApp\Unit\Command\SomethingTest` is still flagged. The exemption is a suffix, so a production
  root containing the substring is not a test root.
- `Acme\Blog\Test\Command\SomethingTest` is still flagged. The exemption is anchored at the first
  segment, so a `Test` segment nested under a production root is not a test root.

The two Http rules were missed when that exemption landed. Their `on` is `**\Http\**` rather than a
root enumeration, so `WebwareTest\Core\Http\Middleware\` is reached by the same prefix as
`Webware\Core\Http\Middleware\`, and both rules flagged every correctly named test class under that
segment. They carry the same shapes now, and the boundary is measured the same way:

- `WebwareTest\Core\Http\Middleware\AttachCoreServicesMiddlewareTest` is clean. This is the class
  reported on webinertia/webware-core.
- `WebwareTest\Acl\Http\Admin\RequestHandler\LoginHandlerTest` is clean. This is the shape reported on
  webinertia/webware-acl.
- `Acme\Blog\Http\Middleware\Handler` and `Acme\Blog\Http\RequestHandler\Dispatcher` are still flagged
  with `must-be-named`. A production root in the matched namespace keeps both clauses.
- `Acme\Blog\Test\Http\Middleware\Handler` and `Acme\Blog\Test\Http\RequestHandler\Dispatcher` are
  still flagged. The first-segment anchoring holds for the Http prefix too.

Those trap cases are what keeps the exemption from swallowing a real violation, and they are the
reason the three-way suffix form is used instead of a single `*Test*\**`.

## The bus interface placement perimeter

The five bus structural rules are inbound only. A rule matches classes already inside its bucket, so
a class outside `Command\`, `CommandHandler\`, `Query\`, `QueryHandler\` or any `Middleware\` bucket
that implements the matching interface was never examined, and the omission was silent rather than
reported. Measured against the preset as shipped by the previous change:

- `Webware\UserManager\Handler\CreateUserHandler implements Webware\MessageBus\CommandHandlerInterface`
  was clean. The command handler rule lives on `**\CommandHandler\*`, which never reached a class in
  a `Handler\` bucket.
- `Webware\UserManager\Handler\LogRequestMiddleware implements Webware\MessageBus\MiddlewareInterface`
  was clean, in a handler bucket that is not a middleware bucket at all.

Each of the five interfaces now carries a perimeter restriction whose `allow-from` is the bucket that
already owns the matching structural rule, the bus package itself, and the three test-root shapes. For
the four message roles the bucket is named explicitly. For the bus middleware it is `**\Middleware\**`:
a `Middleware\` bucket is the bus home at any depth, so both `Acme\Middleware\SomeBusMiddleware` and
`Webware\Core\Middleware\SomeBusMiddleware` are permitted, and a `MessageBus` segment is not required.
The perimeter is the outbound half of the same boundary: it fires wherever the class lives, and the
structural rule keeps firing inside the bucket. PSR-15 has worked this way since the perimeter rules
landed, so the bus side is now consistent with it rather than a new mechanism.

One existing line moved with it: the bus middleware rule's `reason`, which is the note printed with
every `must-implement` finding from that rule. It read "Bus middleware lives in MessageBus\Middleware
and implements the bus MiddlewareInterface", which overstated the rule once the perimeter permitted a
plain `Middleware\` bucket. It now states that classes in a `MessageBus\Middleware` bucket implement
the bus interface and that bus middleware may also live in any `Middleware\` bucket, pointing at the
restriction that permits it. No `on`, `not-on`, `must-be-named`, `must-implement` or `allow-from`
line changed, so the set of classes each rule examines and the verdict each receives are untouched.

The split runs in both directions, and both are pinned:

- `App\Fixtures\Middleware\LogRequestMiddleware` is clean. It sits in a bare `Middleware\` bucket,
  outside `MessageBus\Middleware\`, and the perimeter permits it there.
- `App\Fixtures\Http\Middleware\LogRequestMiddleware` is flagged with `must-implement`, by the Http
  structural rule and not by the perimeter. An `Http\Middleware\` bucket demands PSR-15
  `MiddlewareInterface`, so bus middleware parked in one is still caught. The perimeter permits the
  bus interface in that bucket, which is exactly why the finding comes from the Http rule.
- `App\Fixtures\Service\LogRequestMiddleware` is flagged with `disallowed-use` and
  `disallowed-implements`. It is outside every `Middleware\` bucket, and it was silent before.

Measured after the change:

- `App\Fixtures\Command\CreateUserCommand` is clean, and the same class one namespace away in
  `App\Fixtures\Service\` is flagged with `disallowed-use` and `disallowed-implements`.
- `App\Fixtures\CommandHandler\CreateUserHandler`, `App\Fixtures\Query\FetchUserQuery` and
  `App\Fixtures\QueryHandler\FetchUserHandler` behave the same way, each with its own in-bucket case
  and its own denied pair.
- `AppTest\Fixtures\Service\CreateUserHandlerTest` and its four siblings are clean. They sit outside
  every bucket, so `*Test\**` is the only clause that can permit them, which is why each interface
  has its own test-root case instead of one shared case.

## The nested-bucket case those allow-from globs left open

An `allow-from` entry is a glob, and `**\Command\**` matches a `Command\` segment at any depth, not
only the one the matching structural rule governs. The Http structural rules are the reverse: `on` is
`**\Http\**\Middleware\*` and `**\Http\**\RequestHandler\*`, which match direct children only. The two
shapes compose into a class that is permitted by the perimeter and examined by no structural rule:

- `App\Fixtures\Http\Middleware\Deep\NestedMiddleware implements Webware\MessageBus\MiddlewareInterface`
  was clean. It matched `**\Middleware\**`, and the Http rule does not reach a `Middleware\` segment
  that sits one segment below the bucket the class is in.
- `App\Fixtures\Http\Command\Deep\NestedCommand` and the handler, query and query handler equivalents
  were clean for the same reason, each matching its own `**\Bucket\**` entry below an `Http\` segment.

Five `deny-from` restrictions close it. Each names the same interface as its `allow-from` counterpart
and denies `**\Http\**`; the four message roles also deny `**\Repository\**`, because a repository
persists rows and never speaks the bus's message contracts. The middleware interface carries no
`Repository\` deny: the `Webware\MessageBus\**` restriction at the foot of the preset already denies
every bus symbol there, and a denial is not restated.

Measured after the change:

- `App\Fixtures\Http\Middleware\Deep\NestedMiddleware` is flagged with `disallowed-use` and
  `disallowed-implements`, and so is each of the four message-role traps.
- `App\Fixtures\Middleware\LogRequestMiddleware` is still clean. The pair is the point: a bare
  `Middleware\` bucket passes, and the same interface one segment below `Http\` does not.
- `App\Fixtures\Http\Middleware\LogRequestMiddleware` now reports `must-implement` from the Http
  structural rule and the two perimeter codes together. The structural finding was already there; the
  deny is additive rather than a replacement.
- A bus middleware test double under `AppTest\Core\Http\Middleware\Deep\` is flagged. `deny-from` is
  applied regardless of a matching `allow-from` entry, so the `*Test\**` permit does not rescue a
  namespace that is also below `Http\`. Nothing below `Http\` is a bus home, including a test double
  parked there. The census records no consumer that relies on the shape.

## The interface and trait rules stay unexempted

The `target = "interface"` and `target = "trait"` rules use `on = "**"`, so a test root can match
them. They are deliberately left without `not-on`. A census of the sibling webinertia checkouts found
every interface and trait declaration on a test path already named `*Interface` or `*Trait`, so no
false positive exists to exempt. Those rules also cannot match a PHPUnit test class, because the
target is the interface or trait keyword. Adding a `not-on` here would instead exempt on-the-fly test
doubles and widen the rule without evidence.

## What the transformation changed, as recorded

168 cases, 87 clean, 81 flagged, and 47 verdicts differ from the recorded baseline. The full list is
the `check.php` change report; the shape of it is:

- **25 structural gains.** Enforcement that did not exist now fires, on roots the enumeration never
  named (`Acme\`, `Phpiidoc\`), on real fleet test roots (`PhpDbTest\`, `PhpiidocTest\`,
  `PhpiidocTestIntegration\`), at a namespace depth the enumerated form could not reach, and on
  zero-segment namespaces (`Http\...`). No fleet-root case lost a finding. Seven of the 25 belong to
  the exception pair, which is new outright rather than widened: two naming findings and five
  placement findings, the placements spread across a package root, a nested namespace, a plural
  near-miss and the `App\`, `Webware\` and `Acme\` roots.
- **2 gains of perimeter enforcement.** `repository/acme-service-denied` and
  `messagebus-repository/acme-repository-denied` were clean because no rule covered a third-party
  root. A host application had no persistence boundary over its own repositories.
- **19 permissions.** Each is a namespace that is a legitimate home for the dependency and was
  denied only because its root was not enumerated: `Acme\Http` and zero-segment `Http\` for PSR-15,
  `Acme\Console`, `Acme\Repository`, `Acme\Entity`, `Acme\InputFilter`, and the three fleet test
  roots across the PSR-15, repository, PhpDb, PhpDb validator and Symfony Console restrictions.
- **1 reclassification.** `messagebus-handler/acme-handler-denied` is still denied, but by the bus
  `deny-from` rather than by the PSR-15 perimeter that used to deny its namespace outright.

Two of the 47 are intended widenings rather than pure gains, and both are named here so they are not
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

That section describes the root-agnostic transformation alone. With the test-root exemption cases
added, the corpus is 187 cases, 102 clean, 85 flagged, and 62 verdicts differ from the recorded
baseline. The 15 extra differing verdicts are the test-root cases above; the 4 trap cases do not
differ, which is what proves the exemption did not widen any production rule.

With the Http exemption cases added as well, the corpus is 193 cases, 104 clean, 89 flagged, and 64
verdicts differ from the recorded baseline. The 2 further differing verdicts are
`http-middleware/webwaretest-clean` and `http-request-handler/webwaretest-clean`; the 4 new trap
cases do not differ, which proves the Http exemption left both production clauses intact.

With the bus interface placement cases added, the corpus is 209 cases, 114 clean, 95 flagged, and 69
verdicts differ from the recorded baseline. The 5 further differing verdicts are the five
`*-placement/app-denied` traps, each going from clean to `disallowed-implements` and `disallowed-use`.
The 10 clean cases, one in-bucket and one test-root case per interface, do not differ, which is what
proves the five restrictions fire outside the intended homes and nowhere else. The middleware group
carries a fourth case, the Http bucket trap, whose `must-implement` verdict is unchanged by the
perimeter: an `Http\Middleware\` bucket is not a bus home, so the Http structural rule keeps doing the
objecting there. Fifteen of the sixteen placement cases were clean before the change, so
`baseline.json` gained one verdict entry, for that trap, and its `config` field grew one clause naming
the generation they were recorded against.

With the nested-bucket deny cases added, the corpus is 214 cases, 114 clean, 100 flagged, and 75
verdicts differ from the recorded baseline. The 5 further traps are clean before and report
`disallowed-implements` with `disallowed-use` after. The sixth new difference is
`bus-middleware-placement/http-bucket-trap`, which gains the two perimeter codes while keeping its
`must-implement`: the deny adds a second objection rather than replacing the structural one. All five
new traps were clean against the recorded configuration, so `baseline.json` gained no verdict entry
for them, and its `config` field grew one clause naming that generation. The 114 clean cases do not
differ, which is what keeps the deny from reaching a bare `Middleware\` bucket or a test root that is
not below `Http\`.
