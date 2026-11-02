<?php
/**
 * 404.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
?>
<div class="tw-container tw-archive">
	<p class="tw-kicker">404</p>
	<h1><?php esc_html_e('This page is not in the newsroom.', 'tajiksworld'); ?></h1>
	<p class="tw-dek"><?php esc_html_e('Try a search, or continue with the latest stories.', 'tajiksworld'); ?></p>
	<?php get_search_form(); ?>
	<?php
	$latest = TajiksWorld\News\Queries::news(['count' => 4, 'post_type' => 'post']);
	if ($latest !== []) :
		?>
		<div class="tw-auto-grid tw-cols-4">
			<?php foreach ($latest as $post) : ?>
				<?php echo TajiksWorld\Helpers\Cards::render($post, ['variant' => 'grid', 'show_image' => true, 'show_excerpt' => false]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
