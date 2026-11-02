<?php
/**
 * Author profile.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
$author = get_queried_object();
?>
<div class="tw-container tw-archive">
	<header class="tw-author tw-archive-head">
		<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ($author instanceof WP_User) : ?>
			<?php echo get_avatar($author->ID, 96, '', $author->display_name, ['class' => 'tw-avatar']); ?>
			<div>
				<p class="tw-kicker"><?php esc_html_e('Author', 'tajiksworld'); ?></p>
				<h1><?php echo esc_html($author->display_name); ?></h1>
				<?php if ($author->description !== '') : ?>
					<p class="tw-dek"><?php echo esc_html($author->description); ?></p>
				<?php endif; ?>
				<?php if ($author->user_url !== '') : ?>
					<p><a href="<?php echo esc_url($author->user_url); ?>" rel="noopener noreferrer"><?php echo esc_html($author->user_url); ?></a></p>
				<?php endif; ?>
			</div>
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
