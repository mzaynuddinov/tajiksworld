import { copyFileSync, mkdirSync } from 'node:fs';
import esbuild from 'esbuild';

mkdirSync('assets/js', { recursive: true });
const files = ['theme', 'builder', 'admin'];
for (const name of files) {
  copyFileSync(`resources/js/${name}.js`, `assets/js/${name}.js`);
  await esbuild.build({
    entryPoints: [`resources/js/${name}.js`],
    outfile: `assets/js/${name}.min.js`,
    minify: true,
    legalComments: 'none',
    target: 'es2019',
  });
}
