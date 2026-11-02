<?php
/**
 * Comments.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (post_password_required()) {
	return;
}
?>
<section id="comments" class="tw-comments">
	<h2>
		<?php
		$count = get_comments_number();
		echo esc_html(sprintf(_n('%d comment', '%d comments', (int) $count, 'tajiksworld'), (int) $count));
		?>
	</h2>
	<?php if (have_comments()) : ?>
		<ol class="tw-comment-list">
			<?php wp_list_comments(['style' => 'ol', 'avatar_size' => 48, 'short_ping' => true]); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(['class_form' => 'tw-comment-form', 'title_reply' => __('Leave a comment', 'tajiksworld')]); ?>
</section>
