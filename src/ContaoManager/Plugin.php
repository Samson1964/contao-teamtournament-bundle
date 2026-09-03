<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoTeamtournamentBundle\ContaoTeamtournamentBundle;

/**
 * Meldet das Bundle beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Gibt die Bundles zurück, die dieses Paket in den Kernel einhängt.
	 *
	 * Das Bundle wird nach dem Contao-Kern geladen, damit dessen Dienste,
	 * Sprachdateien und DCA-Definitionen bereits stehen, wenn die eigenen
	 * Ergänzungen dazukommen.
	 *
	 * Ein Rückgabetyp ist bewusst nicht deklariert: contao/manager-plugin 2.13
	 * schreibt in BundlePluginInterface::getBundles() keinen vor, und ein
	 * eigener würde die Fassungen unnötig auseinanderziehen.
	 *
	 * @param ParserInterface $parser Zerlegt Konfigurationsdateien, hier ungenutzt
	 *
	 * @return array<int, BundleConfig> Die Bundle-Konfiguration dieses Pakets
	 */
	public function getBundles(ParserInterface $parser)
	{
		return array
		(
			BundleConfig::create(ContaoTeamtournamentBundle::class)
				->setLoadAfter(array(ContaoCoreBundle::class)),
		);
	}
}
