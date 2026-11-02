<?php
/**
 * Inline icons.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Helpers;

final class Icons {
	public static function render(string $name, int $size = 20): string {
		$icons = [
			'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
			'play'   => '<path d="M8 5v14l12-7z"/>',
			'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 7 9-7"/>',
			'clock'  => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
			'user'   => '<circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 4-4.5 7-4.5S17.5 16 19 19"/>',
			'rss'    => '<path d="M5 19a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/><path d="M5 5a14 14 0 0 1 14 14"/><path d="M5 11a8 8 0 0 1 8 8"/>',
			'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/>',
			'moon'   => '<path d="M20 14.5A8 8 0 1 1 9.5 4 6.5 6.5 0 0 0 20 14.5z"/>',
		];
		$svg = $icons[$name] ?? $icons['arrow'];
		$size = max(12, min(96, $size));
		return '<svg class="tw-icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $svg . '</svg>';
	}
}
