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
 * Rechnet Brettpunkte zu Mannschaftspunkten zusammen.
 *
 * Ein Mannschaftskampf wird über die Summe der Brettpunkte gewertet: Jede
 * gewonnene Partie zählt einen Punkt, jedes Remis einen halben. Ob das Ergebnis
 * gerechnet oder von Hand eingetragen wird, entscheiden zwei Schalter — die
 * Vorgabe am Turnier und die Ausnahme am einzelnen Wettkampf.
 */
class Wertung
{
	/**
	 * Zuordnung der Brettergebnisse zu Punkten.
	 *
	 * Der erste Wert gehört immer zum Spieler der ersten Mannschaft: Die
	 * Ergebnisse sind laut Sprachdatei „aus Sicht des 1. Spielers" notiert, und
	 * der erste Spieler einer Partie gehört zur ersten Mannschaft. Die Farbe
	 * spielt für die Punktzahl also keine Rolle.
	 *
	 * '+:-' und '-:+' sind kampflose Siege, '-:-' ist die beidseitig nicht
	 * angetretene Partie — sie bringt keiner Seite einen Punkt.
	 *
	 * @var array<string, array{0: float, 1: float}>
	 */
	public const ERGEBNISSE = array
	(
		'1:0' => array(1.0, 0.0),
		'0:1' => array(0.0, 1.0),
		'½:½' => array(0.5, 0.5),
		'+:-' => array(1.0, 0.0),
		'-:+' => array(0.0, 1.0),
		'-:-' => array(0.0, 0.0),
	);

	/**
	 * Liefert die Punkte eines einzelnen Brettergebnisses.
	 *
	 * @param string|null $strErgebnis Das Ergebnis, wie es in
	 *                                 tl_teamtournament_games.result steht
	 *
	 * @return array{0: float, 1: float}|null Punkte beider Spieler, oder null bei
	 *                                        einer noch nicht gespielten Partie
	 *                                        und bei einem unbekannten Ergebnis
	 */
	public static function punkte($strErgebnis): ?array
	{
		return self::ERGEBNISSE[(string) $strErgebnis] ?? null;
	}

	/**
	 * Summiert die Brettpunkte eines Wettkampfes.
	 *
	 * Nicht gespielte oder unverständlich eingetragene Bretter werden
	 * übergangen; ein Wettkampf, von dem erst die Hälfte erfasst ist, liefert
	 * also den Zwischenstand.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes
	 * @param int $intAusnahme  Kennung einer Partie, die nicht mitgezählt wird.
	 *                          Wird beim Löschen gebraucht: Der ondelete_callback
	 *                          läuft, solange der Datensatz noch in der Tabelle
	 *                          steht
	 *
	 * @return array{0: float, 1: float} Brettpunkte der ersten und der zweiten
	 *                                   Mannschaft
	 */
	public static function berechneWettkampf(int $intWettkampf, int $intAusnahme = 0): array
	{
		$fltTeam1 = 0.0;
		$fltTeam2 = 0.0;

		$objBretter = Database::getInstance()
			->prepare("SELECT id, result FROM tl_teamtournament_games WHERE pid=?")
			->execute($intWettkampf);

		while ($objBretter->next())
		{
			if ($intAusnahme && (int) $objBretter->id === $intAusnahme)
			{
				continue;
			}

			$arrPunkte = self::punkte($objBretter->result);

			if (null === $arrPunkte)
			{
				continue;
			}

			$fltTeam1 += $arrPunkte[0];
			$fltTeam2 += $arrPunkte[1];
		}

		return array($fltTeam1, $fltTeam2);
	}

	/**
	 * Entscheidet, ob das Ergebnis eines Wettkampfes gerechnet wird.
	 *
	 * Die Vorgabe steht am Turnier (`calculateResults`). Der einzelne Wettkampf
	 * kann sie mit `overrideResult` aufheben — für kampflose Wertungen und
	 * Entscheidungen am grünen Tisch, die sich aus den Brettern nicht ergeben.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes
	 *
	 * @return bool true, wenn das Ergebnis aus den Brettpunkten kommen soll;
	 *              false bei Handeingabe und bei einem unbekannten Wettkampf
	 */
	public static function wirdGerechnet(int $intWettkampf): bool
	{
		$objWettkampf = Database::getInstance()
			->prepare("SELECT m.overrideResult, t.calculateResults FROM tl_teamtournament_matches m LEFT JOIN tl_teamtournament t ON t.id=m.pid WHERE m.id=?")
			->execute($intWettkampf);

		if (!$objWettkampf->numRows)
		{
			return false;
		}

		return (bool) $objWettkampf->calculateResults && !$objWettkampf->overrideResult;
	}

	/**
	 * Schreibt das gerechnete Ergebnis in den Wettkampf.
	 *
	 * Tut nichts, wenn das Turnier die Berechnung nicht vorsieht oder der
	 * Wettkampf sie aufhebt — die von Hand eingetragenen Punkte bleiben dann
	 * unangetastet.
	 *
	 * Aufgerufen wird die Methode aus den onsubmit_callbacks von Wettkampf und
	 * Partie sowie aus der Ergebnismaske, also immer **nach** dem Schreiben des
	 * auslösenden Datensatzes. Im save_callback wäre sie falsch aufgehoben: Dort
	 * würde Contao 5 das eigene UPDATE hinterher wieder überschreiben.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes
	 * @param int $intAusnahme  Kennung einer Partie, die nicht mitgezählt wird
	 *
	 * @return bool true, wenn geschrieben wurde
	 */
	public static function schreibeWettkampf(int $intWettkampf, int $intAusnahme = 0): bool
	{
		if (!$intWettkampf || !self::wirdGerechnet($intWettkampf))
		{
			return false;
		}

		$arrPunkte = self::berechneWettkampf($intWettkampf, $intAusnahme);

		Database::getInstance()
			->prepare("UPDATE tl_teamtournament_matches SET resultTeam1=?, resultTeam2=?, tstamp=? WHERE id=?")
			->execute(self::inZahl($arrPunkte[0]), self::inZahl($arrPunkte[1]), time(), $intWettkampf);

		return true;
	}

	/**
	 * Ermittelt den Wettkampf, zu dem eine Partie gehört.
	 *
	 * @param int $intPartie Kennung der Partie aus tl_teamtournament_games
	 *
	 * @return int Kennung des Wettkampfes, oder 0, wenn es die Partie nicht gibt
	 */
	public static function getWettkampf(int $intPartie): int
	{
		if (!$intPartie)
		{
			return 0;
		}

		$objPartie = Database::getInstance()
			->prepare("SELECT pid FROM tl_teamtournament_games WHERE id=?")
			->execute($intPartie);

		return $objPartie->numRows ? (int) $objPartie->pid : 0;
	}

	/**
	 * Bringt einen Punktwert in die Anzeigeform mit Komma.
	 *
	 * @param mixed $varWert Der Wert aus der Datenbank, mit Punkt als
	 *                       Dezimaltrennzeichen
	 *
	 * @return string Der Wert mit einer Nachkommastelle und Komma, etwa '4,5';
	 *                eine leere Zeichenkette, wenn nichts erfasst ist
	 */
	public static function ausZahl($varWert): string
	{
		$strWert = trim((string) $varWert);

		if ('' === $strWert)
		{
			return '';
		}

		if (!is_numeric($strWert))
		{
			// Unverständliches unverändert zeigen, statt es stillschweigend
			// zu 0,0 zu machen — sonst sieht der Fehler wie ein Ergebnis aus
			return $strWert;
		}

		return str_replace('.', ',', sprintf('%01.1f', (float) $strWert));
	}

	/**
	 * Bringt einen eingegebenen Punktwert in die Datenbankform.
	 *
	 * Erlaubt sind Komma und Punkt als Dezimaltrennzeichen sowie das Zeichen ½
	 * als halber Punkt, wie es in Turniertabellen üblich ist ('3½' ist 3,5).
	 *
	 * @param mixed $varWert Die Eingabe aus der Maske
	 *
	 * @return string|null Der Wert mit Punkt als Dezimaltrennzeichen, eine leere
	 *                     Zeichenkette bei leerer Eingabe, oder null, wenn sich
	 *                     aus der Eingabe keine Zahl lesen lässt
	 */
	public static function inZahl($varWert): ?string
	{
		if (\is_float($varWert) || \is_int($varWert))
		{
			return sprintf('%01.1f', $varWert);
		}

		$strWert = trim((string) $varWert);

		if ('' === $strWert)
		{
			return '';
		}

		// '3½' und '½' als halbe Punkte lesen
		if ('½' === substr($strWert, -2))
		{
			$strGanz = substr($strWert, 0, -2);
			$strWert = ('' === $strGanz ? '0' : $strGanz).'.5';
		}

		$strWert = str_replace(',', '.', $strWert);

		if (!is_numeric($strWert))
		{
			return null;
		}

		return sprintf('%01.1f', (float) $strWert);
	}
}
