import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';

const root = new URL('..', import.meta.url).pathname;
const bad = [
  { name: 'eval', re: /\beval\s*\(/ },
  { name: 'assert-string', re: /\bassert\s*\(\s*\$/ },
  { name: 'create_function', re: /\bcreate_function\s*\(/ },
  { name: 'raw-extract', re: /\bextract\s*\(\s*\$_(GET|POST|REQUEST)/ },
];
let failed = 0;

function walk(dir, out = []) {
  for (const entry of readdirSync(dir)) {
    if (entry === 'node_modules' || entry === 'vendor' || entry === 'tests') continue;
    const path = join(dir, entry);
    const stat = statSync(path);
    if (stat.isDirectory()) walk(path, out);
    else if (path.endsWith('.php')) out.push(path);
  }
  return out;
}

const files = walk(root);
if (files.length < 40) {
  console.error('FAIL expected a full theme, found', files.length, 'php files');
  process.exit(1);
}
console.log('PASS php file count', files.length);

for (const file of files) {
  const text = readFileSync(file, 'utf8');
  for (const rule of bad) {
    if (rule.re.test(text)) {
      console.error('FAIL', rule.name, file);
      failed += 1;
    }
  }
  if (/echo\s+\$_(GET|POST|REQUEST)/.test(text)) {
    console.error('FAIL raw echo superglobal', file);
    failed += 1;
  }
}

const style = readFileSync(join(root, 'style.css'), 'utf8');
for (const needle of ['Theme Name: TajiksWorld', 'Text Domain: tajiksworld', 'Requires PHP: 8.2']) {
  if (!style.includes(needle)) {
    console.error('FAIL style.css missing', needle);
    failed += 1;
  } else {
    console.log('PASS', needle);
  }
}

console.log(failed ? `FAILED ${failed}` : 'ALL PASSED');
process.exit(failed ? 1 : 0);
