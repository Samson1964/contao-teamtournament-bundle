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
use Schachbulle\ContaoTeamtournamentBundle\Classes\Rueckweg;

/**
 * Prüft die Zieladressen des Zurück-Knopfes.
 *
 * Geprüft wird der rechnende Teil; das Eintragen in den Sitzungsspeicher
 * braucht eine Contao-Installation und steht im Prüfstand.
 */
class RueckwegTest extends TestCase
{
	/**
	 * @dataProvider zieleProvider
	 *
	 * @param string   $strTabelle  Die angezeigte Kindtabelle
	 * @param int|null $intElternId Kennung des Zieldatensatzes
	 * @param string   $strErwartet Die erwarteten Adressparameter
	 */
	public function testParameter(string $strTabelle, ?int $intElternId, string $strErwartet): void
	{
		$this->assertSame($strErwartet, Rueckweg::parameter($strTabelle, $intElternId));
	}

	/**
	 * @return array<string, array{0: string, 1: int|null, 2: string}>
	 */
	public function zieleProvider(): array
	{
		return array
		(
			'Mannschaften führen zur Übersicht' => array('tl_teamtournament_teams', null, 'do=teamtournament'),
			'Wettkämpfe führen zur Übersicht'   => array('tl_teamtournament_matches', 7, 'do=teamtournament'),
			'Spieler führen zur Mannschaftsliste des Turniers' => array('tl_teamtournament_players', 11, 'do=teamtournament&table=tl_teamtournament_teams&id=11'),
			'Partien führen zur Wettkampfliste des Turniers'   => array('tl_teamtournament_games', 243, 'do=teamtournament&table=tl_teamtournament_matches&id=243'),
			'Spieler ohne Turnier: kein Ziel'   => array('tl_teamtournament_players', null, ''),
			'Partien ohne Turnier: kein Ziel'   => array('tl_teamtournament_games', 0, ''),
			'fremde Tabelle: kein Ziel'         => array('tl_content', 5, ''),
		);
	}
}
