<?php
/**
 * Generic archives.
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
		<h1><?php the_archive_title(); ?></h1>
		<?php the_archive_description('<div class="tw-dek">', '</div>'); ?>
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
