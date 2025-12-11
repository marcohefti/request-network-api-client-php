#!/usr/bin/env node
import fs from 'node:fs';
import fsp from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PKG_DIR = path.resolve(__dirname, '..');
const OUT_DIR = path.join(PKG_DIR, 'specs');

function resolveContractsDir() {
  // Prefer workspace path to avoid Node resolution ambiguity.
  const workspaceCandidates = [
    path.resolve(PKG_DIR, '..', 'request-client-contracts'),
    path.resolve(PKG_DIR, '..', '..', 'request-client-contracts'),
  ];

  for (const candidate of workspaceCandidates) {
    if (fs.existsSync(candidate)) return candidate;
  }

  // Fallback to Node resolution if installed as a dependency.
  try {
    const pkgPath = require.resolve('@marcohefti/request-network-api-contracts/package.json', { paths: [PKG_DIR] });
    return path.dirname(pkgPath);
  } catch {
    return null;
  }
}

async function copyTree(src, dest) {
  await fsp.mkdir(dest, { recursive: true });
  const entries = await fsp.readdir(src, { withFileTypes: true });
  for (const entry of entries) {
    const s = path.join(src, entry.name);
    const d = path.join(dest, entry.name);
    if (entry.isDirectory()) {
      await copyTree(s, d);
    } else if (entry.isFile()) {
      await fsp.mkdir(path.dirname(d), { recursive: true });
      await fsp.copyFile(s, d);
    }
  }
}

function sha256FileSync(filePath) {
  const buf = fs.readFileSync(filePath);
  return crypto.createHash('sha256').update(buf).digest('hex');
}

async function main() {
  const contractsDir = resolveContractsDir();
  if (!contractsDir) {
    console.error('❌ Could not resolve @marcohefti/request-network-api-contracts');
    process.exit(1);
  }

  const sources = [
    { src: path.join(contractsDir, 'specs', 'openapi'), dest: path.join(OUT_DIR, 'openapi') },
    { src: path.join(contractsDir, 'specs', 'webhooks'), dest: path.join(OUT_DIR, 'webhooks') },
    { src: path.join(contractsDir, 'fixtures', 'webhooks'), dest: path.join(OUT_DIR, 'fixtures', 'webhooks') },
  ];

  for (const { src, dest } of sources) {
    if (!fs.existsSync(src)) continue;
    await copyTree(src, dest);
  }

  // Write meta with checksums for quick drift checks
  const meta = { generatedAt: new Date().toISOString(), files: {} };
  for (const { dest } of sources) {
    if (!fs.existsSync(dest)) continue;
    const stack = [dest];
    while (stack.length) {
      const cur = stack.pop();
      const entries = fs.readdirSync(cur, { withFileTypes: true });
      for (const e of entries) {
        const full = path.join(cur, e.name);
        if (e.isDirectory()) stack.push(full);
        else if (e.isFile()) meta.files[path.relative(OUT_DIR, full)] = sha256FileSync(full);
      }
    }
  }

  await fsp.mkdir(OUT_DIR, { recursive: true });
  await fsp.writeFile(path.join(OUT_DIR, 'meta.json'), JSON.stringify(meta, null, 2) + '\n');
  console.log(`✅ Synced contracts -> ${path.relative(PKG_DIR, OUT_DIR)}`);
}

main().catch((err) => {
  console.error('❌ Failed to sync contracts:', err?.message || err);
  process.exit(1);
});
