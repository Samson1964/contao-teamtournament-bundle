<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\Tests\Classes;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer;

/**
 * Prüft die Altersberechnung.
 *
 * Nur alter() wird hier geprüft: bild() und standardbild() brauchen den
 * Symfony-Behälter und die Contao-Konfiguration und lassen sich ohne
 * Installation nicht sinnvoll aufrufen.
 */
class HelferTest extends TestCase
{
	/**
	 * Prüft die Altersberechnung für vollständige und unvollständige Daten.
	 *
	 * @dataProvider alterProvider
	 *
	 * @param string   $strGeburtsdatum  Geburtsdatum in der Anzeigeform
	 * @param string   $strReferenzdatum Stichtag in der Anzeigeform
	 * @param int|null $intErwartet      Das erwartete Alter
	 */
	public function testAlter(string $strGeburtsdatum, string $strReferenzdatum, ?int $intErwartet): void
	{
		$this->assertSame($intErwartet, Helfer::alter($strGeburtsdatum, $strReferenzdatum));
	}

	/**
	 * Liefert die Prüffälle für testAlter().
	 *
	 * @return array<string, array{0: string, 1: string, 2: int|null}>
	 */
	public function alterProvider(): array
	{
		return array
		(
			'Geburtstag lag bereits'        => array('15.03.2000', '01.07.2020', 20),
			'Geburtstag steht noch aus'     => array('15.03.2000', '01.01.2020', 19),
			'Geburtstag ist der Stichtag'   => array('15.03.2000', '15.03.2020', 20),
			'nur Monat und Jahr bekannt'    => array('03.2000', '01.07.2020', 20),
			'nur das Jahr bekannt'          => array('2000', '01.07.2020', 20),
			'Geburt vor 1970'               => array('02.11.1948', '01.07.2020', 71),
			'Stichtag nur als Jahr'         => array('15.03.2000', '2020', 19),
			'leeres Geburtsdatum'           => array('', '01.07.2020', null),
			'unbrauchbares Geburtsdatum'    => array('irgendwas', '01.07.2020', null),
			'leerer Stichtag'               => array('15.03.2000', '', null),
		);
	}
}
