<?php
/**
 * Styles, scripts, and design tokens.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Core;

use TajiksWorld\Helpers\Options;

final class Assets {
	/** @var array<string, string> */
	private const FONTS = [
		'Newsreader'      => 'Newsreader:opsz,wght@6..72,400;6..72,560;6..72,650',
		'Source Serif 4'  => 'Source+Serif+4:opsz,wght@8..60,500;8..60,650',
		'Literata'        => 'Literata:opsz,wght@7..72,500;7..72,650',
		'Source Sans 3'   => 'Source+Sans+3:wght@400;600;700',
		'Libre Franklin'  => 'Libre+Franklin:wght@400;600;700',
	];

	public static function register(): void {
		add_action('wp_enqueue_scripts', [self::class, 'front']);
		add_action('admin_enqueue_scripts', [self::class, 'admin']);
		add_action('wp_head', [self::class, 'tokens'], 2);
		add_action('wp_head', [self::class, 'boot_theme'], 0);
		add_action('wp_head', [self::class, 'header_extras'], 20);
		add_action('wp_footer', [self::class, 'footer_extras'], 20);
		add_filter('body_class', [self::class, 'body_class']);
		add_filter('language_attributes', [self::class, 'rtl']);
	}

	public static function front(): void {
		$css = TAJIKSWORLD_DIR . '/assets/css/theme.css';
		$js_name = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) || !is_file(TAJIKSWORLD_DIR . '/assets/js/theme.min.js') ? 'theme.js' : 'theme.min.js';
		$js  = TAJIKSWORLD_DIR . '/assets/js/' . $js_name;
		wp_enqueue_style('tajiksworld', TAJIKSWORLD_URI . '/assets/css/theme.css', [], self::version($css));
		wp_enqueue_script('tajiksworld', TAJIKSWORLD_URI . '/assets/js/' . $js_name, [], self::version($js), true);
		wp_script_add_data('tajiksworld', 'strategy', 'defer');
		if (is_singular() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}
		wp_localize_script('tajiksworld', 'TajiksWorldFront', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'viewNonce' => wp_create_nonce('tw_view'),
			'postId' => is_singular() ? get_queried_object_id() : 0,
			'mode' => (string) Options::get('color_mode', 'system'),
			'allowDark' => (bool) Options::get('allow_user_dark', true),
		]);
		$families = [];
		foreach ([(string) Options::get('font_heading', 'Newsreader'), (string) Options::get('font_body', 'Source Sans 3')] as $font) {
			if (isset(self::FONTS[$font])) {
				$families[] = 'family=' . self::FONTS[$font];
			}
		}
		if ($families !== []) {
			wp_enqueue_style('tajiksworld-fonts', 'https://fonts.googleapis.com/css2?' . implode('&', array_unique($families)) . '&display=swap', [], null);
		}
	}

	public static function admin(string $hook): void {
		$page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
		$ours = str_starts_with($page, 'tajiksworld');
		if (!$ours && !in_array($hook, ['post.php', 'post-new.php', 'edit-tags.php', 'term.php'], true)) {
			return;
		}
		wp_enqueue_style('tajiksworld-admin', TAJIKSWORLD_URI . '/assets/css/admin.css', [], self::version(TAJIKSWORLD_DIR . '/assets/css/admin.css'));
		wp_enqueue_media();
		$admin_js = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) || !is_file(TAJIKSWORLD_DIR . '/assets/js/admin.min.js') ? 'admin.js' : 'admin.min.js';
		wp_enqueue_script('tajiksworld-admin', TAJIKSWORLD_URI . '/assets/js/' . $admin_js, ['jquery'], self::version(TAJIKSWORLD_DIR . '/assets/js/' . $admin_js), true);
		if (in_array($page, ['tajiksworld-builder', 'tajiksworld-header', 'tajiksworld-footer'], true)) {
			$builder_js = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) || !is_file(TAJIKSWORLD_DIR . '/assets/js/builder.min.js') ? 'builder.js' : 'builder.min.js';
			$context = $page === 'tajiksworld-header' ? 'header' : ($page === 'tajiksworld-footer' ? 'footer' : 'home');
			$post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
			if ($post_id > 0) {
				$context = 'page';
			}
			wp_enqueue_script('tajiksworld-builder', TAJIKSWORLD_URI . '/assets/js/' . $builder_js, [], self::version(TAJIKSWORLD_DIR . '/assets/js/' . $builder_js), true);
			$registry = \TajiksWorld\Builder\LayoutSanitizer::load_registry();
			foreach ($registry as &$element) {
				$element['label'] = __((string) $element['label'], 'tajiksworld');
				if (isset($element['fields']) && is_array($element['fields'])) {
					foreach ($element['fields'] as &$field) {
						$field['label'] = __((string) $field['label'], 'tajiksworld');
					}
				}
			}
			unset($element, $field);
			$categories = [];
			foreach (get_categories(['hide_empty' => false]) as $cat) {
				$categories[] = ['slug' => $cat->slug, 'name' => $cat->name];
			}
			$preview_args = [
				'tw_builder_preview' => '1',
				'tw_context' => $context,
				'post_id' => $post_id,
			];
			if ($context === 'page' && $post_id > 0) {
				$preview_args['preview'] = 'true';
			}
			wp_localize_script('tajiksworld-builder', 'TajiksWorldBuilder', [
				'rest' => esc_url_raw(rest_url('tajiksworld/v1/')),
				'nonce' => wp_create_nonce('wp_rest'),
				'context' => $context,
				'postId' => $post_id,
				'registry' => $registry,
				'categories' => $categories,
				'preview' => add_query_arg($preview_args, $context === 'page' && $post_id > 0 ? (string) get_permalink($post_id) : home_url('/')),
				'i18n' => [
					'saved' => __('Saved.', 'tajiksworld'),
					'draft' => __('Draft saved.', 'tajiksworld'),
					'published' => __('Published.', 'tajiksworld'),
					'empty' => __('Nothing is selected.', 'tajiksworld'),
					'imported' => __('Layout imported.', 'tajiksworld'),
					'confirmDelete' => __('Delete this section?', 'tajiksworld'),
				],
			]);
		}
	}

	public static function boot_theme(): void {
		$mode = (string) Options::get('color_mode', 'system');
		$allow = Options::get('allow_user_dark', true) ? 'true' : 'false';
		echo '<script>try{var d=' . wp_json_encode($mode) . ';var a=' . $allow . ';var s=a?localStorage.getItem("tw-theme"):null;var m=s||d;if(m==="system"){m=matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}if(m==="dark"){document.documentElement.setAttribute("data-theme","dark");}}catch(e){}</script>' . "\n";
	}

	public static function tokens(): void {
		$s = Options::all();
		$heading = (string) $s['font_heading'];
		$body = (string) $s['font_body'];
		$map = [
			'--tw-primary' => Options::hex((string) $s['primary'], '#a11d2e'),
			'--tw-secondary' => Options::hex((string) $s['secondary'], '#1f3a5f'),
			'--tw-accent' => Options::hex((string) $s['accent'], '#8a6a2f'),
			'--tw-bg' => Options::hex((string) $s['background'], '#f4f1ea'),
			'--tw-text' => Options::hex((string) $s['text'], '#161513'),
			'--tw-link' => Options::hex((string) $s['link'], '#8e1828'),
			'--tw-header-bg' => Options::hex((string) $s['header_bg'], '#f4f1ea'),
			'--tw-header-text' => Options::hex((string) $s['header_text'], '#161513'),
			'--tw-dark-bg' => Options::hex((string) $s['dark_bg'], '#141311'),
			'--tw-dark-text' => Options::hex((string) $s['dark_text'], '#f3efe6'),
			'--tw-dark-card' => Options::hex((string) $s['dark_card'], '#1e1c19'),
			'--tw-dark-primary' => Options::hex((string) $s['dark_primary'], '#e05868'),
			'--tw-font-size' => max(15, min(22, (int) $s['font_size'])) . 'px',
			'--tw-line' => (string) $s['line_height'],
			'--tw-heading-weight' => (string) $s['heading_weight'],
			'--tw-font-heading' => '"' . str_replace('"', '', $heading) . '", Georgia, serif',
			'--tw-font-body' => '"' . str_replace('"', '', $body) . '", system-ui, sans-serif',
		];
		$css = ':root{';
		foreach ($map as $name => $value) {
			$css .= $name . ':' . $value . ';';
		}
		$css .= '}';
		$custom = (string) $s['custom_css'];
		if ($custom !== '') {
			$css .= "\n" . $custom;
		}
		echo '<style id="tw-tokens">' . $css . '</style>' . "\n";
	}

	public static function header_extras(): void {
		$s = Options::all();
		$scripts = (string) $s['header_scripts'];
		if ($scripts !== '') {
			echo $scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- administrators with unfiltered_html only.
		}
		$ga = (string) $s['ga_id'];
		if ($ga !== '') {
			echo '<script async src="' . esc_url('https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ga)) . '"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . wp_json_encode($ga) . ');</script>' . "\n";
		}
		$pixel = (string) $s['pixel_id'];
		if ($pixel !== '') {
			echo '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init",' . wp_json_encode($pixel) . ');fbq("track","PageView");</script>' . "\n";
		}
	}

	public static function footer_extras(): void {
		$s = Options::all();
		$js = (string) $s['custom_js'];
		if ($js !== '') {
			echo '<script>' . str_ireplace('</script', '<\\/script', $js) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		$scripts = (string) $s['footer_scripts'];
		if ($scripts !== '') {
			echo $scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/** @param array<int, string> $classes @return array<int, string> */
	public static function body_class(array $classes): array {
		if (Options::get('sticky_header', true)) {
			$classes[] = 'tw-has-sticky';
		}
		if (Options::get('transparent_header', false) && is_front_page()) {
			$classes[] = 'tw-has-transparent';
		}
		return $classes;
	}

	public static function rtl(string $output): string {
		if (!Options::get('force_rtl', false)) {
			return $output;
		}
		if (str_contains($output, 'dir=')) {
			return (string) preg_replace('/dir="[^"]*"/', 'dir="rtl"', $output);
		}
		return $output . ' dir="rtl"';
	}

	private static function version(string $path): string {
		return is_file($path) ? (string) filemtime($path) : TAJIKSWORLD_VERSION;
	}
}
