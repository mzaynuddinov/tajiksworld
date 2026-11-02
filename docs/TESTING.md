# Testing report

Environment: PHP 8.3.24 CLI (static build with gd, mbstring, tokenizer, and zip), Node.js, and Composer 2.8.9. No WordPress or MySQL service was available in this environment, so theme activation inside a live site was not run.

## Ran and passed

| Check | Result |
| --- | --- |
| `php -l` on every theme PHP file (111 files, including silence indexes) | No syntax errors |
| `php tests/run.php` | 69 checks passed, 0 failed. Covers sanitizer (scripts, colors, classes, javascript URLs, unknown elements), renderer escaping, default homepage/header/footer element types, registry round-trip for every builder element |
| `npm test` (`tests/run-node-tests.mjs` and `tests/security-scan.mjs`) | Passed. JavaScript syntax, builder HTML escaping, built CSS (18034 bytes) and `builder.min.js` (19614 bytes), 109 PHP files outside tests scanned for `eval`, `create_function`, raw `extract($_GET/POST/REQUEST)`, and raw echoed superglobals |
| `npm run build` | Tailwind CSS and esbuild completed |
| `composer dump-autoload` | `TajiksWorld\Builder\LayoutSanitizer` loads from `vendor/autoload.php`. Runtime still uses `app/autoload.php`; `vendor/` is not required to install the theme |
| `node bin/compile-translations.mjs` | 284 interface strings and 3 plural pairs. `tajiksworld-ru_RU.mo` and `tajiksworld-tg_TJ.mo` start with MO magic `0x950412de` |
| `php bin/screenshot.php` | Wrote `screenshot.png` (7770 bytes, 1200×900) |

## Not run

- Activating the theme in WordPress and clicking through the admin screens.
- Saving a layout through the REST API against a real site.
- Rendering the homepage with MySQL content.
- Polylang or WPML language URLs on a live site.
- Browser checks of desktop, tablet, and mobile CSS.

Those need a WordPress install. Do not treat them as passed.

## Known limitations

- Without Polylang or WPML, `?tw_lang=` only changes the interface locale. It does not translate articles and does not create language-specific URLs.
- Custom HTML blocks are passed through `wp_kses_post`, so scripts are removed. Tracking scripts belong in Theme Settings and only stick for administrators with `unfiltered_html`.
- WebP delivery depends on WordPress and the server image editor. The theme asks for responsive image sizes; it does not convert uploads itself.
- The featured, breaking, and trending blocks stay empty until posts are flagged. They do not fall back to the latest posts.
- Builder capabilities are added on theme activation. Editors who already existed need the theme activated once (or the capability added by hand) before the builder menu appears.
- The Themes-screen screenshot is a generated editorial image, not a capture of a populated site.
