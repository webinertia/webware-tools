# webware-tools: Copilot Agent Instructions

## Read this before acting: fleet working agreements

`vendor/webware/webware-tools/agent-working-agreements.md` is authoritative for how work is done
in this fleet, and it overrides anything else in this file or in any local note. Read it before
acting, and again before reporting. The non-negotiables:

- Do exactly the scope asked - only the files, branches and repositories named. No adjacent edits,
  no cleanup, no onboarding, no surveys.
- Anything else you notice gets one line at most, with no proposed fix.
- Never close a reply with a condensed restatement of it, and never present an inference as a
  measurement.
- Every change lands through a pull request. Releases and tags belong to the repository owner.

## Mago Analyzer Docblock Types

When resolving `mago analyze` findings (e.g. `mixed-argument`, `mixed-assignment`,
`missing-return-type`), consult [`mago-analysis-types.md`](../mago-analysis-types.md) for the
full list of docblock-only types Mago's analyzer supports (`positive-int`, `non-empty-string`,
`list<T>`, `key-of<T>`, etc.) before falling back to `mixed`.
