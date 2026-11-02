<?php
/**
 * Category color, image, and description support (description is native).
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Taxonomies;

final class CategoryFields {
	public static function register(): void {
		add_action('category_add_form_fields', [self::class, 'add']);
		add_action('category_edit_form_fields', [self::class, 'edit']);
		add_action('created_category', [self::class, 'save']);
		add_action('edited_category', [self::class, 'save']);
		add_action('admin_enqueue_scripts', [self::class, 'assets']);
	}

	public static function assets(string $hook): void {
		if (!in_array($hook, ['edit-tags.php', 'term.php'], true)) {
			return;
		}
		wp_enqueue_media();
	}

	public static function add(): void {
		wp_nonce_field('tw_category_meta', 'tw_category_nonce');
		echo '<div class="form-field"><label for="tw_color">' . esc_html__('Color', 'tajiksworld') . '</label><input type="color" name="tw_color" id="tw_color" value="#a11d2e" /></div>';
		echo '<div class="form-field"><label for="tw_image_id">' . esc_html__('Image ID', 'tajiksworld') . '</label><input type="number" name="tw_image_id" id="tw_image_id" min="0" value="0" /><p class="description">' . esc_html__('Attachment ID from the media library. Child categories are created with the Parent dropdown above.', 'tajiksworld') . '</p></div>';
	}

	public static function edit(\WP_Term $term): void {
		wp_nonce_field('tw_category_meta', 'tw_category_nonce');
		$color = (string) get_term_meta($term->term_id, '_tw_color', true);
		if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
			$color = '#a11d2e';
		}
		$image = (int) get_term_meta($term->term_id, '_tw_image_id', true);
		echo '<tr class="form-field"><th scope="row"><label for="tw_color">' . esc_html__('Color', 'tajiksworld') . '</label></th><td><input type="color" name="tw_color" id="tw_color" value="' . esc_attr($color) . '" /></td></tr>';
		echo '<tr class="form-field"><th scope="row"><label for="tw_image_id">' . esc_html__('Image ID', 'tajiksworld') . '</label></th><td><input type="number" name="tw_image_id" id="tw_image_id" min="0" value="' . esc_attr((string) $image) . '" />';
		if ($image > 0) {
			echo '<div style="margin-top:8px">' . wp_get_attachment_image($image, 'thumbnail') . '</div>';
		}
		echo '</td></tr>';
	}

	public static function save(int $term_id): void {
		if (!isset($_POST['tw_category_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['tw_category_nonce'])), 'tw_category_meta')) {
			return;
		}
		if (!current_user_can('manage_categories')) {
			return;
		}
		$color = isset($_POST['tw_color']) ? sanitize_hex_color(wp_unslash((string) $_POST['tw_color'])) : '';
		if (is_string($color) && $color !== '') {
			update_term_meta($term_id, '_tw_color', $color);
		}
		update_term_meta($term_id, '_tw_image_id', absint($_POST['tw_image_id'] ?? 0));
	}
}
