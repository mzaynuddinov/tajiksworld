# Implemented features

## Visual builder

Drag-and-drop sections and blocks, live theme preview, desktop/tablet/mobile canvas, duplicate, delete, reorder, draft, publish, undo/redo, JSON import/export, reusable sections, spacing, colors, typography, backgrounds, borders, device visibility, columns, grids, containers, and separate homepage, header, footer, and page builders. Layouts are data, not hardcoded templates. Activation seeds a full news homepage that can be reordered.

## Newsroom

Posts are the news type, with source, gallery IDs, video URL, breaking, featured, editor's choice, trending, view counts, reading time, and per-story SEO title and description. Categories support parents, a color, and an image. Views increment through an AJAX request, once per reader per half hour, not during page render.

## Content types

Videos, photo galleries, podcasts, interviews, and special reports each have an admin screen, archive, single template, and extra fields (video URL, gallery IDs, audio URL, guest, PDF).

## Homepage sections in the default layout

Breaking ticker, featured lead, carousel, latest with popular and trending, homepage and between-story ads, politics, economy, technology, world, Tajikistan, sports, culture, video, photo desk, podcasts, statistics, and newsletter. Header and footer are builder layouts and include logo, navigation, language switcher, search, dark mode, social links, menus, ads, and a footer newsletter.

## Article

Breadcrumbs, category, title, excerpt, author, dates, reading time, views, featured image and caption, content, share links, font-size controls, print, related stories, author box, previous/next, comments, and sidebar ads.

## Administration

TajiksWorld menu: dashboard counts, news, categories, tags, flag screens, content types, authors, builders, advertisement manager, newsletter CSV, theme settings, SEO status, import/export, system status, and optional sample content. The native WordPress dashboard stays in place; a TajiksWorld widget is added to it.

## Ads

Placements: header, homepage, sidebar, in-article, between stories, footer, mobile. Each ad has an image or HTML, URL, start and end dates, active flag, and device visibility. Empty placements output nothing.

## SEO

Semantic templates, NewsArticle JSON-LD, Open Graph, Twitter cards, breadcrumbs, and core canonical URLs and sitemaps. If Yoast SEO or Rank Math is active, TajiksWorld does not print its own description, Open Graph, or Twitter tags.

## Languages

Text domain `tajiksworld`, with compiled Tajik and Russian catalogs. Header switcher uses Polylang or WPML when present, including their URLs. Without those plugins it only switches the interface locale.

## Security

Nonces on forms and AJAX, capability checks on admin and REST routes, sanitized settings, escaped output, prepared newsletter SQL, and rejection of `javascript:` and `data:` URLs in builder fields.
