<?php
/**
 * Template helpers.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/** @return array<int, array{label:string, url:string}> */
function tw_breadcrumb_items(): array {
	$items = [
		['label' => __('Home', 'tajiksworld'), 'url' => home_url('/')],
	];
	if (is_singular()) {
		$post = get_queried_object();
		if ($post instanceof WP_Post && $post->post_type === 'post') {
			$cats = get_the_category($post->ID);
			if (isset($cats[0]) && $cats[0] instanceof WP_Term) {
				$items[] = ['label' => $cats[0]->name, 'url' => (string) get_category_link($cats[0])];
			}
		} elseif ($post instanceof WP_Post) {
			$obj = get_post_type_object($post->post_type);
			if ($obj && is_string($obj->has_archive)) {
				$items[] = ['label' => $obj->labels->name, 'url' => (string) get_post_type_archive_link($post->post_type)];
			}
		}
		$items[] = ['label' => wp_strip_all_tags(get_the_title()), 'url' => (string) get_permalink()];
	} elseif (is_category() || is_tag() || is_tax()) {
		$term = get_queried_object();
		if ($term instanceof WP_Term) {
			$items[] = ['label' => $term->name, 'url' => (string) get_term_link($term)];
		}
	} elseif (is_search()) {
		$items[] = ['label' => __('Search', 'tajiksworld'), 'url' => get_search_link()];
	} elseif (is_author()) {
		$items[] = ['label' => get_the_author(), 'url' => (string) get_author_posts_url((int) get_queried_object_id())];
	} elseif (is_404()) {
		$items[] = ['label' => __('Not found', 'tajiksworld'), 'url' => ''];
	}
	return $items;
}

function tw_breadcrumbs(): string {
	$items = tw_breadcrumb_items();
	if (count($items) < 2) {
		return '';
	}
	$html = '<nav class="tw-breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'tajiksworld') . '"><ol>';
	$last = count($items) - 1;
	foreach ($items as $index => $item) {
		if ($index === $last || $item['url'] === '') {
			$html .= '<li aria-current="page">' . esc_html($item['label']) . '</li>';
		} else {
			$html .= '<li><a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a></li>';
		}
	}
	return $html . '</ol></nav>';
}

function tw_language_switcher(): string {
	if (function_exists('pll_the_languages')) {
		ob_start();
		echo '<nav class="tw-langs" aria-label="' . esc_attr__('Languages', 'tajiksworld') . '">';
		pll_the_languages(['show_flags' => 0, 'show_names' => 1, 'hide_if_empty' => 0]);
		echo '</nav>';
		return (string) ob_get_clean();
	}
	if (function_exists('icl_get_languages') || defined('ICL_SITEPRESS_VERSION')) {
		$languages = apply_filters('wpml_active_languages', null, ['skip_missing' => 0]);
		if (is_array($languages) && $languages !== []) {
			$html = '<ul class="tw-langs">';
			foreach ($languages as $language) {
				if (!is_array($language)) {
					continue;
				}
				$html .= '<li><a href="' . esc_url((string) ($language['url'] ?? '#')) . '" hreflang="' . esc_attr((string) ($language['language_code'] ?? '')) . '">' . esc_html((string) ($language['native_name'] ?? '')) . '</a></li>';
			}
			return $html . '</ul>';
		}
	}
	$current = \TajiksWorld\Core\I18n::current_code();
	$labels = ['tj' => 'Тоҷикӣ', 'ru' => 'Русский', 'en' => 'English'];
	$html = '<ul class="tw-langs" aria-label="' . esc_attr__('Languages', 'tajiksworld') . '">';
	foreach ($labels as $code => $label) {
		$url = add_query_arg('tw_lang', $code);
		$attr = $code === $current ? ' aria-current="true"' : '';
		$html .= '<li><a href="' . esc_url($url) . '"' . $attr . '>' . esc_html($label) . '</a></li>';
	}
	return $html . '</ul>';
}

function tw_share_links(int $post_id): string {
	$url = rawurlencode((string) get_permalink($post_id));
	$title = rawurlencode(wp_strip_all_tags(get_the_title($post_id)));
	$links = [
		'Telegram' => 'https://t.me/share/url?url=' . $url . '&text=' . $title,
		'X'        => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
		'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
		'WhatsApp' => 'https://wa.me/?text=' . $title . '%20' . $url,
	];
	$html = '<div class="tw-share"><span>' . esc_html__('Share', 'tajiksworld') . '</span><ul>';
	foreach ($links as $label => $href) {
		$html .= '<li><a href="' . esc_url($href) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a></li>';
	}
	$html .= '<li><button type="button" data-tw-copy="' . esc_url((string) get_permalink($post_id)) . '">' . esc_html__('Copy link', 'tajiksworld') . '</button></li>';
	return $html . '</ul></div>';
}

function tw_layout_for(string $context, int $post_id = 0): array {
	$preview = \TajiksWorld\Builder\Preview::layout();
	if (is_array($preview) && \TajiksWorld\Builder\Preview::context() === $context && ($context !== 'page' || \TajiksWorld\Builder\Preview::post_id() === $post_id)) {
		return $preview;
	}
	return \TajiksWorld\Builder\Repository::published($context, $post_id);
}
