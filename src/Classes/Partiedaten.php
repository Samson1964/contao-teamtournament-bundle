<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\Classes;

/**
 * Bereitet eine Brettpaarung für den PGN-Viewer auf.
 *
 * Die Klasse benutzt bewusst keine Contao-Klassen: Sie erzeugt nur
 * Zeichenketten und Felder aus übergebenen Werten. Dadurch lässt sie sich ohne
 * Contao-Installation prüfen, und ein anderes Modul kann denselben Schalter
 * und dieselbe Datenzeile erzeugen.
 */
final class Partiedaten
{
	/**
	 * Ergebnisse ohne gespielte Partie.
	 *
	 * Für sie gibt es keinen Viewer, auch wenn versehentlich PGN-Daten
	 * hinterlegt sind.
	 */
	public const KAMPFLOS = array('+:-', '-:+', '-:-');

	/**
	 * Öffentlicher Pfad der Viewer-Dateien, relativ zum Web-Verzeichnis.
	 */
	public const PFAD = 'bundles/contaoteamtournament/pgnviewer/';

	/**
	 * Prüft, ob ein Ergebnis kampflos zustande kam.
	 *
	 * @param string|null $strErgebnis Ergebnis aus tl_teamtournament_games
	 *
	 * @return bool true für +:-, -:+ und -:-
	 */
	public static function istKampflos(?string $strErgebnis): bool
	{
		return \in_array(trim((string) $strErgebnis), self::KAMPFLOS, true);
	}

	/**
	 * Holt den PGN-Text aus der Datenbank in lesbarer Form.
	 *
	 * Bis 0.4.2 speicherte die Backend-Maske das Feld durch Contaos
	 * Eingabefilter: Anführungszeichen landeten als &quot; in der Datenbank,
	 * aus [White "Müller"] wurde [White &quot;Müller&quot;]. Kein PGN-Parser
	 * liest das. Die Entitäten werden deshalb hier aufgelöst; für Datensätze,
	 * die mit der neuen Maske gespeichert wurden, ändert das nichts.
	 *
	 * Die Zeilenumbrüche werden vereinheitlicht, damit Windows- und
	 * Mac-Umbrüche den Parser nicht stören.
	 *
	 * @param mixed $varWert Inhalt des Feldes pgn, auch null
	 *
	 * @return string Der PGN-Text; eine leere Zeichenkette, wenn nichts außer
	 *                Leerraum darin steht
	 */
	public static function pgnAusDatenbank($varWert): string
	{
		$strText = html_entity_decode((string) $varWert, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$strText = str_replace(array("\r\n", "\r"), "\n", $strText);

		return trim($strText);
	}

	/**
	 * Schreibt ein Ergebnis in der Reihenfolge Weiß – Schwarz.
	 *
	 * In tl_teamtournament_games steht das Ergebnis aus Sicht des ersten
	 * Spielers, und die Farbe gilt ebenfalls für ihn. Hatte er Schwarz, muss
	 * das Ergebnis für den Kopf des Viewers gedreht werden: Dort steht Weiß
	 * immer zuerst.
	 *
	 * @param string $strErgebnis Ergebnis aus Sicht von Spieler 1, etwa '1:0'
	 * @param string $strFarbe    'w' oder 's' für die Farbe von Spieler 1;
	 *                            jeder andere Wert lässt die Reihenfolge stehen
	 *
	 * @return string Etwa '0–1'; eine leere Zeichenkette ohne Ergebnis
	 */
	public static function ergebnisWeissSchwarz(string $strErgebnis, string $strFarbe): string
	{
		$arrTeile = explode(':', trim($strErgebnis));

		if (2 !== \count($arrTeile) || '' === $arrTeile[0])
		{
			return '';
		}

		if ('s' === $strFarbe)
		{
			$arrTeile = array_reverse($arrTeile);
		}

		return $arrTeile[0].'–'.$arrTeile[1];
	}

	/**
	 * Baut die Kopfdaten des Viewers aus den Datensätzen.
	 *
	 * Die Kopfzeile kommt ausdrücklich nicht aus den PGN-Tags, weil die in
	 * den Turnierdateien nicht verlässlich gefüllt sind.
	 *
	 * Ist keine Farbe erfasst, bleibt Spieler 1 vorn und der Viewer
	 * beschriftet die Zeilen mit „Spieler 1/2" statt „Weiß/Schwarz".
	 *
	 * Alle Texte werden entschlüsselt: Contao legt Namen mit HTML-Entitäten ab
	 * (&amp;), der Viewer setzt sie aber als reinen Text ein und würde die
	 * Entitäten sonst sichtbar ausgeben.
	 *
	 * @param array<string, mixed> $arrSpieler1 name, titel, elo, mannschaft
	 * @param array<string, mixed> $arrSpieler2 name, titel, elo, mannschaft
	 * @param string               $strFarbe    Farbe von Spieler 1: 'w', 's' oder ''
	 * @param string               $strErgebnis Ergebnis aus Sicht von Spieler 1
	 * @param mixed                $varRunde    Nummer der Runde
	 * @param mixed                $varBrett    Nummer des Brettes
	 *
	 * @return array<string, mixed> Die Kopfdaten für das JSON des Viewers
	 */
	public static function kopf(array $arrSpieler1, array $arrSpieler2, string $strFarbe, string $strErgebnis, $varRunde, $varBrett): array
	{
		$blnBekannt = \in_array($strFarbe, array('w', 's'), true);
		$blnTauschen = 's' === $strFarbe;

		return array
		(
			'weiss'        => self::spieler($blnTauschen ? $arrSpieler2 : $arrSpieler1),
			'schwarz'      => self::spieler($blnTauschen ? $arrSpieler1 : $arrSpieler2),
			'farbeBekannt' => $blnBekannt,
			'ergebnis'     => self::ergebnisWeissSchwarz($strErgebnis, $strFarbe),
			'runde'        => (int) $varRunde ?: null,
			'brett'        => (int) $varBrett ?: null,
		);
	}

	/**
	 * Verpackt die Viewer-Daten als JSON für einen <script>-Block.
	 *
	 * Drei Stolpersteine sind abgesichert:
	 *
	 * - Ein PGN-Kommentar mit "</script>" würde den Block vorzeitig beenden.
	 *   JSON_HEX_TAG schreibt < und > als < und >.
	 * - Contao ersetzt nach dem Rendern alle {{…}} der Seite als Inserttags —
	 *   auch innerhalb eines Script-Blocks. Eine öffnende Doppelklammer wird
	 *   deshalb aufgebrochen ({{). Das ist nur innerhalb von
	 *   Zeichenketten möglich, und dort ist { gültiges JSON.
	 * - Ungültiges UTF-8 (etwa ein Latin-1-Umlaut aus einer alten PGN-Datei)
	 *   brächte json_encode zum Abbruch; es wird durch das Ersatzzeichen
	 *   ersetzt, statt die ganze Rundenübersicht zu verlieren.
	 *
	 * Außerdem werden Contaos „Basis-Entitäten" ([-], [lt], [nbsp] …)
	 * aufgebrochen. StringUtil::restoreBasicEntities() macht daraus sonst
	 * HTML-Entitäten, und ein PGN-Kommentar wie „Weiß steht besser [-]" käme
	 * verändert im Viewer an. Auch diese Folgen können nur innerhalb von
	 * Zeichenketten stehen.
	 *
	 * @param array<string, mixed> $arrDaten sprache, pgn, kopf
	 *
	 * @return string Das JSON
	 */
	public static function json(array $arrDaten): string
	{
		$strJson = json_encode(
			$arrDaten,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
		);

		if (false === $strJson)
		{
			return '{}';
		}

		// Der Backslash der JSON-Escapes wird über chr(92) gebaut und nicht als
		// Literal geschrieben: Werkzeuge, die Quelltext als JSON übertragen,
		// lösen eine Folge wie Backslash-u-007b sonst schon beim Speichern zur
		// Klammer auf — und die Maskierung wäre stillschweigend wirkungslos.
		// Der Test testJsonIstSicherUndVerlustfrei() fängt genau das ab.
		$strEscape = \chr(92).'u';

		$strJson = str_replace('{{', '{'.$strEscape.'007b', $strJson);

		return (string) preg_replace_callback(
			'/\[(?=(?:-|lt|gt|nbsp|zwsp|lsqb|rsqb)\])/',
			static fn (): string => $strEscape.'005b',
			$strJson
		);
	}

	/**
	 * Erzeugt den Schalter „Partie nachspielen".
	 *
	 * Der Schalter steht mit hidden im Markup und wird erst vom Lader
	 * eingeblendet — ohne JavaScript sieht also niemand einen Knopf, der
	 * nichts tut.
	 *
	 * Der zugängliche Name beginnt mit dem sichtbaren Text und nennt dann das
	 * Brett und die Spieler. In einer Tabelle mit vielen gleichlautenden
	 * Knöpfen ist sonst nicht zu hören, welche Partie sich öffnet.
	 *
	 * @param string $strId      Eindeutiger Teil der IDs, etwa '12-345'
	 * @param string $strSprache 'de' oder 'en'
	 * @param string $strName1   Name von Spieler 1, bereits entschlüsselt
	 * @param string $strName2   Name von Spieler 2, bereits entschlüsselt
	 * @param mixed  $varBrett   Nummer des Brettes
	 *
	 * @return string Das Markup des Schalters
	 */
	public static function schalter(string $strId, string $strSprache, string $strName1, string $strName2, $varBrett): string
	{
		$blnEn = 'en' === $strSprache;
		$strText = $blnEn ? 'Replay game' : 'Partie nachspielen';
		$strLabel = sprintf(
			$blnEn ? '%s: board %s, %s against %s' : '%s: Brett %s, %s gegen %s',
			$strText,
			(string) $varBrett,
			$strName1,
			$strName2
		);

		return sprintf(
			'<button type="button" class="tt-pgn-schalter" hidden aria-expanded="false" aria-controls="tt-pgn-%1$s" data-tt-pgn-huelle="tt-pgn-zeile-%1$s" aria-label="%2$s">%3$s</button>',
			self::attribut($strId),
			self::attribut($strLabel),
			self::attribut($strText)
		);
	}

	/**
	 * Erzeugt die Tabellenzeile, in der der Viewer aufklappt.
	 *
	 * Die Zeile ist verborgen und enthält nur den Datenblock; Brett und
	 * Zugliste baut erst der Viewer beim Öffnen.
	 *
	 * @param string               $strId      Derselbe ID-Teil wie beim Schalter
	 * @param string               $strSprache 'de' oder 'en'
	 * @param array<string, mixed> $arrDaten   sprache, pgn, kopf
	 * @param int                  $intSpalten Spaltenzahl der Tabelle
	 *
	 * @return string Das Markup der Zeile
	 */
	public static function zeile(string $strId, string $strSprache, array $arrDaten, int $intSpalten = 6): string
	{
		$blnEn = 'en' === $strSprache;

		return sprintf(
			'<tr class="tt-pgn-zeile" id="tt-pgn-zeile-%1$s" hidden><td class="tt-pgn-zelle" colspan="%2$d"><div class="tt-pgn" id="tt-pgn-%1$s" data-tt-pgn-laedt="%3$s" data-tt-pgn-fehler="%4$s"><script type="application/json" class="tt-pgn-daten">%5$s</script></div></td></tr>',
			self::attribut($strId),
			$intSpalten,
			$blnEn ? 'Loading game …' : 'Partie wird geladen …',
			$blnEn ? 'The viewer could not be loaded.' : 'Der Viewer konnte nicht geladen werden.',
			self::json($arrDaten)
		);
	}

	/**
	 * Erzeugt das Script-Element des Laders.
	 *
	 * Als ES-Modul wird der Lader automatisch erst nach dem Aufbau der Seite
	 * ausgeführt. Die Versionsangabe hängt am Änderungsdatum der gebauten
	 * Dateien, damit Browser nach einem Update nicht den alten Viewer aus dem
	 * Zwischenspeicher nehmen; der Lader reicht sie an Viewer und Stylesheet
	 * weiter.
	 *
	 * @param string $strVerzeichnis Verzeichnis der gebauten Dateien im Dateisystem
	 *
	 * @return string Das Script-Element
	 */
	public static function laderSkript(string $strVerzeichnis): string
	{
		return sprintf('<script type="module" src="%stt-pgn-loader.js?v=%s"></script>', self::PFAD, self::version($strVerzeichnis));
	}

	/**
	 * Liefert eine kurze Versionskennung der gebauten Dateien.
	 *
	 * @param string $strVerzeichnis Verzeichnis der gebauten Dateien im Dateisystem
	 *
	 * @return string Acht Hex-Zeichen; ändert sich, sobald Lader oder Viewer neu
	 *                gebaut wurden
	 */
	public static function version(string $strVerzeichnis): string
	{
		$strStempel = '';

		foreach (array('tt-pgn-loader.js', 'tt-pgnviewer.js', 'tt-pgnviewer.css') as $strDatei)
		{
			$strPfad = rtrim($strVerzeichnis, '/\\').'/'.$strDatei;
			$strStempel .= is_file($strPfad) ? (string) filemtime($strPfad).filesize($strPfad) : '';
		}

		return substr(md5($strStempel), 0, 8);
	}

	/**
	 * Bereitet die Angaben eines Spielers für den Kopf auf.
	 *
	 * @param array<string, mixed> $arrSpieler name, titel, elo, mannschaft
	 *
	 * @return array<string, string|int|null> Entschlüsselte Texte, Elo als Zahl
	 *                                        oder null
	 */
	private static function spieler(array $arrSpieler): array
	{
		$text = static fn ($v): string => trim(html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		return array
		(
			'name'       => $text($arrSpieler['name'] ?? ''),
			'titel'      => $text($arrSpieler['titel'] ?? ''),
			'elo'        => (int) ($arrSpieler['elo'] ?? 0) ?: null,
			'mannschaft' => $text($arrSpieler['mannschaft'] ?? ''),
		);
	}

	/**
	 * Maskiert einen Wert für ein HTML-Attribut.
	 *
	 * @param string $strWert Der Rohwert
	 *
	 * @return string Der maskierte Wert
	 */
	private static function attribut(string $strWert): string
	{
		return htmlspecialchars($strWert, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}
}
