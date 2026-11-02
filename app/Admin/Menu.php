<?php
/**
 * TajiksWorld administration menu. Does not replace the WordPress dashboard.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Admin;

final class Menu {
	public static function register(): void {
		add_action('admin_menu', [self::class, 'menu']);
		add_action('admin_post_tw_save_settings', [Screens::class, 'save_settings']);
		add_action('admin_post_tw_save_ad', [Screens::class, 'save_ad']);
		add_action('admin_post_tw_delete_ad', [Screens::class, 'delete_ad']);
		add_action('admin_post_tw_flag', [Screens::class, 'toggle_flag']);
		add_action('admin_post_tw_delete_subscriber', [Screens::class, 'delete_subscriber']);
		add_action('admin_post_tw_export_subscribers', [Screens::class, 'export_subscribers']);
		add_action('admin_post_tw_import', [Screens::class, 'import']);
		add_action('admin_post_tw_export', [Screens::class, 'export']);
		add_action('admin_post_tw_sample', [Screens::class, 'sample']);
		add_action('admin_post_tw_reset_layout', [Screens::class, 'reset_layout']);
		add_action('wp_dashboard_setup', [Screens::class, 'dashboard_widget']);
	}

	public static function menu(): void {
		$cap = 'manage_options';
		add_menu_page(__('TajiksWorld', 'tajiksworld'), __('TajiksWorld', 'tajiksworld'), 'edit_posts', 'tajiksworld', [Screens::class, 'dashboard'], 'dashicons-media-document', 3);
		add_submenu_page('tajiksworld', __('Dashboard', 'tajiksworld'), __('Dashboard', 'tajiksworld'), 'edit_posts', 'tajiksworld', [Screens::class, 'dashboard']);
		self::link(__('News', 'tajiksworld'), 'edit_posts', 'edit.php');
		self::link(__('Categories', 'tajiksworld'), 'manage_categories', 'edit-tags.php?taxonomy=category');
		self::link(__('Tags', 'tajiksworld'), 'manage_categories', 'edit-tags.php?taxonomy=post_tag');
		add_submenu_page('tajiksworld', __('Breaking News', 'tajiksworld'), __('Breaking News', 'tajiksworld'), 'edit_posts', 'tajiksworld-breaking', [Screens::class, 'breaking']);
		add_submenu_page('tajiksworld', __('Featured News', 'tajiksworld'), __('Featured News', 'tajiksworld'), 'edit_posts', 'tajiksworld-featured', [Screens::class, 'featured']);
		add_submenu_page('tajiksworld', __('Trending News', 'tajiksworld'), __('Trending News', 'tajiksworld'), 'edit_posts', 'tajiksworld-trending', [Screens::class, 'trending']);
		add_submenu_page('tajiksworld', __("Editor's choice", 'tajiksworld'), __("Editor's choice", 'tajiksworld'), 'edit_posts', 'tajiksworld-editors', [Screens::class, 'editors']);
		self::link(__('Videos', 'tajiksworld'), 'edit_posts', 'edit.php?post_type=tw_video');
		self::link(__('Galleries', 'tajiksworld'), 'edit_posts', 'edit.php?post_type=tw_gallery');
		self::link(__('Podcasts', 'tajiksworld'), 'edit_posts', 'edit.php?post_type=tw_podcast');
		self::link(__('Interviews', 'tajiksworld'), 'edit_posts', 'edit.php?post_type=tw_interview');
		self::link(__('Special Reports', 'tajiksworld'), 'edit_posts', 'edit.php?post_type=tw_report');
		self::link(__('Authors', 'tajiksworld'), 'list_users', 'users.php');
		add_submenu_page('tajiksworld', __('Visual Builder', 'tajiksworld'), __('Visual Builder', 'tajiksworld'), 'edit_pages', 'tajiksworld-builder', [Screens::class, 'builder']);
		add_submenu_page('tajiksworld', __('Header Builder', 'tajiksworld'), __('Header Builder', 'tajiksworld'), 'edit_theme_options', 'tajiksworld-header', [Screens::class, 'builder']);
		add_submenu_page('tajiksworld', __('Footer Builder', 'tajiksworld'), __('Footer Builder', 'tajiksworld'), 'edit_theme_options', 'tajiksworld-footer', [Screens::class, 'builder']);
		add_submenu_page('tajiksworld', __('Advertisement Manager', 'tajiksworld'), __('Advertisement Manager', 'tajiksworld'), $cap, 'tajiksworld-ads', [Screens::class, 'ads']);
		add_submenu_page('tajiksworld', __('Newsletter', 'tajiksworld'), __('Newsletter', 'tajiksworld'), $cap, 'tajiksworld-newsletter', [Screens::class, 'newsletter']);
		add_submenu_page('tajiksworld', __('Theme Settings', 'tajiksworld'), __('Theme Settings', 'tajiksworld'), $cap, 'tajiksworld-settings', [Screens::class, 'settings']);
		add_submenu_page('tajiksworld', __('SEO Settings', 'tajiksworld'), __('SEO Settings', 'tajiksworld'), $cap, 'tajiksworld-seo', [Screens::class, 'seo']);
		add_submenu_page('tajiksworld', __('Import / Export', 'tajiksworld'), __('Import / Export', 'tajiksworld'), $cap, 'tajiksworld-import', [Screens::class, 'transfer']);
		add_submenu_page('tajiksworld', __('System Status', 'tajiksworld'), __('System Status', 'tajiksworld'), $cap, 'tajiksworld-status', [Screens::class, 'status']);
		add_submenu_page('tajiksworld', __('Sample Content', 'tajiksworld'), __('Sample Content', 'tajiksworld'), $cap, 'tajiksworld-sample', [Screens::class, 'sample_screen']);
	}

	private static function link(string $label, string $cap, string $target): void {
		global $submenu;
		$submenu['tajiksworld'][] = [$label, $cap, $target];
	}
}
