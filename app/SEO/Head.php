<?php
/**
 * SEO tags that yield to Yoast SEO and Rank Math.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\SEO;

final class Head {
	public static function register(): void {
		add_filter('document_title_parts', [self::class, 'title'], 20);
		add_action('wp_head', [self::class, 'meta'], 5);
		add_action('wp_head', [self::class, 'schema'], 20);
	}

	public static function plugin_active(): bool {
		return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('RankMath');
	}

	/** @param array<string, string> $parts @return array<string, string> */
	public static function title(array $parts): array {
		if (self::plugin_active() || !is_singular()) {
			return $parts;
		}
		$custom = (string) get_post_meta(get_queried_object_id(), '_tw_seo_title', true);
		if ($custom !== '') {
			$parts['title'] = $custom;
		}
		return $parts;
	}

	public static function meta(): void {
		if (self::plugin_active()) {
			return;
		}
		$description = self::description();
		if ($description !== '') {
			echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
		}
		if (is_singular()) {
			$url = (string) get_permalink();
			$title = wp_get_document_title();
			$image = get_the_post_thumbnail_url(null, 'tw-hero') ?: '';
			echo '<meta property="og:type" content="article" />' . "\n";
			echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
			echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
			if ($description !== '') {
				echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
				echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
			}
			if ($image !== '') {
				echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
				echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
				echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
			} else {
				echo '<meta name="twitter:card" content="summary" />' . "\n";
			}
			echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
		}
	}

	public static function schema(): void {
		if (!is_singular()) {
			return;
		}
		$post_id = get_queried_object_id();
		$type = get_post_type($post_id) === 'post' ? 'NewsArticle' : 'Article';
		$data = [
			'@context'         => 'https://schema.org',
			'@type'            => $type,
			'headline'         => wp_strip_all_tags(get_the_title($post_id)),
			'datePublished'    => get_the_date('c', $post_id),
			'dateModified'     => get_the_modified_date('c', $post_id),
			'mainEntityOfPage' => get_permalink($post_id),
			'author'           => [
				'@type' => 'Person',
				'name'  => get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id)),
			],
			'publisher'        => [
				'@type' => 'Organization',
				'name'  => get_bloginfo('name'),
			],
		];
		$description = self::description();
		if ($description !== '') {
			$data['description'] = $description;
		}
		$image = get_the_post_thumbnail_url($post_id, 'full');
		if (is_string($image) && $image !== '') {
			$data['image'] = [$image];
		}
		echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
		if (function_exists('tw_breadcrumb_items')) {
			$crumbs = tw_breadcrumb_items();
			if (count($crumbs) > 1) {
				$list = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
				$position = 1;
				foreach ($crumbs as $crumb) {
					$list['itemListElement'][] = [
						'@type'    => 'ListItem',
						'position' => $position,
						'name'     => $crumb['label'],
						'item'     => $crumb['url'],
					];
					$position++;
				}
				echo '<script type="application/ld+json">' . wp_json_encode($list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
			}
		}
	}

	private static function description(): string {
		if (!is_singular()) {
			return is_front_page() ? wp_strip_all_tags((string) get_bloginfo('description')) : '';
		}
		$custom = (string) get_post_meta(get_queried_object_id(), '_tw_seo_description', true);
		if ($custom !== '') {
			return wp_strip_all_tags($custom);
		}
		return wp_strip_all_tags(wp_trim_words((string) get_the_excerpt(), 28, '…'));
	}
}
