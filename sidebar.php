<?php
/**
 * Sidebar.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (!is_active_sidebar('sidebar-1') && !is_singular()) {
	return;
}
?>
<aside class="tw-sidebar" aria-label="<?php esc_attr_e('Sidebar', 'tajiksworld'); ?>">
	<?php
	$popular = TajiksWorld\News\Queries::news(['count' => 5, 'order' => 'views', 'post_type' => 'post']);
	if ($popular !== []) :
		?>
		<section class="tw-widget">
			<h2 class="tw-widget-title"><?php esc_html_e('Popular', 'tajiksworld'); ?></h2>
			<ol class="tw-rank">
				<?php $n = 1; foreach ($popular as $post) : ?>
					<li><span class="tw-rank-n"><?php echo esc_html((string) $n); ?></span><a href="<?php echo esc_url((string) get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></li>
					<?php $n++; endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>
	<?php echo TajiksWorld\Ads\Manager::render('sidebar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php dynamic_sidebar('sidebar-1'); ?>
</aside>
