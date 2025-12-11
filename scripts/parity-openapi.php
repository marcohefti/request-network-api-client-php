#!/usr/bin/env php
<?php

declare(strict_types=1);

// OpenAPI parity guard: compares operationIds in the synced spec vs. the
// canonical registry in src/Validation/Operations.php.

$root = dirname(__DIR__);
$specPath = $root . '/specs/openapi/request-network-openapi.json';
$opsFile = $root . '/src/Validation/Operations.php';

if (!file_exists($specPath)) {
    fwrite(STDOUT, "ℹ️  OpenAPI spec not found at specs/openapi/… — skipping parity.\n");
    exit(0);
}

if (!file_exists($opsFile)) {
    fwrite(STDOUT, "ℹ️  Operations registry not found — skipping parity.\n");
    exit(0);
}

$raw = file_get_contents($specPath);
if ($raw === false) {
    fwrite(STDERR, "❌ Failed to read OpenAPI spec.\n");
    exit(1);
}

$spec = json_decode($raw, true);
if (!is_array($spec)) {
    fwrite(STDERR, "❌ OpenAPI spec is not valid JSON.\n");
    exit(1);
}

$methods = ['get','post','put','patch','delete','options','head'];
$specOps = [];
foreach (($spec['paths'] ?? []) as $pathItem) {
    if (!is_array($pathItem)) continue;
    foreach ($methods as $m) {
        if (!isset($pathItem[$m]) || !is_array($pathItem[$m])) continue;
        $opId = $pathItem[$m]['operationId'] ?? null;
        if (is_string($opId) && $opId !== '') {
            $specOps[] = $opId;
        }
    }
}
$specOps = array_values(array_unique($specOps));

$opsSource = file_get_contents($opsFile) ?: '';
// Match: const NAME = 'value';
preg_match_all('/const\s+[A-Z0-9_]+\s*=\s*([\'\"])(.*?)\1\s*;/', $opsSource, $m);
$phpOps = array_values(array_unique($m[2] ?? []));

if (count($phpOps) === 0) {
    fwrite(STDOUT, "ℹ️  Operations registry empty — skipping parity.\n");
    exit(0);
}

$missing = array_values(array_diff($specOps, $phpOps));
$extra   = array_values(array_diff($phpOps, $specOps));

if ($missing || $extra) {
    fwrite(STDERR, "❌ OpenAPI parity drift detected\n");
    if ($missing) {
        fwrite(STDERR, "Missing (in PHP):\n  - " . implode("\n  - ", $missing) . "\n");
    }
    if ($extra) {
        fwrite(STDERR, "Extra (in PHP only):\n  + " . implode("\n  + ", $extra) . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "✅ OpenAPI parity OK (" . count($phpOps) . " ops)\n");
exit(0);
