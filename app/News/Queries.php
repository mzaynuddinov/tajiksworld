<?php
/**
 * Reusable news queries.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\News;

final class Queries {
	/** @var array<string, string> */
	private const FLAGS = [
		'breaking' => '_tw_breaking',
		'featured' => '_tw_featured',
		'trending' => '_tw_trending',
		'editors'  => '_tw_editors_choice',
	];

	/** @var array<int, string> */
	private const TYPES = ['post', 'tw_video', 'tw_gallery', 'tw_podcast', 'tw_interview', 'tw_report'];

	/** @param array<string, mixed> $opts @return array<int, \WP_Post> */
	public static function news(array $opts): array {
		if (!class_exists(\WP_Query::class)) {
			return [];
		}
		$type = (string) ($opts['post_type'] ?? 'post');
		if (!in_array($type, self::TYPES, true)) {
			$type = 'post';
		}
		$args = [
			'post_type'           => $type,
			'posts_per_page'      => max(1, min(24, (int) ($opts['count'] ?? 6))),
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];
		$category = sanitize_title((string) ($opts['category'] ?? ''));
		if ($category !== '' && $type === 'post') {
			$args['category_name'] = $category;
		}
		$flag = (string) ($opts['flag'] ?? '');
		if (isset(self::FLAGS[$flag]) && $type === 'post') {
			$args['meta_query'] = [
				[
					'key'   => self::FLAGS[$flag],
					'value' => '1',
				],
			];
		}
		if (($opts['order'] ?? '') === 'views') {
			$wanted = $args['posts_per_page'];
			$args['posts_per_page'] = min(24, max($wanted, 12));
			$query = new \WP_Query($args);
			$posts = is_array($query->posts) ? $query->posts : [];
			usort($posts, static function (\WP_Post $a, \WP_Post $b): int {
				$views = (int) get_post_meta($b->ID, '_tw_views', true) <=> (int) get_post_meta($a->ID, '_tw_views', true);
				return $views !== 0 ? $views : strcmp((string) $b->post_date, (string) $a->post_date);
			});
			return array_slice($posts, 0, $wanted);
		}
		$offset = (int) ($opts['offset'] ?? 0);
		if ($offset > 0) {
			$args['offset'] = min(30, $offset);
		}
		$query = new \WP_Query($args);
		return $query->posts;
	}

	/** @return array<int, \WP_Post> */
	public static function related(int $post_id, int $count = 4): array {
		if (!class_exists(\WP_Query::class) || !function_exists('wp_get_post_categories')) {
			return [];
		}
		$cats = wp_get_post_categories($post_id);
		$args = [
			'post_type'           => get_post_type($post_id) ?: 'post',
			'posts_per_page'      => max(1, min(8, $count)),
			'post_status'         => 'publish',
			'post__not_in'        => [$post_id],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];
		if (is_array($cats) && $cats !== [] && ($args['post_type'] ?? 'post') === 'post') {
			$args['category__in'] = array_map('intval', $cats);
		}
		$query = new \WP_Query($args);
		return $query->posts;
	}
}
