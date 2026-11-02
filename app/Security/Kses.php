<?php
/**
 * HTML allow-list used when WordPress kses is unavailable and as a fallback.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Security;

final class Kses {
	public static function post(string $html): string {
		if (function_exists('wp_kses_post')) {
			return wp_kses_post($html);
		}
		$html = preg_replace('#<(script|style|iframe|object|embed|form|link|meta)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
		$html = preg_replace('#<(script|style|iframe|object|embed|form|link|meta)\b[^>]*\/?>#is', '', $html) ?? '';
		$html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
		$html = preg_replace('#(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2#i', '$1=$2#$2', $html) ?? '';
		return strip_tags(
			$html,
			'<a><abbr><b><blockquote><br><cite><code><div><em><figcaption><figure><h2><h3><h4><h5><hr><i><img><li><ol><p><span><strong><sub><sup><table><tbody><td><th><thead><tr><ul>'
		);
	}

	public static function strip_php(string $code): string {
		$stripped = preg_replace('/<\?(?:php)?[\s\S]*?\?>/i', '', $code);
		return is_string($stripped) ? $stripped : '';
	}
}
