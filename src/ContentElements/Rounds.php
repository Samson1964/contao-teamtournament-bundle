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
use Schachbulle\ContaoTeamtournamentBundle\Classes\Partiedaten;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Rangliste;
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

		// Am Turnier lassen sich die Bilder je Bereich abschalten
		$mannschaft = $this->getMannschaften((int) $this->teamtournament_turnier, $objTurnier->imageSize_flags, !$objTurnier->hideImages_flags);
		$spieler = $this->getSpieler((int) $this->teamtournament_turnier, $objTurnier->gender, $objTurnier->imageSize_results, !$objTurnier->hideImages_results);

		$objWettkaempfe = Database::getInstance()
			// Die Unterabfrage zählt die Bretter mit Ergebnis; daran hängt, ob
			// der Wettkampf als gespielt gilt (siehe getErgebnis())
			->prepare("SELECT m.*, (SELECT COUNT(*) FROM tl_teamtournament_games g WHERE g.pid=m.id AND g.result!='') AS bretter FROM tl_teamtournament_matches m WHERE m.pid=? AND m.round=? ORDER BY m.board ASC, m.id ASC")
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

			$ergebnis = self::getErgebnis($objWettkaempfe->resultTeam1, $objWettkaempfe->resultTeam2, (int) $objWettkaempfe->bretter);

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

				// PGN-Viewer nur für gespielte Partien mit hinterlegten Daten.
				// Ohne PGN bleibt die Zeile Zeichen für Zeichen wie bisher.
				$pgn = Partiedaten::pgnAusDatenbank($objBretter->pgn);
				$mitViewer = '' !== $pgn && !Partiedaten::istKampflos($objBretter->result);
				$viewerId = $this->id.'-'.$objBretter->id;

				$content .= '<tr>';
				$content .= '<td class="board">'.$objBretter->board.'</td>';
				$content .= '<td class="player'.($weiss ? ' white' : ' black').'">'.$this->getSpielerZelle($spieler, $objBretter->player1).'</td>';
				$content .= '<td class="rating">'.($spieler[$objBretter->player1]['fide_elo'] ?? '').'</td>';

				if ($mitViewer)
				{
					$content .= '<td class="result">'.$objBretter->result.Partiedaten::schalter(
						$viewerId,
						(string) $objTurnier->language,
						html_entity_decode((string) ($spieler[$objBretter->player1]['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
						html_entity_decode((string) ($spieler[$objBretter->player2]['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
						$objBretter->board
					).'</td>';
				}
				else
				{
					$content .= '<td class="result">'.$objBretter->result.'</td>';
				}

				$content .= '<td class="player'.($weiss ? ' black' : ' white').'">'.$this->getSpielerZelle($spieler, $objBretter->player2).'</td>';
				$content .= '<td class="rating">'.($spieler[$objBretter->player2]['fide_elo'] ?? '').'</td>';
				$content .= '</tr>';

				if ($mitViewer)
				{
					$content .= Partiedaten::zeile($viewerId, (string) $objTurnier->language, array
					(
						'sprache' => (string) $objTurnier->language,
						'pgn'     => $pgn,
						'kopf'    => Partiedaten::kopf(
							$this->getSpielerDaten($spieler, $objBretter->player1, $mannschaft[$objWettkaempfe->team1]['name'] ?? ''),
							$this->getSpielerDaten($spieler, $objBretter->player2, $mannschaft[$objWettkaempfe->team2]['name'] ?? ''),
							(string) $objBretter->colors,
							(string) $objBretter->result,
							$objWettkaempfe->round,
							$objBretter->board
						),
					));

					$this->bindeViewerEin();
				}
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
	private function getMannschaften(int $intTurnier, $varGroesse, bool $blnBilder = true): array
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
				'flagge'  => $blnBilder ? Helfer::bild($objMannschaften->flag, $varGroesse, 'tt'.$objMannschaften->id) : '',
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
	private function getSpieler(int $intTurnier, $strGeschlecht, $varGroesse, bool $blnBilder = true): array
	{
		$arrSpieler = array();
		$strStandardbild = $blnBilder ? Helfer::standardbild($strGeschlecht) : null;

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
				'bild'       => $blnBilder ? Helfer::bild($bild_id, $varGroesse, 'tt'.$objSpieler->id) : '',
			);
		}

		return $arrSpieler;
	}

	/**
	 * Stellt die Angaben eines Spielers für den Kopf des PGN-Viewers zusammen.
	 *
	 * @param array<int, array<string, string>> $arrSpieler      Die eingelesenen Spieler
	 * @param mixed                             $varId           Kennung des Spielers am Brett
	 * @param string                            $strMannschaft   Name seiner Mannschaft
	 *
	 * @return array<string, mixed> name, titel, elo, mannschaft; leere Werte, wenn
	 *                              das Brett unbesetzt ist
	 */
	private function getSpielerDaten(array $arrSpieler, $varId, string $strMannschaft): array
	{
		return array
		(
			'name'       => $arrSpieler[$varId]['name'] ?? '',
			'titel'      => $arrSpieler[$varId]['fide_title'] ?? '',
			'elo'        => $arrSpieler[$varId]['fide_elo'] ?? 0,
			'mannschaft' => $strMannschaft,
		);
	}

	/**
	 * Meldet Lader und Schalter-Stylesheet des PGN-Viewers für die Seite an.
	 *
	 * Wird nur aufgerufen, wenn wirklich eine Partie mit PGN ausgegeben wird —
	 * Seiten ohne Partien laden also gar nichts vom Viewer. Die festen Schlüssel
	 * verhindern, dass mehrere Rundenübersichten auf einer Seite die Dateien
	 * doppelt einbinden.
	 *
	 * Der Lader kommt über TL_BODY ans Ende der Seite. Das Stylesheet des
	 * Schalters steht dagegen im Kopf: Es ist winzig, und ohne es erschiene der
	 * Schalter beim Einblenden kurz in der Browser-Grundform.
	 */
	private function bindeViewerEin(): void
	{
		$strVerzeichnis = __DIR__.'/../Resources/public/pgnviewer';

		// Ohne Zusatz wie "|static" — dieselbe Schreibweise wie in der
		// config.php, die in Contao 4.13 und 5 gleich ausgewertet wird
		$GLOBALS['TL_CSS']['tt-pgn-schalter'] = Partiedaten::PFAD.'tt-pgn-schalter.css';
		$GLOBALS['TL_BODY']['tt-pgn-loader'] = Partiedaten::laderSkript($strVerzeichnis);
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
	 * Ob ein Wettkampf überhaupt gespielt wurde, entscheidet
	 * Rangliste::istGewertet(): In der Datenbank steht bei ungespielten
	 * Wettkämpfen meist 0.0 : 0.0, und das wurde hier bis 0.5.0 als Ergebnis
	 * ausgegeben.
	 *
	 * @param mixed $erg1       Brettpunkte der ersten Mannschaft
	 * @param mixed $erg2       Brettpunkte der zweiten Mannschaft
	 * @param int   $intBretter Anzahl der Bretter dieses Wettkampfes mit Ergebnis
	 *
	 * @return string Das Ergebnis als '4,5 : 3,5', oder '-', solange der
	 *                Wettkampf nicht gespielt ist
	 */
	public static function getErgebnis($erg1, $erg2, int $intBretter = 0): string
	{
		if (!Rangliste::istGewertet($erg1, $erg2, $intBretter))
		{
			return '-';
		}

		return Wertung::ausZahl($erg1).' : '.Wertung::ausZahl($erg2);
	}
}
