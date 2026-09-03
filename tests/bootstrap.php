<?php

declare(strict_types=1);

/*
 * Bootstrap für die Unit-Tests.
 *
 * Liegt ein Composer-Autoloader (vendor/autoload.php) vor, wird der benutzt.
 * Sonst springt ein einfacher PSR-4-Autoloader ein, damit die Tests auch ohne
 * installierte Abhängigkeiten mit einem eigenständigen PHPUnit laufen.
 */

$autoload = __DIR__.'/../vendor/autoload.php';

if (is_file($autoload))
{
	require $autoload;

	return;
}

spl_autoload_register(
	static function (string $class): void
	{
		$map = array
		(
			'Schachbulle\\ContaoTeamtournamentBundle\\Tests\\' => __DIR__.'/',
			'Schachbulle\\ContaoTeamtournamentBundle\\' => __DIR__.'/../src/',
		);

		foreach ($map as $prefix => $directory)
		{
			if (0 !== strncmp($class, $prefix, \strlen($prefix)))
			{
				continue;
			}

			$file = $directory.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';

			if (is_file($file))
			{
				require $file;
			}

			return;
		}
	}
);
