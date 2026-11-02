<?php
/**
 * Interface languages: Tajik, Russian, and English.
 * Article translations stay with Polylang or WPML.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Core;

use TajiksWorld\Helpers\Options;

final class I18n {
	/** @var array<string, string> */
	public const LOCALES = [
		'en' => 'en_US',
		'ru' => 'ru_RU',
		'tj' => 'tg_TJ',
	];

	public static function register(): void {
		add_filter('locale', [self::class, 'locale']);
		add_action('init', [self::class, 'load'], 0);
		add_action('template_redirect', [self::class, 'persist_choice']);
		add_action('init', [self::class, 'polylang_strings']);
	}

	public static function plugin_controls_language(): bool {
		return function_exists('pll_current_language') || defined('ICL_SITEPRESS_VERSION') || has_filter('wpml_current_language');
	}

	public static function locale(string $locale): string {
		if (self::plugin_controls_language()) {
			return $locale;
		}
		$code = self::requested_code();
		return self::LOCALES[$code] ?? $locale;
	}

	public static function load(): void {
		load_theme_textdomain('tajiksworld', TAJIKSWORLD_DIR . '/languages');
	}

	public static function persist_choice(): void {
		if (self::plugin_controls_language() || !isset($_GET['tw_lang'])) {
			return;
		}
		$code = sanitize_key(wp_unslash((string) $_GET['tw_lang']));
		if (!isset(self::LOCALES[$code])) {
			return;
		}
		$secure = is_ssl();
		setcookie('tw_ui_lang', $code, [
			'expires'  => time() + YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ?: '/',
			'secure'   => $secure,
			'httponly' => false,
			'samesite' => 'Lax',
		]);
		$_COOKIE['tw_ui_lang'] = $code;
		wp_safe_redirect(remove_query_arg('tw_lang'));
		exit;
	}

	public static function current_code(): string {
		if (function_exists('pll_current_language')) {
			$lang = (string) pll_current_language('slug');
			if ($lang === 'tg') {
				return 'tj';
			}
			return in_array($lang, ['en', 'ru', 'tj'], true) ? $lang : 'en';
		}
		return self::requested_code();
	}

	public static function polylang_strings(): void {
		if (!function_exists('pll_register_string')) {
			return;
		}
		pll_register_string('copyright', (string) Options::get('copyright', ''), 'TajiksWorld');
		pll_register_string('footer_description', (string) Options::get('footer_description', ''), 'TajiksWorld');
		pll_register_string('topbar_text', (string) Options::get('topbar_text', ''), 'TajiksWorld');
	}

	private static function requested_code(): string {
		if (isset($_GET['tw_lang'])) {
			$code = sanitize_key(wp_unslash((string) $_GET['tw_lang']));
			if (isset(self::LOCALES[$code])) {
				return $code;
			}
		}
		if (isset($_COOKIE['tw_ui_lang'])) {
			$code = sanitize_key(wp_unslash((string) $_COOKIE['tw_ui_lang']));
			if (isset(self::LOCALES[$code])) {
				return $code;
			}
		}
		$default = function_exists('get_option') ? (string) Options::get('default_language', 'en') : 'en';
		return isset(self::LOCALES[$default]) ? $default : 'en';
	}
}
