# webware-tools — Copilot Agent Instructions

## Mago Analyzer Docblock Types

When resolving `mago analyze` findings (e.g. `mixed-argument`, `mixed-assignment`,
`missing-return-type`), consult [`mago-analysis-types.md`](../mago-analysis-types.md) for the
full list of docblock-only types Mago's analyzer supports (`positive-int`, `non-empty-string`,
`list<T>`, `key-of<T>`, etc.) before falling back to `mixed`.
