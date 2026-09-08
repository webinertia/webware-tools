# Docblock-only types

Types that cannot be written as native PHP type hints and must be declared in a docblock
(`@var`, `@param`, `@return`, `@property`, `@method`, `@template`, ...).

Sources:

- keyword table: `crates/phpdoc-syntax/src/parser/internal/type/keyword.rs` (`lookup_keyword`)
- syntax forms: `crates/phpdoc-syntax/src/cst/type/mod.rs` (`enum Type`)
- resolution to internal types: `crates/codex/src/ttype/builder.rs` (`get_union_from_type`)

Native PHP types (`int`, `float`, `string`, `bool`, `array`, `object`, `callable`, `iterable`,
`mixed`, `void`, `never`, `null`, `true`, `false`, `self`, `static`, `parent`, class names) are
writable as real hints and are not listed here.

## Integer types

| Type | Notes |
|---|---|
| `integer` | alias of `int` (docblock spelling only) |
| `positive-int` | `>= 1` |
| `negative-int` | `<= -1` |
| `non-positive-int` | `<= 0` |
| `non-negative-int` | `>= 0` |
| `non-zero-int` | `!= 0` |
| `int<min, max>` | range; bounds are literal ints (incl. negative) or the `min` / `max` keywords |
| `int-mask<A, B, C>` | all bitwise combinations of the given ints |
| `int-mask-of<Foo::BAR_*>` | mask over a constant / enum set |
| `literal-int` | integer that originated from a literal in source |
| `42`, `-1`, `+1` | literal int values (`Type::LiteralInt`, plus `Negated` / `Posited`) |

## String types

| Type | Notes |
|---|---|
| `non-empty-string` | |
| `lowercase-string` | |
| `uppercase-string` | |
| `non-empty-lowercase-string` | |
| `non-empty-uppercase-string` | |
| `numeric-string` | |
| `truthy-string` | |
| `non-falsy-string` | |
| `literal-string` | |
| `non-empty-literal-string` | |
| `callable-string` | |
| `lowercase-callable-string` | |
| `uppercase-callable-string` | |
| `class-string`, `class-string<T>` | parameter must resolve to an object / template / alias |
| `class-like-string`, `class-like-string<T>` | |
| `interface-string`, `interface-string<T>` | |
| `enum-string`, `enum-string<T>` | |
| `trait-string`, `trait-string<T>` | |
| `'foo'`, `"foo"` | literal string values |

## Float types

| Type | Notes |
|---|---|
| `real` | alias of `float` |
| `double` | alias of `float` |
| `literal-float` | float that originated from a literal in source |
| `1.5` | literal float values |

## Bool types

| Type | Notes |
|---|---|
| `boolean` | alias of `bool` |
| `true`, `false` | native since PHP 8.2, still needed in docblocks for older targets |

## Array / list / iterable types

- `array<V>`, `array<K, V>`
- `array{a: int, b?: string}` — shapes, including the `...<T>` additional-fields form
- `T[]` — slice syntax (sugar for `array<array-key, T>`)
- `non-empty-array`, `non-empty-array<K, V>`
- `associative-array`, `associative-array<K, V>`
- `list`, `list<T>`
- `non-empty-list`, `non-empty-list<T>`
- `iterable<K, V>` — bare `iterable` is native, the generics are not
- `array-key`

## Callable types

- `callable(int, string=, mixed...): bool` — parameter / return signature
- `pure-callable` (+ signature)
- `pure-closure` (+ signature)
- `Closure<...>`, `Generator<TKey, TValue, TSend, TReturn>` and other generic references

## Object types

- `object{foo: int, bar: string}` — object shapes
- `stringable-object`
- `Foo<T>` — generics on any class reference
- `$this` — `$this`-bound propagation
- intersections such as `Foo&Bar`, and `object&callable` (collapses to a `__invoke` holder)

## Resource types

`resource`, `open-resource`, `closed-resource`

## Scalar / mixed / bottom types

| Type | Notes |
|---|---|
| `scalar` | |
| `numeric` | `int\|float\|numeric-string` |
| `empty` | |
| `empty-scalar` | |
| `non-empty-mixed` | truthy mixed |
| `nothing`, `no-return`, `never-return`, `never-returns` | aliases of `never` |
| `*`, `_` | wildcard (treated as `mixed`; also marks bivariance in generics) |

## Type operators / derived types

- `key-of<T>`
- `value-of<T>`
- `properties-of<T>`, `public-properties-of<T>`, `private-properties-of<T>`, `protected-properties-of<T>`
- `new<T>`
- `template-type<T, Foo>`
- `T[K]` — index access
- `($x is Foo ? A : B)` — conditional types (also `as`, `not`)
- `Foo::CONSTANT`, `Foo::PREFIX_*`, `Foo::*_SUFFIX`, `Foo::*` — member references with wildcards
- `PREFIX_*`, `*_SUFFIX` — global constant wildcards
- `Foo::MyAlias`, `!Foo::MyAlias` — type-alias references (`@phpstan-type` / `@psalm-type` / `@phpstan-import-type`)
- `$param` — variable types (template / `@param-out`-style references)
- `covariant` / `contravariant` — generic variance markers on parameters

## Parser notes

- All keywords are matched case-insensitively **except** `min`, `max`, `real`, `double`, `scalar`,
  `boolean`, `integer`, `nothing`, `numeric`, and `resource`, which require exact lowercase
  (`eq_exact` in `keyword.rs`).
- `as`, `is`, `not`, `min`, `max` are contextual: outside a conditional type or an int range they
  parse as ordinary class references.
