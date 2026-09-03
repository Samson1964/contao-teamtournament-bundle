<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\ContentElements;

use Contao\ContentElement;
use Contao\Database;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Wertung;

/**
 * Inhaltselement „Rundenübersicht".
 *
 * Gibt alle Wettkämpfe einer Runde aus: je Wettkampf eine Kopfzeile mit den
 * beiden Mannschaften und dem Mannschaftsergebnis, darunter die Einzelpartien
 * an den Brettern.
 */
class Rounds extends ContentElement
{
	/**
	 * Name des Frontend-Templates.
	 *
	 * @var string
	 */
	protected $strTemplate = 'ce_tt-round';

	/**
	 * Baut die Tabelle der Rundenübersicht zusammen.
	 *
	 * Mannschaften und Spieler werden vorab einmal eingelesen und in zwei
	 * Feldern abgelegt, damit die Schleife über die Bretter ohne weitere
	 * Datenbankabfragen auskommt.
	 */
	protected function compile()
	{
		$objTurnier = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament WHERE id=?")
			->execute($this->teamtournament_turnier);

		if (!$objTurnier->numRows)
		{
			$this->Template->content = '';

			return;
		}

		$mannschaft = $this->getMannschaften((int) $this->teamtournament_turnier, $objTurnier->imageSize_flags);
		$spieler = $this->getSpieler((int) $this->teamtournament_turnier, $objTurnier->gender, $objTurnier->imageSize_results);

		$objWettkaempfe = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament_matches WHERE pid=? AND round=? ORDER BY board ASC, id ASC")
			->execute($this->teamtournament_turnier, $this->teamtournament_runde);

		$brett = $objTurnier->language == 'en' ? 'Bo.' : 'Br.';

		$content = '<table>';
		$tisch = 0;

		while ($objWettkaempfe->next())
		{
			++$tisch;

			// Zwischen zwei Wettkämpfen eine Leerzeile
			if ($tisch > 1)
			{
				$content .= '<tr class="empty"><td class="empty" colspan="6">&nbsp;</td></tr>';
			}

			$ergebnis = self::getErgebnis($objWettkaempfe->resultTeam1, $objWettkaempfe->resultTeam2);

			$content .= '<tr class="head">';
			$content .= '<th class="board">'.$brett.'</th>';
			$content .= '<th class="team">'.trim(($mannschaft[$objWettkaempfe->team1]['flagge'] ?? '').' '.($mannschaft[$objWettkaempfe->team1]['name'] ?? '')).'</th>';
			$content .= '<th class="rating">Elo</th>';
			$content .= '<th class="result">'.$ergebnis.'</th>';
			$content .= '<th class="team">'.trim(($mannschaft[$objWettkaempfe->team2]['flagge'] ?? '').' '.($mannschaft[$objWettkaempfe->team2]['name'] ?? '')).'</th>';
			$content .= '<th class="rating">Elo</th>';
			$content .= '</tr>';

			$objBretter = Database::getInstance()
				->prepare("SELECT * FROM tl_teamtournament_games WHERE pid=? ORDER BY board ASC")
				->execute($objWettkaempfe->id);

			while ($objBretter->next())
			{
				// Die Farbangabe gilt für den Spieler der ersten Mannschaft;
				// der Gegner hat immer die andere Farbe
				$weiss = $objBretter->colors == 'w';

				$content .= '<tr>';
				$content .= '<td class="board">'.$objBretter->board.'</td>';
				$content .= '<td class="player'.($weiss ? ' white' : ' black').'">'.$this->getSpielerZelle($spieler, $objBretter->player1).'</td>';
				$content .= '<td class="rating">'.($spieler[$objBretter->player1]['fide_elo'] ?? '').'</td>';
				$content .= '<td class="result">'.$objBretter->result.'</td>';
				$content .= '<td class="player'.($weiss ? ' black' : ' white').'">'.$this->getSpielerZelle($spieler, $objBretter->player2).'</td>';
				$content .= '<td class="rating">'.($spieler[$objBretter->player2]['fide_elo'] ?? '').'</td>';
				$content .= '</tr>';
			}
		}

		$content .= '</table>';

		$this->Template->content = $content;
	}

	/**
	 * Liest alle Mannschaften eines Turniers ein.
	 *
	 * @param int   $intTurnier Kennung des Turniers
	 * @param mixed $varGroesse Bildgröße für die Mannschaftslogos, wie am Turnier
	 *                          hinterlegt
	 *
	 * @return array<int, array<string, string>> Mannschaftskennung => Name, Land
	 *                                           und fertiges Logo-Markup
	 */
	private function getMannschaften(int $intTurnier, $varGroesse): array
	{
		$arrMannschaften = array();

		$objMannschaften = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament_teams WHERE pid=?")
			->execute($intTurnier);

		while ($objMannschaften->next())
		{
			$arrMannschaften[$objMannschaften->id] = array
			(
				'name'    => $objMannschaften->name,
				'country' => $objMannschaften->country,
				'flagge'  => Helfer::bild($objMannschaften->flag, $varGroesse, 'tt'.$objMannschaften->id),
			);
		}

		return $arrMannschaften;
	}

	/**
	 * Liest alle Spieler eines Turniers ein.
	 *
	 * Die Abfrage lief früher ohne Einschränkung über die gesamte
	 * Spielertabelle — bei mehreren Turnieren in einer Installation wurden also
	 * auch alle fremden Spieler samt Bildern aufbereitet. Der Verbund über die
	 * Mannschaftstabelle beschränkt das auf das gewählte Turnier.
	 *
	 * @param int         $intTurnier    Kennung des Turniers
	 * @param string|null $strGeschlecht 'm' oder 'w'; entscheidet, welches
	 *                                   Standardbild einspringt
	 * @param mixed       $varGroesse    Bildgröße für die Spielerfotos
	 *
	 * @return array<int, array<string, string>> Spielerkennung => Name, Titel,
	 *                                           Elo-Zahl und Foto-Markup
	 */
	private function getSpieler(int $intTurnier, $strGeschlecht, $varGroesse): array
	{
		$arrSpieler = array();
		$strStandardbild = Helfer::standardbild($strGeschlecht);

		$objSpieler = Database::getInstance()
			->prepare("SELECT p.* FROM tl_teamtournament_players p LEFT JOIN tl_teamtournament_teams t ON t.id=p.pid WHERE t.pid=?")
			->execute($intTurnier);

		while ($objSpieler->next())
		{
			$bild_id = $objSpieler->singleSRC ?: $strStandardbild;

			$arrSpieler[$objSpieler->id] = array
			(
				'name'       => trim($objSpieler->prename.' '.$objSpieler->surname),
				'fide_title' => $objSpieler->fide_title,
				'fide_elo'   => $objSpieler->fide_elo,
				'bild'       => Helfer::bild($bild_id, $varGroesse, 'tt'.$objSpieler->id),
			);
		}

		return $arrSpieler;
	}

	/**
	 * Setzt die Zelle eines Spielers aus Foto, Titel und Namen zusammen.
	 *
	 * @param array<int, array<string, string>> $arrSpieler Die eingelesenen Spieler
	 * @param mixed                             $varId      Kennung des Spielers am Brett;
	 *                                                      0, wenn das Brett unbesetzt ist
	 *
	 * @return string Der Inhalt der Zelle, oder eine leere Zeichenkette, wenn zu
	 *                der Kennung kein Spieler vorliegt
	 */
	private function getSpielerZelle(array $arrSpieler, $varId): string
	{
		if (!isset($arrSpieler[$varId]))
		{
			return '';
		}

		return trim($arrSpieler[$varId]['bild'].' '.$arrSpieler[$varId]['fide_title'].' '.$arrSpieler[$varId]['name']);
	}

	/**
	 * Formatiert das Mannschaftsergebnis eines Wettkampfes.
	 *
	 * Die Punkte stehen in der Datenbank mit Punkt als Dezimaltrennzeichen; in
	 * der Ausgabe steht das hierzulande übliche Komma.
	 *
	 * @param mixed $erg1 Brettpunkte der ersten Mannschaft
	 * @param mixed $erg2 Brettpunkte der zweiten Mannschaft
	 *
	 * @return string Das Ergebnis als '4,5 : 3,5', oder '-', solange der
	 *                Wettkampf noch nicht gewertet ist
	 */
	public static function getErgebnis($erg1, $erg2): string
	{
		// Geprüft wird auf „nichts eingetragen", nicht auf „unwahr": Ein
		// Wettkampf, der 0:4 ausgegangen ist, hat auf der einen Seite eine
		// Null stehen, und die wäre in PHP unwahr
		$strErg1 = trim((string) $erg1);
		$strErg2 = trim((string) $erg2);

		if ('' === $strErg1 && '' === $strErg2)
		{
			return '-';
		}

		return Wertung::ausZahl($strErg1).' : '.Wertung::ausZahl($strErg2);
	}
}
