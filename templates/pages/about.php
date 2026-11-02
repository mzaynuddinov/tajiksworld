<?php
/**
 * Template Name: About
 * About page.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
while (have_posts()) :
	the_post();
	?>
	<article <?php post_class('tw-container tw-page'); ?>>
		<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p class="tw-kicker"><?php esc_html_e('About the newsroom', 'tajiksworld'); ?></p>
		<h1><?php the_title(); ?></h1>
		<?php if (has_post_thumbnail()) : ?>
			<figure class="tw-article-figure"><?php the_post_thumbnail('tw-hero'); ?></figure>
		<?php endif; ?>
		<div class="tw-article-body"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;
get_footer();
