<?php
/**
 * TajiksWorld bootstrap.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('TAJIKSWORLD_VERSION', '1.0.0');
define('TAJIKSWORLD_DIR', get_template_directory());
define('TAJIKSWORLD_URI', get_template_directory_uri());

require TAJIKSWORLD_DIR . '/app/autoload.php';
require TAJIKSWORLD_DIR . '/inc/template-functions.php';

TajiksWorld\Core\Theme::boot();
