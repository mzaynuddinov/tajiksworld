<?php
/**
 * Builder REST endpoints.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Builder;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RestController {
	public static function register(): void {
		add_action('rest_api_init', [self::class, 'routes']);
	}

	public static function routes(): void {
		register_rest_route('tajiksworld/v1', '/layout', [
			[
				'methods'             => 'GET',
				'callback'            => [self::class, 'get_layout'],
				'permission_callback' => [self::class, 'can'],
			],
			[
				'methods'             => 'POST',
				'callback'            => [self::class, 'save_layout'],
				'permission_callback' => [self::class, 'can'],
			],
		]);
		register_rest_route('tajiksworld/v1', '/preview', [
			'methods'             => 'POST',
			'callback'            => [self::class, 'preview'],
			'permission_callback' => [self::class, 'can'],
		]);
		register_rest_route('tajiksworld/v1', '/library', [
			[
				'methods'             => 'GET',
				'callback'            => [self::class, 'library_list'],
				'permission_callback' => [self::class, 'can'],
			],
			[
				'methods'             => 'POST',
				'callback'            => [self::class, 'library_add'],
				'permission_callback' => [self::class, 'can'],
			],
			[
				'methods'             => 'DELETE',
				'callback'            => [self::class, 'library_delete'],
				'permission_callback' => [self::class, 'can'],
			],
		]);
		register_rest_route('tajiksworld/v1', '/export', [
			'methods'             => 'GET',
			'callback'            => [self::class, 'export'],
			'permission_callback' => [self::class, 'can_manage'],
		]);
		register_rest_route('tajiksworld/v1', '/import', [
			'methods'             => 'POST',
			'callback'            => [self::class, 'import'],
			'permission_callback' => [self::class, 'can_manage'],
		]);
	}

	public static function can(WP_REST_Request $request): bool {
		$post_id = (int) $request->get_param('post_id');
		if ($post_id > 0) {
			return current_user_can('edit_post', $post_id);
		}
		return current_user_can('edit_pages') || current_user_can('edit_theme_options') || current_user_can('manage_options');
	}

	public static function can_manage(): bool {
		return current_user_can('manage_options');
	}

	public static function get_layout(WP_REST_Request $request): WP_REST_Response {
		$context = self::context($request);
		$post_id = self::post_id($request, $context);
		$which = $request->get_param('which') === 'publish' ? 'publish' : 'draft';
		$layout = $which === 'publish' ? Repository::published($context, $post_id) : Repository::draft($context, $post_id);
		return new WP_REST_Response([
			'context'   => $context,
			'post_id'   => $post_id,
			'layout'    => $layout,
			'revisions' => $post_id > 0 ? [] : Repository::revisions($context),
		]);
	}

	public static function save_layout(WP_REST_Request $request): WP_REST_Response|WP_Error {
		$context = self::context($request);
		$post_id = self::post_id($request, $context);
		$status = $request->get_param('status') === 'draft' ? 'draft' : 'publish';
		$layout = $request->get_param('layout');
		if (!is_array($layout)) {
			return new WP_Error('tw_layout', __('Layout payload is missing.', 'tajiksworld'), ['status' => 400]);
		}
		Repository::save($context, $layout, $status, $post_id);
		return new WP_REST_Response(['saved' => true, 'status' => $status]);
	}

	public static function preview(WP_REST_Request $request): WP_REST_Response|WP_Error {
		$layout = $request->get_param('layout');
		if (!is_array($layout)) {
			return new WP_Error('tw_layout', __('Layout payload is missing.', 'tajiksworld'), ['status' => 400]);
		}
		$clean = (new LayoutSanitizer())->sanitize($layout, current_user_can('unfiltered_html'));
		Preview::store($clean);
		return new WP_REST_Response(['ok' => true]);
	}

	public static function library_list(): WP_REST_Response {
		return new WP_REST_Response(Library::all());
	}

	public static function library_add(WP_REST_Request $request): WP_REST_Response|WP_Error {
		$section = $request->get_param('section');
		if (!is_array($section)) {
			return new WP_Error('tw_section', __('Section payload is missing.', 'tajiksworld'), ['status' => 400]);
		}
		$name = (string) $request->get_param('name');
		return new WP_REST_Response(Library::add($name !== '' ? $name : __('Reusable section', 'tajiksworld'), $section));
	}

	public static function library_delete(WP_REST_Request $request): WP_REST_Response {
		return new WP_REST_Response(Library::delete((string) $request->get_param('id')));
	}

	public static function export(): WP_REST_Response {
		return new WP_REST_Response([
			'theme'    => 'tajiksworld',
			'version'  => TAJIKSWORLD_VERSION,
			'settings' => \TajiksWorld\Helpers\Options::all(),
			'layouts'  => [
				'home'   => Repository::published('home'),
				'header' => Repository::published('header'),
				'footer' => Repository::published('footer'),
			],
			'library'  => Library::all(),
			'ads'      => \TajiksWorld\Ads\Manager::all(),
		]);
	}

	public static function import(WP_REST_Request $request): WP_REST_Response|WP_Error {
		$payload = $request->get_json_params();
		if (!is_array($payload)) {
			return new WP_Error('tw_import', __('Import file is not valid JSON.', 'tajiksworld'), ['status' => 400]);
		}
		if (($payload['theme'] ?? '') !== 'tajiksworld') {
			return new WP_Error('tw_import', __('This file is not a TajiksWorld export.', 'tajiksworld'), ['status' => 400]);
		}
		if (isset($payload['layouts']) && is_array($payload['layouts'])) {
			foreach (['home', 'header', 'footer'] as $context) {
				if (isset($payload['layouts'][$context]) && is_array($payload['layouts'][$context])) {
					Repository::save($context, $payload['layouts'][$context], 'publish');
				}
			}
		}
		if (isset($payload['ads']) && is_array($payload['ads'])) {
			\TajiksWorld\Ads\Manager::replace($payload['ads']);
		}
		if (isset($payload['settings']) && is_array($payload['settings'])) {
			update_option(\TajiksWorld\Helpers\Options::KEY, \TajiksWorld\Settings\Store::sanitize($payload['settings'], \TajiksWorld\Helpers\Options::all()), false);
		}
		return new WP_REST_Response(['imported' => true]);
	}

	private static function context(WP_REST_Request $request): string {
		$context = sanitize_key((string) $request->get_param('context'));
		return in_array($context, ['home', 'header', 'footer', 'page'], true) ? $context : 'home';
	}

	private static function post_id(WP_REST_Request $request, string $context): int {
		if ($context !== 'page') {
			return 0;
		}
		return absint($request->get_param('post_id'));
	}
}
