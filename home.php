<?php
/**
 * Blog posts index when a static front page is set.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
?>
<div class="tw-container tw-archive">
	<header class="tw-archive-head">
		<p class="tw-kicker"><?php esc_html_e('Newsroom', 'tajiksworld'); ?></p>
		<h1><?php esc_html_e('Latest news', 'tajiksworld'); ?></h1>
	</header>
	<?php if (have_posts()) : ?>
		<div class="tw-auto-grid tw-cols-3">
			<?php
			while (have_posts()) :
				the_post();
				echo TajiksWorld\Helpers\Cards::render(get_post(), ['variant' => 'grid', 'show_image' => true, 'show_excerpt' => true]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endwhile;
			?>
		</div>
		<?php the_posts_pagination(['mid_size' => 1]); ?>
	<?php else : ?>
		<?php get_template_part('template-parts/news/empty'); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
