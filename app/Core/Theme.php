<?php
/**
 * Theme bootstrap.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Core;

use TajiksWorld\Ads\Manager as Ads;
use TajiksWorld\API\Contact;
use TajiksWorld\Builder\Preview;
use TajiksWorld\Builder\RestController;
use TajiksWorld\ContentTypes\Types;
use TajiksWorld\Newsletter\Subscribers;
use TajiksWorld\News\Meta;
use TajiksWorld\News\Views;
use TajiksWorld\SEO\Head;
use TajiksWorld\Taxonomies\CategoryFields;

final class Theme {
	public static function boot(): void {
		Activator::register();
		Roles::register();
		I18n::register();
		Assets::register();
		Types::register();
		Meta::register();
		Views::register();
		CategoryFields::register();
		RestController::register();
		Preview::register();
		Head::register();
		Ads::register();
		Subscribers::register();
		Contact::register();
		\TajiksWorld\Admin\Menu::register();
		add_action('after_setup_theme', [self::class, 'setup']);
		add_action('widgets_init', [self::class, 'widgets']);
		add_filter('theme_page_templates', [self::class, 'page_templates']);
		add_action('wp_head', [self::class, 'skip_target'], 1);
	}

	public static function setup(): void {
		load_theme_textdomain('tajiksworld', TAJIKSWORLD_DIR . '/languages');
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('automatic-feed-links');
		add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
		add_theme_support('custom-logo', ['height' => 80, 'width' => 280, 'flex-height' => true, 'flex-width' => true]);
		add_theme_support('responsive-embeds');
		add_theme_support('align-wide');
		add_theme_support('editor-styles');
		add_editor_style('assets/css/theme.css');
		add_image_size('tw-hero', 1600, 900, true);
		add_image_size('tw-card', 800, 520, true);
		add_image_size('tw-thumb', 240, 160, true);
		register_nav_menus([
			'primary' => __('Primary menu', 'tajiksworld'),
			'footer'  => __('Footer menu', 'tajiksworld'),
			'topbar'  => __('Top bar menu', 'tajiksworld'),
		]);
		add_theme_support('wp-block-styles');
	}

	public static function widgets(): void {
		register_sidebar([
			'name'          => __('News sidebar', 'tajiksworld'),
			'id'            => 'sidebar-1',
			'before_widget' => '<section class="tw-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="tw-widget-title">',
			'after_title'   => '</h2>',
		]);
		for ($i = 1; $i <= 4; $i++) {
			register_sidebar([
				'name'          => sprintf(
					/* translators: %d: column number */
					__('Footer column %d', 'tajiksworld'),
					$i
				),
				'id'            => 'footer-' . $i,
				'before_widget' => '<section class="tw-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="tw-widget-title">',
				'after_title'   => '</h2>',
			]);
		}
	}

	/** @param array<string, string> $templates @return array<string, string> */
	public static function page_templates(array $templates): array {
		$templates['templates/pages/about.php'] = __('About', 'tajiksworld');
		$templates['templates/pages/contact.php'] = __('Contact', 'tajiksworld');
		$templates['templates/pages/builder.php'] = __('Builder canvas', 'tajiksworld');
		return $templates;
	}

	public static function skip_target(): void {
		echo '<style>.screen-reader-text{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}.tw-hp{position:absolute!important;left:-9999px!important}</style>' . "\n";
	}
}
