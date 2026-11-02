import { spawnSync } from 'node:child_process';
import { readFileSync, statSync } from 'node:fs';

let failed = 0;
const assert = (ok, message) => {
  console.log(`${ok ? 'PASS' : 'FAIL'} ${message}`);
  if (!ok) failed += 1;
};

for (const file of ['resources/js/theme.js', 'resources/js/builder.js', 'resources/js/admin.js', 'bin/build-js.mjs']) {
  const check = spawnSync(process.execPath, ['--check', file], { encoding: 'utf8' });
  assert(check.status === 0, `syntax ${file}`);
}

const builder = readFileSync('resources/js/builder.js', 'utf8');
assert(builder.includes("'&': '&'"), 'builder escapes ampersands');
assert(builder.includes("'<': '<'"), 'builder escapes markup');
assert(builder.includes('function reidentifySection'), 'section copies receive new ids');
assert(!builder.includes('TODO') && !builder.includes('FIXME'), 'builder has no unfinished markers');

const css = statSync('assets/css/theme.css');
const js = statSync('assets/js/builder.min.js');
assert(css.size > 8000, `theme.css is built (${css.size} bytes)`);
assert(js.size > 2000, `builder.min.js is built (${js.size} bytes)`);

const scan = spawnSync(process.execPath, ['tests/security-scan.mjs'], { encoding: 'utf8' });
process.stdout.write(scan.stdout || '');
process.stderr.write(scan.stderr || '');
assert(scan.status === 0, 'security scan');

console.log(failed ? `FAILED ${failed}` : 'ALL PASSED');
process.exit(failed ? 1 : 0);
