<?php
/**
 * Reusable builder sections.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

final class Library {
	private const KEY = 'tajiksworld_library';

	/** @return array<int, array<string, mixed>> */
	public static function all(): array {
		$items = get_option(self::KEY, []);
		return is_array($items) ? array_values($items) : [];
	}

	/** @param array<string, mixed> $section */
	public static function add(string $name, array $section): array {
		$sanitizer = new LayoutSanitizer();
		$wrapped = $sanitizer->sanitize(['sections' => [$section]], current_user_can('unfiltered_html'));
		$clean = $wrapped['sections'][0] ?? null;
		if (!is_array($clean)) {
			return self::all();
		}
		$items = self::all();
		$items[] = [
			'id'      => 'lib_' . bin2hex(random_bytes(4)),
			'name'    => mb_substr(sanitize_text_field($name), 0, 80),
			'section' => $clean,
		];
		update_option(self::KEY, array_slice($items, -40), false);
		return self::all();
	}

	public static function delete(string $id): array {
		$id = preg_match('/^[a-zA-Z0-9_-]{1,40}$/', $id) === 1 ? $id : '';
		$items = array_values(array_filter(
			self::all(),
			static fn (array $item): bool => ($item['id'] ?? '') !== $id
		));
		update_option(self::KEY, $items, false);
		return $items;
	}
}
