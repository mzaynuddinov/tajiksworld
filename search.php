<?php
/**
 * Search results.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
?>
<div class="tw-container tw-archive">
	<header class="tw-archive-head">
		<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h1><?php printf(esc_html__('Results for “%s”', 'tajiksworld'), esc_html(get_search_query())); ?></h1>
		<?php get_search_form(); ?>
		<p><?php printf(esc_html(_n('%d result', '%d results', (int) $wp_query->found_posts, 'tajiksworld')), (int) $wp_query->found_posts); ?></p>
	</header>
	<?php if (have_posts()) : ?>
		<div class="tw-rows">
			<?php
			while (have_posts()) :
				the_post();
				echo TajiksWorld\Helpers\Cards::render(get_post(), ['variant' => 'row', 'show_image' => true, 'show_excerpt' => true]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
