<?php
/**
 * Runnable checks that do not need a WordPress install.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';

function esc_html(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_url(mixed $value): string { return (string) $value; }
function __(string $text, string $domain = 'tajiksworld'): string { return $text; }
function esc_html__(string $text, string $domain = 'tajiksworld'): string { return $text; }
function esc_attr__(string $text, string $domain = 'tajiksworld'): string { return $text; }

use TajiksWorld\Builder\Defaults;
use TajiksWorld\Builder\LayoutSanitizer;
use TajiksWorld\Builder\Renderer;

$failed = 0;
$assert = static function (bool $condition, string $message) use (&$failed): void {
	if ($condition) {
		echo "PASS {$message}\n";
		return;
	}
	echo "FAIL {$message}\n";
	$failed++;
};

$sanitizer = new LayoutSanitizer();
$dirty = [
	'sections' => [[
		'id' => 'sec_1',
		'settings' => [
			'background' => 'javascript:alert(1)',
			'css_class' => 'Good bad()',
			'hide_desktop' => '1',
			'container' => 'wide',
		],
		'columns' => [[
			'id' => 'col_1',
			'width' => 100,
			'blocks' => [
				['id' => 'b1', 'type' => 'heading', 'settings' => ['text' => '<script>alert(1)</script>Hello', 'tag' => 'h1', 'align' => 'center']],
				['id' => 'b2', 'type' => 'custom_html', 'settings' => ['html' => '<p onclick="x()">Safe</p><script>alert(1)</script>']],
				['id' => 'b3', 'type' => 'button', 'settings' => ['label' => 'Go', 'url' => 'javascript:alert(1)', 'style' => 'primary', 'align' => 'left', 'new_tab' => false]],
				['id' => 'b4', 'type' => 'not_a_block', 'settings' => []],
			],
		]],
	]],
];
$clean = $sanitizer->sanitize($dirty, false);
$encoded = json_encode($clean);
$assert(is_string($encoded) && !str_contains(strtolower($encoded), '<script'), 'sanitizer removes script markup');
$assert(($clean['sections'][0]['settings']['background'] ?? 'x') === '', 'sanitizer drops unsafe colors');
$assert(($clean['sections'][0]['settings']['css_class'] ?? '') === 'Good', 'sanitizer keeps safe class tokens');
$assert(($clean['sections'][0]['settings']['container'] ?? '') === 'wide', 'sanitizer keeps allowed container');
$assert(($clean['sections'][0]['columns'][0]['blocks'][2]['settings']['url'] ?? 'x') === '', 'sanitizer blocks javascript URLs');
$assert(($clean['sections'][0]['columns'][0]['blocks'][3]['type'] ?? '') === 'spacer', 'unknown elements become spacers');

$html = (new Renderer())->render($clean);
$assert(str_contains($html, 'Hello'), 'renderer prints heading text');
$assert(str_contains($html, '<h1'), 'renderer respects heading level');
$assert(!str_contains(strtolower($html), '<script'), 'renderer does not emit script tags');
$assert(str_contains($html, 'Safe'), 'renderer keeps allowed HTML text');
$assert(str_contains($html, 'tw-hide-desktop'), 'section visibility class is rendered');

$collect = static function (array $layout) use (&$collect): array {
	$types = [];
	$walk = static function (array $blocks) use (&$walk, &$types): void {
		foreach ($blocks as $block) {
			if (!is_array($block)) {
				continue;
			}
			$types[] = (string) ($block['type'] ?? '');
			if (isset($block['blocks']) && is_array($block['blocks'])) {
				$walk($block['blocks']);
			}
			foreach ((array) ($block['columns'] ?? []) as $column) {
				if (is_array($column)) {
					$walk((array) ($column['blocks'] ?? []));
				}
			}
		}
	};
	foreach ($layout['sections'] as $section) {
		foreach ((array) ($section['columns'] ?? []) as $column) {
			if (is_array($column)) {
				$walk((array) ($column['blocks'] ?? []));
			}
		}
	}
	return $types;
};

$home = Defaults::home();
$header = Defaults::header();
$footer = Defaults::footer();
$home_types = $collect($home);
foreach (['breaking_news', 'featured_news', 'news_carousel', 'latest_news', 'popular_news', 'trending_news', 'news_grid', 'news_list', 'advertisement', 'statistics', 'newsletter'] as $type) {
	$assert(in_array($type, $home_types, true), "homepage layout includes {$type}");
}
$header_types = $collect($header);
foreach (['logo', 'navigation', 'language_switcher', 'social_links', 'search_form', 'dark_toggle', 'advertisement'] as $type) {
	$assert(in_array($type, $header_types, true), "header layout includes {$type}");
}
$footer_types = $collect($footer);
foreach (['copyright', 'newsletter', 'logo'] as $type) {
	$assert(in_array($type, $footer_types, true), "footer layout includes {$type}");
}
$assert(in_array('copyright', $footer_types, true), 'footer layout includes copyright');

$registry = LayoutSanitizer::load_registry();
$assert(count($registry) >= 33, 'registry has the required elements plus header controls');
foreach (array_keys($registry) as $type) {
	$round = $sanitizer->sanitize(['sections' => [[
		'columns' => [[
			'blocks' => [['type' => $type, 'settings' => []]],
		]],
	]]]);
	$assert(($round['sections'][0]['columns'][0]['blocks'][0]['type'] ?? '') === $type, "registry type survives sanitizing: {$type}");
}

$round_trip = $sanitizer->sanitize($home);
$assert($round_trip['sections'] !== [], 'default homepage survives a second sanitize');

echo $failed === 0 ? "ALL PASSED\n" : "FAILED {$failed}\n";
exit($failed === 0 ? 0 : 1);
