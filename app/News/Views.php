<?php
/**
 * View counter that stays compatible with page caches.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\News;

final class Views {
	public static function register(): void {
		add_action('wp_ajax_tw_view', [self::class, 'count']);
		add_action('wp_ajax_nopriv_tw_view', [self::class, 'count']);
	}

	public static function count(): void {
		check_ajax_referer('tw_view', 'nonce');
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		$post = get_post($post_id);
		if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
			wp_send_json_error(['message' => 'invalid'], 400);
		}
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '0';
		$key = 'tw_view_' . $post_id . '_' . hash('sha256', $ip);
		if (get_transient($key)) {
			wp_send_json_success(['views' => (int) get_post_meta($post_id, '_tw_views', true), 'counted' => false]);
		}
		set_transient($key, 1, 30 * MINUTE_IN_SECONDS);
		$views = (int) get_post_meta($post_id, '_tw_views', true) + 1;
		update_post_meta($post_id, '_tw_views', (string) $views);
		wp_send_json_success(['views' => $views, 'counted' => true]);
	}
}
