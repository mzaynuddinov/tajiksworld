<?php
/**
 * Custom content types for video, galleries, podcasts, interviews, and reports.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\ContentTypes;

final class Types {
	public static function register(): void {
		add_action('init', [self::class, 'register_types']);
		add_filter('pll_get_post_types', [self::class, 'polylang'], 10, 2);
	}

	public static function register_types(): void {
		$types = [
			'tw_video' => [
				'slug'  => 'videos',
				'name'  => __('Videos', 'tajiksworld'),
				'single'=> __('Video', 'tajiksworld'),
				'icon'  => 'dashicons-format-video',
			],
			'tw_gallery' => [
				'slug'  => 'galleries',
				'name'  => __('Galleries', 'tajiksworld'),
				'single'=> __('Gallery', 'tajiksworld'),
				'icon'  => 'dashicons-format-gallery',
			],
			'tw_podcast' => [
				'slug'  => 'podcasts',
				'name'  => __('Podcasts', 'tajiksworld'),
				'single'=> __('Podcast', 'tajiksworld'),
				'icon'  => 'dashicons-microphone',
			],
			'tw_interview' => [
				'slug'  => 'interviews',
				'name'  => __('Interviews', 'tajiksworld'),
				'single'=> __('Interview', 'tajiksworld'),
				'icon'  => 'dashicons-format-chat',
			],
			'tw_report' => [
				'slug'  => 'reports',
				'name'  => __('Special Reports', 'tajiksworld'),
				'single'=> __('Special Report', 'tajiksworld'),
				'icon'  => 'dashicons-media-document',
			],
		];
		foreach ($types as $key => $type) {
			register_post_type($key, [
				'labels' => [
					'name'          => $type['name'],
					'singular_name' => $type['single'],
					'add_new_item'  => sprintf(
						/* translators: %s: singular content type */
						__('Add %s', 'tajiksworld'),
						$type['single']
					),
					'edit_item'     => sprintf(
						/* translators: %s: singular content type */
						__('Edit %s', 'tajiksworld'),
						$type['single']
					),
				],
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => true,
				'menu_icon'    => $type['icon'],
				'show_in_menu' => false,
				'rewrite'      => ['slug' => $type['slug']],
				'supports'     => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions', 'custom-fields'],
				'taxonomies'   => ['post_tag'],
			]);
		}
	}

	/** @param array<string, string> $types @return array<string, string> */
	public static function polylang(array $types, bool $is_settings): array {
		unset($is_settings);
		foreach (['tw_video', 'tw_gallery', 'tw_podcast', 'tw_interview', 'tw_report'] as $type) {
			$types[$type] = $type;
		}
		return $types;
	}
}
