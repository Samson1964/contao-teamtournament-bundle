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
	 * Prüft die Anzeigeform der Turnierdaten.
	 *
	 * @dataProvider datumProvider
	 *
	 * @param mixed  $varWert     Der gespeicherte Wert
	 * @param string $strErwartet Die erwartete Anzeige
	 */
	public function testDatum($varWert, string $strErwartet): void
	{
		$this->assertSame($strErwartet, Helfer::datum($varWert));
	}

	/**
	 * Liefert die Prüffälle für testDatum().
	 *
	 * Abgedeckt sind beide Schreibweisen im Bestand: die verkürzte aus
	 * tl_teamtournament::putDate() und die mit Nullen aufgefüllte des
	 * Helper-Bundles.
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function datumProvider(): array
	{
		return array
		(
			'vollständig als Zahl'        => array(20260916, '16.09.2026'),
			'vollständig als String'      => array('20260927', '27.09.2026'),
			'Monat und Jahr, verkürzt'    => array(202609, '09.2026'),
			'Monat und Jahr, aufgefüllt'  => array(20260900, '09.2026'),
			'nur Jahr, verkürzt'          => array(2026, '2026'),
			'nur Jahr, aufgefüllt'        => array(20260000, '2026'),
			'nur Jahr, sechsstellig'      => array(202600, '2026'),
			'leer: 0'                     => array(0, ''),
			'leer: String 0'              => array('0', ''),
			'leer: null'                  => array(null, ''),
			'leer: leerer String'         => array('', ''),
			'unbekannte Länge bleibt'     => array(12345, '12345'),
			'keine Zahl bleibt'           => array('irgendwann', 'irgendwann'),
		);
	}

	/**
	 * Ohne Land bleibt die Spalte leer; ohne Contao-Behälter erscheint das Kürzel.
	 *
	 * Die Flagge selbst lässt sich hier nicht prüfen: Dafür braucht es das
	 * Flaggen-Bundle und den Behälter. Das übernimmt der Prüfstand.
	 */
	public function testFlaggeOhneLandUndOhneDienste(): void
	{
		$this->assertSame('', Helfer::flagge(null));
		$this->assertSame('', Helfer::flagge(' '));
		$this->assertSame('XX', Helfer::landesname('XX'));
	}

	/**
	 * Prüft, dass ohne hinterlegtes Bild kein Markup entsteht.
	 *
	 * Der Fall wird abgefangen, bevor der Bilderdienst überhaupt geholt wird —
	 * deshalb läuft die Prüfung ohne Symfony-Behälter.
	 *
	 * @dataProvider leeresBildProvider
	 *
	 * @param mixed $varBild Ein Wert, der kein Bild bezeichnet
	 */
	public function testMiniaturOhneBild($varBild): void
	{
		$this->assertSame('', Helfer::miniatur($varBild));
	}

	/**
	 * Liefert die leeren Bildwerte für testMiniaturOhneBild().
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function leeresBildProvider(): array
	{
		return array
		(
			'null'            => array(null),
			'leerer String'   => array(''),
			'Null als Zahl'   => array(0),
			'Null als String' => array('0'),
		);
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
