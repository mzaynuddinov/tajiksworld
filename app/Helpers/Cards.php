<?php
/**
 * Shared article cards.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Helpers;

final class Cards {
	public static function reading_minutes(int $post_id): int {
		$content = (string) get_post_field('post_content', $post_id);
		$text    = trim(wp_strip_all_tags($content));
		if ($text === '') {
			return 0;
		}
		$parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
		$count = is_array($parts) ? count($parts) : 0;
		return $count > 0 ? (int) max(1, (int) ceil($count / 200)) : 0;
	}

	public static function kicker(int $post_id): string {
		$cats = get_the_category($post_id);
		if (!is_array($cats) || $cats === []) {
			$type = get_post_type($post_id);
			return match ($type) {
				'tw_video' => __('Video', 'tajiksworld'),
				'tw_gallery' => __('Gallery', 'tajiksworld'),
				'tw_podcast' => __('Podcast', 'tajiksworld'),
				'tw_interview' => __('Interview', 'tajiksworld'),
				'tw_report' => __('Special report', 'tajiksworld'),
				default => '',
			};
		}
		$cat = $cats[0];
		$color = (string) get_term_meta($cat->term_id, '_tw_color', true);
		$color_attr = preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? ' style="--kicker:' . esc_attr($color) . '"' : '';
		return '<a class="tw-kicker"' . $color_attr . ' href="' . esc_url(get_category_link($cat)) . '">' . esc_html($cat->name) . '</a>';
	}

	/** @param array<string, mixed> $args */
	public static function render(\WP_Post $post, array $args = []): string {
		$variant = (string) ($args['variant'] ?? 'grid');
		$show_image = (bool) ($args['show_image'] ?? true);
		$show_excerpt = (bool) ($args['show_excerpt'] ?? false);
		$permalink = get_permalink($post);
		$title = get_the_title($post);
		$minutes = self::reading_minutes((int) $post->ID);
		$meta = '<time datetime="' . esc_attr(get_the_date('c', $post)) . '">' . esc_html(get_the_date('', $post)) . '</time>';
		if ($minutes > 0) {
			$meta .= '<span>' . esc_html(sprintf(
				/* translators: %d: minutes */
				_n('%d min read', '%d min read', $minutes, 'tajiksworld'),
				$minutes
			)) . '</span>';
		}
		$image = '';
		if ($show_image) {
			$thumb = get_the_post_thumbnail($post, $variant === 'lead' ? 'tw-hero' : 'tw-card', ['class' => 'tw-card-img', 'loading' => 'lazy']);
			if ($thumb === '') {
				$thumb = '<span class="tw-ph" aria-hidden="true"></span>';
			}
			$image = '<a class="tw-card-media" href="' . esc_url((string) $permalink) . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>';
		}
		$excerpt = '';
		if ($show_excerpt) {
			$raw = has_excerpt($post) ? $post->post_excerpt : $post->post_content;
			$excerpt = '<p class="tw-card-excerpt">' . esc_html(wp_trim_words(wp_strip_all_tags((string) $raw), 24, '…')) . '</p>';
		}
		$kicker = self::kicker((int) $post->ID);
		$heading = $variant === 'lead' ? 'h2' : 'h3';
		$title_html = '<' . $heading . ' class="tw-card-title"><a href="' . esc_url((string) $permalink) . '">' . esc_html($title) . '</a></' . $heading . '>';
		$play = get_post_type($post) === 'tw_video' ? '<span class="tw-play">' . Icons::render('play', 18) . '</span>' : '';
		return '<article class="tw-card tw-card-' . esc_attr($variant) . '">' . $image . $play . '<div class="tw-card-body">' . $kicker . $title_html . $excerpt . '<div class="tw-card-meta">' . $meta . '</div></div></article>';
	}

	/** @param array<int, \WP_Post> $posts */
	public static function empty_note(string $message): string {
		return '<p class="tw-empty">' . esc_html($message) . '</p>';
	}
}
