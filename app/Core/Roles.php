<?php
/**
 * Capabilities added with the theme and removed when another theme is activated.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Core;

final class Roles {
	public static function register(): void {
		add_action('after_switch_theme', [self::class, 'add']);
		add_action('switch_theme', [self::class, 'remove']);
	}

	public static function add(): void {
		$editor = get_role('editor');
		$admin = get_role('administrator');
		if ($editor) {
			$editor->add_cap('tajiksworld_builder');
		}
		if ($admin) {
			$admin->add_cap('tajiksworld_builder');
			$admin->add_cap('tajiksworld_manage');
		}
	}

	public static function remove(): void {
		foreach (['editor', 'administrator'] as $name) {
			$role = get_role($name);
			if (!$role) {
				continue;
			}
			$role->remove_cap('tajiksworld_builder');
			$role->remove_cap('tajiksworld_manage');
		}
	}
}
