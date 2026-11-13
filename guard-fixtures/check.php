<?php

declare(strict_types=1);

/**
 * Verifies the guard fixture corpus against `manifest.json`.
 *
 * Run from the tooling container, from anywhere in the repository:
 *
 *     php guard-fixtures/check.php
 *
 * Modes:
 *
 *     php guard-fixtures/check.php                 compare against the manifest
 *     php guard-fixtures/check.php --coverage      compare, then print what proves each guard block
 *     php guard-fixtures/check.php --record=FILE   write the observed verdicts to FILE
 *
 * A comparison run asserts four things.
 *
 *   1. The observed verdicts equal `expectAfter` for every case. This is the shipped
 *      configuration's contract.
 *   2. `baseline.json` equals `expectBefore` for every case. This pins the record taken from
 *      the unmodified, enumerated configuration, so a later edit cannot quietly rewrite
 *      history to match a regression.
 *   3. No fixture file is left out of the manifest and no manifest case is missing a file.
 *   4. Every guard block in `mago.toml` is exercised by at least one fixture. A rule nothing
 *      exercises is indistinguishable from a rule that passes, which is how a nested bucket
 *      sat unfenced inside a rule whose only fixtures were the clean case it was written to
 *      accept. Assertion 4 derives its list of blocks from the preset at check time, so adding
 *      a rule without a fixture fails the check rather than passing silently.
 *
 * The corpus is a cross-root matrix: the same boundary is exercised from `App\`, `Webware\`,
 * a real fleet root that was never enumerated, and a root that does not exist in the fleet.
 * That is what makes the superset claim falsifiable rather than asserted.
 */

const CORPUS_PATH = 'guard-fixtures/cases';

$root = dirname(__DIR__);
chdir($root);

$recordTo = null;
$showCoverage = false;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--record=')) {
        $recordTo = substr($argument, strlen('--record='));
    } elseif ($argument === '--coverage') {
        $showCoverage = true;
    } else {
        fwrite(STDERR, "unknown argument: {$argument}\n");
        exit(2);
    }
}

$manifest = json_decode((string) file_get_contents(__DIR__ . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$observed = runGuard();

if ($recordTo !== null) {
    record($recordTo, $observed);
    exit(0);
}

$failures = 0;
$changed = [];
$clean = 0;
$flagged = 0;

foreach ($manifest['cases'] as $case) {
    $file = $case['file'];
    $got = $observed[$file] ?? [];
    $want = $case['expectAfter'];
    $before = $case['expectBefore'];

    $missing = multisetDiff($want, $got);
    $unexpected = multisetDiff($got, $want);

    if ($missing !== [] || $unexpected !== []) {
        $failures++;
        printf("FAIL %s\n", $case['id']);
        printf("     file     %s\n", $file);
        printf("     expected %s\n", render($want));
        printf("     observed %s\n", render($got));
        if ($missing !== []) {
            printf("     missing  %s\n", render($missing));
        }
        if ($unexpected !== []) {
            printf("     extra    %s\n", render($unexpected));
        }
    }

    if ($got === []) {
        $clean++;
    } else {
        $flagged++;
    }

    if (multisetDiff($got, $before) !== [] || multisetDiff($before, $got) !== []) {
        $changed[] = [
            'id' => $case['id'],
            'file' => $file,
            'before' => $before,
            'after' => $got,
            'added' => multisetDiff($got, $before),
            'removed' => multisetDiff($before, $got),
            'note' => $case['note'],
        ];
    }
}

$baselineFile = __DIR__ . '/baseline.json';
if (!is_file($baselineFile)) {
    printf("FAIL baseline.json is missing; record it against the unmodified configuration first\n");
    $failures++;
} else {
    $baseline = json_decode((string) file_get_contents($baselineFile), true, 512, JSON_THROW_ON_ERROR);
    foreach ($manifest['cases'] as $case) {
        $recorded = $baseline['verdicts'][$case['file']] ?? [];
        $want = $case['expectBefore'];
        if (multisetDiff($want, $recorded) !== [] || multisetDiff($recorded, $want) !== []) {
            $failures++;
            printf("FAIL %s\n", $case['id']);
            printf("     baseline.json records %s but the manifest documents %s\n", render($recorded), render($want));
        }
    }

    foreach (array_keys($observed) as $file) {
        if (!isset($baseline['verdicts'][$file]) && !hasCase($manifest, $file)) {
            $failures++;
            printf("FAIL %s is not covered by the manifest\n", $file);
        }
    }
}

foreach ($manifest['cases'] as $case) {
    if (!is_file($root . '/' . $case['file'])) {
        $failures++;
        printf("FAIL %s names a missing file %s\n", $case['id'], $case['file']);
    }
}

$failures += checkCoverage($root, $manifest, $showCoverage);

printf("\n%d cases, %d clean, %d flagged\n", count($manifest['cases']), $clean, $flagged);

if ($changed === []) {
    print("no verdict changed against the recorded baseline\n");
} else {
    printf("\n%d verdicts changed against the recorded baseline\n\n", count($changed));
    foreach ($changed as $change) {
        printf("%-52s %s -> %s\n", $change['id'], render($change['before']), render($change['after']));
        if ($change['added'] !== []) {
            printf("%-52s added   %s\n", '', render($change['added']));
        }
        if ($change['removed'] !== []) {
            printf("%-52s removed %s\n", '', render($change['removed']));
        }
        printf("%-52s %s\n", '', $change['note']);
    }
}

if ($failures > 0) {
    printf("\n%d failures\n", $failures);
    exit(1);
}

print("\nall cases match the manifest and the recorded baseline\n");
exit(0);

/**
 * @return array<string, list<string>>
 */
function runGuard(): array
{
    $lines = [];
    $status = 0;
    exec('mago guard ' . CORPUS_PATH . ' --reporting-format=json 2>&1', $lines, $status);

    $raw = implode("\n", $lines);

    if (str_contains($raw, 'No files found to check with guard')) {
        fail('guard reported no files; the corpus is missing or excluded from the source set');
    }

    $offset = null;
    $cursor = 0;
    foreach (explode("\n", $raw) as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '{')) {
            $offset = $cursor + (strlen($line) - strlen($trimmed));
            break;
        }
        $cursor += strlen($line) + 1;
    }

    if ($offset === null) {
        fail("guard produced no JSON object:\n{$raw}");
    }

    try {
        $decoded = json_decode(substr($raw, $offset), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail("could not decode guard output: {$exception->getMessage()}\n{$raw}");
    }

    $verdicts = [];
    foreach ($decoded['issues'] ?? [] as $issue) {
        $name = $issue['annotations'][0]['span']['file_id']['name'] ?? null;
        if (!is_string($name)) {
            fail('a guard issue carried no file annotation');
        }
        $file = str_replace('\\', '/', $name);
        $file = preg_replace('#^\./#', '', $file);
        $verdicts[$file][] = (string) $issue['code'];
    }

    foreach ($verdicts as $file => $codes) {
        sort($codes);
        $verdicts[$file] = array_values(array_unique($codes));
    }
    ksort($verdicts);

    return $verdicts;
}

/**
 * @param array<string, list<string>> $verdicts
 */
function record(string $path, array $verdicts): void
{
    $version = trim((string) shell_exec('mago --version 2>/dev/null'));

    $payload = [
        'description' => 'Verdicts recorded from the unmodified, enumerated guard configuration. check.php asserts these against expectBefore.',
        'mago' => $version,
        'command' => 'mago guard ' . CORPUS_PATH . ' --reporting-format=json',
        'config' => 'mago.toml before the root-agnostic transformation',
        'verdicts' => $verdicts,
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    file_put_contents($path, $json);

    printf("recorded %d files to %s using %s\n", count($verdicts), $path, $version);
}

/**
 * @param list<string> $want
 * @param list<string> $got
 *
 * @return list<string>
 */
function multisetDiff(array $want, array $got): array
{
    $left = array_count_values($want);
    $right = array_count_values($got);
    $out = [];
    foreach ($left as $code => $count) {
        $surplus = $count - ($right[$code] ?? 0);
        for ($i = 0; $i < $surplus; $i++) {
            $out[] = (string) $code;
        }
    }
    sort($out);

    return $out;
}

/**
 * @param list<string> $codes
 */
function render(array $codes): string
{
    return $codes === [] ? 'clean' : implode(', ', $codes);
}

/**
 * @param array{cases: list<array{file: string}>} $manifest
 */
function hasCase(array $manifest, string $file): bool
{
    foreach ($manifest['cases'] as $case) {
        if ($case['file'] === $file) {
            return true;
        }
    }

    return false;
}

/**
 * Asserts that every guard block in `mago.toml` is exercised by the corpus.
 *
 * The block list is read from the preset, never from a list kept here: a hand-maintained list of
 * covered rules is the same defect as no check at all, because it is updated by the same edit that
 * forgets the fixture. A block counts as exercised when some fixture reaches the signal the block
 * acts on, which is the closest thing the corpus has to watching the rule decide:
 *
 *   - a structural rule matches the declared symbol name of a fixture, after `not-on` and `target`
 *     have had their say;
 *   - a perimeter restriction's `dependency` is referenced by a fixture, and that fixture's own
 *     namespace is then classified by `deny-from` or `allow-from`. A deny list is proven by a
 *     denied reference, because a deny that never fires is the thing that goes unnoticed. An allow
 *     list is proven twice over: once by a reference it permits, which is the outcome the list
 *     exists to produce, and once by a reference it refuses by omission, because a list that
 *     permits everything it is shown is indistinguishable from no list at all.
 *
 * @param array{cases: list<array{file: string, id: string}>} $manifest
 */
function checkCoverage(string $root, array $manifest, bool $report): int
{
    $preset = loadPreset($root . '/mago.toml');
    if ($preset['structural'] === [] && $preset['perimeter'] === []) {
        fwrite(STDERR, "FAIL could not read any guard block from mago.toml; the coverage assertion cannot run\n");

        return 1;
    }

    $parsed = count($preset['structural']) + count($preset['perimeter']);
    if ($parsed !== $preset['declared']) {
        fwrite(STDERR, sprintf(
            "FAIL mago.toml declares %d guard block(s) but only %d could be read; a block the reader cannot parse is a block this check cannot hold to a fixture\n",
            $preset['declared'],
            $parsed,
        ));

        return 1;
    }

    $fixtures = [];
    foreach ($manifest['cases'] as $case) {
        $fixtures[$case['id']] = inspectFixture($root . '/' . $case['file'], $case['file']);
    }

    $failures = 0;
    $rows = [];

    foreach ($preset['structural'] as $rule) {
        $selector = (string) ($rule['on'] ?? '');
        $notOn = is_string($rule['not-on'] ?? null) ? $rule['not-on'] : null;
        $target = is_string($rule['target'] ?? null) ? $rule['target'] : null;

        if ($selector === '') {
            $failures++;
            print("FAIL a structural rule carries no `on` selector the reader could read\n");
            continue;
        }

        $proven = [];
        foreach ($fixtures as $id => $fixture) {
            if ($fixture['fqcn'] === '') {
                continue;
            }
            if ($target !== null && $fixture['kind'] !== $target) {
                continue;
            }
            if (!globMatches($selector, $fixture['fqcn'])) {
                continue;
            }
            if ($notOn !== null && globMatches($notOn, $fixture['fqcn'])) {
                continue;
            }
            $proven[] = $id;
        }

        $rows[] = sprintf(
            'structural  %-52s %3d fixture(s)%s',
            renderSelector($selector, $target),
            count($proven),
            $proven === [] ? '' : '  ' . implode(', ', array_slice($proven, 0, 4)) . (count($proven) > 4 ? ', ...' : ''),
        );

        if ($proven === []) {
            $failures++;
            printf("FAIL no fixture exercises the structural rule on = \"%s\"\n", $selector);
            printf("     %s\n", $target === null ? 'no target restriction' : "target = \"{$target}\"");
            printf("     no fixture declares a symbol matched by that selector after `not-on` is applied\n");
        }
    }

    foreach ($preset['perimeter'] as $block) {
        $dependency = (string) ($block['dependency'] ?? '');
        $allow = is_array($block['allow-from'] ?? null) ? $block['allow-from'] : [];
        $deny = is_array($block['deny-from'] ?? null) ? $block['deny-from'] : [];

        if ($dependency === '') {
            $failures++;
            print("FAIL a perimeter restriction carries no `dependency` the reader could read\n");
            continue;
        }

        $references = 0;
        $permitted = [];
        $denied = [];
        $omitted = [];
        foreach ($fixtures as $id => $fixture) {
            $referencesDependency = false;
            foreach ($fixture['references'] as $reference) {
                if (globMatches($dependency, $reference)) {
                    $referencesDependency = true;
                    break;
                }
            }
            if (!$referencesDependency) {
                continue;
            }
            $references++;

            $deniedHere = false;
            foreach ($deny as $pattern) {
                if (globMatches($pattern, $fixture['namespace'])) {
                    $deniedHere = true;
                    break;
                }
            }
            if ($deniedHere) {
                $denied[] = $id;
                continue;
            }
            if ($allow === []) {
                continue;
            }
            $permittedHere = false;
            foreach ($allow as $pattern) {
                if (globMatches($pattern, $fixture['namespace'])) {
                    $permittedHere = true;
                    break;
                }
            }
            if ($permittedHere) {
                $permitted[] = $id;
            } else {
                $omitted[] = $id;
            }
        }

        $rows[] = sprintf(
            'perimeter   %-52s refs %3d, allow %3d, deny %3d, omitted %3d',
            renderSelector($dependency, null),
            $references,
            count($permitted),
            count($denied),
            count($omitted),
        );

        if ($references === 0) {
            $failures++;
            printf("FAIL no fixture references the perimeter dependency \"%s\"\n", $dependency);
            printf("     the restriction cannot be observed; no fixture imports or extends that symbol\n");
            continue;
        }

        if ($allow !== [] && $permitted === []) {
            $failures++;
            printf("FAIL no fixture is permitted by the allow-from list of \"%s\"\n", $dependency);
            printf("     allow-from = [%s]\n", implode(', ', array_map(static fn (string $p): string => "\"{$p}\"", $allow)));
            printf("     %d fixture(s) reference the dependency, none from a namespace the list permits\n", $references);
        }

        if ($allow !== [] && $omitted === []) {
            $failures++;
            printf("FAIL no fixture is refused by the allow-from list of \"%s\"\n", $dependency);
            printf("     allow-from = [%s]\n", implode(', ', array_map(static fn (string $p): string => "\"{$p}\"", $allow)));
            printf("     every fixture that references the dependency is permitted, so nothing proves the list refuses\n");
        }

        if ($deny !== [] && $denied === []) {
            $failures++;
            printf("FAIL no fixture is denied by the deny-from list of \"%s\"\n", $dependency);
            printf("     deny-from = [%s]\n", implode(', ', array_map(static fn (string $p): string => "\"{$p}\"", $deny)));
            printf("     %d fixture(s) reference the dependency, none from a namespace the list denies\n", $references);
        }
    }

    if ($report) {
        printf("\ncoverage, %d structural and %d perimeter block(s) in mago.toml\n\n", count($preset['structural']), count($preset['perimeter']));
        foreach ($rows as $row) {
            printf("  %s\n", $row);
        }
        print("\n");
    }

    return $failures;
}

function renderSelector(string $selector, ?string $target): string
{
    $rendered = $selector . ($target === null ? '' : " [{$target}]");

    return strlen($rendered) > 52 ? substr($rendered, 0, 49) . '...' : $rendered;
}

/**
 * Reads the two guard block kinds out of the preset. The value grammar here is deliberately small:
 * quoted strings, integer and boolean scalars, and string arrays. Anything else is kept verbatim.
 *
 * `declared` is the number of block headers in the file, counted textually, so the caller can fail
 * when the reader understood fewer blocks than the preset contains. A block this reader skips would
 * otherwise be a block no fixture is required to exercise, which is the defect the check exists for.
 *
 * @return array{structural: list<array<string, string|list<string>|bool|int>>, perimeter: list<array<string, string|list<string>|bool|int>>, declared: int}
 */
function loadPreset(string $path): array
{
    $text = (string) file_get_contents($path);
    $lines = explode("\n", $text);

    $blocks = ['structural' => [], 'perimeter' => []];
    $current = null;
    $section = null;
    $key = null;
    $buffer = null;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($current !== null && $buffer === null && preg_match('/^\[\[(guard\.(?:structural\.rules|perimeter\.restrictions))\]\]$/', $trimmed, $match) === 1) {
            $blocks[$section][] = $current;
            $current = [];
            $section = str_contains($match[1], 'perimeter') ? 'perimeter' : 'structural';
            $key = null;
            continue;
        }

        if ($current === null) {
            if (preg_match('/^\[\[(guard\.(?:structural\.rules|perimeter\.restrictions))\]\]$/', $trimmed, $match) === 1) {
                $current = [];
                $section = str_contains($match[1], 'perimeter') ? 'perimeter' : 'structural';
            }
            continue;
        }

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        if ($buffer !== null) {
            $buffer .= ' ' . $trimmed;
            if (substr_count($buffer, '[') <= substr_count($buffer, ']')) {
                $current[$key] = parseValue($buffer);
                $buffer = null;
                $key = null;
            }
            continue;
        }

        if (preg_match('/^([A-Za-z0-9_-]+)\s*=\s*(.+)$/', $trimmed, $match) !== 1) {
            continue;
        }

        $key = $match[1];
        $value = stripInlineComment($match[2]);

        if (str_starts_with($value, '[') && substr_count($value, '[') > substr_count($value, ']')) {
            $buffer = $value;
            continue;
        }

        $current[$key] = parseValue($value);
        $key = null;
    }

    if ($current !== null) {
        $blocks[$section][] = $current;
    }

    $declared = preg_match_all('/^\[\[guard\.(?:structural\.rules|perimeter\.restrictions)\]\]$/m', $text);

    return $blocks + ['declared' => (int) $declared];
}

/**
 * @return string|list<string>|bool|int
 */
function parseValue(string $raw): string|array|bool|int
{
    $value = trim($raw);

    if (str_starts_with($value, '[')) {
        $items = [];
        if (preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $value, $matches) > 0) {
            foreach ($matches[1] as $item) {
                $items[] = unescapeToml($item);
            }
        }

        return $items;
    }

    if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
        return unescapeToml(substr($value, 1, -1));
    }

    return match ($value) {
        'true' => true,
        'false' => false,
        default => is_numeric($value) ? (int) $value : $value,
    };
}

function unescapeToml(string $value): string
{
    return str_replace(['\\\\', '\\"'], ['\\', '"'], $value);
}

function stripInlineComment(string $value): string
{
    $quoted = false;
    $length = strlen($value);
    for ($i = 0; $i < $length; $i++) {
        $char = $value[$i];
        if ($char === '"' && ($i === 0 || $value[$i - 1] !== '\\')) {
            $quoted = !$quoted;
            continue;
        }
        if ($char === '#' && !$quoted) {
            return trim(substr($value, 0, $i));
        }
    }

    return trim($value);
}

/**
 * @return array{namespace: string, kind: string, name: string, fqcn: string, references: list<string>}
 */
function inspectFixture(string $path, string $relative): array
{
    $source = (string) file_get_contents($path);

    $namespace = '';
    if (preg_match('/^namespace\s+([^;]+);/m', $source, $match) === 1) {
        $namespace = trim($match[1]);
    }

    $imports = [];
    if (preg_match_all('/^use\s+(?!function\s|const\s)([^;]+);/m', $source, $match, PREG_SET_ORDER) > 0) {
        foreach ($match as $use) {
            $clause = trim($use[1]);
            if (preg_match('/^([A-Za-z0-9_\\\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?$/', $clause, $inner) !== 1) {
                continue;
            }
            $name = ltrim($inner[1], '\\');
            $alias = $inner[2] ?? (str_contains($name, '\\') ? substr($name, strrpos($name, '\\') + 1) : $name);
            $imports[$alias] = $name;
        }
    }

    $kind = '';
    $symbol = '';
    $clause = '';
    if (preg_match('/^[ \t]*(?:(?:final|abstract|readonly)\s+)*(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)([^{]*)/m', $source, $match) === 1) {
        $kind = $match[1];
        $symbol = $match[2];
        $clause = $match[3];
    }

    if ($symbol === '') {
        fwrite(STDERR, "WARN {$relative} declares no class, interface, trait or enum\n");

        return ['namespace' => $namespace, 'kind' => '', 'name' => '', 'fqcn' => '', 'references' => array_values($imports)];
    }

    $references = array_values($imports);
    if (preg_match_all('/(?:implements|extends)\s+([A-Za-z0-9_\\\\,\s]+)/', $clause, $match) > 0) {
        foreach ($match[1] as $list) {
            foreach (explode(',', $list) as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
                if (str_contains($item, '\\')) {
                    $references[] = ltrim($item, '\\');
                    continue;
                }
                $references[] = $imports[$item] ?? ($namespace === '' ? $item : $namespace . '\\' . $item);
            }
        }
    }

    return [
        'namespace' => $namespace,
        'kind' => $kind,
        'name' => $symbol,
        'fqcn' => $namespace === '' ? $symbol : $namespace . '\\' . $symbol,
        'references' => array_values(array_unique($references)),
    ];
}

function globMatches(string $pattern, string $subject): bool
{
    return preg_match(globToRegex($pattern), $subject) === 1;
}

/**
 * Translates a guard selector into an anchored regular expression.
 *
 * `*` matches one segment and may match an empty run, which is why `*Event` also accepts `Event`.
 * `**` matches zero or more segments, so it owns the separators around itself: `**\Http\**\Middleware\*`
 * has to accept `App\Http\Middleware\Foo` with the middle `**` matching nothing, which a naive
 * translation of the two separators into one regex cannot do.
 */
function globToRegex(string $pattern): string
{
    $segments = splitSegments($pattern);
    $last = count($segments) - 1;
    $regex = '';

    foreach ($segments as $index => $segment) {
        $previous = $index === 0 ? null : $segments[$index - 1];
        $isLast = $index === $last;

        if ($segment === '**') {
            if ($previous === null) {
                $regex .= $isLast ? '(?:[^\\\\]+(?:\\\\[^\\\\]+)*)?' : '(?:[^\\\\]+\\\\)*';
            } else {
                $regex .= $isLast ? '(?:\\\\[^\\\\]+)*' : '(?:\\\\[^\\\\]+)*\\\\';
            }
            continue;
        }

        if ($previous !== null && $previous !== '**') {
            $regex .= '\\\\';
        }

        $regex .= segmentRegex($segment);
    }

    return '#^' . $regex . '$#';
}

/**
 * @return list<string>
 */
function splitSegments(string $pattern): array
{
    $segments = [];
    $buffer = '';
    $depth = 0;
    $length = strlen($pattern);

    for ($i = 0; $i < $length; $i++) {
        $char = $pattern[$i];
        if ($char === '{') {
            $depth++;
        } elseif ($char === '}') {
            $depth--;
        } elseif ($char === '\\' && $depth === 0) {
            $segments[] = $buffer;
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    $segments[] = $buffer;

    return $segments;
}

function segmentRegex(string $segment): string
{
    $regex = '';
    $length = strlen($segment);

    for ($i = 0; $i < $length; $i++) {
        $char = $segment[$i];
        if ($char === '{') {
            [$inner, $i] = readBrace($segment, $i);
            $alternatives = [];
            foreach (explode(',', $inner) as $alternative) {
                $alternatives[] = segmentRegex($alternative);
            }
            $regex .= '(?:' . implode('|', $alternatives) . ')';
            continue;
        }
        if ($char === '*') {
            $regex .= '[^\\\\]*';
            continue;
        }
        if ($char === '\\') {
            $regex .= '\\\\';
            continue;
        }
        $regex .= preg_quote($char, '#');
    }

    return $regex;
}

/**
 * @return array{0: string, 1: int} the brace body and the index just past its closing brace
 */
function readBrace(string $segment, int $start): array
{
    $depth = 0;
    $body = '';
    $length = strlen($segment);

    for ($i = $start; $i < $length; $i++) {
        $char = $segment[$i];
        if ($char === '{') {
            $depth++;
            if ($depth === 1) {
                continue;
            }
        } elseif ($char === '}') {
            $depth--;
            if ($depth === 0) {
                return [$body, $i];
            }
        }
        $body .= $char;
    }

    return [$body, $length];
}

function fail(string $message): never
{
    fwrite(STDERR, "FAIL {$message}\n");
    exit(1);
}
