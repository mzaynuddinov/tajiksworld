<?php
/**
 * Single news article.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
while (have_posts()) :
	the_post();
	$post_id = get_the_ID();
	?>
	<article <?php post_class('tw-article tw-container'); ?>>
		<div class="tw-article-layout">
			<div class="tw-article-main">
				<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<header class="tw-article-head">
					<?php echo TajiksWorld\Helpers\Cards::kicker($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h1><?php the_title(); ?></h1>
					<?php if (has_excerpt()) : ?>
						<p class="tw-dek"><?php echo esc_html(get_the_excerpt()); ?></p>
					<?php endif; ?>
					<div class="tw-byline">
						<?php echo get_avatar(get_the_author_meta('ID'), 48, '', get_the_author(), ['class' => 'tw-avatar']); ?>
						<div>
							<a href="<?php echo esc_url(get_author_posts_url((int) get_the_author_meta('ID'))); ?>"><?php the_author(); ?></a>
							<p>
								<time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?> <?php echo esc_html(get_the_time()); ?></time>
								<?php if (get_the_modified_date('U') !== get_the_date('U')) : ?>
									<span><?php esc_html_e('Updated', 'tajiksworld'); ?> <?php echo esc_html(get_the_modified_date()); ?></span>
								<?php endif; ?>
								<?php
								$minutes = TajiksWorld\Helpers\Cards::reading_minutes($post_id);
								if ($minutes > 0) {
									echo '<span>' . esc_html(sprintf(_n('%d min read', '%d min read', $minutes, 'tajiksworld'), $minutes)) . '</span>';
								}
								?>
								<span><span data-tw-view-count><?php echo esc_html((string) (int) get_post_meta($post_id, '_tw_views', true)); ?></span> <?php esc_html_e('views', 'tajiksworld'); ?></span>
							</p>
						</div>
					</div>
					<div class="tw-article-tools">
						<?php echo tw_share_links($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="tw-font-size" role="group" aria-label="<?php esc_attr_e('Article text size', 'tajiksworld'); ?>">
							<button type="button" data-tw-font="sm">A−</button>
							<button type="button" data-tw-font="md" aria-pressed="true">A</button>
							<button type="button" data-tw-font="lg">A+</button>
						</div>
						<button type="button" data-tw-print><?php esc_html_e('Print', 'tajiksworld'); ?></button>
					</div>
				</header>
				<?php if (has_post_thumbnail()) : ?>
					<figure class="tw-article-figure">
						<?php the_post_thumbnail('tw-hero', ['class' => 'tw-article-img']); ?>
						<?php $caption = wp_get_attachment_caption(get_post_thumbnail_id()); ?>
						<?php if (is_string($caption) && $caption !== '') : ?>
							<figcaption><?php echo esc_html($caption); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>
				<?php echo TajiksWorld\Ads\Manager::render('in_article'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="tw-article-body">
					<?php the_content(); ?>
					<?php
					$video = (string) get_post_meta($post_id, '_tw_video_url', true);
					if ($video !== '') {
						echo (new TajiksWorld\Builder\Renderer())->block(['type' => 'video', 'settings' => ['url' => $video, 'caption' => ''], 'style' => []]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					$gallery = (string) get_post_meta($post_id, '_tw_gallery', true);
					if ($gallery !== '') {
						echo (new TajiksWorld\Builder\Renderer())->block(['type' => 'gallery', 'settings' => ['images' => $gallery, 'columns' => '3'], 'style' => []]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					$audio = (string) get_post_meta($post_id, '_tw_audio_url', true);
					if ($audio !== '') {
						echo (new TajiksWorld\Builder\Renderer())->block(['type' => 'video', 'settings' => ['url' => $audio, 'caption' => ''], 'style' => []]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					$guest = (string) get_post_meta($post_id, '_tw_guest', true);
					if ($guest !== '') {
						$role = (string) get_post_meta($post_id, '_tw_guest_role', true);
						echo '<p class="tw-source">' . esc_html__('Guest', 'tajiksworld') . ': ' . esc_html($guest) . ($role !== '' ? ' — ' . esc_html($role) : '') . '</p>';
					}
					$pdf = (string) get_post_meta($post_id, '_tw_pdf_url', true);
					if ($pdf !== '') {
						echo '<p><a class="tw-btn tw-btn-secondary" href="' . esc_url($pdf) . '">' . esc_html__('Download the report', 'tajiksworld') . '</a></p>';
					}
					$source = (string) get_post_meta($post_id, '_tw_source', true);
					if ($source !== '') {
						echo '<p class="tw-source">' . esc_html__('Source', 'tajiksworld') . ': ' . esc_html($source) . '</p>';
					}
					?>
				</div>
				<?php echo TajiksWorld\Ads\Manager::render('in_article'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php the_tags('<p class="tw-tags"><span>' . esc_html__('Tags', 'tajiksworld') . '</span> ', ', ', '</p>'); ?>
				<?php echo tw_share_links($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo (new TajiksWorld\Builder\Renderer())->block(['type' => 'author_box', 'settings' => ['user_id' => 0, 'show_bio' => true], 'style' => []]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<nav class="tw-pager" aria-label="<?php esc_attr_e('More stories', 'tajiksworld'); ?>">
					<?php previous_post_link('<div class="tw-pager-prev"><span>' . esc_html__('Previous', 'tajiksworld') . '</span>%link</div>'); ?>
					<?php next_post_link('<div class="tw-pager-next"><span>' . esc_html__('Next', 'tajiksworld') . '</span>%link</div>'); ?>
				</nav>
				<?php
				$related = TajiksWorld\News\Queries::related($post_id, 3);
				if ($related !== []) :
					?>
					<section class="tw-related">
						<h2><?php esc_html_e('Related news', 'tajiksworld'); ?></h2>
						<div class="tw-auto-grid tw-cols-3">
							<?php foreach ($related as $related_post) : ?>
								<?php echo TajiksWorld\Helpers\Cards::render($related_post, ['variant' => 'grid', 'show_image' => true, 'show_excerpt' => false]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
				<?php if (comments_open() || get_comments_number()) { comments_template(); } ?>
			</div>
			<?php get_sidebar(); ?>
		</div>
	</article>
	<?php
endwhile;
get_footer();
