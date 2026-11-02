# Installation

## Requirements

- WordPress 6.4 or newer
- PHP 8.2 or newer
- MySQL 8 or MariaDB with the WordPress database API

## Upload

1. Download `dist/tajiksworld.zip` from this repository, or zip the theme folder so the archive root contains `style.css` inside a `tajiksworld/` directory.
2. In WordPress open **Appearance → Themes → Add New → Upload Theme**.
3. Choose the ZIP and install it.
4. Activate **TajiksWorld**.

Activation does four things:

- Seeds the homepage, header, and footer builder layouts if they are not already saved.
- Creates the `{prefix}tw_subscribers` table.
- Registers the Primary menu location and flushes rewrite rules for the extra content types.
- Adds the `tajiksworld_builder` and `tajiksworld_manage` capabilities. Editors and administrators receive the builder capability. Only administrators receive management.

There is no companion plugin.

## After activation

1. Open **TajiksWorld → Theme Settings** and set the site title, description, timezone, logo, and contact details.
2. Open **Settings → Permalinks** and click Save so video, gallery, podcast, interview, and report archives resolve.
3. Optional: **TajiksWorld → Sample Content** inserts original sample categories and stories. Delete them like any other WordPress content.
4. Assign **Appearance → Menus** to Primary and Footer. If no menu is assigned, the header falls back to a category list.
5. Mark stories as Breaking, Featured, Trending, or Editor's choice in the TajiksWorld details box. The featured block stays empty until at least one story is flagged.
6. For Tajik, Russian, and English articles with their own URLs, install Polylang or WPML and create translations there. The header language control uses the plugin when it is active.

## Interface language without a multilingual plugin

The header links `?tw_lang=tj`, `ru`, or `en` store a `tw_ui_lang` cookie and reload the interface strings. They do not translate post content and they do not create language-specific URLs.

## Update

Replace the theme folder or upload a newer ZIP. Saved layouts, settings, ads, and subscribers stay in the database. Seed layouts are not overwritten once saved.

## Remove

Switch to another theme. Theme roles are removed on switch. Options, post meta, and the subscribers table remain until you delete them in the database.
