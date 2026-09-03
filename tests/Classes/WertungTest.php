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
use Schachbulle\ContaoTeamtournamentBundle\Classes\Wertung;

/**
 * Prüft die Punkteumrechnung.
 *
 * Geprüft wird nur, was ohne Datenbank auskommt: die Zuordnung der
 * Brettergebnisse und die Umwandlung zwischen Anzeige- und Datenbankform.
 * berechneWettkampf() und schreibeWettkampf() brauchen eine Verbindung und
 * gehören in die Prüfung an einer echten Installation.
 */
class WertungTest extends TestCase
{
	/**
	 * Prüft die Zuordnung eines Brettergebnisses zu Punkten.
	 *
	 * @dataProvider punkteProvider
	 *
	 * @param string|null                    $strErgebnis Das Brettergebnis
	 * @param array{0: float, 1: float}|null $arrErwartet Die erwarteten Punkte
	 */
	public function testPunkte($strErgebnis, ?array $arrErwartet): void
	{
		$this->assertSame($arrErwartet, Wertung::punkte($strErgebnis));
	}

	/**
	 * Liefert die Prüffälle für testPunkte().
	 *
	 * @return array<string, array{0: string|null, 1: array{0: float, 1: float}|null}>
	 */
	public function punkteProvider(): array
	{
		return array
		(
			'Weiß gewinnt'        => array('1:0', array(1.0, 0.0)),
			'Schwarz gewinnt'     => array('0:1', array(0.0, 1.0)),
			'Remis'               => array('½:½', array(0.5, 0.5)),
			'kampfloser Sieg 1'   => array('+:-', array(1.0, 0.0)),
			'kampfloser Sieg 2'   => array('-:+', array(0.0, 1.0)),
			'beide nicht da'      => array('-:-', array(0.0, 0.0)),
			'noch nicht gespielt' => array('', null),
			'unbekannt'           => array('quatsch', null),
			'null'                => array(null, null),
		);
	}

	/**
	 * Prüft die Umwandlung einer Eingabe in die Datenbankform.
	 *
	 * @dataProvider inZahlProvider
	 *
	 * @param mixed       $varEingabe  Die Eingabe aus der Maske
	 * @param string|null $strErwartet Der erwartete Wert für die Datenbank
	 */
	public function testInZahl($varEingabe, ?string $strErwartet): void
	{
		$this->assertSame($strErwartet, Wertung::inZahl($varEingabe));
	}

	/**
	 * Liefert die Prüffälle für testInZahl().
	 *
	 * @return array<string, array{0: mixed, 1: string|null}>
	 */
	public function inZahlProvider(): array
	{
		return array
		(
			'Komma'            => array('4,5', '4.5'),
			'Punkt'            => array('4.5', '4.5'),
			'ganze Zahl'       => array('4', '4.0'),
			'Null'             => array('0', '0.0'),
			'mit Leerzeichen'  => array('  3,5 ', '3.5'),
			'halber Punkt'     => array('½', '0.5'),
			'drei ein halb'    => array('3½', '3.5'),
			'Gleitkommazahl'   => array(2.5, '2.5'),
			'Ganzzahl'         => array(4, '4.0'),
			'leer'             => array('', ''),
			'unbrauchbar'      => array('vier', null),
		);
	}

	/**
	 * Prüft die Umwandlung eines Datenbankwertes in die Anzeigeform.
	 *
	 * @dataProvider ausZahlProvider
	 *
	 * @param mixed  $varWert     Der Wert aus der Datenbank
	 * @param string $strErwartet Die erwartete Anzeige
	 */
	public function testAusZahl($varWert, string $strErwartet): void
	{
		$this->assertSame($strErwartet, Wertung::ausZahl($varWert));
	}

	/**
	 * Liefert die Prüffälle für testAusZahl().
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function ausZahlProvider(): array
	{
		return array
		(
			'halbe Punkte'     => array('4.5', '4,5'),
			'ganze Punkte'     => array('4', '4,0'),
			'Null bleibt Null' => array('0', '0,0'),
			// Der eigentliche Fehler der bisherigen Fassung: Ein leeres
			// Ergebnis wurde als '0,0' angezeigt und beim nächsten Speichern
			// als gewerteter Wettkampf abgelegt
			'leer bleibt leer' => array('', ''),
			'null bleibt leer' => array(null, ''),
			'Unsinn bleibt stehen' => array('vier', 'vier'),
		);
	}

	/**
	 * Prüft, dass jedes angebotene Brettergebnis auch bewertbar ist.
	 *
	 * Die Auswahlliste in tl_teamtournament_games und die Zuordnung hier dürfen
	 * nicht auseinanderlaufen — sonst zählt ein auswählbares Ergebnis nicht mit.
	 */
	public function testAlleErgebnisseHabenPunkte(): void
	{
		$arrAuswahl = array('1:0', '0:1', '½:½', '+:-', '-:+', '-:-');

		foreach ($arrAuswahl as $strErgebnis)
		{
			$this->assertNotNull(Wertung::punkte($strErgebnis), 'Ergebnis '.$strErgebnis.' ist nicht bewertbar');
		}

		$this->assertSame($arrAuswahl, array_keys(Wertung::ERGEBNISSE));
	}
}
