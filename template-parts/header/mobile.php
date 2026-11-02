<?php
/**
 * Mobile navigation panel.
 *
 * @package TajiksWorld
 */
defined('ABSPATH') || exit;
?>
<button type="button" class="tw-burger" data-tw-menu aria-expanded="false" aria-controls="tw-mobile-panel">
	<span class="screen-reader-text"><?php esc_html_e('Menu', 'tajiksworld'); ?></span>
	<span></span><span></span><span></span>
</button>
<div id="tw-mobile-panel" class="tw-mobile" hidden>
	<?php
	wp_nav_menu([
		'theme_location' => 'primary',
		'container'      => 'nav',
		'container_class'=> 'tw-nav tw-nav-mobile',
		'fallback_cb'    => ['TajiksWorld\Builder\Renderer', 'nav_fallback'],
		'depth'          => 2,
	]);
	echo tw_language_switcher(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo TajiksWorld\Ads\Manager::render('mobile'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
