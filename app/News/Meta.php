<?php
/**
 * Editorial fields for news and the other content types.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\News;

final class Meta {
	/** @var array<int, string> */
	private const TYPES = ['post', 'tw_video', 'tw_gallery', 'tw_podcast', 'tw_interview', 'tw_report'];

	public static function register(): void {
		add_action('init', [self::class, 'register_meta']);
		add_action('add_meta_boxes', [self::class, 'boxes']);
		add_action('save_post', [self::class, 'save']);
		add_filter('manage_post_posts_columns', [self::class, 'columns']);
		add_action('manage_post_posts_custom_column', [self::class, 'column'], 10, 2);
	}

	public static function register_meta(): void {
		$string_keys = ['_tw_source', '_tw_video_url', '_tw_audio_url', '_tw_seo_title', '_tw_seo_description', '_tw_guest', '_tw_guest_role', '_tw_pdf_url'];
		foreach (self::TYPES as $type) {
			foreach ($string_keys as $key) {
				register_post_meta($type, $key, [
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static fn (): bool => current_user_can('edit_posts'),
				]);
			}
			register_post_meta($type, '_tw_gallery', [
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback'=> static fn (): bool => current_user_can('edit_posts'),
			]);
		}
	}

	public static function boxes(): void {
		foreach (self::TYPES as $type) {
			add_meta_box('tajiksworld_editorial', __('TajiksWorld details', 'tajiksworld'), [self::class, 'render'], $type, 'normal', 'high');
		}
		add_meta_box('tajiksworld_page_builder', __('TajiksWorld builder', 'tajiksworld'), [self::class, 'render_page'], 'page', 'side', 'high');
	}

	public static function render_page(\WP_Post $post): void {
		$url = admin_url('admin.php?page=tajiksworld-builder&post_id=' . $post->ID . '&context=page');
		echo '<p><a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Edit with the visual builder', 'tajiksworld') . '</a></p>';
		echo '<p class="description">' . esc_html__('Opens the page canvas. Publishing a layout replaces the page content on the front end.', 'tajiksworld') . '</p>';
	}

	public static function render(\WP_Post $post): void {
		wp_nonce_field('tw_news_meta', 'tw_news_meta_nonce');
		$flags = [
			'_tw_breaking'       => __('Breaking', 'tajiksworld'),
			'_tw_featured'       => __('Featured', 'tajiksworld'),
			'_tw_trending'       => __('Trending', 'tajiksworld'),
			'_tw_editors_choice' => __("Editor's choice", 'tajiksworld'),
		];
		echo '<div class="tw-meta-grid">';
		if ($post->post_type === 'post') {
			echo '<p><strong>' . esc_html__('Newsroom flags', 'tajiksworld') . '</strong></p><p>';
			foreach ($flags as $key => $label) {
				echo '<label style="margin-right:1rem"><input type="checkbox" name="' . esc_attr($key) . '" value="1" ' . checked(get_post_meta($post->ID, $key, true), '1', false) . ' /> ' . esc_html($label) . '</label>';
			}
			echo '</p>';
		}
		self::field(__('Source', 'tajiksworld'), '_tw_source', (string) get_post_meta($post->ID, '_tw_source', true), 'text');
		self::field(__('Video URL', 'tajiksworld'), '_tw_video_url', (string) get_post_meta($post->ID, '_tw_video_url', true), 'url');
		self::field(__('Audio URL', 'tajiksworld'), '_tw_audio_url', (string) get_post_meta($post->ID, '_tw_audio_url', true), 'url');
		self::field(__('Gallery image IDs (comma separated)', 'tajiksworld'), '_tw_gallery', (string) get_post_meta($post->ID, '_tw_gallery', true), 'text');
		if ($post->post_type === 'tw_interview') {
			self::field(__('Guest', 'tajiksworld'), '_tw_guest', (string) get_post_meta($post->ID, '_tw_guest', true), 'text');
			self::field(__('Guest role', 'tajiksworld'), '_tw_guest_role', (string) get_post_meta($post->ID, '_tw_guest_role', true), 'text');
		}
		if ($post->post_type === 'tw_report') {
			self::field(__('PDF URL', 'tajiksworld'), '_tw_pdf_url', (string) get_post_meta($post->ID, '_tw_pdf_url', true), 'url');
		}
		self::field(__('SEO title', 'tajiksworld'), '_tw_seo_title', (string) get_post_meta($post->ID, '_tw_seo_title', true), 'text');
		self::field(__('SEO description', 'tajiksworld'), '_tw_seo_description', (string) get_post_meta($post->ID, '_tw_seo_description', true), 'textarea');
		$views = (int) get_post_meta($post->ID, '_tw_views', true);
		echo '<p><label>' . esc_html__('Views', 'tajiksworld') . '<br /><input type="number" min="0" name="_tw_views" value="' . esc_attr((string) $views) . '" /></label></p>';
		echo '</div>';
	}

	public static function save(int $post_id): void {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (!isset($_POST['tw_news_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['tw_news_meta_nonce'])), 'tw_news_meta')) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		$post_type = get_post_type($post_id);
		if (!is_string($post_type) || !in_array($post_type, self::TYPES, true)) {
			return;
		}
		foreach (['_tw_breaking', '_tw_featured', '_tw_trending', '_tw_editors_choice'] as $flag) {
			update_post_meta($post_id, $flag, isset($_POST[$flag]) ? '1' : '0');
		}
		$strings = ['_tw_source', '_tw_guest', '_tw_guest_role', '_tw_seo_title'];
		foreach ($strings as $key) {
			update_post_meta($post_id, $key, sanitize_text_field(wp_unslash((string) ($_POST[$key] ?? ''))));
		}
		foreach (['_tw_video_url', '_tw_audio_url', '_tw_pdf_url'] as $key) {
			update_post_meta($post_id, $key, esc_url_raw(wp_unslash((string) ($_POST[$key] ?? ''))));
		}
		$gallery = sanitize_text_field(wp_unslash((string) ($_POST['_tw_gallery'] ?? '')));
		$ids = array_filter(array_map('absint', explode(',', $gallery)));
		update_post_meta($post_id, '_tw_gallery', implode(',', $ids));
		update_post_meta($post_id, '_tw_seo_description', sanitize_textarea_field(wp_unslash((string) ($_POST['_tw_seo_description'] ?? ''))));
		if (isset($_POST['_tw_views'])) {
			update_post_meta($post_id, '_tw_views', (string) absint($_POST['_tw_views']));
		}
	}

	/** @param array<string, string> $columns @return array<string, string> */
	public static function columns(array $columns): array {
		$columns['tw_flags'] = __('Flags', 'tajiksworld');
		$columns['tw_views'] = __('Views', 'tajiksworld');
		return $columns;
	}

	public static function column(string $column, int $post_id): void {
		if ($column === 'tw_views') {
			echo esc_html((string) (int) get_post_meta($post_id, '_tw_views', true));
			return;
		}
		if ($column !== 'tw_flags') {
			return;
		}
		$labels = [];
		if (get_post_meta($post_id, '_tw_breaking', true) === '1') {
			$labels[] = __('Breaking', 'tajiksworld');
		}
		if (get_post_meta($post_id, '_tw_featured', true) === '1') {
			$labels[] = __('Featured', 'tajiksworld');
		}
		if (get_post_meta($post_id, '_tw_trending', true) === '1') {
			$labels[] = __('Trending', 'tajiksworld');
		}
		if (get_post_meta($post_id, '_tw_editors_choice', true) === '1') {
			$labels[] = __("Editor's choice", 'tajiksworld');
		}
		echo esc_html($labels === [] ? '—' : implode(', ', $labels));
	}

	private static function field(string $label, string $name, string $value, string $type): void {
		echo '<p><label for="' . esc_attr($name) . '"><strong>' . esc_html($label) . '</strong></label><br />';
		if ($type === 'textarea') {
			echo '<textarea class="widefat" rows="3" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
		} else {
			echo '<input class="widefat" type="' . esc_attr($type) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
		}
		echo '</p>';
	}
}
