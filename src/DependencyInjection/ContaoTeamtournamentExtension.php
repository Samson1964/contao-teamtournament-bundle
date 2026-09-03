<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Lädt die Dienstbeschreibung des Bundles in den Symfony-Behälter.
 */
class ContaoTeamtournamentExtension extends Extension
{
	/**
	 * Liest die services.yml ein.
	 *
	 * Die Basisklasse liegt in Symfony 5.4 wie in 7.x unter demselben
	 * Namensraum, und ExtensionInterface::load() schreibt keinen Rückgabetyp
	 * vor — ein eigenes ": void" ist deshalb erlaubt.
	 *
	 * @param array<int, array<string, mixed>> $mergedConfig Die zusammengeführte
	 *                                                       Konfiguration; das Bundle
	 *                                                       wertet sie nicht aus
	 * @param ContainerBuilder                 $container    Der Behälter, in den die
	 *                                                       Dienste geschrieben werden
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);

		$loader->load('services.yml');
	}
}
