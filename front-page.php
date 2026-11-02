<?php
/**
 * Front page. Sections come from the saved builder layout.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
echo (new TajiksWorld\Builder\Renderer())->render(tw_layout_for('home')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
get_footer();
