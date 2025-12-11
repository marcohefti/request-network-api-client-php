#!/usr/bin/env php
<?php

declare(strict_types=1);

// Webhook parity guard: compares webhook fixture names vs. implemented event classes.

function pascal_case(string $kebab): string {
    $parts = preg_split('/[^a-zA-Z0-9]+/', $kebab) ?: [];
    $parts = array_map(fn($p) => ucfirst(strtolower($p)), array_filter($parts, fn($p) => $p !== ''));
    return implode('', $parts);
}

$root = dirname(__DIR__);
$fixturesDir = $root . '/specs/fixtures/webhooks';
$eventsDir   = $root . '/src/Webhooks/Events';

if (!is_dir($fixturesDir)) {
    fwrite(STDOUT, "ℹ️  Webhook fixtures not found — skipping parity.\n");
    exit(0);
}

$expected = [];
$dh = opendir($fixturesDir);
if ($dh === false) {
    fwrite(STDERR, "❌ Unable to read fixtures directory.\n");
    exit(1);
}
while (($file = readdir($dh)) !== false) {
    if ($file === '.' || $file === '..') continue;
    if (!str_ends_with($file, '.json')) continue;
    $base = substr($file, 0, -5);
    $expected[] = pascal_case($base) . 'Event';
}
closedir($dh);
sort($expected);

// Implemented events are discovered by filename (PSR-4 name = filename)
$implemented = [];
if (is_dir($eventsDir)) {
    $dh = opendir($eventsDir);
    while ($dh && ($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;
        if (!str_ends_with($file, '.php')) continue;
        $class = substr($file, 0, -4);
        if ($class === 'UnknownEvent') continue;
        $implemented[] = $class;
    }
    if ($dh) closedir($dh);
}
sort($implemented);

if (count($implemented) === 0) {
    fwrite(STDOUT, "ℹ️  No webhook event classes present — skipping parity.\n");
    exit(0);
}

$expectedOnly = array_values(array_diff($expected, $implemented));
$extraOnly    = array_values(array_diff($implemented, $expected));

if ($expectedOnly || $extraOnly) {
    fwrite(STDERR, "❌ Webhook parity drift detected\n");
    if ($expectedOnly) {
        fwrite(STDERR, "Missing events: \n  - " . implode("\n  - ", $expectedOnly) . "\n");
    }
    if ($extraOnly) {
        fwrite(STDERR, "Extra events: \n  + " . implode("\n  + ", $extraOnly) . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "✅ Webhook parity OK (" . count($implemented) . " events)\n");
exit(0);

