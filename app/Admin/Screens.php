<?php
/**
 * TajiksWorld admin screens.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Admin;

use TajiksWorld\Ads\Manager as Ads;
use TajiksWorld\Builder\Defaults;
use TajiksWorld\Builder\Repository;
use TajiksWorld\Helpers\Options;
use TajiksWorld\Newsletter\Subscribers;
use TajiksWorld\Settings\Store;

final class Screens {
	public static function dashboard(): void {
		if (!current_user_can('edit_posts')) {
			wp_die(esc_html__('You cannot view this screen.', 'tajiksworld'));
		}
		$counts = wp_count_posts('post');
		global $wpdb;
		$views = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_tw_views'));
		$popular = get_posts(['post_type' => 'post', 'posts_per_page' => 5, 'meta_key' => '_tw_views', 'orderby' => 'meta_value_num', 'order' => 'DESC']);
		$recent = get_posts(['post_type' => ['post', 'tw_video', 'tw_gallery', 'tw_podcast', 'tw_interview', 'tw_report'], 'posts_per_page' => 6]);
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('TajiksWorld', 'tajiksworld') . '</h1>';
		echo '<div class="tw-stat-grid">';
		self::stat(__('Published news', 'tajiksworld'), (string) ($counts->publish ?? 0));
		self::stat(__('Drafts', 'tajiksworld'), (string) ($counts->draft ?? 0));
		self::stat(__('Total views', 'tajiksworld'), (string) $views);
		self::stat(__('Subscribers', 'tajiksworld'), (string) Subscribers::count_active());
		echo '</div><div class="tw-admin-cols"><section><h2>' . esc_html__('Most read', 'tajiksworld') . '</h2><ol>';
		foreach ($popular as $post) {
			echo '<li><a href="' . esc_url((string) get_edit_post_link($post)) . '">' . esc_html(get_the_title($post)) . '</a> <span>' . esc_html((string) (int) get_post_meta($post->ID, '_tw_views', true)) . '</span></li>';
		}
		if ($popular === []) {
			echo '<li>' . esc_html__('No stories yet.', 'tajiksworld') . '</li>';
		}
		echo '</ol></section><section><h2>' . esc_html__('Recent activity', 'tajiksworld') . '</h2><ul>';
		foreach ($recent as $post) {
			echo '<li><a href="' . esc_url((string) get_edit_post_link($post)) . '">' . esc_html(get_the_title($post)) . '</a> <time>' . esc_html(get_the_date('', $post)) . '</time></li>';
		}
		echo '</ul></section></div>';
		echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=tajiksworld-builder')) . '">' . esc_html__('Edit the homepage', 'tajiksworld') . '</a> ';
		echo '<a class="button" href="' . esc_url(admin_url('post-new.php')) . '">' . esc_html__('Add news', 'tajiksworld') . '</a></p></div>';
	}

	public static function dashboard_widget(): void {
		wp_add_dashboard_widget('tajiksworld_snapshot', __('TajiksWorld', 'tajiksworld'), static function (): void {
			$counts = wp_count_posts('post');
			echo '<p>' . esc_html(sprintf(__('Published news: %d', 'tajiksworld'), (int) ($counts->publish ?? 0))) . '</p>';
			echo '<p><a href="' . esc_url(admin_url('admin.php?page=tajiksworld')) . '">' . esc_html__('Open TajiksWorld', 'tajiksworld') . '</a></p>';
		});
	}

	public static function breaking(): void { self::flags('_tw_breaking', __('Breaking News', 'tajiksworld')); }
	public static function featured(): void { self::flags('_tw_featured', __('Featured News', 'tajiksworld')); }
	public static function trending(): void { self::flags('_tw_trending', __('Trending News', 'tajiksworld')); }
	public static function editors(): void { self::flags('_tw_editors_choice', __("Editor's choice", 'tajiksworld')); }

	private static function flags(string $key, string $title): void {
		if (!current_user_can('edit_posts')) {
			wp_die(esc_html__('You cannot view this screen.', 'tajiksworld'));
		}
		$posts = get_posts(['post_type' => 'post', 'posts_per_page' => 50, 'post_status' => ['publish', 'draft']]);
		echo '<div class="wrap tw-admin"><h1>' . esc_html($title) . '</h1><table class="widefat striped"><thead><tr><th>' . esc_html__('Story', 'tajiksworld') . '</th><th>' . esc_html__('Status', 'tajiksworld') . '</th><th></th></tr></thead><tbody>';
		foreach ($posts as $post) {
			$on = get_post_meta($post->ID, $key, true) === '1';
			echo '<tr><td><a href="' . esc_url((string) get_edit_post_link($post)) . '">' . esc_html(get_the_title($post)) . '</a></td><td>' . esc_html($on ? __('On', 'tajiksworld') : __('Off', 'tajiksworld')) . '</td><td>';
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
			wp_nonce_field('tw_flag');
			echo '<input type="hidden" name="action" value="tw_flag" /><input type="hidden" name="post_id" value="' . esc_attr((string) $post->ID) . '" /><input type="hidden" name="flag" value="' . esc_attr($key) . '" /><input type="hidden" name="value" value="' . esc_attr($on ? '0' : '1') . '" />';
			submit_button($on ? __('Remove', 'tajiksworld') : __('Mark', 'tajiksworld'), 'secondary', 'submit', false);
			echo '</form></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function toggle_flag(): void {
		check_admin_referer('tw_flag');
		$post_id = absint($_POST['post_id'] ?? 0);
		if (!current_user_can('edit_post', $post_id)) {
			wp_die(esc_html__('You cannot edit this story.', 'tajiksworld'));
		}
		$flag = (string) ($_POST['flag'] ?? '');
		if (!in_array($flag, ['_tw_breaking', '_tw_featured', '_tw_trending', '_tw_editors_choice'], true)) {
			wp_die(esc_html__('Unknown flag.', 'tajiksworld'));
		}
		update_post_meta($post_id, $flag, ($_POST['value'] ?? '') === '1' ? '1' : '0');
		wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=tajiksworld'));
		exit;
	}

	public static function builder(): void {
		if (!current_user_can('edit_pages') && !current_user_can('edit_theme_options')) {
			wp_die(esc_html__('You cannot use the builder.', 'tajiksworld'));
		}
		$page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : 'tajiksworld-builder';
		$titles = [
			'tajiksworld-builder' => __('Visual Builder', 'tajiksworld'),
			'tajiksworld-header'  => __('Header Builder', 'tajiksworld'),
			'tajiksworld-footer'  => __('Footer Builder', 'tajiksworld'),
		];
		echo '<div class="wrap tw-admin tw-builder-wrap"><h1>' . esc_html($titles[$page] ?? __('Visual Builder', 'tajiksworld')) . '</h1>';
		echo '<p class="description">' . esc_html__('Drag blocks, reorder sections, then save a draft or publish. The preview uses the real theme.', 'tajiksworld') . ' <a href="' . esc_url(admin_url('admin.php?page=tajiksworld-settings')) . '">' . esc_html__('Global design', 'tajiksworld') . '</a></p>';
		echo '<div id="tw-builder-app" class="tw-builder-app"></div></div>';
	}

	public static function ads(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot manage advertisements.', 'tajiksworld'));
		}
		$edit = null;
		$requested = isset($_GET['ad']) ? sanitize_text_field((string) $_GET['ad']) : '';
		foreach (Ads::all() as $ad) {
			if (($ad['id'] ?? '') === $requested) {
				$edit = $ad;
			}
		}
		$edit = is_array($edit) ? $edit : ['id' => '', 'title' => '', 'placement' => 'homepage', 'image_id' => 0, 'html' => '', 'url' => '', 'start' => '', 'end' => '', 'active' => true, 'devices' => ['desktop', 'tablet', 'mobile']];
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('Advertisement Manager', 'tajiksworld') . '</h1>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Title', 'tajiksworld') . '</th><th>' . esc_html__('Placement', 'tajiksworld') . '</th><th>' . esc_html__('Active', 'tajiksworld') . '</th><th></th></tr></thead><tbody>';
		foreach (Ads::all() as $ad) {
			echo '<tr><td>' . esc_html((string) $ad['title']) . '</td><td>' . esc_html((string) $ad['placement']) . '</td><td>' . esc_html(!empty($ad['active']) ? __('Yes', 'tajiksworld') : __('No', 'tajiksworld')) . '</td><td><a href="' . esc_url(admin_url('admin.php?page=tajiksworld-ads&ad=' . rawurlencode((string) $ad['id']))) . '">' . esc_html__('Edit', 'tajiksworld') . '</a></td></tr>';
		}
		echo '</tbody></table><h2>' . esc_html($edit['id'] === '' ? __('Add advertisement', 'tajiksworld') : __('Edit advertisement', 'tajiksworld')) . '</h2>';
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="tw-admin-form">';
		wp_nonce_field('tw_save_ad');
		echo '<input type="hidden" name="action" value="tw_save_ad" /><input type="hidden" name="id" value="' . esc_attr((string) $edit['id']) . '" />';
		echo '<p><label>' . esc_html__('Title', 'tajiksworld') . '<input class="regular-text" name="title" value="' . esc_attr((string) $edit['title']) . '" /></label></p>';
		echo '<p><label>' . esc_html__('Placement', 'tajiksworld') . '<select name="placement">';
		foreach (Ads::PLACEMENTS as $placement) {
			echo '<option value="' . esc_attr($placement) . '" ' . selected($edit['placement'], $placement, false) . '>' . esc_html($placement) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><label>' . esc_html__('Image ID', 'tajiksworld') . '<input type="number" name="image_id" value="' . esc_attr((string) (int) $edit['image_id']) . '" /> <button type="button" class="button" data-tw-media="image_id">' . esc_html__('Choose image', 'tajiksworld') . '</button></label></p>';
		echo '<p><label>' . esc_html__('Destination URL', 'tajiksworld') . '<input class="regular-text" type="url" name="url" value="' . esc_attr((string) $edit['url']) . '" /></label></p>';
		echo '<p><label>' . esc_html__('HTML', 'tajiksworld') . '<textarea name="html" rows="5" class="large-text">' . esc_textarea((string) $edit['html']) . '</textarea></label></p>';
		echo '<p><label>' . esc_html__('Start', 'tajiksworld') . '<input type="date" name="start" value="' . esc_attr((string) $edit['start']) . '" /></label> <label>' . esc_html__('End', 'tajiksworld') . '<input type="date" name="end" value="' . esc_attr((string) $edit['end']) . '" /></label></p>';
		echo '<p><label><input type="checkbox" name="active" value="1" ' . checked(!empty($edit['active']), true, false) . ' /> ' . esc_html__('Active', 'tajiksworld') . '</label></p><p>';
		foreach (['desktop', 'tablet', 'mobile'] as $device) {
			echo '<label style="margin-right:1rem"><input type="checkbox" name="devices[]" value="' . esc_attr($device) . '" ' . checked(in_array($device, (array) $edit['devices'], true), true, false) . ' /> ' . esc_html($device) . '</label>';
		}
		echo '</p>';
		submit_button(__('Save advertisement', 'tajiksworld'));
		echo '</form>';
		if ($edit['id'] !== '') {
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
			wp_nonce_field('tw_delete_ad');
			echo '<input type="hidden" name="action" value="tw_delete_ad" /><input type="hidden" name="id" value="' . esc_attr((string) $edit['id']) . '" />';
			submit_button(__('Delete', 'tajiksworld'), 'delete');
			echo '</form>';
		}
		echo '</div>';
	}

	public static function save_ad(): void {
		check_admin_referer('tw_save_ad');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot manage advertisements.', 'tajiksworld'));
		}
		Ads::save_one([
			'id' => sanitize_text_field((string) ($_POST['id'] ?? '')),
			'title' => (string) ($_POST['title'] ?? ''),
			'placement' => (string) ($_POST['placement'] ?? ''),
			'image_id' => absint($_POST['image_id'] ?? 0),
			'html' => (string) wp_unslash($_POST['html'] ?? ''),
			'url' => (string) ($_POST['url'] ?? ''),
			'start' => (string) ($_POST['start'] ?? ''),
			'end' => (string) ($_POST['end'] ?? ''),
			'active' => isset($_POST['active']),
			'devices' => isset($_POST['devices']) && is_array($_POST['devices']) ? array_map('sanitize_key', wp_unslash($_POST['devices'])) : [],
		], current_user_can('unfiltered_html'));
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-ads&updated=1'));
		exit;
	}

	public static function delete_ad(): void {
		check_admin_referer('tw_delete_ad');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot manage advertisements.', 'tajiksworld'));
		}
		Ads::delete(sanitize_text_field((string) ($_POST['id'] ?? '')));
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-ads'));
		exit;
	}

	public static function newsletter(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot view subscribers.', 'tajiksworld'));
		}
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('Newsletter', 'tajiksworld') . '</h1>';
		echo '<p>' . esc_html(sprintf(__('Active subscribers: %d', 'tajiksworld'), Subscribers::count_active())) . '</p>';
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
		wp_nonce_field('tw_export_subscribers');
		echo '<input type="hidden" name="action" value="tw_export_subscribers" />';
		submit_button(__('Download CSV', 'tajiksworld'), 'secondary', 'submit', false);
		echo '</form><table class="widefat striped"><thead><tr><th>' . esc_html__('Email', 'tajiksworld') . '</th><th>' . esc_html__('Status', 'tajiksworld') . '</th><th>' . esc_html__('Date', 'tajiksworld') . '</th><th></th></tr></thead><tbody>';
		foreach (Subscribers::list() as $row) {
			echo '<tr><td>' . esc_html((string) $row['email']) . '</td><td>' . esc_html((string) $row['status']) . '</td><td>' . esc_html((string) $row['created_at']) . '</td><td>';
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
			wp_nonce_field('tw_delete_subscriber');
			echo '<input type="hidden" name="action" value="tw_delete_subscriber" /><input type="hidden" name="id" value="' . esc_attr((string) $row['id']) . '" />';
			submit_button(__('Delete', 'tajiksworld'), 'delete', 'submit', false);
			echo '</form></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function delete_subscriber(): void {
		check_admin_referer('tw_delete_subscriber');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot view subscribers.', 'tajiksworld'));
		}
		Subscribers::delete(absint($_POST['id'] ?? 0));
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-newsletter'));
		exit;
	}

	public static function export_subscribers(): void {
		check_admin_referer('tw_export_subscribers');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot view subscribers.', 'tajiksworld'));
		}
		nocache_headers();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=tajiksworld-subscribers.csv');
		$out = fopen('php://output', 'w');
		if ($out === false) {
			exit;
		}
		fputcsv($out, ['email', 'status', 'created_at']);
		foreach (Subscribers::list(5000) as $row) {
			fputcsv($out, [$row['email'], $row['status'], $row['created_at']]);
		}
		fclose($out);
		exit;
	}

	public static function settings(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot change settings.', 'tajiksworld'));
		}
		$s = Options::all();
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('TajiksWorld Settings', 'tajiksworld') . '</h1>';
		if (isset($_GET['updated'])) {
			echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'tajiksworld') . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="tw-admin-form">';
		wp_nonce_field('tw_save_settings');
		echo '<input type="hidden" name="action" value="tw_save_settings" />';
		self::h(__('General', 'tajiksworld'));
		self::text('site_title', __('Website title', 'tajiksworld'), (string) get_option('blogname'));
		self::text('site_description', __('Website description', 'tajiksworld'), (string) get_option('blogdescription'));
		self::number('logo_id', __('Logo attachment ID', 'tajiksworld'), (int) get_theme_mod('custom_logo'));
		echo '<p><button type="button" class="button" data-tw-media="logo_id">' . esc_html__('Choose logo', 'tajiksworld') . '</button></p>';
		self::number('retina_logo', __('Retina logo attachment ID', 'tajiksworld'), (int) $s['retina_logo']);
		self::number('site_icon', __('Favicon / site icon ID', 'tajiksworld'), (int) get_option('site_icon'));
		self::select('timezone', __('Timezone', 'tajiksworld'), (string) get_option('timezone_string'), self::timezones());
		self::select('default_language', __('Default interface language', 'tajiksworld'), (string) $s['default_language'], ['en' => 'English', 'ru' => 'Русский', 'tj' => 'Тоҷикӣ']);
		self::select('date_format', __('Date format', 'tajiksworld'), (string) get_option('date_format'), ['F j, Y' => 'F j, Y', 'j F Y' => 'j F Y', 'Y-m-d' => 'Y-m-d', 'd/m/Y' => 'd/m/Y', 'm/d/Y' => 'm/d/Y']);
		self::select('time_format', __('Time format', 'tajiksworld'), (string) get_option('time_format'), ['g:i a' => 'g:i a', 'H:i' => 'H:i']);
		self::text('contact_email', __('Contact email', 'tajiksworld'), (string) $s['contact_email']);
		self::text('contact_phone', __('Phone', 'tajiksworld'), (string) $s['contact_phone']);
		self::textarea('contact_address', __('Address', 'tajiksworld'), (string) $s['contact_address']);
		self::h(__('Header', 'tajiksworld'));
		self::check('sticky_header', __('Sticky header', 'tajiksworld'), !empty($s['sticky_header']));
		self::check('transparent_header', __('Transparent header on the homepage', 'tajiksworld'), !empty($s['transparent_header']));
		self::check('topbar_enabled', __('Top bar', 'tajiksworld'), !empty($s['topbar_enabled']));
		self::text('topbar_text', __('Top bar text', 'tajiksworld'), (string) $s['topbar_text']);
		self::check('show_search', __('Search icon', 'tajiksworld'), !empty($s['show_search']));
		self::color('header_bg', __('Header background', 'tajiksworld'), (string) $s['header_bg']);
		self::color('header_text', __('Header text', 'tajiksworld'), (string) $s['header_text']);
		self::h(__('Footer', 'tajiksworld'));
		self::number('footer_logo', __('Footer logo ID', 'tajiksworld'), (int) $s['footer_logo']);
		self::textarea('footer_description', __('Footer description', 'tajiksworld'), (string) $s['footer_description']);
		self::text('copyright', __('Copyright text', 'tajiksworld'), (string) $s['copyright']);
		self::check('show_developer', __('Show developer credit', 'tajiksworld'), !empty($s['show_developer']));
		self::text('developer_credit', __('Developer credit', 'tajiksworld'), (string) $s['developer_credit']);
		self::h(__('Social links', 'tajiksworld'));
		foreach (['social_facebook' => 'Facebook', 'social_x' => 'X', 'social_instagram' => 'Instagram', 'social_telegram' => 'Telegram', 'social_youtube' => 'YouTube'] as $key => $label) {
			self::text($key, $label, (string) $s[$key]);
		}
		self::check('social_rss', __('Show RSS', 'tajiksworld'), !empty($s['social_rss']));
		self::h(__('Typography', 'tajiksworld'));
		$fonts = ['Newsreader' => 'Newsreader', 'Source Serif 4' => 'Source Serif 4', 'Literata' => 'Literata', 'Source Sans 3' => 'Source Sans 3', 'Libre Franklin' => 'Libre Franklin', 'system-ui' => 'System'];
		self::select('font_heading', __('Heading font', 'tajiksworld'), (string) $s['font_heading'], $fonts);
		self::select('font_body', __('Body font', 'tajiksworld'), (string) $s['font_body'], $fonts);
		self::number('font_size', __('Body size (px)', 'tajiksworld'), (int) $s['font_size']);
		self::select('line_height', __('Line height', 'tajiksworld'), (string) $s['line_height'], ['1.45' => '1.45', '1.55' => '1.55', '1.65' => '1.65', '1.8' => '1.8']);
		self::select('heading_weight', __('Heading weight', 'tajiksworld'), (string) $s['heading_weight'], ['500' => '500', '560' => '560', '650' => '650', '750' => '750']);
		self::h(__('Colors', 'tajiksworld'));
		foreach (['primary' => __('Primary', 'tajiksworld'), 'secondary' => __('Secondary', 'tajiksworld'), 'accent' => __('Accent', 'tajiksworld'), 'background' => __('Background', 'tajiksworld'), 'text' => __('Text', 'tajiksworld'), 'link' => __('Link', 'tajiksworld'), 'dark_bg' => __('Dark background', 'tajiksworld'), 'dark_text' => __('Dark text', 'tajiksworld'), 'dark_card' => __('Dark card', 'tajiksworld'), 'dark_primary' => __('Dark primary', 'tajiksworld')] as $key => $label) {
			self::color($key, $label, (string) $s[$key]);
		}
		self::h(__('Additional', 'tajiksworld'));
		self::select('color_mode', __('Color mode', 'tajiksworld'), (string) $s['color_mode'], ['light' => __('Light', 'tajiksworld'), 'dark' => __('Dark', 'tajiksworld'), 'system' => __('Match system', 'tajiksworld')]);
		self::check('allow_user_dark', __('Let visitors toggle dark mode', 'tajiksworld'), !empty($s['allow_user_dark']));
		self::check('force_rtl', __('Force RTL layout', 'tajiksworld'), !empty($s['force_rtl']));
		self::textarea('custom_css', __('Custom CSS', 'tajiksworld'), (string) $s['custom_css']);
		self::textarea('custom_js', __('Custom JavaScript', 'tajiksworld'), (string) $s['custom_js']);
		self::text('ga_id', __('Google Analytics ID', 'tajiksworld'), (string) $s['ga_id']);
		self::text('pixel_id', __('Meta Pixel ID', 'tajiksworld'), (string) $s['pixel_id']);
		self::textarea('header_scripts', __('Header scripts', 'tajiksworld'), (string) $s['header_scripts']);
		self::textarea('footer_scripts', __('Footer scripts', 'tajiksworld'), (string) $s['footer_scripts']);
		echo '<p class="description">' . esc_html__('Custom code and tracking scripts are stored only by administrators allowed to post unfiltered HTML.', 'tajiksworld') . '</p>';
		submit_button(__('Save settings', 'tajiksworld'));
		echo '</form></div>';
	}

	public static function save_settings(): void {
		check_admin_referer('tw_save_settings');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot change settings.', 'tajiksworld'));
		}
		$input = isset($_POST['tw']) && is_array($_POST['tw']) ? wp_unslash($_POST['tw']) : [];
		$clean = Store::sanitize($input, Options::all());
		update_option(Options::KEY, $clean, false);
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-settings&updated=1'));
		exit;
	}

	public static function seo(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot change settings.', 'tajiksworld'));
		}
		$active = \TajiksWorld\SEO\Head::plugin_active();
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('SEO Settings', 'tajiksworld') . '</h1>';
		echo '<p>' . esc_html($active ? __('Yoast SEO or Rank Math is active, so TajiksWorld is not printing its own description, Open Graph, or Twitter tags.', 'tajiksworld') : __('No SEO plugin detected. TajiksWorld prints a meta description, Open Graph, and Twitter cards, and steps aside automatically if Yoast or Rank Math is activated.', 'tajiksworld')) . '</p>';
		echo '<ul class="tw-check-list">';
		echo '<li>' . esc_html__('NewsArticle JSON-LD is printed on news stories. Canonical URLs come from WordPress core.', 'tajiksworld') . '</li>';
		echo '<li>' . esc_html__('Per-story SEO title and description are edited in the TajiksWorld details box on each article.', 'tajiksworld') . '</li>';
		echo '<li>' . esc_html__('Public content types are included in the WordPress core XML sitemap.', 'tajiksworld') . '</li>';
		echo '</ul><p><a class="button" href="' . esc_url(admin_url('options-reading.php')) . '">' . esc_html__('Reading settings', 'tajiksworld') . '</a> <a class="button" href="' . esc_url(admin_url('options-permalink.php')) . '">' . esc_html__('Permalinks', 'tajiksworld') . '</a></p></div>';
	}

	public static function transfer(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot import settings.', 'tajiksworld'));
		}
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('Import / Export', 'tajiksworld') . '</h1>';
		if (isset($_GET['imported'])) {
			echo '<div class="notice notice-success"><p>' . esc_html__('Import finished.', 'tajiksworld') . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
		wp_nonce_field('tw_export');
		echo '<input type="hidden" name="action" value="tw_export" />';
		submit_button(__('Download export JSON', 'tajiksworld'), 'secondary');
		echo '</form><form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
		wp_nonce_field('tw_import');
		echo '<input type="hidden" name="action" value="tw_import" /><p><input type="file" name="tw_file" accept="application/json,.json" required /></p>';
		submit_button(__('Import JSON', 'tajiksworld'));
		echo '</form><h2>' . esc_html__('Reset a layout', 'tajiksworld') . '</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
		wp_nonce_field('tw_reset_layout');
		echo '<input type="hidden" name="action" value="tw_reset_layout" /><select name="context"><option value="home">home</option><option value="header">header</option><option value="footer">footer</option></select> ';
		submit_button(__('Restore default layout', 'tajiksworld'), 'delete', 'submit', false);
		echo '</form></div>';
	}

	public static function export(): void {
		check_admin_referer('tw_export');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot import settings.', 'tajiksworld'));
		}
		$payload = [
			'theme' => 'tajiksworld',
			'version' => TAJIKSWORLD_VERSION,
			'settings' => Options::all(),
			'layouts' => [
				'home' => Repository::published('home'),
				'header' => Repository::published('header'),
				'footer' => Repository::published('footer'),
			],
			'ads' => Ads::all(),
			'library' => \TajiksWorld\Builder\Library::all(),
		];
		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename=tajiksworld-export.json');
		echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		exit;
	}

	public static function import(): void {
		check_admin_referer('tw_import');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot import settings.', 'tajiksworld'));
		}
		if (!isset($_FILES['tw_file']['tmp_name']) || !is_uploaded_file((string) $_FILES['tw_file']['tmp_name'])) {
			wp_die(esc_html__('No file was uploaded.', 'tajiksworld'));
		}
		$raw = file_get_contents((string) $_FILES['tw_file']['tmp_name']);
		$data = is_string($raw) ? json_decode($raw, true) : null;
		if (!is_array($data) || ($data['theme'] ?? '') !== 'tajiksworld') {
			wp_die(esc_html__('This file is not a TajiksWorld export.', 'tajiksworld'));
		}
		if (isset($data['layouts']) && is_array($data['layouts'])) {
			foreach (['home', 'header', 'footer'] as $context) {
				if (isset($data['layouts'][$context]) && is_array($data['layouts'][$context])) {
					Repository::save($context, $data['layouts'][$context], 'publish');
				}
			}
		}
		if (isset($data['settings']) && is_array($data['settings'])) {
			update_option(Options::KEY, Store::sanitize($data['settings'], Options::all()), false);
		}
		if (isset($data['ads']) && is_array($data['ads'])) {
			Ads::replace($data['ads']);
		}
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-import&imported=1'));
		exit;
	}

	public static function reset_layout(): void {
		check_admin_referer('tw_reset_layout');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot change settings.', 'tajiksworld'));
		}
		$context = sanitize_key((string) ($_POST['context'] ?? 'home'));
		$layout = match ($context) {
			'header' => Defaults::header(),
			'footer' => Defaults::footer(),
			default => Defaults::home(),
		};
		Repository::save($context === 'header' || $context === 'footer' ? $context : 'home', $layout, 'publish');
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-import&imported=1'));
		exit;
	}

	public static function status(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot view this screen.', 'tajiksworld'));
		}
		global $wpdb;
		$rows = [
			__('WordPress', 'tajiksworld') => get_bloginfo('version'),
			__('PHP', 'tajiksworld') => PHP_VERSION,
			__('Theme', 'tajiksworld') => TAJIKSWORLD_VERSION,
			__('MySQL', 'tajiksworld') => (string) $wpdb->db_version(),
			__('Permalink structure', 'tajiksworld') => (string) get_option('permalink_structure') ?: __('Plain', 'tajiksworld'),
			__('HTTPS', 'tajiksworld') => is_ssl() ? __('Yes', 'tajiksworld') : __('No', 'tajiksworld'),
			__('Theme stylesheet', 'tajiksworld') => is_file(TAJIKSWORLD_DIR . '/assets/css/theme.css') ? __('Present', 'tajiksworld') : __('Missing', 'tajiksworld'),
			__('Uploads writable', 'tajiksworld') => wp_is_writable((string) wp_upload_dir()['basedir']) ? __('Yes', 'tajiksworld') : __('No', 'tajiksworld'),
			__('Polylang', 'tajiksworld') => function_exists('pll_current_language') ? __('Active', 'tajiksworld') : __('Not active', 'tajiksworld'),
			__('WPML', 'tajiksworld') => defined('ICL_SITEPRESS_VERSION') ? __('Active', 'tajiksworld') : __('Not active', 'tajiksworld'),
			__('SEO plugin', 'tajiksworld') => \TajiksWorld\SEO\Head::plugin_active() ? __('Active', 'tajiksworld') : __('Not active', 'tajiksworld'),
			__('Debug', 'tajiksworld') => (defined('WP_DEBUG') && WP_DEBUG) ? __('On', 'tajiksworld') : __('Off', 'tajiksworld'),
			__('Memory limit', 'tajiksworld') => ini_get('memory_limit') ?: '',
			__('Newsletter table', 'tajiksworld') => get_option('tajiksworld_newsletter_db') === '1' ? __('Installed', 'tajiksworld') : __('Pending', 'tajiksworld'),
		];
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('System Status', 'tajiksworld') . '</h1><table class="widefat striped"><tbody>';
		foreach ($rows as $label => $value) {
			echo '<tr><th>' . esc_html((string) $label) . '</th><td>' . esc_html((string) $value) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function sample_screen(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot import sample content.', 'tajiksworld'));
		}
		echo '<div class="wrap tw-admin"><h1>' . esc_html__('Sample Content', 'tajiksworld') . '</h1>';
		echo '<p>' . esc_html__('Creates original sample categories and stories so the homepage can be reviewed. You can delete them like any other WordPress content.', 'tajiksworld') . '</p>';
		if (isset($_GET['done'])) {
			echo '<div class="notice notice-success"><p>' . esc_html__('Sample content is ready.', 'tajiksworld') . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
		wp_nonce_field('tw_sample');
		echo '<input type="hidden" name="action" value="tw_sample" />';
		submit_button(__('Import sample content', 'tajiksworld'));
		echo '</form></div>';
	}

	public static function sample(): void {
		check_admin_referer('tw_sample');
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You cannot import sample content.', 'tajiksworld'));
		}
		SampleContent::import();
		wp_safe_redirect(admin_url('admin.php?page=tajiksworld-sample&done=1'));
		exit;
	}

	/** @param array<string, string> $options */
	private static function select(string $key, string $label, string $value, array $options): void {
		echo '<p><label>' . esc_html($label) . '<br /><select name="tw[' . esc_attr($key) . ']">';
		foreach ($options as $option => $text) {
			echo '<option value="' . esc_attr((string) $option) . '" ' . selected($value, (string) $option, false) . '>' . esc_html((string) $text) . '</option>';
		}
		echo '</select></label></p>';
	}

	private static function text(string $key, string $label, string $value): void {
		echo '<p><label>' . esc_html($label) . '<br /><input class="regular-text" type="text" name="tw[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" /></label></p>';
	}

	private static function number(string $key, string $label, int $value): void {
		echo '<p><label>' . esc_html($label) . '<br /><input type="number" name="tw[' . esc_attr($key) . ']" value="' . esc_attr((string) $value) . '" /></label></p>';
	}

	private static function color(string $key, string $label, string $value): void {
		echo '<p><label>' . esc_html($label) . ' <input type="color" name="tw[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" /></label></p>';
	}

	private static function textarea(string $key, string $label, string $value): void {
		echo '<p><label>' . esc_html($label) . '<br /><textarea class="large-text" rows="4" name="tw[' . esc_attr($key) . ']">' . esc_textarea($value) . '</textarea></label></p>';
	}

	private static function check(string $key, string $label, bool $on): void {
		echo '<p><label><input type="checkbox" name="tw[' . esc_attr($key) . ']" value="1" ' . checked($on, true, false) . ' /> ' . esc_html($label) . '</label></p>';
	}

	private static function h(string $label): void {
		echo '<h2>' . esc_html($label) . '</h2>';
	}

	private static function stat(string $label, string $value): void {
		echo '<div class="tw-stat"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong></div>';
	}

	/** @return array<string, string> */
	private static function timezones(): array {
		$list = timezone_identifiers_list();
		$options = ['' => __('Select', 'tajiksworld')];
		foreach ($list as $zone) {
			$options[$zone] = $zone;
		}
		return $options;
	}
}
