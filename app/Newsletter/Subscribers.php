<?php
/**
 * Newsletter storage, subscribe endpoint, and unsubscribe links.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Newsletter;

final class Subscribers {
	public static function register(): void {
		add_action('after_switch_theme', [self::class, 'install']);
		add_action('init', [self::class, 'maybe_install'], 5);
		add_action('wp_ajax_tw_subscribe', [self::class, 'subscribe']);
		add_action('wp_ajax_nopriv_tw_subscribe', [self::class, 'subscribe']);
		add_action('template_redirect', [self::class, 'unsubscribe']);
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'tw_subscribers';
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table = self::table();
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) {$charset};";
		dbDelta($sql);
		update_option('tajiksworld_newsletter_db', '1', false);
	}

	public static function maybe_install(): void {
		if (get_option('tajiksworld_newsletter_db') !== '1') {
			self::install();
		}
	}

	public static function subscribe(): void {
		check_ajax_referer('tw_subscribe', 'nonce');
		if (!empty($_POST['tw_company'])) {
			wp_send_json_error(['message' => __('Subscription could not be completed.', 'tajiksworld')], 400);
		}
		$email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
		if (!is_email($email)) {
			wp_send_json_error(['message' => __('Enter a valid email address.', 'tajiksworld')], 400);
		}
		$hash = self::ip_hash();
		$key = 'tw_sub_' . $hash;
		$hits = (int) get_transient($key);
		if ($hits >= 5) {
			wp_send_json_error(['message' => __('Too many attempts. Please try again later.', 'tajiksworld')], 429);
		}
		set_transient($key, $hits + 1, HOUR_IN_SECONDS);
		global $wpdb;
		$existing = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . self::table() . ' WHERE email = %s', $email));
		$now = current_time('mysql');
		if ($existing) {
			$wpdb->update(self::table(), ['status' => 'active'], ['id' => (int) $existing], ['%s'], ['%d']);
		} else {
			$wpdb->insert(
				self::table(),
				[
					'email'      => $email,
					'status'     => 'active',
					'created_at' => $now,
					'ip_hash'    => $hash,
				],
				['%s', '%s', '%s', '%s']
			);
		}
		wp_send_json_success(['message' => __('You are subscribed. Thank you.', 'tajiksworld')]);
	}

	public static function unsubscribe(): void {
		if (!isset($_GET['tw_unsub'], $_GET['email'], $_GET['token'])) {
			return;
		}
		$email = sanitize_email(wp_unslash((string) $_GET['email']));
		$token = sanitize_text_field(wp_unslash((string) $_GET['token']));
		if (!is_email($email) || !hash_equals(self::token($email), $token)) {
			wp_die(esc_html__('This unsubscribe link is not valid.', 'tajiksworld'), '', ['response' => 400]);
		}
		global $wpdb;
		$wpdb->update(self::table(), ['status' => 'unsubscribed'], ['email' => $email], ['%s'], ['%s']);
		wp_die(esc_html__('You have been unsubscribed.', 'tajiksworld'), esc_html__('Newsletter', 'tajiksworld'), ['response' => 200]);
	}

	public static function token(string $email): string {
		return hash_hmac('sha256', strtolower($email), wp_salt('auth'));
	}

	/** @return array<int, array<string, mixed>> */
	public static function list(int $limit = 200): array {
		global $wpdb;
		$table = self::table();
		$like = $wpdb->esc_like($table);
		$found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
		if ($found !== $table) {
			return [];
		}
		$rows = $wpdb->get_results($wpdb->prepare("SELECT id, email, status, created_at FROM {$table} ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);
		return is_array($rows) ? $rows : [];
	}

	public static function delete(int $id): void {
		global $wpdb;
		$wpdb->delete(self::table(), ['id' => $id], ['%d']);
	}

	public static function count_active(): int {
		global $wpdb;
		$table = self::table();
		$value = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'active'));
		return (int) $value;
	}

	private static function ip_hash(): string {
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
		return hash('sha256', $ip . '|' . wp_salt('auth'));
	}
}
