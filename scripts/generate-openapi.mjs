#!/usr/bin/env node

import { mkdir, readFile, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import path from "node:path";

const SCRIPT_DIR = path.dirname(fileURLToPath(new URL(import.meta.url)));
const PKG_ROOT = path.resolve(SCRIPT_DIR, "..");
const SPEC_PATH = path.join(PKG_ROOT, "specs", "openapi", "request-network-openapi.json");
const GENERATED_DIR = path.join(PKG_ROOT, "generated");
const SCHEMA_DIR = path.join(GENERATED_DIR, "Validation", "Schemas");
const MANIFEST_PATH = path.join(SCHEMA_DIR, "index.json");
const OPERATIONS_PATH = path.join(GENERATED_DIR, "OpenApi", "Operations.php");

const HTTP_METHODS = ["get", "post", "put", "patch", "delete", "options", "head"];

function encodePointerSegment(segment) {
  return String(segment).replace(/~/g, "~0").replace(/\//g, "~1");
}

function isJsonMediaType(mediaType = "") {
  const value = mediaType.toLowerCase();
  return value.includes("json");
}

function pointerForResponse(apiPath, method, statusCode, mediaType) {
  return `/paths/${encodePointerSegment(apiPath)}/${method}/responses/${encodePointerSegment(statusCode)}/content/${encodePointerSegment(mediaType)}/schema`;
}

function pointerForRequest(apiPath, method, mediaType) {
  return `/paths/${encodePointerSegment(apiPath)}/${method}/requestBody/content/${encodePointerSegment(mediaType)}/schema`;
}

function normaliseStatus(statusCode) {
  const numeric = Number(statusCode);
  return Number.isFinite(numeric) ? numeric : null;
}

function addStatus(target, status) {
  if (status === null) {
    return;
  }

  if (!target.includes(status)) {
    target.push(status);
  }
}

function phpExport(value, indent = 0) {
  const pad = "    ".repeat(indent);
  if (value === null) {
    return "null";
  }

  if (typeof value === "boolean") {
    return value ? "true" : "false";
  }

  if (typeof value === "number") {
    return Number.isFinite(value) ? String(value) : "0";
  }

  if (typeof value === "string") {
    return `'${value.replace(/\\/g, "\\\\").replace(/'/g, "\\'")}'`;
  }

  if (Array.isArray(value)) {
    if (value.length === 0) {
      return "[]";
    }

    const items = value.map((item) => `${"    ".repeat(indent + 1)}${phpExport(item, indent + 1)}`);
    return `[
${items.join(",\n")}
${pad}]`;
  }

  if (typeof value === "object") {
    const entries = Object.entries(value);
    if (entries.length === 0) {
      return "[]";
    }

    const lines = entries.map(([key, val]) => `${"    ".repeat(indent + 1)}'${key}' => ${phpExport(val, indent + 1)}`);
    return `[
${lines.join(",\n")}
${pad}]`;
  }

  return "null";
}

async function ensureDir(dir) {
  await mkdir(dir, { recursive: true });
}

async function generate() {
  const specRaw = await readFile(SPEC_PATH, "utf8");
  const spec = JSON.parse(specRaw);
  const manifest = {
    generatedAt: new Date().toISOString(),
    specPath: path.relative(PKG_ROOT, SPEC_PATH),
    entries: [],
  };

  const operations = {};

  for (const [apiPath, pathItem] of Object.entries(spec.paths ?? {})) {
    for (const method of HTTP_METHODS) {
      const operation = pathItem?.[method];
      if (!operation || !operation.operationId) {
        continue;
      }

      const operationId = operation.operationId;
      const upperMethod = method.toUpperCase();
      const tags = Array.isArray(operation.tags) ? operation.tags : [];

      if (!operations[operationId]) {
        operations[operationId] = {
          method: upperMethod,
          path: apiPath,
          tags,
          summary: operation.summary ?? null,
          hasJsonRequest: false,
          successStatuses: [],
          errorStatuses: [],
        };
      }

      const requestContent = operation.requestBody?.content ?? {};
      for (const [mediaType, descriptor] of Object.entries(requestContent)) {
        if (!isJsonMediaType(mediaType) || !descriptor?.schema) {
          continue;
        }

        manifest.entries.push({
          key: {
            operationId,
            kind: "request",
            variant: mediaType,
          },
          pointer: pointerForRequest(apiPath, method, mediaType),
        });

        operations[operationId].hasJsonRequest = true;
      }

      const responses = operation.responses ?? {};
      for (const [statusCode, response] of Object.entries(responses)) {
        const statusValue = normaliseStatus(statusCode);
        const targetArray = statusValue !== null && statusValue >= 200 && statusValue < 400
          ? operations[operationId].successStatuses
          : operations[operationId].errorStatuses;

        addStatus(targetArray, statusValue);

        const content = response?.content ?? {};
        for (const [mediaType, descriptor] of Object.entries(content)) {
          if (!isJsonMediaType(mediaType) || !descriptor?.schema) {
            continue;
          }

          manifest.entries.push({
            key: {
              operationId,
              kind: statusValue !== null && statusValue >= 400 ? "error" : "response",
              variant: mediaType,
              status: statusValue,
            },
            pointer: pointerForResponse(apiPath, method, statusCode, mediaType),
          });
        }
      }
    }
  }

  manifest.entries.sort((a, b) => {
    const left = a.key.operationId.localeCompare(b.key.operationId);
    if (left !== 0) {
      return left;
    }

    const kindCompare = (a.key.kind ?? '').localeCompare(b.key.kind ?? '');
    if (kindCompare !== 0) {
      return kindCompare;
    }

    const statusA = a.key.status ?? 0;
    const statusB = b.key.status ?? 0;
    return statusA - statusB;
  });

  const operationsPhp = `<?php

declare(strict_types=1);

namespace RequestSuite\\RequestPhpClient\\Generated\\OpenApi;

final class Operations
{
    public const DATA = ${phpExport(operations, 2)};

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::DATA;
    }
}
`;

  await ensureDir(path.dirname(MANIFEST_PATH));
  await ensureDir(path.dirname(OPERATIONS_PATH));

  await writeFile(MANIFEST_PATH, `${JSON.stringify(manifest, null, 2)}\n`);
  await writeFile(OPERATIONS_PATH, `${operationsPhp}`);

  console.log(`✅ Generated schema manifest (${manifest.entries.length} entries)`);
  console.log(`✅ Generated operations manifest at ${path.relative(PKG_ROOT, OPERATIONS_PATH)}`);
}

generate().catch((error) => {
  console.error("❌ Failed to generate OpenAPI artifacts", error);
  process.exitCode = 1;
});
