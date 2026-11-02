<?php
/**
 * Contact form handler.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\API;

use TajiksWorld\Helpers\Options;

final class Contact {
	public static function register(): void {
		add_action('admin_post_nopriv_tw_contact', [self::class, 'handle']);
		add_action('admin_post_tw_contact', [self::class, 'handle']);
	}

	public static function handle(): void {
		if (!isset($_POST['tw_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['tw_contact_nonce'])), 'tw_contact')) {
			wp_die(esc_html__('The form expired. Please try again.', 'tajiksworld'), '', ['response' => 403]);
		}
		$back = wp_get_referer() ?: home_url('/');
		if (!empty($_POST['tw_company'])) {
			wp_safe_redirect(add_query_arg('contact', 'sent', $back));
			exit;
		}
		$hash = hash('sha256', (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '') . wp_salt('auth'));
		if ((int) get_transient('tw_contact_' . $hash) >= 5) {
			wp_safe_redirect(add_query_arg('contact', 'limit', $back));
			exit;
		}
		set_transient('tw_contact_' . $hash, (int) get_transient('tw_contact_' . $hash) + 1, HOUR_IN_SECONDS);
		$name = sanitize_text_field(wp_unslash((string) ($_POST['tw_name'] ?? '')));
		$email = sanitize_email(wp_unslash((string) ($_POST['tw_email'] ?? '')));
		$message = sanitize_textarea_field(wp_unslash((string) ($_POST['tw_message'] ?? '')));
		if ($name === '' || !is_email($email) || mb_strlen($message) < 10) {
			wp_safe_redirect(add_query_arg('contact', 'invalid', $back));
			exit;
		}
		$to = (string) Options::get('contact_email', '');
		if ($to === '' || !is_email($to)) {
			$to = (string) get_option('admin_email');
		}
		$sent = wp_mail(
			$to,
			sprintf('[TajiksWorld] %s', $name),
			"From: {$name} <{$email}>\n\n{$message}",
			['Reply-To: ' . $name . ' <' . $email . '>']
		);
		wp_safe_redirect(add_query_arg('contact', $sent ? 'sent' : 'error', $back));
		exit;
	}
}
