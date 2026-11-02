<?php
/**
 * Per-user live preview transient.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

final class Preview {
	public static function register(): void {
		add_action('template_redirect', [self::class, 'nocache'], 0);
	}

	public static function active(): bool {
		if (!isset($_GET['tw_builder_preview'])) {
			return false;
		}
		return is_user_logged_in() && (current_user_can('edit_pages') || current_user_can('edit_theme_options') || current_user_can('manage_options'));
	}

	public static function context(): string {
		$context = isset($_GET['tw_context']) ? sanitize_key((string) wp_unslash($_GET['tw_context'])) : 'home';
		return in_array($context, ['home', 'header', 'footer', 'page'], true) ? $context : 'home';
	}

	public static function post_id(): int {
		return isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
	}

	/** @return array<string, mixed>|null */
	public static function layout(): ?array {
		if (!self::active()) {
			return null;
		}
		$stored = get_transient(self::key());
		return is_array($stored) ? $stored : null;
	}

	/** @param array<string, mixed> $layout */
	public static function store(array $layout): void {
		set_transient(self::key(), $layout, 10 * MINUTE_IN_SECONDS);
	}

	public static function nocache(): void {
		if (!self::active()) {
			return;
		}
		nocache_headers();
		if (self::context() !== 'page' || !is_404()) {
			return;
		}
		$post = get_post(self::post_id());
		if (!$post instanceof \WP_Post || !current_user_can('edit_post', $post->ID)) {
			return;
		}
		global $wp_query;
		$wp_query->posts = [$post];
		$wp_query->post = $post;
		$wp_query->post_count = 1;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = (int) $post->ID;
		$wp_query->is_404 = false;
		$wp_query->is_page = true;
		$wp_query->is_singular = true;
		$wp_query->is_single = false;
		status_header(200);
	}

	private static function key(): string {
		return 'tw_builder_preview_' . get_current_user_id();
	}
}
