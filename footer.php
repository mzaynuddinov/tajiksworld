<?php
/**
 * Site footer.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;
?>
</main>
<?php
echo (new TajiksWorld\Builder\Renderer())->render(tw_layout_for('footer')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
get_template_part('template-parts/footer/search-overlay');
?>
<div class="tw-lightbox" data-tw-lightbox hidden>
	<button type="button" class="tw-lightbox-close" aria-label="<?php esc_attr_e('Close', 'tajiksworld'); ?>">×</button>
	<img alt="" />
</div>
<?php wp_footer(); ?>
</body>
</html>
