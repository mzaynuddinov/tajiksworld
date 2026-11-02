<?php
/**
 * Load and store builder layouts.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

final class Repository {
	private const CONTEXTS = ['home', 'header', 'footer'];

	public static function published(string $context, int $post_id = 0): array {
		return self::read($context, $post_id, false);
	}

	public static function draft(string $context, int $post_id = 0): array {
		$draft = self::read($context, $post_id, true);
		if (($draft['sections'] ?? []) !== []) {
			return $draft;
		}
		return self::published($context, $post_id);
	}

	/** @param array<string, mixed> $layout */
	public static function save(string $context, array $layout, string $status, int $post_id = 0): bool {
		$sanitizer = new LayoutSanitizer();
		$allow = function_exists('current_user_can') && current_user_can('unfiltered_html');
		$clean = $sanitizer->sanitize($layout, $allow);
		$key = self::key($context, $status === 'draft', $post_id);
		if ($key === '') {
			return false;
		}
		if ($post_id > 0) {
			update_post_meta($post_id, $key, wp_json_encode($clean));
		} else {
			update_option($key, $clean, false);
		}
		if ($status === 'publish' && $post_id === 0) {
			self::push_revision($context, $clean);
			delete_option(self::key($context, true, 0));
		}
		if ($status === 'publish' && $post_id > 0) {
			delete_post_meta($post_id, self::key($context, true, $post_id));
		}
		return true;
	}

	/** @return array<int, array<string, mixed>> */
	public static function revisions(string $context): array {
		if (!in_array($context, self::CONTEXTS, true)) {
			return [];
		}
		$stored = get_option('tajiksworld_revisions_' . $context, []);
		return is_array($stored) ? $stored : [];
	}

	public static function seed_if_missing(): void {
		foreach (['home' => 'home', 'header' => 'header', 'footer' => 'footer'] as $context => $method) {
			$current = get_option(self::key($context, false, 0), null);
			if (is_array($current) && ($current['sections'] ?? null) !== null) {
				continue;
			}
			$layout = match ($context) {
				'header' => Defaults::header(),
				'footer' => Defaults::footer(),
				default => Defaults::home(),
			};
			update_option(self::key($context, false, 0), $layout, false);
		}
	}

	/** @return array{version:int, sections:array<int, mixed>} */
	private static function read(string $context, int $post_id, bool $draft): array {
		$key = self::key($context, $draft, $post_id);
		if ($key === '') {
			return ['version' => 1, 'sections' => []];
		}
		if ($post_id > 0) {
			$raw = get_post_meta($post_id, $key, true);
			if (is_string($raw) && $raw !== '') {
				$decoded = json_decode($raw, true);
				return is_array($decoded) ? $decoded : ['version' => 1, 'sections' => []];
			}
			return ['version' => 1, 'sections' => []];
		}
		$stored = get_option($key, ['version' => 1, 'sections' => []]);
		return is_array($stored) ? $stored : ['version' => 1, 'sections' => []];
	}

	private static function key(string $context, bool $draft, int $post_id): string {
		if ($post_id > 0) {
			return $draft ? '_tw_layout_draft' : '_tw_layout';
		}
		if (!in_array($context, self::CONTEXTS, true)) {
			return '';
		}
		return 'tajiksworld_layout_' . $context . ($draft ? '_draft' : '');
	}

	/** @param array<string, mixed> $layout */
	private static function push_revision(string $context, array $layout): void {
		$key = 'tajiksworld_revisions_' . $context;
		$list = get_option($key, []);
		if (!is_array($list)) {
			$list = [];
		}
		array_unshift($list, [
			'saved'  => time(),
			'layout' => $layout,
		]);
		update_option($key, array_slice($list, 0, 15), false);
	}
}
