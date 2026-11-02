<?php
/**
 * Template Name: Contact
 * Contact page.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();
$status = isset($_GET['contact']) ? sanitize_key((string) $_GET['contact']) : '';
$settings = TajiksWorld\Helpers\Options::all();
while (have_posts()) :
	the_post();
	?>
	<article <?php post_class('tw-container tw-page'); ?>>
		<?php echo tw_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h1><?php the_title(); ?></h1>
		<div class="tw-contact">
			<div class="tw-article-body">
				<?php the_content(); ?>
				<ul class="tw-contact-list">
					<?php if ($settings['contact_email'] !== '') : ?><li><?php echo esc_html((string) $settings['contact_email']); ?></li><?php endif; ?>
					<?php if ($settings['contact_phone'] !== '') : ?><li><?php echo esc_html((string) $settings['contact_phone']); ?></li><?php endif; ?>
					<?php if ($settings['contact_address'] !== '') : ?><li><?php echo esc_html((string) $settings['contact_address']); ?></li><?php endif; ?>
				</ul>
			</div>
			<form class="tw-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<?php wp_nonce_field('tw_contact', 'tw_contact_nonce'); ?>
				<input type="hidden" name="action" value="tw_contact" />
				<input class="tw-hp" type="text" name="tw_company" tabindex="-1" autocomplete="off" aria-hidden="true" />
				<?php if ($status === 'sent') : ?><p class="tw-form-note" role="status"><?php esc_html_e('Message sent. Thank you.', 'tajiksworld'); ?></p><?php endif; ?>
				<?php if ($status === 'invalid') : ?><p class="tw-form-note" role="alert"><?php esc_html_e('Please complete every field with a valid email and a longer message.', 'tajiksworld'); ?></p><?php endif; ?>
				<?php if ($status === 'error') : ?><p class="tw-form-note" role="alert"><?php esc_html_e('The message could not be sent. Try again later.', 'tajiksworld'); ?></p><?php endif; ?>
				<?php if ($status === 'limit') : ?><p class="tw-form-note" role="alert"><?php esc_html_e('Too many messages. Please wait and try again.', 'tajiksworld'); ?></p><?php endif; ?>
				<label><?php esc_html_e('Name', 'tajiksworld'); ?><input required type="text" name="tw_name" /></label>
				<label><?php esc_html_e('Email', 'tajiksworld'); ?><input required type="email" name="tw_email" /></label>
				<label><?php esc_html_e('Message', 'tajiksworld'); ?><textarea required name="tw_message" rows="6"></textarea></label>
				<button class="tw-btn tw-btn-primary" type="submit"><?php esc_html_e('Send', 'tajiksworld'); ?></button>
			</form>
		</div>
	</article>
	<?php
endwhile;
get_footer();
