<?php
/**
 * Frontend renderer for saved builder layouts.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

use TajiksWorld\Ads\Manager as Ads;
use TajiksWorld\Helpers\Cards;
use TajiksWorld\Helpers\Icons;
use TajiksWorld\Helpers\Options;
use TajiksWorld\News\Queries;
use TajiksWorld\Security\Kses;

final class Renderer {
	private int $form_seq = 0;
	public function render(array $layout): string {
		$sections = isset($layout['sections']) && is_array($layout['sections']) ? $layout['sections'] : [];
		if ($sections === []) {
			return $this->empty_layout();
		}
		$html = '<div class="tw-builder">';
		foreach ($sections as $section) {
			if (is_array($section)) {
				$html .= $this->section($section);
			}
		}
		return $html . '</div>';
	}

	/** @param array<string, mixed> $section */
	private function section(array $section): string {
		$settings = isset($section['settings']) && is_array($section['settings']) ? $section['settings'] : [];
		$classes = ['tw-section'];
		if (!empty($settings['css_class'])) {
			$classes[] = (string) $settings['css_class'];
		}
		foreach (['desktop' => 'hide_desktop', 'tablet' => 'hide_tablet', 'mobile' => 'hide_mobile'] as $device => $key) {
			if (!empty($settings[$key])) {
				$classes[] = 'tw-hide-' . $device;
			}
		}
		$style = '';
		if (!empty($settings['background'])) {
			$style .= 'background:' . $settings['background'] . ';';
		}
		if (!empty($settings['color'])) {
			$style .= 'color:' . $settings['color'] . ';';
		}
		if (!empty($settings['padding_top'])) {
			$style .= 'padding-top:' . $this->css_unit((string) $settings['padding_top']) . ';';
		}
		if (!empty($settings['padding_bottom'])) {
			$style .= 'padding-bottom:' . $this->css_unit((string) $settings['padding_bottom']) . ';';
		}
		if (!empty($settings['border_width'])) {
			$style .= 'border-top:' . (int) $settings['border_width'] . 'px solid ' . ($settings['border_color'] ?: 'currentColor') . ';';
		}
		if (!empty($settings['radius'])) {
			$style .= 'border-radius:' . (int) $settings['radius'] . 'px;';
		}
		$container = (string) ($settings['container'] ?? 'boxed');
		$container_class = match ($container) {
			'full' => 'tw-container tw-container-full',
			'wide' => 'tw-container tw-container-wide',
			default => 'tw-container',
		};
		$gap = (int) ($settings['gap'] ?? 28);
		$cols = '';
		foreach ((array) ($section['columns'] ?? []) as $column) {
			if (is_array($column)) {
				$cols .= $this->column($column);
			}
		}
		$id = !empty($settings['css_id']) ? ' id="' . esc_attr((string) $settings['css_id']) . '"' : '';
		return '<section' . $id . ' class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($style) . '"><div class="' . esc_attr($container_class) . '"><div class="tw-cols" style="' . esc_attr('gap:' . $gap . 'px') . '">' . $cols . '</div></div></section>';
	}

	/** @param array<string, mixed> $column */
	private function column(array $column): string {
		$width = max(10, min(100, (int) ($column['width'] ?? 100)));
		$inner = '';
		foreach ((array) ($column['blocks'] ?? []) as $block) {
			if (is_array($block)) {
				$inner .= $this->block($block);
			}
		}
		return '<div class="tw-col" style="' . esc_attr('--w:' . $width . '%') . '">' . $inner . '</div>';
	}

	/** @param array<string, mixed> $block */
	public function block(array $block): string {
		$type = (string) ($block['type'] ?? '');
		$settings = isset($block['settings']) && is_array($block['settings']) ? $block['settings'] : [];
		$inner = match ($type) {
			'heading' => $this->heading($settings),
			'text' => $this->text($settings),
			'image' => $this->image($settings),
			'gallery' => $this->gallery($settings),
			'video' => $this->video($settings),
			'button' => $this->button($settings),
			'icon' => $this->icon($settings),
			'divider' => $this->divider($settings),
			'spacer' => $this->spacer($settings),
			'container' => $this->container($block, $settings),
			'columns' => $this->columns($block, $settings),
			'grid' => $this->grid($block, $settings),
			'latest_news' => $this->news_cards($settings, ['title' => 'Latest']),
			'trending_news' => $this->news_list_block($settings, 'trending', 'Trending'),
			'featured_news' => $this->featured($settings),
			'breaking_news' => $this->breaking($settings),
			'popular_news' => $this->news_list_block($settings, 'views', 'Popular'),
			'news_categories' => $this->categories($settings),
			'news_carousel' => $this->carousel($settings),
			'news_grid' => $this->news_cards($settings, []),
			'news_list' => $this->news_rows($settings),
			'author_box' => $this->author($settings),
			'advertisement' => $this->advertisement($settings),
			'newsletter' => $this->newsletter($settings),
			'social_links' => $this->social($settings),
			'search_form' => $this->search($settings),
			'breadcrumbs' => function_exists('tw_breadcrumbs') ? tw_breadcrumbs() : '',
			'statistics' => $this->statistics($settings),
			'custom_html' => '<div class="tw-html">' . Kses::post((string) ($settings['html'] ?? '')) . '</div>',
			'logo' => $this->logo($settings),
			'navigation' => $this->navigation($settings),
			'language_switcher' => function_exists('tw_language_switcher') ? tw_language_switcher() : '',
			'dark_toggle' => $this->dark_toggle(),
			'copyright' => $this->copyright($settings),
			default => '',
		};
		if ($inner === '') {
			return '';
		}
		return $this->chrome($block, $inner);
	}

	/** @param array<string, mixed> $block */
	private function chrome(array $block, string $inner): string {
		$style = isset($block['style']) && is_array($block['style']) ? $block['style'] : [];
		$classes = ['tw-block', 'tw-block-' . preg_replace('/[^a-z0-9_-]/', '', (string) ($block['type'] ?? 'x'))];
		if (!empty($style['css_class'])) {
			$classes[] = (string) $style['css_class'];
		}
		foreach (['desktop' => 'hide_desktop', 'tablet' => 'hide_tablet', 'mobile' => 'hide_mobile'] as $device => $key) {
			if (!empty($style[$key])) {
				$classes[] = 'tw-hide-' . $device;
			}
		}
		$css = '';
		if (!empty($style['margin_top'])) {
			$css .= 'margin-top:' . $this->css_unit((string) $style['margin_top']) . ';';
		}
		if (!empty($style['margin_bottom'])) {
			$css .= 'margin-bottom:' . $this->css_unit((string) $style['margin_bottom']) . ';';
		}
		return '<div class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($css) . '">' . $inner . '</div>';
	}

	/** @param array<string, mixed> $s */
	private function heading(array $s): string {
		$tag = (string) ($s['tag'] ?? 'h2');
		if (!in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
			$tag = 'h2';
		}
		$align = (string) ($s['align'] ?? 'left');
		return '<' . $tag . ' class="tw-heading tw-align-' . esc_attr($align) . '">' . esc_html($this->label((string) ($s['text'] ?? ''))) . '</' . $tag . '>';
	}

	/** @param array<string, mixed> $s */
	private function text(array $s): string {
		$raw = (string) ($s['content'] ?? '');
		if ($raw === '{date}') {
			$raw = function_exists('date_i18n') ? date_i18n(get_option('date_format') ?: 'j F Y') : gmdate('j F Y');
		} elseif ($raw === '{about}') {
			$about = function_exists('get_option') ? (string) Options::get('footer_description', '') : '';
			if ($about === '' && function_exists('get_bloginfo')) {
				$about = (string) get_bloginfo('description');
			}
			$raw = $about;
		}
		if (trim($raw) === '') {
			return '';
		}
		$align = (string) ($s['align'] ?? 'left');
		$paragraphs = preg_split("/\n{2,}/", $raw) ?: [$raw];
		$html = '';
		foreach ($paragraphs as $paragraph) {
			$paragraph = trim($paragraph);
			if ($paragraph !== '') {
				$html .= '<p>' . nl2br(esc_html($paragraph)) . '</p>';
			}
		}
		return '<div class="tw-text tw-align-' . esc_attr($align) . '">' . $html . '</div>';
	}

	/** @param array<string, mixed> $s */
	private function image(array $s): string {
		$id = (int) ($s['image_id'] ?? 0);
		if ($id <= 0 || !function_exists('wp_get_attachment_image')) {
			return '';
		}
		$alt = (string) ($s['alt'] ?? '');
		$img = wp_get_attachment_image($id, 'large', false, ['class' => 'tw-frame-img', 'alt' => $alt]);
		if ($img === '') {
			return '';
		}
		$url = (string) ($s['url'] ?? '');
		if ($url !== '') {
			$img = '<a href="' . esc_url($url) . '">' . $img . '</a>';
		}
		$caption = (string) ($s['caption'] ?? '');
		$cap = $caption !== '' ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '';
		return '<figure class="tw-frame tw-align-' . esc_attr((string) ($s['align'] ?? 'center')) . '">' . $img . $cap . '</figure>';
	}

	/** @param array<string, mixed> $s */
	private function gallery(array $s): string {
		$ids = array_filter(array_map('absint', explode(',', (string) ($s['images'] ?? ''))));
		if ($ids === [] || !function_exists('wp_get_attachment_image')) {
			return '';
		}
		$cols = (string) ($s['columns'] ?? '3');
		$html = '<div class="tw-gallery tw-cols-' . esc_attr($cols) . '" data-tw-gallery>';
		foreach ($ids as $id) {
			$full = wp_get_attachment_image_url($id, 'full') ?: '';
			$img = wp_get_attachment_image($id, 'tw-card', false, ['loading' => 'lazy']);
			if ($img === '' || $full === '') {
				continue;
			}
			$html .= '<a class="tw-gallery-item" href="' . esc_url($full) . '">' . $img . '</a>';
		}
		return $html . '</div>';
	}

	/** @param array<string, mixed> $s */
	private function video(array $s): string {
		$url = (string) ($s['url'] ?? '');
		if ($url === '') {
			return '';
		}
		$embed = $this->embed($url);
		if ($embed === '') {
			return '';
		}
		$caption = (string) ($s['caption'] ?? '');
		$cap = $caption !== '' ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '';
		return '<figure class="tw-video">' . $embed . $cap . '</figure>';
	}

	private function embed(string $url): string {
		if (!function_exists('wp_oembed_get')) {
			return '';
		}
		$path = (string) wp_parse_url($url, PHP_URL_PATH);
		if (preg_match('/\.(mp4|webm|ogg)$/i', $path) === 1) {
			return (string) wp_video_shortcode(['src' => $url]);
		}
		if (preg_match('/\.(mp3|m4a|wav|ogg)$/i', $path) === 1) {
			return (string) wp_audio_shortcode(['src' => $url]);
		}
		$embed = wp_oembed_get($url);
		if (is_string($embed) && $embed !== '') {
			return '<div class="tw-embed">' . $embed . '</div>';
		}
		return '';
	}

	/** @param array<string, mixed> $s */
	private function button(array $s): string {
		$label = (string) ($s['label'] ?? '');
		$url = (string) ($s['url'] ?? '#');
		if ($label === '') {
			return '';
		}
		$target = !empty($s['new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
		return '<p class="tw-align-' . esc_attr((string) ($s['align'] ?? 'left')) . '"><a class="tw-btn tw-btn-' . esc_attr((string) ($s['style'] ?? 'primary')) . '" href="' . esc_url($url) . '"' . $target . '>' . esc_html($this->label($label)) . '</a></p>';
	}

	/** @param array<string, mixed> $s */
	private function icon(array $s): string {
		$icon = Icons::render((string) ($s['name'] ?? 'arrow'), (int) ($s['size'] ?? 28));
		$url = (string) ($s['url'] ?? '');
		if ($url !== '') {
			return '<a class="tw-icon-link" href="' . esc_url($url) . '">' . $icon . '</a>';
		}
		return $icon;
	}

	/** @param array<string, mixed> $s */
	private function divider(array $s): string {
		$style = (string) ($s['style'] ?? 'solid');
		return '<hr class="tw-divider tw-divider-' . esc_attr($style) . '" />';
	}

	/** @param array<string, mixed> $s */
	private function spacer(array $s): string {
		$height = max(4, min(240, (int) ($s['height'] ?? 32)));
		return '<div class="tw-spacer" style="' . esc_attr('height:' . $height . 'px') . '" aria-hidden="true"></div>';
	}

	/** @param array<string, mixed> $block @param array<string, mixed> $s */
	private function container(array $block, array $s): string {
		$max = (int) ($s['max_width'] ?? 1100);
		$pad = (int) ($s['padding'] ?? 0);
		$inner = '';
		foreach ((array) ($block['blocks'] ?? []) as $child) {
			if (is_array($child)) {
				$inner .= $this->block($child);
			}
		}
		return '<div class="tw-nested" style="' . esc_attr('max-width:' . $max . 'px;padding:' . $pad . 'px') . '">' . $inner . '</div>';
	}

	/** @param array<string, mixed> $block @param array<string, mixed> $s */
	private function columns(array $block, array $s): string {
		$gap = (int) ($s['gap'] ?? 24);
		$html = '<div class="tw-cols" style="' . esc_attr('gap:' . $gap . 'px') . '">';
		foreach ((array) ($block['columns'] ?? []) as $column) {
			if (is_array($column)) {
				$html .= $this->column($column);
			}
		}
		return $html . '</div>';
	}

	/** @param array<string, mixed> $block @param array<string, mixed> $s */
	private function grid(array $block, array $s): string {
		$cols = (string) ($s['columns'] ?? '3');
		$gap = (int) ($s['gap'] ?? 16);
		$html = '<div class="tw-auto-grid tw-cols-' . esc_attr($cols) . '" style="' . esc_attr('gap:' . $gap . 'px') . '">';
		foreach ((array) ($block['blocks'] ?? []) as $child) {
			if (is_array($child)) {
				$html .= $this->block($child);
			}
		}
		return $html . '</div>';
	}

	/** @param array<string, mixed> $s @param array<string, mixed> $defaults */
	private function news_cards(array $s, array $defaults): string {
		$title = (string) ($s['title'] ?? ($defaults['title'] ?? ''));
		$posts = Queries::news([
			'count'     => (int) ($s['count'] ?? 6),
			'category'  => (string) ($s['category'] ?? ''),
			'post_type' => (string) ($s['post_type'] ?? 'post'),
		]);
		$cols = (string) ($s['columns'] ?? '3');
		$body = '';
		if ($posts === []) {
			$body = Cards::empty_note(__('No stories in this section yet.', 'tajiksworld'));
		} else {
			$body = '<div class="tw-auto-grid tw-cols-' . esc_attr($cols) . '">';
			foreach ($posts as $post) {
				$body .= Cards::render($post, [
					'variant' => 'grid',
					'show_image' => !isset($s['show_image']) || !empty($s['show_image']),
					'show_excerpt' => !empty($s['show_excerpt']),
				]);
			}
			$body .= '</div>';
		}
		return $this->headed($title, (string) ($s['category'] ?? ''), $body);
	}

	/** @param array<string, mixed> $s */
	private function news_list_block(array $s, string $mode, string $fallback): string {
		$opts = ['count' => (int) ($s['count'] ?? 5), 'post_type' => 'post'];
		if ($mode === 'views') {
			$opts['order'] = 'views';
		} else {
			$opts['flag'] = $mode;
		}
		$posts = Queries::news($opts);
		$title = (string) ($s['title'] ?? $fallback);
		if ($posts === []) {
			return $this->headed($title, '', Cards::empty_note(__('No stories in this section yet.', 'tajiksworld')));
		}
		$html = '<ol class="tw-rank">';
		$index = 1;
		foreach ($posts as $post) {
			$html .= '<li><span class="tw-rank-n">' . esc_html((string) $index) . '</span><a href="' . esc_url((string) get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a></li>';
			$index++;
		}
		return $this->headed($title, '', $html . '</ol>');
	}

	/** @param array<string, mixed> $s */
	private function news_rows(array $s): string {
		$posts = Queries::news([
			'count' => (int) ($s['count'] ?? 5),
			'category' => (string) ($s['category'] ?? ''),
			'post_type' => (string) ($s['post_type'] ?? 'post'),
		]);
		$title = (string) ($s['title'] ?? '');
		if ($posts === []) {
			return $this->headed($title, (string) ($s['category'] ?? ''), Cards::empty_note(__('No stories in this section yet.', 'tajiksworld')));
		}
		$html = '<div class="tw-rows">';
		foreach ($posts as $post) {
			$html .= Cards::render($post, [
				'variant' => 'row',
				'show_image' => !isset($s['show_thumb']) || !empty($s['show_thumb']),
				'show_excerpt' => false,
			]);
		}
		return $this->headed($title, (string) ($s['category'] ?? ''), $html . '</div>');
	}

	/** @param array<string, mixed> $s */
	private function featured(array $s): string {
		$posts = Queries::news([
			'count' => (int) ($s['count'] ?? 4),
			'category' => (string) ($s['category'] ?? ''),
			'flag' => 'featured',
			'post_type' => 'post',
		]);
		if ($posts === []) {
			return $this->headed((string) ($s['title'] ?? ''), '', Cards::empty_note(__('No stories in this section yet.', 'tajiksworld')));
		}
		$lead = array_shift($posts);
		$html = '<div class="tw-feature">';
		if ($lead instanceof \WP_Post) {
			$html .= Cards::render($lead, ['variant' => 'lead', 'show_image' => true, 'show_excerpt' => true]);
		}
		$html .= '<div class="tw-feature-rail">';
		foreach ($posts as $post) {
			$html .= Cards::render($post, ['variant' => 'rail', 'show_image' => true, 'show_excerpt' => false]);
		}
		$html .= '</div></div>';
		return $this->headed((string) ($s['title'] ?? ''), '', $html);
	}

	/** @param array<string, mixed> $s */
	private function breaking(array $s): string {
		$posts = Queries::news(['count' => (int) ($s['count'] ?? 8), 'flag' => 'breaking', 'post_type' => 'post']);
		if ($posts === []) {
			return '';
		}
		$label = $this->label((string) ($s['label'] ?? 'Breaking'));
		$items = '';
		foreach ($posts as $post) {
			$items .= '<a href="' . esc_url((string) get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a>';
		}
		return '<div class="tw-ticker" role="region" aria-label="' . esc_attr($label) . '"><span class="tw-ticker-label">' . esc_html($label) . '</span><div class="tw-ticker-viewport"><div class="tw-ticker-track">' . $items . $items . '</div></div></div>';
	}

	/** @param array<string, mixed> $s */
	private function categories(array $s): string {
		if (!function_exists('get_categories')) {
			return '';
		}
		$args = ['hide_empty' => false, 'number' => 12];
		if (!empty($s['parents_only'])) {
			$args['parent'] = 0;
		}
		$terms = get_categories($args);
		if (!is_array($terms) || $terms === []) {
			return $this->headed((string) ($s['title'] ?? ''), '', Cards::empty_note(__('No categories yet.', 'tajiksworld')));
		}
		$html = '<ul class="tw-cat-list">';
		foreach ($terms as $term) {
			$color = (string) get_term_meta($term->term_id, '_tw_color', true);
			$style = preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? ' style="' . esc_attr('--kicker:' . $color) . '"' : '';
			$count = !empty($s['show_count']) ? ' <span>(' . esc_html((string) $term->count) . ')</span>' : '';
			$html .= '<li' . $style . '><a href="' . esc_url(get_category_link($term)) . '">' . esc_html($term->name) . '</a>' . $count . '</li>';
		}
		return $this->headed((string) ($s['title'] ?? ''), '', $html . '</ul>');
	}

	/** @param array<string, mixed> $s */
	private function carousel(array $s): string {
		$posts = Queries::news([
			'count' => (int) ($s['count'] ?? 5),
			'category' => (string) ($s['category'] ?? ''),
			'post_type' => (string) ($s['post_type'] ?? 'post'),
		]);
		if ($posts === []) {
			return $this->headed((string) ($s['title'] ?? ''), '', Cards::empty_note(__('No stories in this section yet.', 'tajiksworld')));
		}
		$slides = '';
		foreach ($posts as $post) {
			$slides .= '<div class="tw-slide">' . Cards::render($post, ['variant' => 'slide', 'show_image' => true, 'show_excerpt' => true]) . '</div>';
		}
		$label_prev = esc_attr__('Previous', 'tajiksworld');
		$label_next = esc_attr__('Next', 'tajiksworld');
		$body = '<div class="tw-carousel" data-tw-carousel><button type="button" class="tw-carousel-btn" data-dir="-1" aria-label="' . $label_prev . '">‹</button><div class="tw-carousel-track">' . $slides . '</div><button type="button" class="tw-carousel-btn" data-dir="1" aria-label="' . $label_next . '">›</button></div>';
		return $this->headed((string) ($s['title'] ?? ''), '', $body);
	}

	/** @param array<string, mixed> $s */
	private function author(array $s): string {
		if (!function_exists('get_userdata')) {
			return '';
		}
		$user_id = (int) ($s['user_id'] ?? 0);
		if ($user_id <= 0 && function_exists('is_singular') && is_singular()) {
			$user_id = (int) get_the_author_meta('ID');
		}
		$user = $user_id > 0 ? get_userdata($user_id) : false;
		if (!$user) {
			return '<p class="tw-empty">' . esc_html__('Author details appear on an article or when a user ID is set.', 'tajiksworld') . '</p>';
		}
		$bio = !empty($s['show_bio']) ? '<p>' . esc_html((string) get_the_author_meta('description', $user->ID)) . '</p>' : '';
		$avatar = get_avatar($user->ID, 72, '', $user->display_name, ['class' => 'tw-avatar']);
		$url = get_author_posts_url($user->ID);
		return '<aside class="tw-author"><div class="tw-author-avatar">' . $avatar . '</div><div><p class="tw-kicker">' . esc_html__('Author', 'tajiksworld') . '</p><h3><a href="' . esc_url($url) . '">' . esc_html($user->display_name) . '</a></h3>' . $bio . '</div></aside>';
	}

	/** @param array<string, mixed> $s */
	private function advertisement(array $s): string {
		if (!class_exists(Ads::class)) {
			return '';
		}
		return Ads::render((string) ($s['placement'] ?? 'homepage'));
	}

	/** @param array<string, mixed> $s */
	private function newsletter(array $s): string {
		$title = $this->label((string) ($s['title'] ?? 'The morning briefing'));
		$text = $this->label((string) ($s['text'] ?? ''));
		$button = $this->label((string) ($s['button'] ?? 'Subscribe'));
		$nonce = function_exists('wp_create_nonce') ? wp_create_nonce('tw_subscribe') : '';
		$action = function_exists('admin_url') ? admin_url('admin-ajax.php') : '';
		$id = 'tw-news-email-' . (++$this->form_seq);
		return '<form class="tw-newsletter" data-tw-newsletter action="' . esc_url($action) . '" method="post"><div><p class="tw-kicker">' . esc_html__('Newsletter', 'tajiksworld') . '</p><h2>' . esc_html($title) . '</h2><p>' . esc_html($text) . '</p></div><div class="tw-newsletter-fields"><label class="screen-reader-text" for="' . esc_attr($id) . '">' . esc_html__('Email', 'tajiksworld') . '</label><input id="' . esc_attr($id) . '" type="email" name="email" required placeholder="' . esc_attr__('you@example.com', 'tajiksworld') . '" /><input type="text" name="tw_company" class="tw-hp" tabindex="-1" autocomplete="off" aria-hidden="true" /><input type="hidden" name="action" value="tw_subscribe" /><input type="hidden" name="nonce" value="' . esc_attr($nonce) . '" /><button class="tw-btn tw-btn-primary" type="submit">' . esc_html($button) . '</button></div><p class="tw-form-note" role="status"></p></form>';
	}

	/** @param array<string, mixed> $s */
	private function social(array $s): string {
		if (!function_exists('home_url')) {
			return '';
		}
		$links = [
			'Facebook'  => (string) Options::get('social_facebook', ''),
			'X'         => (string) Options::get('social_x', ''),
			'Instagram' => (string) Options::get('social_instagram', ''),
			'Telegram'  => (string) Options::get('social_telegram', ''),
			'YouTube'   => (string) Options::get('social_youtube', ''),
		];
		if (!empty(Options::get('social_rss', true))) {
			$links['RSS'] = (string) get_bloginfo('rss2_url');
		}
		$html = '<ul class="tw-social tw-social-' . esc_attr((string) ($s['style'] ?? 'text')) . '">';
		$any = false;
		foreach ($links as $label => $url) {
			if ($url === '') {
				continue;
			}
			$any = true;
			$html .= '<li><a href="' . esc_url($url) . '" rel="noopener noreferrer">' . esc_html($label) . '</a></li>';
		}
		if (!$any) {
			return '';
		}
		return $html . '</ul>';
	}

	/** @param array<string, mixed> $s */
	private function search(array $s): string {
		if (!function_exists('home_url')) {
			return '';
		}
		$style = (string) ($s['style'] ?? 'inline');
		if ($style === 'icon') {
			return '<button type="button" class="tw-icon-btn" data-tw-search-open aria-label="' . esc_attr__('Search', 'tajiksworld') . '">' . Icons::render('search', 20) . '</button>';
		}
		$placeholder = $this->label((string) ($s['placeholder'] ?? 'Search the newsroom'));
		return '<form class="tw-search" role="search" method="get" action="' . esc_url(home_url('/')) . '"><label class="screen-reader-text" for="tw-search-inline">' . esc_html__('Search', 'tajiksworld') . '</label><input id="tw-search-inline" type="search" name="s" placeholder="' . esc_attr($placeholder) . '" /><button class="tw-btn tw-btn-secondary" type="submit">' . esc_html__('Search', 'tajiksworld') . '</button></form>';
	}

	/** @param array<string, mixed> $s */
	private function statistics(array $s): string {
		if (($s['mode'] ?? 'auto') === 'manual') {
			$items = [];
			for ($i = 1; $i <= 4; $i++) {
				$items[] = [(string) ($s['label_' . $i] ?? ''), (string) ($s['value_' . $i] ?? '')];
			}
		} else {
			$posts = function_exists('wp_count_posts') ? wp_count_posts('post') : null;
			$published = $posts && isset($posts->publish) ? (string) $posts->publish : '0';
			$cats_raw = function_exists('wp_count_terms') ? wp_count_terms(['taxonomy' => 'category', 'hide_empty' => false]) : '0';
			$cats = is_wp_error($cats_raw) ? '0' : (string) $cats_raw;
			$views = '0';
			if (isset($GLOBALS['wpdb']) && is_object($GLOBALS['wpdb'])) {
				$wpdb = $GLOBALS['wpdb'];
				$views = (string) $wpdb->get_var($wpdb->prepare("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s", '_tw_views'));
				if ($views === '') {
					$views = '0';
				}
			}
			$items = [
				[__('Stories', 'tajiksworld'), $published],
				[__('Sections', 'tajiksworld'), $cats],
				[__('Languages', 'tajiksworld'), '3'],
				[__('Views', 'tajiksworld'), $views],
			];
		}
		$html = '<dl class="tw-stats">';
		foreach ($items as [$label, $value]) {
			if ($label === '' && $value === '') {
				continue;
			}
			$html .= '<div><dt>' . esc_html($this->label((string) $label)) . '</dt><dd>' . esc_html((string) $value) . '</dd></div>';
		}
		return $html . '</dl>';
	}

	/** @param array<string, mixed> $s */
	private function logo(array $s): string {
		if (!function_exists('get_bloginfo')) {
			return '';
		}
		$name = (string) get_bloginfo('name');
		$home = esc_url(home_url('/'));
		$logo = '';
		if (function_exists('has_custom_logo') && has_custom_logo()) {
			$logo = (string) get_custom_logo();
		}
		$retina = (int) Options::get('retina_logo', 0);
		if ($logo === '' && $retina > 0 && function_exists('wp_get_attachment_image')) {
			$logo = '<a href="' . $home . '" class="custom-logo-link">' . wp_get_attachment_image($retina, 'full', false, ['class' => 'custom-logo']) . '</a>';
		}
		if ($logo === '') {
			$logo = '<a class="tw-wordmark" href="' . $home . '">' . esc_html($name) . '</a>';
		}
		$tag = '';
		if (!empty($s['show_tagline'])) {
			$desc = (string) get_bloginfo('description');
			if ($desc !== '') {
				$tag = '<p class="tw-tagline">' . esc_html($desc) . '</p>';
			}
		}
		return '<div class="tw-logo">' . $logo . $tag . '</div>';
	}

	/** @param array<string, mixed> $s */
	private function navigation(array $s): string {
		if (!function_exists('wp_nav_menu')) {
			return '';
		}
		$location = (string) ($s['location'] ?? 'primary');
		$menu = wp_nav_menu([
			'theme_location' => $location,
			'container'      => 'nav',
			'container_class'=> 'tw-nav tw-nav-' . $location,
			'container_aria_label' => __('Primary', 'tajiksworld'),
			'fallback_cb'    => [self::class, 'nav_fallback'],
			'echo'           => false,
			'depth'          => 2,
		]);
		return is_string($menu) ? $menu : '';
	}

	public static function nav_fallback(\stdClass $args): string {
		$items = '';
		if (function_exists('get_categories')) {
			foreach (get_categories(['parent' => 0, 'hide_empty' => false, 'number' => 8]) as $cat) {
				$items .= '<li class="menu-item"><a href="' . esc_url(get_category_link($cat)) . '">' . esc_html($cat->name) . '</a></li>';
			}
		}
		if ($items === '' && function_exists('home_url')) {
			$items = '<li class="menu-item"><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'tajiksworld') . '</a></li>';
		}
		$class = esc_attr($args->container_class ?? 'tw-nav');
		return '<nav class="' . $class . '" aria-label="' . esc_attr__('Primary', 'tajiksworld') . '"><ul class="tw-menu">' . $items . '</ul></nav>';
	}

	private function dark_toggle(): string {
		if (function_exists('get_option') && !Options::get('allow_user_dark', true) && (string) Options::get('color_mode', 'system') !== 'system') {
			return '';
		}
		return '<button type="button" class="tw-icon-btn" data-tw-theme aria-pressed="false" aria-label="' . esc_attr__('Toggle color mode', 'tajiksworld') . '"><span class="tw-when-light">' . Icons::render('moon', 18) . '</span><span class="tw-when-dark">' . Icons::render('sun', 18) . '</span></button>';
	}

	/** @param array<string, mixed> $s */
	private function copyright(array $s): string {
		$text = (string) ($s['text'] ?? '');
		if ($text === '' && function_exists('get_option')) {
			$text = (string) Options::get('copyright', '');
		}
		$site = function_exists('get_bloginfo') ? (string) get_bloginfo('name') : '';
		$year = function_exists('wp_date') ? wp_date('Y') : gmdate('Y');
		$text = str_replace(['{year}', '{site}'], [$year, $site], $text);
		$credit = '';
		if (function_exists('get_option') && Options::get('show_developer', true)) {
			$dev = trim((string) Options::get('developer_credit', ''));
			if ($dev !== '') {
				$credit = ' <span class="tw-credit">' . esc_html($dev) . '</span>';
			}
		}
		return '<p class="tw-copyright">' . esc_html($text) . $credit . '</p>';
	}

	private function headed(string $title, string $category, string $body): string {
		if ($title === '' && $body === '') {
			return '';
		}
		$head = '';
		if ($title !== '') {
			$more = '';
			if ($category !== '' && function_exists('get_term_by')) {
				$term = get_term_by('slug', $category, 'category');
				if ($term instanceof \WP_Term) {
					$more = '<a class="tw-more" href="' . esc_url(get_category_link($term)) . '">' . esc_html__('All', 'tajiksworld') . '</a>';
				}
			}
			$head = '<header class="tw-section-head"><h2>' . esc_html($this->label($title)) . '</h2>' . $more . '</header>';
		}
		return '<div class="tw-block-news">' . $head . $body . '</div>';
	}

	private function label(string $text): string {
		if ($text === '' || !function_exists('__')) {
			return $text;
		}
		return __($text, 'tajiksworld');
	}

	private function css_unit(string $value): string {
		if (preg_match('/^\d{1,3}$/', $value) === 1) {
			return $value . 'px';
		}
		return $value;
	}

	private function empty_layout(): string {
		if (function_exists('current_user_can') && current_user_can('edit_theme_options')) {
			$url = admin_url('admin.php?page=tajiksworld-builder');
			return '<div class="tw-container tw-empty-layout"><p>' . esc_html__('This layout is empty.', 'tajiksworld') . '</p><p><a class="tw-btn tw-btn-primary" href="' . esc_url($url) . '">' . esc_html__('Open the visual builder', 'tajiksworld') . '</a></p></div>';
		}
		return '<div class="tw-container tw-empty-layout"><p>' . esc_html__('News will appear here soon.', 'tajiksworld') . '</p></div>';
	}
}
