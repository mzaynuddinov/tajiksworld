<?php
/**
 * PSR-4 autoloader for TajiksWorld\ — no Composer runtime required.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

spl_autoload_register(
	static function (string $class): void {
		$prefix = 'TajiksWorld\\';
		if (!str_starts_with($class, $prefix)) {
			return;
		}
		$relative = substr($class, strlen($prefix));
		$file     = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
		if (is_file($file)) {
			require $file;
		}
	}
);
