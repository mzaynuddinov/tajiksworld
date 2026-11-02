<?php
/**
 * Site header.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="tw-skip" href="#content"><?php esc_html_e('Skip to content', 'tajiksworld'); ?></a>
<?php
echo (new TajiksWorld\Builder\Renderer())->render(tw_layout_for('header')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
get_template_part('template-parts/header/mobile');
?>
<main id="content" class="tw-main">
