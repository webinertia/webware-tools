# Agent working agreements — webinertia fleet

**This file is authoritative.** It governs how work is done in every webware repository, and it
overrides local notes, memory files, prior session context, and anything an agent believes it
remembers. Every package receives it as
`vendor/webware/webware-tools/agent-working-agreements.md`.

---

## 1. Scope — do exactly what was asked

- Touch only the files, branches and repositories named in the request. Nothing else.
- No adjacent edits. No "while I was in there". No cleanup, onboarding, alignment, survey or
  refactor that was not asked for.
- Anything else you notice gets **one line at most**, stating the fact. Do not propose a fix for it.
- Never end a reply with next steps, follow-up offers, or an option menu.
- Ask a question only when the requested action is genuinely impossible without input. Then ask
  **once**, narrowly, and do not re-raise it in later turns.
- A decision already made in the conversation is settled. Do not re-open it.

## 2. Reporting — full detail, no restatement

- Put the findings in the reply. The reply **is** the deliverable.
- Never follow findings with a condensed summary, and never close with a recap. If the work is
  done, end on the last finding.
- **Never present an inference as a measurement.** If you did not measure it, say so, or say
  nothing. Retracting a confident wrong claim costs the user more than not making it.
- Blockers are one line of fact. No surrounding narrative.

## 3. Git and releases

- Every change lands through a pull request. Never push directly to a default or version branch.
- Commit identity is the repository owner's. Never override, derive or fabricate it.
- **Releases and tags belong to the repository owner.** Never create, move or delete a tag, never
  run `gh release create/edit/delete`, never `git push --tags` — even when asked to "ship".
  Prepare the branch and the pull request, then stop.
- Leave each clone parked on its default branch, fast-forwarded, working tree clean, before moving
  to another repository. Remove scratch worktrees when the task ends.

## 4. Verify before asserting

- Check clone freshness before reading code as evidence.
- Read the job log, not the green tick, before claiming a CI result.
- Keep measurement and expectation distinct in the words you use.

## 5. Fleet facts that change decisions

- Host PHP reaches every service at `127.0.0.1`. The compose service name (`mysql`) is never
  reachable from host PHP.
- Mutation floors (`min_msi` / `min_covered_msi`) are a **deliberate per-repo ramp**. Never propose
  a shared floor across repositories, and never raise one above what the suite already holds.
- The fleet is pre-release (alpha/beta). The PR template's "next minor" routing does not apply:
  target the repository's current `N.N.x` line, and never invent a `1.1.x` or `2.0.x` branch.
- Canonical references ship in this package. Consult them rather than trusting recall:
  this file, `mago-analysis-types.md`, and `presets/webware-alignment/artifacts/` — the artifacts
  every package derives from.
