<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\Classes;

use Contao\Database;

/**
 * Rechnet aus den Wettkämpfen eines Turniers die Tabelle aus.
 *
 * In der Datenbank stehen je Wettkampf nur die Brettpunkte beider
 * Mannschaften. Mannschaftspunkte, Siege, Unentschieden und der Rang ergeben
 * sich daraus und werden nirgends gespeichert — sie werden hier bei jedem
 * Aufruf neu gerechnet. Das ist gewollt: So kann eine nachträglich korrigierte
 * Brettpaarung die Tabelle nicht aus dem Tritt bringen.
 *
 * Die eigentliche Rechnung steht in ausWettkaempfen() und kommt ohne
 * Datenbank aus; nur fuerTurnier() liest die Datensätze ein.
 */
final class Rangliste
{
	/**
	 * Mannschaftspunkte für einen gewonnenen Wettkampf.
	 */
	public const SIEG = 2;

	/**
	 * Mannschaftspunkte für ein Unentschieden.
	 */
	public const REMIS = 1;

	/**
	 * Stellt die Tabelle eines Turniers zusammen.
	 *
	 * Gezählt werden nur Wettkämpfe mit eingetragenem Ergebnis. Ein noch nicht
	 * gespielter Wettkampf darf die Tabelle nicht als 0:0 verfälschen.
	 *
	 * @param int $intTurnier Kennung des Turniers
	 *
	 * @return array<int, array<string, mixed>> Die Mannschaften in der
	 *                                          Reihenfolge der Tabelle, siehe
	 *                                          ausWettkaempfen()
	 */
	public static function fuerTurnier(int $intTurnier): array
	{
		$arrMannschaften = array();

		$objMannschaften = Database::getInstance()
			->prepare("SELECT id, name, country, flag FROM tl_teamtournament_teams WHERE pid=? ORDER BY name ASC")
			->execute($intTurnier);

		while ($objMannschaften->next())
		{
			$arrMannschaften[(int) $objMannschaften->id] = array
			(
				'name'    => (string) $objMannschaften->name,
				'country' => (string) $objMannschaften->country,
				'flag'    => $objMannschaften->flag,
			);
		}

		$arrWettkaempfe = array();

		$objWettkaempfe = Database::getInstance()
			->prepare("SELECT team1, team2, resultTeam1, resultTeam2 FROM tl_teamtournament_matches WHERE pid=?")
			->execute($intTurnier);

		while ($objWettkaempfe->next())
		{
			$arrWettkaempfe[] = array
			(
				'team1'       => (int) $objWettkaempfe->team1,
				'team2'       => (int) $objWettkaempfe->team2,
				'resultTeam1' => $objWettkaempfe->resultTeam1,
				'resultTeam2' => $objWettkaempfe->resultTeam2,
			);
		}

		return self::ausWettkaempfen($arrMannschaften, $arrWettkaempfe);
	}

	/**
	 * Rechnet die Tabelle aus Mannschaften und Wettkämpfen.
	 *
	 * Sortiert wird nach Mannschaftspunkten, bei Gleichstand nach
	 * Brettpunkten, danach nach dem Namen. Mannschaften, die in beidem
	 * gleichauf liegen, teilen sich den Rang; der nächste Rang überspringt
	 * entsprechend viele Plätze (1., 1., 3.). Eine Feinwertung wie der direkte
	 * Vergleich oder Sonneborn-Berger ist bewusst nicht eingebaut — welche
	 * gelten soll, hängt an der Ausschreibung des Turniers.
	 *
	 * @param array<int, array<string, mixed>> $arrMannschaften Kennung => name,
	 *                                                          country, flag
	 * @param array<int, array<string, mixed>> $arrWettkaempfe  Liste mit team1,
	 *                                                          team2, resultTeam1,
	 *                                                          resultTeam2
	 *
	 * @return array<int, array<string, mixed>> Je Mannschaft: id, name, country,
	 *                                          flag, kaempfe, siege, remis,
	 *                                          niederlagen, mp, bp, bpGegen, rang
	 */
	public static function ausWettkaempfen(array $arrMannschaften, array $arrWettkaempfe): array
	{
		$arrTabelle = array();

		foreach ($arrMannschaften as $intId => $arrMannschaft)
		{
			$arrTabelle[$intId] = array
			(
				'id'          => $intId,
				'name'        => $arrMannschaft['name'] ?? '',
				'country'     => $arrMannschaft['country'] ?? '',
				'flag'        => $arrMannschaft['flag'] ?? null,
				'kaempfe'     => 0,
				'siege'       => 0,
				'remis'       => 0,
				'niederlagen' => 0,
				'mp'          => 0,
				'bp'          => 0.0,
				'bpGegen'     => 0.0,
				'rang'        => 0,
			);
		}

		foreach ($arrWettkaempfe as $arrWettkampf)
		{
			$intEins = (int) ($arrWettkampf['team1'] ?? 0);
			$intZwei = (int) ($arrWettkampf['team2'] ?? 0);

			// Ein Wettkampf gegen eine gelöschte Mannschaft bleibt unberücksichtigt
			if (!isset($arrTabelle[$intEins], $arrTabelle[$intZwei]))
			{
				continue;
			}

			if (!self::istGewertet($arrWettkampf['resultTeam1'] ?? '', $arrWettkampf['resultTeam2'] ?? ''))
			{
				continue;
			}

			$fltEins = (float) str_replace(',', '.', (string) ($arrWettkampf['resultTeam1'] ?? 0));
			$fltZwei = (float) str_replace(',', '.', (string) ($arrWettkampf['resultTeam2'] ?? 0));

			$arrTabelle[$intEins]['kaempfe']++;
			$arrTabelle[$intZwei]['kaempfe']++;
			$arrTabelle[$intEins]['bp'] += $fltEins;
			$arrTabelle[$intZwei]['bp'] += $fltZwei;
			$arrTabelle[$intEins]['bpGegen'] += $fltZwei;
			$arrTabelle[$intZwei]['bpGegen'] += $fltEins;

			if ($fltEins > $fltZwei)
			{
				$arrTabelle[$intEins]['siege']++;
				$arrTabelle[$intZwei]['niederlagen']++;
				$arrTabelle[$intEins]['mp'] += self::SIEG;
			}
			elseif ($fltEins < $fltZwei)
			{
				$arrTabelle[$intZwei]['siege']++;
				$arrTabelle[$intEins]['niederlagen']++;
				$arrTabelle[$intZwei]['mp'] += self::SIEG;
			}
			else
			{
				$arrTabelle[$intEins]['remis']++;
				$arrTabelle[$intZwei]['remis']++;
				$arrTabelle[$intEins]['mp'] += self::REMIS;
				$arrTabelle[$intZwei]['mp'] += self::REMIS;
			}
		}

		$arrTabelle = array_values($arrTabelle);

		usort(
			$arrTabelle,
			static function (array $a, array $b): int
			{
				// Punkte absteigend, bei Gleichstand der Name aufsteigend
				return (array($b['mp'], $b['bp']) <=> array($a['mp'], $a['bp'])) ?: strcasecmp($a['name'], $b['name']);
			}
		);

		// Rangnummern vergeben; gleiche Punkte teilen sich den Rang
		$intRang = 0;
		$intPlatz = 0;
		$arrVorher = null;

		foreach ($arrTabelle as $intIndex => $arrZeile)
		{
			++$intPlatz;

			if (null === $arrVorher || $arrVorher !== array($arrZeile['mp'], $arrZeile['bp']))
			{
				$intRang = $intPlatz;
				$arrVorher = array($arrZeile['mp'], $arrZeile['bp']);
			}

			$arrTabelle[$intIndex]['rang'] = $intRang;
		}

		return $arrTabelle;
	}

	/**
	 * Stellt die Kreuztabelle eines Turniers zusammen.
	 *
	 * Die Reihenfolge der Zeilen und Spalten ist die der Tabelle. Mehrfache
	 * Begegnungen zweier Mannschaften (Hin- und Rückrunde) stehen beide in
	 * derselben Zelle.
	 *
	 * @param int $intTurnier Kennung des Turniers
	 *
	 * @return array{tabelle: array<int, array<string, mixed>>, zellen: array<int, array<int, array<int, string>>>}
	 *         'tabelle' ist die Rangliste, 'zellen' enthält je Mannschaftspaar
	 *         die Ergebnisse aus Sicht der Zeilenmannschaft ('2,5 : 1,5')
	 */
	public static function kreuztabelle(int $intTurnier): array
	{
		$arrTabelle = self::fuerTurnier($intTurnier);
		$arrZellen = array();

		$objWettkaempfe = Database::getInstance()
			->prepare("SELECT team1, team2, resultTeam1, resultTeam2 FROM tl_teamtournament_matches WHERE pid=? ORDER BY round ASC, board ASC, id ASC")
			->execute($intTurnier);

		while ($objWettkaempfe->next())
		{
			$intEins = (int) $objWettkaempfe->team1;
			$intZwei = (int) $objWettkaempfe->team2;

			if (!self::istGewertet($objWettkaempfe->resultTeam1, $objWettkaempfe->resultTeam2))
			{
				continue;
			}

			$arrZellen[$intEins][$intZwei][] = Wertung::ausZahl($objWettkaempfe->resultTeam1).' : '.Wertung::ausZahl($objWettkaempfe->resultTeam2);
			$arrZellen[$intZwei][$intEins][] = Wertung::ausZahl($objWettkaempfe->resultTeam2).' : '.Wertung::ausZahl($objWettkaempfe->resultTeam1);
		}

		return array('tabelle' => $arrTabelle, 'zellen' => $arrZellen);
	}

	/**
	 * Baut das Markup der Tabelle.
	 *
	 * Steht hier und nicht im Inhaltselement, damit sich die Ausgabe ohne
	 * Contao prüfen lässt (assets/pruefseite-tabellen.php) und ein anderes
	 * Modul sie wiederverwenden kann.
	 *
	 * @param array<int, array<string, mixed>> $arrTabelle Ergebnis von fuerTurnier()
	 * @param bool                             $blnEn      Englische Spaltenköpfe
	 * @param array<int, string>               $arrFlaggen Mannschaftskennung =>
	 *                                                     fertiges Bild-Markup;
	 *                                                     fehlt eine Kennung, steht
	 *                                                     dort nur der Name
	 *
	 * @return string Die Tabelle als HTML
	 */
	public static function markupTabelle(array $arrTabelle, bool $blnEn = false, array $arrFlaggen = array()): string
	{
		$arrKopf = $blnEn
			? array('rang' => '#', 'team' => 'Team', 'kaempfe' => 'Games', 'siege' => 'W', 'remis' => 'D', 'niederlagen' => 'L', 'mp' => 'MP', 'bp' => 'BP')
			: array('rang' => 'Pl.', 'team' => 'Mannschaft', 'kaempfe' => 'Kämpfe', 'siege' => 'S', 'remis' => 'U', 'niederlagen' => 'N', 'mp' => 'MP', 'bp' => 'BP');

		$strHtml = '<table class="tt-tabelle"><thead><tr>';

		foreach ($arrKopf as $strKlasse => $strText)
		{
			$strHtml .= '<th class="'.$strKlasse.'" scope="col">'.htmlspecialchars($strText, ENT_QUOTES, 'UTF-8').'</th>';
		}

		$strHtml .= '</tr></thead><tbody>';

		foreach ($arrTabelle as $arrZeile)
		{
			$strHtml .= '<tr>';
			$strHtml .= '<td class="rang">'.$arrZeile['rang'].'</td>';
			$strHtml .= '<th class="team" scope="row">'.self::mannschaft($arrZeile, $arrFlaggen).'</th>';
			$strHtml .= '<td class="kaempfe">'.$arrZeile['kaempfe'].'</td>';
			$strHtml .= '<td class="siege">'.$arrZeile['siege'].'</td>';
			$strHtml .= '<td class="remis">'.$arrZeile['remis'].'</td>';
			$strHtml .= '<td class="niederlagen">'.$arrZeile['niederlagen'].'</td>';
			$strHtml .= '<td class="mp">'.$arrZeile['mp'].'</td>';
			$strHtml .= '<td class="bp">'.Wertung::ausZahl($arrZeile['bp']).'</td>';
			$strHtml .= '</tr>';
		}

		return $strHtml.'</tbody></table>';
	}

	/**
	 * Baut das Markup der Kreuztabelle.
	 *
	 * Die Spalten tragen nur die Platznummer; mit ausgeschriebenen Namen wäre
	 * die Tabelle bei vierzig Mannschaften nicht mehr lesbar. Der volle Name
	 * steht im title-Attribut des Spaltenkopfes.
	 *
	 * @param array{tabelle: array<int, array<string, mixed>>, zellen: array<int, array<int, array<int, string>>>} $arrDaten
	 *                            Ergebnis von kreuztabelle()
	 * @param bool                $blnEn      Englische Spaltenköpfe
	 * @param array<int, string>  $arrFlaggen Mannschaftskennung => Bild-Markup
	 *
	 * @return string Die Kreuztabelle als HTML
	 */
	public static function markupKreuztabelle(array $arrDaten, bool $blnEn = false, array $arrFlaggen = array()): string
	{
		$arrTabelle = $arrDaten['tabelle'] ?? array();
		$arrZellen = $arrDaten['zellen'] ?? array();

		$strHtml = '<div class="tt-kreuztabelle-rahmen"><table class="tt-kreuztabelle"><thead><tr>';
		$strHtml .= '<th class="nummer" scope="col">'.($blnEn ? '#' : 'Nr.').'</th>';
		$strHtml .= '<th class="team" scope="col">'.($blnEn ? 'Team' : 'Mannschaft').'</th>';

		foreach ($arrTabelle as $intIndex => $arrZeile)
		{
			$strHtml .= '<th class="gegner" scope="col" title="'.htmlspecialchars((string) $arrZeile['name'], ENT_QUOTES, 'UTF-8').'">'.($intIndex + 1).'</th>';
		}

		$strHtml .= '<th class="mp" scope="col">MP</th><th class="bp" scope="col">BP</th><th class="rang" scope="col">'.($blnEn ? 'Rank' : 'Pl.').'</th>';
		$strHtml .= '</tr></thead><tbody>';

		foreach ($arrTabelle as $intIndex => $arrZeile)
		{
			$strHtml .= '<tr>';
			$strHtml .= '<td class="nummer">'.($intIndex + 1).'</td>';
			$strHtml .= '<th class="team" scope="row">'.self::mannschaft($arrZeile, $arrFlaggen).'</th>';

			foreach ($arrTabelle as $arrSpalte)
			{
				if ($arrSpalte['id'] === $arrZeile['id'])
				{
					// Gegen sich selbst spielt niemand
					$strHtml .= '<td class="selbst">&mdash;</td>';
					continue;
				}

				$arrErgebnisse = $arrZellen[$arrZeile['id']][$arrSpalte['id']] ?? array();
				$strHtml .= '<td class="ergebnis">'.implode('<br>', array_map(static fn ($e) => htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8'), $arrErgebnisse)).'</td>';
			}

			$strHtml .= '<td class="mp">'.$arrZeile['mp'].'</td>';
			$strHtml .= '<td class="bp">'.Wertung::ausZahl($arrZeile['bp']).'</td>';
			$strHtml .= '<td class="rang">'.$arrZeile['rang'].'</td>';
			$strHtml .= '</tr>';
		}

		return $strHtml.'</tbody></table></div>';
	}

	/**
	 * Setzt die Zelle einer Mannschaft aus Flagge und Namen zusammen.
	 *
	 * @param array<string, mixed> $arrZeile   Eine Zeile der Tabelle
	 * @param array<int, string>   $arrFlaggen Mannschaftskennung => Bild-Markup
	 *
	 * @return string Der Inhalt der Zelle
	 */
	private static function mannschaft(array $arrZeile, array $arrFlaggen): string
	{
		$strFlagge = $arrFlaggen[$arrZeile['id']] ?? '';

		return trim($strFlagge.' '.htmlspecialchars((string) $arrZeile['name'], ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * Prüft, ob ein Wettkampf gewertet ist.
	 *
	 * Maßgeblich ist, ob überhaupt etwas eingetragen wurde — eine Null ist ein
	 * Ergebnis (0:4), eine leere Eingabe nicht. Dieselbe Unterscheidung trifft
	 * die Liste im Backend.
	 *
	 * @param mixed $varEins Brettpunkte der ersten Mannschaft
	 * @param mixed $varZwei Brettpunkte der zweiten Mannschaft
	 *
	 * @return bool true, wenn der Wettkampf in die Wertung gehört
	 */
	public static function istGewertet($varEins, $varZwei): bool
	{
		return '' !== trim((string) $varEins) || '' !== trim((string) $varZwei);
	}
}
