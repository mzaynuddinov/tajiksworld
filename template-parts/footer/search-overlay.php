<?php
/**
 * Search overlay opened from the header icon.
 *
 * @package TajiksWorld
 */
defined('ABSPATH') || exit;
?>
<div class="tw-search-overlay" data-tw-search hidden>
	<button type="button" class="tw-lightbox-close" data-tw-search-close aria-label="<?php esc_attr_e('Close search', 'tajiksworld'); ?>">×</button>
	<?php get_search_form(); ?>
</div>
