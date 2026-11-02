<?php
/**
 * Advertisement storage and rendering.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Ads;

use TajiksWorld\Security\Kses;

final class Manager {
	public const KEY = 'tajiksworld_ads';

	/** @var array<int, string> */
	public const PLACEMENTS = ['header', 'homepage', 'sidebar', 'in_article', 'between', 'footer', 'mobile'];

	public static function register(): void {
		add_action('init', [self::class, 'ensure'], 20);
	}

	public static function ensure(): void {
		if (!is_array(get_option(self::KEY, null)) && get_option(self::KEY, null) !== []) {
			// Option may be false. Leave it until the first save.
		}
	}

	/** @return array<int, array<string, mixed>> */
	public static function all(): array {
		$ads = get_option(self::KEY, []);
		return is_array($ads) ? array_values($ads) : [];
	}

	/** @param array<string, mixed> $input */
	public static function save_one(array $input, bool $allow_html): array {
		$ads = self::all();
		$id = isset($input['id']) && preg_match('/^ad_[a-z0-9]{8}$/', (string) $input['id']) === 1
			? (string) $input['id']
			: 'ad_' . bin2hex(random_bytes(4));
		$placement = (string) ($input['placement'] ?? 'homepage');
		if (!in_array($placement, self::PLACEMENTS, true)) {
			$placement = 'homepage';
		}
		$devices = [];
		foreach ((array) ($input['devices'] ?? []) as $device) {
			if (in_array((string) $device, ['desktop', 'tablet', 'mobile'], true)) {
				$devices[] = (string) $device;
			}
		}
		if ($devices === []) {
			$devices = ['desktop', 'tablet', 'mobile'];
		}
		$html = (string) ($input['html'] ?? '');
		$html = $allow_html ? Kses::strip_php($html) : Kses::post($html);
		$record = [
			'id'        => $id,
			'title'     => mb_substr(sanitize_text_field((string) ($input['title'] ?? '')), 0, 120),
			'placement' => $placement,
			'image_id'  => absint($input['image_id'] ?? 0),
			'html'      => $html,
			'url'       => esc_url_raw((string) ($input['url'] ?? '')),
			'start'     => self::date((string) ($input['start'] ?? '')),
			'end'       => self::date((string) ($input['end'] ?? '')),
			'active'    => !empty($input['active']),
			'devices'   => array_values(array_unique($devices)),
		];
		$found = false;
		foreach ($ads as $index => $ad) {
			if (($ad['id'] ?? '') === $id) {
				$ads[$index] = $record;
				$found = true;
			}
		}
		if (!$found) {
			$ads[] = $record;
		}
		update_option(self::KEY, $ads, false);
		return $record;
	}

	public static function delete(string $id): void {
		$ads = array_values(array_filter(self::all(), static fn (array $ad): bool => ($ad['id'] ?? '') !== $id));
		update_option(self::KEY, $ads, false);
	}

	/** @param array<int, mixed> $ads */
	public static function replace(array $ads): void {
		update_option(self::KEY, [], false);
		foreach ($ads as $ad) {
			if (is_array($ad)) {
				self::save_one($ad, current_user_can('unfiltered_html'));
			}
		}
	}

	public static function render(string $placement): string {
		if (!in_array($placement, self::PLACEMENTS, true)) {
			return '';
		}
		$html = '';
		foreach (self::all() as $ad) {
			if (($ad['placement'] ?? '') !== $placement || !self::is_live($ad)) {
				continue;
			}
			$html .= self::markup($ad);
		}
		if ($html === '') {
			return '';
		}
		return $html;
	}

	/** @param array<string, mixed> $ad */
	private static function is_live(array $ad): bool {
		if (empty($ad['active'])) {
			return false;
		}
		$today = function_exists('current_time') ? current_time('Y-m-d') : gmdate('Y-m-d');
		$start = (string) ($ad['start'] ?? '');
		$end = (string) ($ad['end'] ?? '');
		if ($start !== '' && $today < $start) {
			return false;
		}
		if ($end !== '' && $today > $end) {
			return false;
		}
		return true;
	}

	/** @param array<string, mixed> $ad */
	private static function markup(array $ad): string {
		$devices = (array) ($ad['devices'] ?? []);
		$classes = ['tw-ad', 'tw-ad-' . sanitize_html_class((string) $ad['placement'])];
		foreach (['desktop', 'tablet', 'mobile'] as $device) {
			if (!in_array($device, $devices, true)) {
				$classes[] = 'tw-hide-' . $device;
			}
		}
		$image_id = (int) ($ad['image_id'] ?? 0);
		$inner = '';
		if ($image_id > 0 && function_exists('wp_get_attachment_image')) {
			$inner = wp_get_attachment_image($image_id, 'large', false, ['class' => 'tw-ad-img', 'loading' => 'lazy', 'alt' => (string) ($ad['title'] ?? '')]);
			$url = (string) ($ad['url'] ?? '');
			if ($inner !== '' && $url !== '') {
				$inner = '<a href="' . esc_url($url) . '" rel="sponsored noopener noreferrer">' . $inner . '</a>';
			}
		} elseif (!empty($ad['html'])) {
			$inner = Kses::post((string) $ad['html']);
		}
		if ($inner === '') {
			return '';
		}
		return '<aside class="' . esc_attr(implode(' ', $classes)) . '" aria-label="' . esc_attr__('Advertisement', 'tajiksworld') . '">' . $inner . '</aside>';
	}

	private static function date(string $value): string {
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
	}
}
