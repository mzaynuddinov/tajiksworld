<?php
/**
 * Sanitize TajiksWorld settings and mirror identity fields into WordPress options.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Settings;

use TajiksWorld\Helpers\Options;
use TajiksWorld\Security\Kses;

final class Store {
	/** @var array<int, string> */
	private const FONTS = ['Newsreader', 'Source Sans 3', 'Source Serif 4', 'Libre Franklin', 'Literata', 'system-ui'];

	/** @var array<int, string> */
	private const HEX = ['header_bg', 'header_text', 'primary', 'secondary', 'accent', 'background', 'text', 'link', 'dark_bg', 'dark_text', 'dark_card', 'dark_primary'];

	/** @var array<int, string> */
	private const BOOLS = ['topbar_enabled', 'sticky_header', 'transparent_header', 'show_search', 'show_developer', 'allow_user_dark', 'force_rtl', 'social_rss'];

	/** @var array<int, string> */
	private const URLS = ['social_facebook', 'social_x', 'social_instagram', 'social_telegram', 'social_youtube'];

	/**
	 * @param array<string, mixed> $input
	 * @param array<string, mixed> $previous
	 * @return array<string, mixed>
	 */
	public static function sanitize(array $input, array $previous): array {
		$clean = Options::defaults();
		$can_code = function_exists('current_user_can') ? current_user_can('unfiltered_html') : false;

		foreach (self::HEX as $key) {
			$value = isset($input[$key]) ? (string) $input[$key] : (string) $clean[$key];
			$clean[$key] = Options::hex($value, (string) $clean[$key]);
		}
		foreach (self::BOOLS as $key) {
			$clean[$key] = !empty($input[$key]);
		}
		foreach (self::URLS as $key) {
			$url = isset($input[$key]) ? (string) $input[$key] : '';
			$clean[$key] = function_exists('esc_url_raw') ? esc_url_raw($url) : (filter_var($url, FILTER_VALIDATE_URL) ?: '');
		}

		$clean['retina_logo'] = absint($input['retina_logo'] ?? 0);
		$clean['footer_logo'] = absint($input['footer_logo'] ?? 0);
		$clean['contact_email'] = sanitize_email((string) ($input['contact_email'] ?? ''));
		$clean['contact_phone'] = mb_substr(sanitize_text_field((string) ($input['contact_phone'] ?? '')), 0, 40);
		$clean['contact_address'] = mb_substr(sanitize_textarea_field((string) ($input['contact_address'] ?? '')), 0, 300);
		$lang = (string) ($input['default_language'] ?? 'en');
		$clean['default_language'] = in_array($lang, ['en', 'ru', 'tj'], true) ? $lang : 'en';
		$clean['topbar_text'] = mb_substr(sanitize_text_field((string) ($input['topbar_text'] ?? '')), 0, 180);
		$clean['footer_description'] = mb_substr(sanitize_textarea_field((string) ($input['footer_description'] ?? '')), 0, 400);
		$clean['copyright'] = mb_substr(sanitize_text_field((string) ($input['copyright'] ?? $clean['copyright'])), 0, 180);
		$clean['developer_credit'] = mb_substr(sanitize_text_field((string) ($input['developer_credit'] ?? '')), 0, 80);
		$clean['font_heading'] = self::font((string) ($input['font_heading'] ?? ''), 'Newsreader');
		$clean['font_body'] = self::font((string) ($input['font_body'] ?? ''), 'Source Sans 3');
		$clean['font_size'] = max(15, min(22, (int) ($input['font_size'] ?? 18)));
		$lh = (string) ($input['line_height'] ?? '1.65');
		$clean['line_height'] = in_array($lh, ['1.45', '1.55', '1.65', '1.8'], true) ? $lh : '1.65';
		$weight = (string) ($input['heading_weight'] ?? '560');
		$clean['heading_weight'] = in_array($weight, ['500', '560', '650', '750'], true) ? $weight : '560';
		$mode = (string) ($input['color_mode'] ?? 'system');
		$clean['color_mode'] = in_array($mode, ['light', 'dark', 'system'], true) ? $mode : 'system';

		if ($can_code) {
			$clean['custom_css'] = self::safe_css((string) ($input['custom_css'] ?? ''));
			$clean['custom_js'] = Kses::strip_php((string) ($input['custom_js'] ?? ''));
			$clean['header_scripts'] = Kses::strip_php((string) ($input['header_scripts'] ?? ''));
			$clean['footer_scripts'] = Kses::strip_php((string) ($input['footer_scripts'] ?? ''));
		} else {
			$clean['custom_css'] = (string) ($previous['custom_css'] ?? '');
			$clean['custom_js'] = (string) ($previous['custom_js'] ?? '');
			$clean['header_scripts'] = (string) ($previous['header_scripts'] ?? '');
			$clean['footer_scripts'] = (string) ($previous['footer_scripts'] ?? '');
		}

		$ga = strtoupper(trim((string) ($input['ga_id'] ?? '')));
		$clean['ga_id'] = preg_match('/^(G-[A-Z0-9]+|UA-\d+-\d+)$/', $ga) === 1 ? $ga : '';
		$pixel = preg_replace('/\D/', '', (string) ($input['pixel_id'] ?? '')) ?? '';
		$clean['pixel_id'] = preg_match('/^\d{5,20}$/', $pixel) === 1 ? $pixel : '';

		self::apply_core($input);
		return $clean;
	}

	/** @param array<string, mixed> $input */
	public static function apply_core(array $input): void {
		if (!function_exists('update_option')) {
			return;
		}
		if (isset($input['site_title'])) {
			update_option('blogname', sanitize_text_field((string) $input['site_title']));
		}
		if (isset($input['site_description'])) {
			update_option('blogdescription', sanitize_text_field((string) $input['site_description']));
		}
		if (!empty($input['timezone'])) {
			$tz = (string) $input['timezone'];
			$valid = function_exists('timezone_identifiers_list') ? timezone_identifiers_list() : [];
			if (in_array($tz, $valid, true)) {
				update_option('timezone_string', $tz);
			}
		}
		$date_formats = ['F j, Y', 'j F Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'];
		if (isset($input['date_format']) && in_array((string) $input['date_format'], $date_formats, true)) {
			update_option('date_format', (string) $input['date_format']);
		}
		$time_formats = ['g:i a', 'H:i'];
		if (isset($input['time_format']) && in_array((string) $input['time_format'], $time_formats, true)) {
			update_option('time_format', (string) $input['time_format']);
		}
		if (isset($input['logo_id']) && function_exists('set_theme_mod')) {
			set_theme_mod('custom_logo', absint($input['logo_id']));
		}
		if (isset($input['site_icon'])) {
			update_option('site_icon', absint($input['site_icon']));
		}
	}

	private static function font(string $value, string $fallback): string {
		return in_array($value, self::FONTS, true) ? $value : $fallback;
	}

	private static function safe_css(string $css): string {
		$css = wp_strip_all_tags($css);
		$css = str_replace(['<', '>'], '', $css);
		return mb_substr($css, 0, 20000);
	}
}
