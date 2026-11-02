<?php
/**
 * Seed layouts. Stored in the database on activation — not rendered as hardcoded markup.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

final class Defaults {
	/** @return array{version:int, sections:array<int, array<string, mixed>>} */
	public static function home(): array {
		$sections = [
			self::section('sec_breaking', [self::col(100, [self::block('blk_breaking', 'breaking_news', ['label' => 'Breaking', 'count' => 8])])], ['container' => 'full', 'padding_top' => '0', 'padding_bottom' => '0', 'gap' => 0]),
			self::section('sec_featured', [self::col(100, [self::block('blk_featured', 'featured_news', ['title' => '', 'count' => 4, 'category' => ''])])], ['padding_top' => '28px', 'padding_bottom' => '8px']),
			self::section('sec_hero', [self::col(100, [self::block('blk_hero', 'news_carousel', ['title' => 'In focus', 'count' => 5, 'category' => '', 'post_type' => 'post'])])]),
			self::section('sec_latest', [
				self::col(66, [self::block('blk_latest', 'latest_news', ['title' => 'Latest', 'count' => 6, 'columns' => '2', 'category' => '', 'post_type' => 'post', 'show_excerpt' => true, 'show_image' => true])]),
				self::col(34, [
					self::block('blk_popular', 'popular_news', ['title' => 'Popular', 'count' => 5]),
					self::block('blk_trending', 'trending_news', ['title' => 'Trending', 'count' => 5]),
					self::block('blk_ad_side', 'advertisement', ['placement' => 'sidebar']),
				]),
			]),
			self::section('sec_ad_home', [self::col(100, [self::block('blk_ad_home', 'advertisement', ['placement' => 'homepage'])])], ['padding_top' => '8px', 'padding_bottom' => '8px']),
		];
		$categories = [
			'politics'   => 'Politics',
			'economy'    => 'Economy',
			'technology' => 'Technology',
			'world'      => 'World',
			'tajikistan' => 'Tajikistan',
			'sports'     => 'Sports',
			'culture'    => 'Culture',
		];
		foreach ($categories as $slug => $label) {
			$sections[] = self::section(
				'sec_' . $slug,
				[self::col(100, [self::block(
					'blk_' . $slug,
					'news_grid',
					['title' => $label, 'count' => 4, 'columns' => '4', 'category' => $slug, 'post_type' => 'post', 'show_excerpt' => false]
				)])],
				['padding_top' => '12px', 'padding_bottom' => '12px']
			);
		}
		$sections[] = self::section('sec_between_ad', [self::col(100, [self::block('blk_between', 'advertisement', ['placement' => 'between'])])], ['padding_top' => '8px', 'padding_bottom' => '8px']);
		$sections[] = self::section('sec_video', [self::col(100, [self::block('blk_videos', 'news_grid', ['title' => 'Video', 'count' => 4, 'columns' => '4', 'category' => '', 'post_type' => 'tw_video', 'show_excerpt' => false])])]);
		$sections[] = self::section('sec_gallery', [self::col(100, [self::block('blk_galleries', 'news_grid', ['title' => 'Photo desk', 'count' => 4, 'columns' => '4', 'category' => '', 'post_type' => 'tw_gallery', 'show_excerpt' => false])])]);
		$sections[] = self::section('sec_podcast', [self::col(100, [self::block('blk_podcasts', 'news_list', ['title' => 'Podcasts', 'count' => 4, 'category' => '', 'post_type' => 'tw_podcast', 'show_thumb' => true])])]);
		$sections[] = self::section('sec_stats', [self::col(100, [self::block('blk_stats', 'statistics', ['mode' => 'auto', 'label_1' => 'Stories', 'value_1' => '', 'label_2' => 'Sections', 'value_2' => '', 'label_3' => 'Languages', 'value_3' => '3', 'label_4' => 'Desks', 'value_4' => ''])])], ['background' => '#161513', 'color' => '#f3efe6', 'padding_top' => '36px', 'padding_bottom' => '36px']);
		$sections[] = self::section('sec_newsletter', [self::col(100, [self::block('blk_newsletter', 'newsletter', ['title' => 'The morning briefing', 'text' => 'One concise briefing in your inbox. No noise.', 'button' => 'Subscribe'])])]);
		return (new LayoutSanitizer())->sanitize(['version' => 1, 'sections' => $sections]);
	}

	/** @return array{version:int, sections:array<int, array<string, mixed>>} */
	public static function header(): array {
		$sections = [
			self::section('sec_topbar', [
				self::col(40, [self::block('blk_top_text', 'text', ['content' => '{date}', 'align' => 'left'])]),
				self::col(30, [self::block('blk_lang', 'language_switcher')]),
				self::col(30, [self::block('blk_social_top', 'social_links', ['style' => 'text'])]),
			], ['container' => 'wide', 'padding_top' => '0', 'padding_bottom' => '0', 'gap' => 12, 'css_class' => 'tw-topbar-section']),
			self::section('sec_mast', [
				self::col(22, [self::block('blk_logo', 'logo', ['show_tagline' => true])]),
				self::col(58, [self::block('blk_nav', 'navigation', ['location' => 'primary'])]),
				self::col(20, [
					self::block('blk_search', 'search_form', ['placeholder' => 'Search the newsroom', 'style' => 'icon']),
					self::block('blk_dark', 'dark_toggle'),
				]),
			], ['container' => 'wide', 'padding_top' => '14px', 'padding_bottom' => '14px', 'gap' => 16]),
			self::section('sec_header_ad', [self::col(100, [self::block('blk_header_ad', 'advertisement', ['placement' => 'header'])])], ['padding_top' => '0', 'padding_bottom' => '10px']),
		];
		return (new LayoutSanitizer())->sanitize(['version' => 1, 'sections' => $sections]);
	}

	/** @return array{version:int, sections:array<int, array<string, mixed>>} */
	public static function footer(): array {
		$sections = [
			self::section('sec_footer_ad', [self::col(100, [self::block('blk_footer_ad', 'advertisement', ['placement' => 'footer'])])], ['padding_top' => '20px', 'padding_bottom' => '0']),
			self::section('sec_footer_main', [
				self::col(30, [
					self::block('blk_flogo', 'logo', ['show_tagline' => false]),
					self::block('blk_fabout', 'text', ['content' => '{about}', 'align' => 'left']),
				]),
				self::col(25, [self::block('blk_fcats', 'news_categories', ['title' => 'Sections', 'show_count' => false, 'parents_only' => true])]),
				self::col(25, [self::block('blk_flatest', 'news_list', ['title' => 'Latest', 'count' => 4, 'category' => '', 'post_type' => 'post', 'show_thumb' => false])]),
				self::col(20, [self::block('blk_fnav', 'navigation', ['location' => 'footer'])]),
			], ['padding_top' => '36px', 'padding_bottom' => '20px']),
			self::section('sec_footer_news', [self::col(100, [self::block('blk_fnews', 'newsletter', ['title' => 'The morning briefing', 'text' => 'One concise briefing in your inbox. No noise.', 'button' => 'Subscribe'])])], ['padding_top' => '12px', 'padding_bottom' => '12px']),
			self::section('sec_footer_bar', [
				self::col(50, [self::block('blk_copy', 'copyright', ['text' => '© {year} {site}. All rights reserved.'])]),
				self::col(50, [self::block('blk_fsocial', 'social_links', ['style' => 'text'])]),
			], ['padding_top' => '8px', 'padding_bottom' => '24px', 'border_width' => 1, 'border_color' => '#d9d2c5']),
		];
		return (new LayoutSanitizer())->sanitize(['version' => 1, 'sections' => $sections]);
	}

	/**
	 * @param array<int, array<string, mixed>> $columns
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	private static function section(string $id, array $columns, array $settings = []): array {
		return ['id' => $id, 'settings' => $settings, 'columns' => $columns];
	}

	/** @param array<int, array<string, mixed>> $blocks @return array<string, mixed> */
	private static function col(int $width, array $blocks): array {
		return ['id' => 'col_' . $width . '_' . substr(md5((string) json_encode($blocks)), 0, 6), 'width' => $width, 'blocks' => $blocks];
	}

	/** @param array<string, mixed> $settings @return array<string, mixed> */
	private static function block(string $id, string $type, array $settings = []): array {
		return ['id' => $id, 'type' => $type, 'settings' => $settings, 'style' => []];
	}
}
