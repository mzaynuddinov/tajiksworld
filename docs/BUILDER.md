# Visual builder

Open **TajiksWorld → Visual Builder** for the homepage, **Header Builder** for the header, or **Footer Builder** for the footer. On a page, use **Edit with the visual builder** in the TajiksWorld box. Editors need the builder capability added when the theme is activated. Header and footer screens require `edit_theme_options`. Settings, ads, and import require `manage_options`.

## What you can do

- Drag a block from the palette, or arm it and press **+ Add**.
- Reorder sections and blocks, duplicate them, or delete them.
- Change column widths, container width (boxed, wide, full), gap, padding, colors, border, radius, and a CSS class.
- Hide a section or block on desktop, tablet, or mobile. The canvas width buttons preview those widths; the public CSS classes do the real hiding.
- Undo and redo, including Ctrl/Cmd+Z.
- **Save draft** stores a draft layout. The public site keeps the published layout.
- **Publish** replaces the live layout and keeps up to 15 revisions for home, header, and footer.
- **Export** downloads the current canvas as JSON. **Import** replaces the canvas; publish it to make it live.
- **Library** saves a section for reuse and inserts it later with new IDs.
- The preview iframe is the real theme, refreshed from a short-lived private preview. Empty ad slots render nothing.

## Elements

Heading, text, image, gallery, video, button, icon, divider, spacer, container, columns, grid, latest news, trending news, featured news, breaking news, popular news, news categories, news carousel, news grid, news list, author box, advertisement, newsletter, social links, search, breadcrumbs, statistics, custom HTML, plus logo, navigation, language switcher, dark mode toggle, and copyright for the header and footer.

News blocks read published WordPress posts. Featured, breaking, and trending blocks only include stories with that flag. A category slug on a grid limits that section. Video, gallery, and podcast sections use their content types.

Custom HTML is filtered with `wp_kses_post` unless the user has `unfiltered_html`. Scripts in a block are stripped. Tracking belongs in **Theme Settings → Additional**, which is also limited to administrators who can post unfiltered HTML.

## Global design

Colors, fonts, dark mode, sticky header, logos, social URLs, copyright, and custom CSS live in **TajiksWorld → Theme Settings**. The builder does not duplicate those site-wide controls.

## Import and export of the whole site design

**TajiksWorld → Import / Export** moves settings, home/header/footer layouts, the section library, and advertisements. It does not move posts or subscribers.
