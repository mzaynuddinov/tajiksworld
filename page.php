<?php
/**
 * Pages. A published builder layout replaces the classic content.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
while (have_posts()) :
	the_post();
	$layout = tw_layout_for('page', get_the_ID());
	if (($layout['sections'] ?? []) !== []) {
		echo (new TajiksWorld\Builder\Renderer())->render($layout); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		?>
		<article <?php post_class('tw-container tw-page'); ?>>
			<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h1><?php the_title(); ?></h1>
			<div class="tw-article-body"><?php the_content(); ?></div>
		</article>
		<?php
	}
endwhile;
get_footer();
