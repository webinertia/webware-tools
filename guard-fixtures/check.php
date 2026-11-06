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
 *     php guard-fixtures/check.php --record=FILE   write the observed verdicts to FILE
 *
 * A comparison run asserts three things.
 *
 *   1. The observed verdicts equal `expectAfter` for every case. This is the shipped
 *      configuration's contract.
 *   2. `baseline.json` equals `expectBefore` for every case. This pins the record taken from
 *      the unmodified, enumerated configuration, so a later edit cannot quietly rewrite
 *      history to match a regression.
 *   3. No fixture file is left out of the manifest and no manifest case is missing a file.
 *
 * The corpus is a cross-root matrix: the same boundary is exercised from `App\`, `Webware\`,
 * a real fleet root that was never enumerated, and a root that does not exist in the fleet.
 * That is what makes the superset claim falsifiable rather than asserted.
 */

const CORPUS_PATH = 'guard-fixtures/cases';

$root = dirname(__DIR__);
chdir($root);

$recordTo = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--record=')) {
        $recordTo = substr($argument, strlen('--record='));
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

function fail(string $message): never
{
    fwrite(STDERR, "FAIL {$message}\n");
    exit(1);
}
