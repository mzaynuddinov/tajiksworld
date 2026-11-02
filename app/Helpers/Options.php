<?php
/**
 * Theme settings access.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Helpers;

final class Options {
	public const KEY = 'tajiksworld_settings';

	/** @return array<string, mixed> */
	public static function defaults(): array {
		$path = dirname(__DIR__, 2) . '/config/settings.php';
		$defaults = is_file($path) ? require $path : [];
		return is_array($defaults) ? $defaults : [];
	}

	/** @return array<string, mixed> */
	public static function all(): array {
		$saved = get_option(self::KEY, []);
		if (!is_array($saved)) {
			$saved = [];
		}
		return array_merge(self::defaults(), $saved);
	}

	public static function get(string $key, mixed $default = null): mixed {
		$all = self::all();
		return $all[$key] ?? $default;
	}

	public static function hex(string $value, string $fallback): string {
		return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1 ? $value : $fallback;
	}
}
