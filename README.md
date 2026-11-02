# TajiksWorld

A professional multilingual WordPress news and media theme with a visual page builder and an editorial administration panel.

Interface languages: Tajik (`tg_TJ`), Russian (`ru_RU`), and English. Article translations are handled by Polylang or WPML, not by the interface language switcher.

**Тоҷикӣ:** Мавзӯи касбии хабарӣ барои WordPress бо созандаи визуалии саҳифа. Забони интерфейсро иваз кардан матлабҳоро тарҷума намекунад. Барои забонҳои алоҳидаи хабар Polylang ё WPML лозим аст.

**Русский:** Профессиональная новостная тема WordPress с визуальным конструктором. Переключатель языка меняет интерфейс, а не тексты материалов. Переводы статей — через Polylang или WPML.

- Requires WordPress 6.4+ and PHP 8.2+
- License: GPL-2.0-or-later
- No companion plugin. Everything ships inside the theme.
- Author: Majnun Sadriddinov

## Install

See [docs/INSTALLATION.md](docs/INSTALLATION.md). Upload `dist/tajiksworld.zip` in **Appearance → Themes → Add New → Upload Theme**, or copy the `tajiksworld` folder into `wp-content/themes/` and activate it.

Activation seeds the homepage, header, and footer layouts and creates the newsletter table. It does not replace the WordPress dashboard.

## Build

See [docs/BUILD.md](docs/BUILD.md).

```bash
npm install
npm run build
npm test
php tests/run.php
```

Committed `assets/css` and `assets/js` are already built. npm is only required if you change `resources/`.

## Builder

See [docs/BUILDER.md](docs/BUILDER.md).

**TajiksWorld → Visual Builder** edits the homepage. **Header Builder** and **Footer Builder** edit the site chrome. On any page, the TajiksWorld box opens the page canvas. Publish a layout to replace that page's content.

## Features

See [docs/FEATURES.md](docs/FEATURES.md).

## Testing

See [docs/TESTING.md](docs/TESTING.md). The report lists only checks that were actually run.

## Repository

https://github.com/mzaynuddinov/tajiksworld
