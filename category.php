<?php
/**
 * Category archive, including child categories.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
$term = get_queried_object();
?>
<div class="tw-container tw-archive">
	<header class="tw-archive-head">
		<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p class="tw-kicker"><?php esc_html_e('Section', 'tajiksworld'); ?></p>
		<h1><?php single_cat_title(); ?></h1>
		<?php if ($term instanceof WP_Term) : ?>
			<?php $image = (int) get_term_meta($term->term_id, '_tw_image_id', true); ?>
			<?php if ($image > 0) : ?>
				<figure class="tw-archive-image"><?php echo wp_get_attachment_image($image, 'tw-hero'); ?></figure>
			<?php endif; ?>
			<?php if ($term->description !== '') : ?>
				<p class="tw-dek"><?php echo esc_html($term->description); ?></p>
			<?php endif; ?>
			<?php
			$children = get_categories(['parent' => $term->term_id, 'hide_empty' => false]);
			if ($children !== []) :
				echo '<ul class="tw-cat-list">';
				foreach ($children as $child) {
					echo '<li><a href="' . esc_url(get_category_link($child)) . '">' . esc_html($child->name) . '</a></li>';
				}
				echo '</ul>';
			endif;
			?>
		<?php endif; ?>
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
