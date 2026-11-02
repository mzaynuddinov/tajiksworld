<?php
/**
 * Theme activation tasks.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Core;

use TajiksWorld\Builder\Repository;
use TajiksWorld\Newsletter\Subscribers;

final class Activator {
	public static function register(): void {
		add_action('after_switch_theme', [self::class, 'activate']);
	}

	public static function activate(): void {
		Roles::add();
		Repository::seed_if_missing();
		Subscribers::install();
		self::menu();
		flush_rewrite_rules();
	}

	private static function menu(): void {
		$name = 'TajiksWorld Primary';
		$menu = wp_get_nav_menu_object($name);
		$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
		if ($menu_id <= 0) {
			return;
		}
		$locations = get_theme_mod('nav_menu_locations');
		if (!is_array($locations)) {
			$locations = [];
		}
		if (empty($locations['primary'])) {
			$locations['primary'] = $menu_id;
			set_theme_mod('nav_menu_locations', $locations);
		}
	}
}
