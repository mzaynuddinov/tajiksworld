import { readFileSync, writeFileSync, readdirSync, statSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';

const root = new URL('..', import.meta.url).pathname;
const map = JSON.parse(readFileSync(new URL('./i18n-map.json', import.meta.url), 'utf8'));

function walk(dir, out = []) {
  for (const entry of readdirSync(dir)) {
    if (entry === 'node_modules' || entry === 'vendor' || entry === 'languages') continue;
    const path = join(dir, entry);
    if (statSync(path).isDirectory()) walk(path, out);
    else if (path.endsWith('.php')) out.push(path);
  }
  return out;
}

function unescape(value) {
  return value.replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\'/g, "'");
}

const singular = new Set();
const foundPlurals = [];
const call = /(?:esc_html_e|esc_attr_e|esc_html__|esc_attr__|__|_e|_x|_n)\(\s*(['"])((?:\\.|(?!\1).)*)\1/gs;
const plural = /_n\(\s*(['"])((?:\\.|(?!\1).)*)\1\s*,\s*(['"])((?:\\.|(?!\3).)*)\3/gs;

for (const file of walk(root)) {
  const text = readFileSync(file, 'utf8');
  for (const match of text.matchAll(call)) {
    const after = text.slice(match.index, match.index + match[0].length + 120);
    if (after.includes("'tajiksworld'") || after.includes('"tajiksworld"')) {
      singular.add(unescape(match[2]));
    }
  }
  for (const match of text.matchAll(plural)) {
    const after = text.slice(match.index, match.index + match[0].length + 80);
    if (after.includes('tajiksworld')) {
      foundPlurals.push([unescape(match[2]), unescape(match[4])]);
    }
  }
}

const pluralPairs = [];
const seenPlural = new Set();
for (const pair of foundPlurals) {
  const key = pair.join('\0');
  if (seenPlural.has(key)) continue;
  seenPlural.add(key);
  pluralPairs.push(pair);
}

const missing = [];
for (const id of singular) {
  if (!map[id] || !map[id].ru || !map[id].tg) missing.push(id);
}
if (missing.length) {
  console.error('MISSING TRANSLATIONS\n' + missing.join('\n'));
  process.exit(1);
}

function poEscape(value) {
  return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n');
}

function header(locale, pluralForms) {
  return `msgid ""
msgstr ""
"Project-Id-Version: TajiksWorld 1.0.0\\n"
"Language: ${locale}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: ${pluralForms}\\n"
"X-Domain: tajiksworld\\n"
`;
}

function entries(code) {
  const rows = [...singular].sort().map((id) => `msgid "${poEscape(id)}"\nmsgstr "${poEscape(map[id][code])}"\n`);
  for (const [one, many] of pluralPairs) {
    const forms = map[one]?.plural?.[code];
    if (!forms) {
      console.error('MISSING PLURAL', one);
      process.exit(1);
    }
    rows.push(`msgid "${poEscape(one)}"\nmsgid_plural "${poEscape(many)}"\n${forms.map((form, index) => `msgstr[${index}] "${poEscape(form)}"`).join('\n')}\n`);
  }
  return rows.join('\n');
}

function encodeMo(messages) {
  const ids = messages.map((item) => item.id);
  const strs = messages.map((item) => item.str);
  const count = ids.length;
  const headerSize = 28;
  const tableSize = count * 8;
  let offset = headerSize + tableSize * 2;
  const idTable = [];
  const strTable = [];
  const blobs = [];
  for (const value of ids) {
    const bytes = Buffer.from(value + '\0', 'utf8');
    idTable.push([bytes.length - 1, offset]);
    blobs.push(bytes);
    offset += bytes.length;
  }
  for (const value of strs) {
    const bytes = Buffer.from(value + '\0', 'utf8');
    strTable.push([bytes.length - 1, offset]);
    blobs.push(bytes);
    offset += bytes.length;
  }
  const out = Buffer.alloc(offset);
  out.writeUInt32LE(0x950412de, 0);
  out.writeUInt32LE(0, 4);
  out.writeUInt32LE(count, 8);
  out.writeUInt32LE(headerSize, 12);
  out.writeUInt32LE(headerSize + tableSize, 16);
  out.writeUInt32LE(0, 20);
  out.writeUInt32LE(0, 24);
  idTable.forEach(([length, pos], index) => {
    out.writeUInt32LE(length, headerSize + index * 8);
    out.writeUInt32LE(pos, headerSize + index * 8 + 4);
  });
  strTable.forEach(([length, pos], index) => {
    out.writeUInt32LE(length, headerSize + tableSize + index * 8);
    out.writeUInt32LE(pos, headerSize + tableSize + index * 8 + 4);
  });
  let cursor = headerSize + tableSize * 2;
  for (const blob of blobs) {
    blob.copy(out, cursor);
    cursor += blob.length;
  }
  return out;
}

function moMessages(locale) {
  const code = locale === 'ru_RU' ? 'ru' : 'tg';
  const forms = locale === 'ru_RU'
    ? 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);'
    : 'nplurals=2; plural=(n != 1);';
  const messages = [{
    id: '',
    str: `Project-Id-Version: TajiksWorld 1.0.0\nLanguage: ${locale}\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nPlural-Forms: ${forms}\n`,
  }];
  for (const id of [...singular].sort()) {
    messages.push({ id, str: map[id][code] });
  }
  for (const [one, many] of pluralPairs) {
    messages.push({ id: `${one}\0${many}`, str: map[one].plural[code].join('\0') });
  }
  return messages;
}

mkdirSync(join(root, 'languages'), { recursive: true });
const pot = `msgid ""
msgstr ""
"Project-Id-Version: TajiksWorld 1.0.0\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"X-Domain: tajiksworld\\n"

${[...singular].sort().map((id) => `msgid "${poEscape(id)}"\nmsgstr ""\n`).join('\n')}
`;
writeFileSync(join(root, 'languages/tajiksworld.pot'), pot);
const locales = {
  ru_RU: 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
  tg_TJ: 'nplurals=2; plural=(n != 1);',
};
for (const [locale, forms] of Object.entries(locales)) {
  const code = locale === 'ru_RU' ? 'ru' : 'tg';
  const body = header(locale, forms) + '\n' + entries(code);
  writeFileSync(join(root, `languages/tajiksworld-${locale}.po`), body);
  const mo = encodeMo(moMessages(locale));
  writeFileSync(join(root, `languages/tajiksworld-${locale}.mo`), mo);
  if (mo.readUInt32LE(0) !== 0x950412de) {
    throw new Error('bad mo magic');
  }
}
console.log(`Wrote ${singular.size} strings and ${pluralPairs.length} plural pairs.`);
