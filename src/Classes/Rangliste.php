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
	 * @param int      $intTurnier Kennung des Turniers
	 * @param int|null $intRunde   Nur Wettkämpfe bis einschließlich dieser Runde
	 *                             zählen; null nimmt alle Runden
	 *
	 * @return array<int, array<string, mixed>> Die Mannschaften in der
	 *                                          Reihenfolge der Tabelle, siehe
	 *                                          ausWettkaempfen()
	 */
	public static function fuerTurnier(int $intTurnier, ?int $intRunde = null): array
	{
		return self::ausWettkaempfen(self::ladeMannschaften($intTurnier), self::ladeWettkaempfe($intTurnier), $intRunde);
	}

	/**
	 * Liest die Mannschaften eines Turniers.
	 *
	 * @param int $intTurnier Kennung des Turniers
	 *
	 * @return array<int, array<string, mixed>> Kennung => name, country, flag
	 */
	private static function ladeMannschaften(int $intTurnier): array
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

		return $arrMannschaften;
	}

	/**
	 * Liest die Wettkämpfe eines Turniers samt Anzahl der gespielten Bretter.
	 *
	 * Die Unterabfrage zählt die Bretter mit Ergebnis. Daran hängt, ob ein
	 * Wettkampf überhaupt gespielt wurde (siehe istGewertet()).
	 *
	 * @param int $intTurnier Kennung des Turniers
	 *
	 * @return array<int, array<string, mixed>> Liste mit team1, team2,
	 *                                          resultTeam1, resultTeam2, round
	 *                                          und bretter
	 */
	private static function ladeWettkaempfe(int $intTurnier): array
	{
		$arrWettkaempfe = array();

		$objWettkaempfe = Database::getInstance()
			->prepare("SELECT m.team1, m.team2, m.resultTeam1, m.resultTeam2, m.round, (SELECT COUNT(*) FROM tl_teamtournament_games g WHERE g.pid=m.id AND g.result!='') AS bretter FROM tl_teamtournament_matches m WHERE m.pid=? ORDER BY m.round ASC, m.board ASC, m.id ASC")
			->execute($intTurnier);

		while ($objWettkaempfe->next())
		{
			$arrWettkaempfe[] = array
			(
				'team1'       => (int) $objWettkaempfe->team1,
				'team2'       => (int) $objWettkaempfe->team2,
				'resultTeam1' => $objWettkaempfe->resultTeam1,
				'resultTeam2' => $objWettkaempfe->resultTeam2,
				'round'       => (int) $objWettkaempfe->round,
				'bretter'     => (int) $objWettkaempfe->bretter,
			);
		}

		return $arrWettkaempfe;
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
	 *                                                          resultTeam2, dazu
	 *                                                          round und bretter
	 * @param int|null                         $intRunde        Nur Wettkämpfe bis
	 *                                                          einschließlich dieser
	 *                                                          Runde zählen
	 *
	 * @return array<int, array<string, mixed>> Je Mannschaft: id, name, country,
	 *                                          flag, kaempfe, siege, remis,
	 *                                          niederlagen, mp, bp, bpGegen, rang
	 */
	public static function ausWettkaempfen(array $arrMannschaften, array $arrWettkaempfe, ?int $intRunde = null): array
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

			// „Stand nach Runde X": spätere Runden bleiben außen vor
			if (null !== $intRunde && (int) ($arrWettkampf['round'] ?? 0) > $intRunde)
			{
				continue;
			}

			if (!self::istGewertet($arrWettkampf['resultTeam1'] ?? '', $arrWettkampf['resultTeam2'] ?? '', (int) ($arrWettkampf['bretter'] ?? 0)))
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
	 * @param int      $intTurnier Kennung des Turniers
	 * @param int|null $intRunde   Nur Wettkämpfe bis einschließlich dieser Runde
	 *                             zeigen; null nimmt alle Runden
	 *
	 * @return array{tabelle: array<int, array<string, mixed>>, zellen: array<int, array<int, array<int, string>>>}
	 *         'tabelle' ist die Rangliste, 'zellen' enthält je Mannschaftspaar
	 *         die Ergebnisse aus Sicht der Zeilenmannschaft ('2,5 : 1,5')
	 */
	public static function kreuztabelle(int $intTurnier, ?int $intRunde = null): array
	{
		$arrWettkaempfe = self::ladeWettkaempfe($intTurnier);

		return array
		(
			'tabelle' => self::ausWettkaempfen(self::ladeMannschaften($intTurnier), $arrWettkaempfe, $intRunde),
			'zellen'  => self::zellen($arrWettkaempfe, $intRunde),
		);
	}

	/**
	 * Stellt die Zellen der Kreuztabelle zusammen.
	 *
	 * Jede Begegnung steht zweimal darin, einmal aus Sicht jeder der beiden
	 * Mannschaften.
	 *
	 * @param array<int, array<string, mixed>> $arrWettkaempfe Die Wettkämpfe
	 * @param int|null                         $intRunde       Grenze der Runde
	 *
	 * @return array<int, array<int, array<int, string>>> Zeile => Spalte => Ergebnisse
	 */
	public static function zellen(array $arrWettkaempfe, ?int $intRunde = null): array
	{
		$arrZellen = array();

		foreach ($arrWettkaempfe as $arrWettkampf)
		{
			if (null !== $intRunde && (int) ($arrWettkampf['round'] ?? 0) > $intRunde)
			{
				continue;
			}

			if (!self::istGewertet($arrWettkampf['resultTeam1'] ?? '', $arrWettkampf['resultTeam2'] ?? '', (int) ($arrWettkampf['bretter'] ?? 0)))
			{
				continue;
			}

			$intEins = (int) $arrWettkampf['team1'];
			$intZwei = (int) $arrWettkampf['team2'];

			$arrZellen[$intEins][$intZwei][] = Wertung::ausZahl($arrWettkampf['resultTeam1']).' : '.Wertung::ausZahl($arrWettkampf['resultTeam2']);
			$arrZellen[$intZwei][$intEins][] = Wertung::ausZahl($arrWettkampf['resultTeam2']).' : '.Wertung::ausZahl($arrWettkampf['resultTeam1']);
		}

		return $arrZellen;
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
	 * Prüft, ob ein Wettkampf gespielt und damit zu werten ist.
	 *
	 * Die Prüfung „das Feld ist nicht leer" genügt hier nicht. In der
	 * Datenbank steht bei ungespielten Wettkämpfen in aller Regel 0.0 : 0.0:
	 *
	 * - Wertung::schreibeWettkampf() trägt bei Turnieren mit errechneten
	 *   Mannschaftspunkten in jeden Wettkampf eine Summe ein, auch wenn noch
	 *   kein Brett ein Ergebnis hat.
	 * - Bis 0.4.2 machte die Eingabemaske aus einem leeren Punktefeld bei
	 *   jedem Speichern eine 0.0.
	 *
	 * Solche Wettkämpfe zählten als Unentschieden und verfälschten die ganze
	 * Tabelle. Maßgeblich ist deshalb: Hat mindestens ein Brett ein Ergebnis,
	 * gilt der Wettkampf als gespielt — damit zählt auch ein echtes 0:0, bei
	 * dem beide Mannschaften an allen Brettern kampflos verloren haben. Gibt
	 * es gar keine Bretter, weil nur Mannschaftsergebnisse gepflegt werden,
	 * entscheidet die Summe: Alles über null ist ein Ergebnis.
	 *
	 * Nicht unterscheidbar bleibt ein von Hand eingetragenes 0:0 ohne Bretter.
	 * Das ist hingenommen: Es käme praktisch nicht vor, ein versehentliches
	 * 0.0 aus den Altdaten dagegen in jedem zweiten Wettkampf.
	 *
	 * @param mixed $varEins    Brettpunkte der ersten Mannschaft
	 * @param mixed $varZwei    Brettpunkte der zweiten Mannschaft
	 * @param int   $intBretter Anzahl der Bretter dieses Wettkampfes mit Ergebnis
	 *
	 * @return bool true, wenn der Wettkampf in die Wertung gehört
	 */
	public static function istGewertet($varEins, $varZwei, int $intBretter = 0): bool
	{
		if ($intBretter > 0)
		{
			return true;
		}

		$fltSumme = self::inZahl($varEins) + self::inZahl($varZwei);

		return $fltSumme > 0;
	}

	/**
	 * Liest einen Punktwert aus der Datenbank als Zahl.
	 *
	 * In den Feldern steht der Punkt als Dezimaltrennzeichen, in Altbeständen
	 * kommt auch das Komma vor.
	 *
	 * @param mixed $varWert Der gespeicherte Wert
	 *
	 * @return float Die Punktzahl; 0.0 bei leeren und unlesbaren Werten
	 */
	private static function inZahl($varWert): float
	{
		return (float) str_replace(',', '.', trim((string) $varWert));
	}
}
