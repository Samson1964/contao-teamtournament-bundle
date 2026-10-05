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
use Schachbulle\ContaoTeamtournamentBundle\Classes\Rangliste;

/**
 * Prüft die Berechnung der Tabelle aus den Wettkämpfen.
 */
class RanglisteTest extends TestCase
{
	/**
	 * Vier Mannschaften für die Prüffälle.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function mannschaften(): array
	{
		return array
		(
			1 => array('name' => 'Armenien', 'country' => 'am', 'flag' => null),
			2 => array('name' => 'Deutschland', 'country' => 'de', 'flag' => null),
			3 => array('name' => 'Costa Rica', 'country' => 'cr', 'flag' => null),
			4 => array('name' => 'Bulgarien', 'country' => 'bg', 'flag' => null),
		);
	}

	/**
	 * Sucht eine Mannschaft in der fertigen Tabelle.
	 *
	 * @param array<int, array<string, mixed>> $arrTabelle Die Tabelle
	 * @param int                              $intId      Kennung der Mannschaft
	 *
	 * @return array<string, mixed> Die Zeile
	 */
	private function zeile(array $arrTabelle, int $intId): array
	{
		foreach ($arrTabelle as $arrZeile)
		{
			if ($intId === $arrZeile['id'])
			{
				return $arrZeile;
			}
		}

		$this->fail('Mannschaft '.$intId.' fehlt in der Tabelle');
	}

	/**
	 * Sieg, Unentschieden und Niederlage werden richtig gezählt.
	 */
	public function testPunkteUndBilanz(): void
	{
		$arrTabelle = Rangliste::ausWettkaempfen($this->mannschaften(), array
		(
			// Deutschland schlägt Armenien 3:1
			array('team1' => 2, 'team2' => 1, 'resultTeam1' => '3', 'resultTeam2' => '1'),
			// Costa Rica und Bulgarien trennen sich 2:2
			array('team1' => 3, 'team2' => 4, 'resultTeam1' => '2', 'resultTeam2' => '2'),
			// Armenien schlägt Costa Rica 2,5:1,5
			array('team1' => 1, 'team2' => 3, 'resultTeam1' => '2.5', 'resultTeam2' => '1.5'),
		));

		$arrDeutschland = $this->zeile($arrTabelle, 2);
		$this->assertSame(1, $arrDeutschland['kaempfe']);
		$this->assertSame(1, $arrDeutschland['siege']);
		$this->assertSame(2, $arrDeutschland['mp']);
		$this->assertSame(3.0, $arrDeutschland['bp']);
		$this->assertSame(1.0, $arrDeutschland['bpGegen']);

		$arrArmenien = $this->zeile($arrTabelle, 1);
		$this->assertSame(2, $arrArmenien['kaempfe']);
		$this->assertSame(1, $arrArmenien['siege']);
		$this->assertSame(1, $arrArmenien['niederlagen']);
		$this->assertSame(2, $arrArmenien['mp']);
		$this->assertSame(3.5, $arrArmenien['bp']);

		$arrCostaRica = $this->zeile($arrTabelle, 3);
		$this->assertSame(1, $arrCostaRica['remis']);
		$this->assertSame(1, $arrCostaRica['mp']);
		$this->assertSame(3.5, $arrCostaRica['bp']);
	}

	/**
	 * Sortiert wird nach Mannschaftspunkten, dann Brettpunkten, dann Name.
	 */
	public function testReihenfolge(): void
	{
		$arrTabelle = Rangliste::ausWettkaempfen($this->mannschaften(), array
		(
			// Deutschland 2 MP, 4 BP
			array('team1' => 2, 'team2' => 3, 'resultTeam1' => '4', 'resultTeam2' => '0'),
			// Armenien 2 MP, 2,5 BP
			array('team1' => 1, 'team2' => 4, 'resultTeam1' => '2.5', 'resultTeam2' => '1.5'),
		));

		// Bulgarien hat 1,5 Brettpunkte, Costa Rica keinen — sie sind also
		// nicht punktgleich und bekommen eigene Ränge
		$this->assertSame(array('Deutschland', 'Armenien', 'Bulgarien', 'Costa Rica'), array_column($arrTabelle, 'name'));
		$this->assertSame(array(1, 2, 3, 4), array_column($arrTabelle, 'rang'));
	}

	/**
	 * Wer in Mannschafts- und Brettpunkten gleichauf liegt, teilt sich den Rang.
	 *
	 * Der nächste Rang überspringt die geteilten Plätze (1., 1., 3., 3.).
	 * Innerhalb eines geteilten Rangs entscheidet der Name.
	 */
	public function testGeteilteRaenge(): void
	{
		$arrTabelle = Rangliste::ausWettkaempfen($this->mannschaften(), array
		(
			array('team1' => 1, 'team2' => 2, 'resultTeam1' => '3', 'resultTeam2' => '1'),
			array('team1' => 3, 'team2' => 4, 'resultTeam1' => '3', 'resultTeam2' => '1'),
		));

		$this->assertSame(array('Armenien', 'Costa Rica', 'Bulgarien', 'Deutschland'), array_column($arrTabelle, 'name'));
		$this->assertSame(array(1, 1, 3, 3), array_column($arrTabelle, 'rang'));
	}

	/**
	 * Ein Wettkampf ohne Ergebnis zählt nicht, ein 0:4 dagegen schon.
	 */
	public function testNurGewerteteWettkaempfe(): void
	{
		$arrTabelle = Rangliste::ausWettkaempfen($this->mannschaften(), array
		(
			array('team1' => 1, 'team2' => 2, 'resultTeam1' => '', 'resultTeam2' => ''),
			array('team1' => 3, 'team2' => 4, 'resultTeam1' => '0', 'resultTeam2' => '4'),
		));

		$this->assertSame(0, $this->zeile($arrTabelle, 1)['kaempfe'], 'Ohne Ergebnis kein Kampf');
		$this->assertSame(1, $this->zeile($arrTabelle, 4)['kaempfe']);
		$this->assertSame(2, $this->zeile($arrTabelle, 4)['mp'], '0:4 ist ein Ergebnis, kein fehlender Eintrag');
		$this->assertSame(0, $this->zeile($arrTabelle, 3)['mp']);
		$this->assertSame(1, $this->zeile($arrTabelle, 3)['niederlagen']);

		$this->assertTrue(Rangliste::istGewertet('0', '4'));
		$this->assertTrue(Rangliste::istGewertet('', '0'));
		$this->assertFalse(Rangliste::istGewertet('', ''));
		$this->assertFalse(Rangliste::istGewertet(null, ' '));
	}

	/**
	 * Komma statt Punkt im Ergebnis wird mitgerechnet.
	 */
	public function testKommaSchreibweise(): void
	{
		$arrTabelle = Rangliste::ausWettkaempfen($this->mannschaften(), array
		(
			array('team1' => 1, 'team2' => 2, 'resultTeam1' => '2,5', 'resultTeam2' => '1,5'),
		));

		$this->assertSame(2.5, $this->zeile($arrTabelle, 1)['bp']);
		$this->assertSame(2, $this->zeile($arrTabelle, 1)['mp']);
	}

	/**
	 * Ein Wettkampf gegen eine gelöschte Mannschaft bringt die Tabelle nicht durcheinander.
	 */
	public function testUnbekannteMannschaft(): void
	{
		$arrTabelle = Rangliste::ausWettkaempfen($this->mannschaften(), array
		(
			array('team1' => 1, 'team2' => 99, 'resultTeam1' => '4', 'resultTeam2' => '0'),
		));

		$this->assertCount(4, $arrTabelle);
		$this->assertSame(0, $this->zeile($arrTabelle, 1)['kaempfe']);
	}

	/**
	 * Ohne Mannschaften bleibt die Tabelle leer.
	 */
	public function testOhneMannschaften(): void
	{
		$this->assertSame(array(), Rangliste::ausWettkaempfen(array(), array()));
	}
}
