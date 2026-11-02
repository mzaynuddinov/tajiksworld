<?php
/**
 * Search form.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;
?>
<form class="tw-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="tw-s"><?php esc_html_e('Search', 'tajiksworld'); ?></label>
	<input id="tw-s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search the newsroom', 'tajiksworld'); ?>" />
	<button class="tw-btn tw-btn-secondary" type="submit"><?php esc_html_e('Search', 'tajiksworld'); ?></button>
</form>
