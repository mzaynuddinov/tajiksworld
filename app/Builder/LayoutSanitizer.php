<?php
/**
 * Validates builder JSON before it is stored.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

use TajiksWorld\Security\Kses;

final class LayoutSanitizer {
	private const MAX_SECTIONS = 80;
	private const MAX_DEPTH    = 6;

	/** @var array<string, array<string, mixed>> */
	private array $registry;

	public function __construct(?array $registry = null) {
		$this->registry = $registry ?? self::load_registry();
	}

	/** @return array<string, array<string, mixed>> */
	public static function load_registry(): array {
		$path = dirname(__DIR__, 2) . '/config/registry.php';
		$registry = is_file($path) ? require $path : [];
		return is_array($registry) ? $registry : [];
	}

	/** @param mixed $layout @return array{version:int, sections:array<int, array<string, mixed>>} */
	public function sanitize(mixed $layout, bool $allow_unsafe_html = false): array {
		if (!is_array($layout)) {
			return ['version' => 1, 'sections' => []];
		}
		$sections = [];
		$raw = isset($layout['sections']) && is_array($layout['sections']) ? $layout['sections'] : [];
		foreach (array_slice($raw, 0, self::MAX_SECTIONS) as $section) {
			if (is_array($section)) {
				$sections[] = $this->section($section, $allow_unsafe_html, 0);
			}
		}
		return ['version' => 1, 'sections' => $sections];
	}

	/** @param array<string, mixed> $section @return array<string, mixed> */
	private function section(array $section, bool $allow_unsafe, int $depth): array {
		$settings_in = isset($section['settings']) && is_array($section['settings']) ? $section['settings'] : [];
		$columns = [];
		$raw_cols = isset($section['columns']) && is_array($section['columns']) ? $section['columns'] : [];
		if ($raw_cols === []) {
			$raw_cols = [['width' => 100, 'blocks' => []]];
		}
		foreach (array_slice($raw_cols, 0, 6) as $column) {
			if (!is_array($column)) {
				continue;
			}
			$blocks = [];
			$raw_blocks = isset($column['blocks']) && is_array($column['blocks']) ? $column['blocks'] : [];
			foreach (array_slice($raw_blocks, 0, 40) as $block) {
				if (is_array($block)) {
					$blocks[] = $this->block($block, $allow_unsafe, $depth);
				}
			}
			$columns[] = [
				'id'     => $this->id($column['id'] ?? '', 'col'),
				'width'  => $this->int_range($column['width'] ?? 100, 10, 100, 100),
				'blocks' => $blocks,
			];
		}
		return [
			'id'       => $this->id($section['id'] ?? '', 'sec'),
			'settings' => [
				'container'    => $this->enum($settings_in['container'] ?? 'boxed', ['boxed', 'wide', 'full'], 'boxed'),
				'background'   => $this->color($settings_in['background'] ?? ''),
				'color'        => $this->color($settings_in['color'] ?? ''),
				'padding_top'  => $this->spacing($settings_in['padding_top'] ?? ''),
				'padding_bottom' => $this->spacing($settings_in['padding_bottom'] ?? ''),
				'gap'          => $this->int_range($settings_in['gap'] ?? 28, 0, 80, 28),
				'hide_desktop' => $this->bool($settings_in['hide_desktop'] ?? false),
				'hide_tablet'  => $this->bool($settings_in['hide_tablet'] ?? false),
				'hide_mobile'  => $this->bool($settings_in['hide_mobile'] ?? false),
				'css_id'       => $this->css_id($settings_in['css_id'] ?? ''),
				'css_class'    => $this->css_classes($settings_in['css_class'] ?? ''),
				'border_width' => $this->int_range($settings_in['border_width'] ?? 0, 0, 12, 0),
				'border_color' => $this->color($settings_in['border_color'] ?? ''),
				'radius'       => $this->int_range($settings_in['radius'] ?? 0, 0, 40, 0),
			],
			'columns'  => $columns,
		];
	}

	/** @param array<string, mixed> $block @return array<string, mixed> */
	private function block(array $block, bool $allow_unsafe, int $depth): array {
		$type = isset($block['type']) && is_string($block['type']) ? $block['type'] : '';
		if (!isset($this->registry[$type])) {
			$type = 'spacer';
		}
		$def = $this->registry[$type];
		$settings = $this->fields(
			isset($block['settings']) && is_array($block['settings']) ? $block['settings'] : [],
			isset($def['fields']) && is_array($def['fields']) ? $def['fields'] : [],
			$allow_unsafe
		);
		$style_in = isset($block['style']) && is_array($block['style']) ? $block['style'] : [];
		$clean = [
			'id'       => $this->id($block['id'] ?? '', 'blk'),
			'type'     => $type,
			'settings' => $settings,
			'style'    => [
				'margin_top'    => $this->spacing($style_in['margin_top'] ?? ''),
				'margin_bottom' => $this->spacing($style_in['margin_bottom'] ?? ''),
				'hide_desktop'  => $this->bool($style_in['hide_desktop'] ?? false),
				'hide_tablet'   => $this->bool($style_in['hide_tablet'] ?? false),
				'hide_mobile'   => $this->bool($style_in['hide_mobile'] ?? false),
				'css_class'     => $this->css_classes($style_in['css_class'] ?? ''),
			],
		];
		if ($depth >= self::MAX_DEPTH) {
			return $clean;
		}
		$nesting = (string) ($def['nesting'] ?? 'none');
		if ($nesting === 'blocks') {
			$children = [];
			$raw = isset($block['blocks']) && is_array($block['blocks']) ? $block['blocks'] : [];
			foreach (array_slice($raw, 0, 30) as $child) {
				if (is_array($child)) {
					$children[] = $this->block($child, $allow_unsafe, $depth + 1);
				}
			}
			$clean['blocks'] = $children;
		}
		if ($nesting === 'columns') {
			$cols = [];
			$raw = isset($block['columns']) && is_array($block['columns']) ? $block['columns'] : [];
			$count = (int) ($settings['count'] ?? 2);
			if ($raw === []) {
				for ($i = 0; $i < max(2, min(4, $count)); $i++) {
					$raw[] = ['width' => (int) floor(100 / max(2, min(4, $count))), 'blocks' => []];
				}
			}
			foreach (array_slice($raw, 0, 4) as $column) {
				if (!is_array($column)) {
					continue;
				}
				$blocks = [];
				$raw_blocks = isset($column['blocks']) && is_array($column['blocks']) ? $column['blocks'] : [];
				foreach (array_slice($raw_blocks, 0, 20) as $child) {
					if (is_array($child)) {
						$blocks[] = $this->block($child, $allow_unsafe, $depth + 1);
					}
				}
				$cols[] = [
					'id'     => $this->id($column['id'] ?? '', 'col'),
					'width'  => $this->int_range($column['width'] ?? 50, 10, 100, 50),
					'blocks' => $blocks,
				];
			}
			$clean['columns'] = $cols;
		}
		return $clean;
	}

	/**
	 * @param array<string, mixed> $input
	 * @param array<int, array<string, mixed>> $fields
	 * @return array<string, mixed>
	 */
	private function fields(array $input, array $fields, bool $allow_unsafe): array {
		$clean = [];
		foreach ($fields as $field) {
			$key = (string) ($field['key'] ?? '');
			if ($key === '') {
				continue;
			}
			$type = (string) ($field['type'] ?? 'text');
			$value = $input[$key] ?? ($field['default'] ?? '');
			$clean[$key] = match ($type) {
				'textarea' => $this->plain($value, 20000),
				'html' => $allow_unsafe ? Kses::strip_php((string) $value) : Kses::post($this->plain($value, 50000, false)),
				'url' => $this->url($value),
				'number' => $this->int_range($value, (int) ($field['min'] ?? 0), (int) ($field['max'] ?? 9999), (int) ($field['default'] ?? 0)),
				'checkbox' => $this->bool($value),
				'select', 'category' => $this->enum(
					$value,
					$type === 'category' ? null : array_keys((array) ($field['options'] ?? [])),
					(string) ($field['default'] ?? '')
				),
				'image' => $this->int_range($value, 0, 99999999, 0),
				'images' => $this->id_list($value),
				default => $this->plain($value, 500),
			};
		}
		return $clean;
	}

	private function id(mixed $value, string $prefix): string {
		$raw = is_string($value) ? $value : '';
		if (preg_match('/^[a-zA-Z0-9_-]{1,40}$/', $raw) === 1) {
			return $raw;
		}
		return $prefix . '_' . bin2hex(random_bytes(4));
	}

	private function plain(mixed $value, int $limit, bool $strip = true): string {
		$text = is_scalar($value) ? (string) $value : '';
		if ($strip) {
			$text = strip_tags($text);
		}
		$text = trim($text);
		if (function_exists('mb_substr')) {
			return mb_substr($text, 0, $limit);
		}
		return substr($text, 0, $limit);
	}

	/** @param array<int, string>|null $allowed */
	private function enum(mixed $value, ?array $allowed, string $default): string {
		$raw = is_scalar($value) ? (string) $value : '';
		if ($allowed === null) {
			$raw = strtolower($raw);
			return preg_match('/^[a-z0-9-]{0,40}$/', $raw) === 1 ? $raw : '';
		}
		$allowed_strings = array_map('strval', $allowed);
		return in_array($raw, $allowed_strings, true) ? $raw : $default;
	}

	private function bool(mixed $value): bool {
		return $value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'on';
	}

	private function int_range(mixed $value, int $min, int $max, int $default): int {
		if (is_bool($value) || !is_numeric($value)) {
			return $default;
		}
		$number = (int) $value;
		if ($number < $min) {
			return $min;
		}
		if ($number > $max) {
			return $max;
		}
		return $number;
	}

	private function color(mixed $value): string {
		$raw = is_string($value) ? trim($value) : '';
		if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $raw) === 1) {
			return strtolower($raw);
		}
		return '';
	}

	private function spacing(mixed $value): string {
		$raw = is_scalar($value) ? trim((string) $value) : '';
		if ($raw === '') {
			return '';
		}
		if (preg_match('/^\d{1,3}(px|rem|em|%)?$/', $raw) === 1) {
			return $raw;
		}
		return '';
	}

	private function url(mixed $value): string {
		$raw = is_string($value) ? trim($value) : '';
		if ($raw === '') {
			return '';
		}
		if (preg_match('#^(javascript:|data:)#i', $raw) === 1) {
			return '';
		}
		if (str_starts_with($raw, '/') || str_starts_with($raw, '#') || str_starts_with($raw, '?')) {
			return $this->plain($raw, 300);
		}
		if (function_exists('esc_url_raw')) {
			return (string) esc_url_raw($raw);
		}
		$valid = filter_var($raw, FILTER_VALIDATE_URL);
		if (!is_string($valid) || !preg_match('#^https?://#i', $valid)) {
			return '';
		}
		return $valid;
	}

	private function id_list(mixed $value): string {
		$parts = is_array($value) ? $value : explode(',', (string) $value);
		$ids = [];
		foreach ($parts as $part) {
			$id = abs((int) $part);
			if ($id > 0) {
				$ids[] = (string) $id;
			}
		}
		$ids = array_slice(array_unique($ids), 0, 24);
		return implode(',', $ids);
	}

	private function css_id(mixed $value): string {
		$raw = is_string($value) ? $value : '';
		return preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,40}$/', $raw) === 1 ? $raw : '';
	}

	private function css_classes(mixed $value): string {
		$raw = is_string($value) ? $value : '';
		$out = [];
		foreach (preg_split('/\s+/', $raw) ?: [] as $token) {
			if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,40}$/', $token) === 1) {
				$out[] = $token;
			}
		}
		return implode(' ', array_slice($out, 0, 8));
	}
}
