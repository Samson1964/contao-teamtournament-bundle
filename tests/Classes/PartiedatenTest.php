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
use Schachbulle\ContaoTeamtournamentBundle\Classes\Partiedaten;

/**
 * Prüft die Aufbereitung einer Brettpaarung für den PGN-Viewer.
 */
class PartiedatenTest extends TestCase
{
	/**
	 * Kampflose Ergebnisse bekommen keinen Viewer.
	 */
	public function testKampflos(): void
	{
		$this->assertTrue(Partiedaten::istKampflos('+:-'));
		$this->assertTrue(Partiedaten::istKampflos('-:+'));
		$this->assertTrue(Partiedaten::istKampflos(' -:- '));
		$this->assertFalse(Partiedaten::istKampflos('1:0'));
		$this->assertFalse(Partiedaten::istKampflos('½:½'));
		$this->assertFalse(Partiedaten::istKampflos(''));
		$this->assertFalse(Partiedaten::istKampflos(null));
	}

	/**
	 * Entitäten aus dem alten Eingabefilter werden aufgelöst, Umbrüche vereinheitlicht.
	 */
	public function testPgnAusDatenbank(): void
	{
		$this->assertSame(
			"[White \"Müller & Co\"]\n\n1. e4 {<gut>} *",
			Partiedaten::pgnAusDatenbank("  [White &quot;Müller &amp; Co&quot;]\r\n\r\n1. e4 {&lt;gut&gt;} *\r")
		);
		$this->assertSame('', Partiedaten::pgnAusDatenbank(null));
		$this->assertSame('', Partiedaten::pgnAusDatenbank(" \n "));
	}

	/**
	 * Das Ergebnis wird für Weiß–Schwarz gedreht, wenn Spieler 1 Schwarz hatte.
	 *
	 * @dataProvider ergebnisProvider
	 */
	public function testErgebnisWeissSchwarz(string $strErgebnis, string $strFarbe, string $strErwartet): void
	{
		$this->assertSame($strErwartet, Partiedaten::ergebnisWeissSchwarz($strErgebnis, $strFarbe));
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public function ergebnisProvider(): array
	{
		return array
		(
			'Spieler 1 Weiß gewinnt'   => array('1:0', 'w', '1–0'),
			'Spieler 1 Schwarz gewinnt' => array('1:0', 's', '0–1'),
			'Spieler 1 Schwarz verliert' => array('0:1', 's', '1–0'),
			'Remis'                    => array('½:½', 's', '½–½'),
			'Farbe unbekannt'          => array('0:1', '', '0–1'),
			'kein Ergebnis'            => array('', 'w', ''),
		);
	}

	/**
	 * Die Kopfdaten ordnen die Spieler nach Farbe und entschlüsseln die Namen.
	 */
	public function testKopf(): void
	{
		$arrSp1 = array('name' => 'Anna M&amp;ller', 'titel' => 'WFM', 'elo' => '1890', 'mannschaft' => 'Deutschland');
		$arrSp2 = array('name' => 'Bo', 'titel' => '', 'elo' => 0, 'mannschaft' => 'Armenien');

		$arrKopf = Partiedaten::kopf($arrSp1, $arrSp2, 's', '1:0', '3', '2');

		$this->assertSame('Bo', $arrKopf['weiss']['name']);
		$this->assertNull($arrKopf['weiss']['elo']);
		$this->assertSame('Anna M&ller', $arrKopf['schwarz']['name']);
		$this->assertSame(1890, $arrKopf['schwarz']['elo']);
		$this->assertSame('Deutschland', $arrKopf['schwarz']['mannschaft']);
		$this->assertTrue($arrKopf['farbeBekannt']);
		$this->assertSame('0–1', $arrKopf['ergebnis']);
		$this->assertSame(3, $arrKopf['runde']);
		$this->assertSame(2, $arrKopf['brett']);

		$arrOhneFarbe = Partiedaten::kopf($arrSp1, $arrSp2, '', '1:0', 0, 0);
		$this->assertFalse($arrOhneFarbe['farbeBekannt']);
		$this->assertSame('Anna M&ller', $arrOhneFarbe['weiss']['name'], 'Ohne Farbe bleibt Spieler 1 vorn');
		$this->assertNull($arrOhneFarbe['runde']);
	}

	/**
	 * Das JSON kann den Script-Block nicht beenden, keine Inserttags und keine
	 * Basis-Entitäten auslösen — und liefert dabei denselben Inhalt zurück.
	 */
	public function testJsonIstSicherUndVerlustfrei(): void
	{
		$strPgn = "[Event \"A\"]\n1. e4 {</script><b>fett</b> {{link::1}} Weiß [-] [lt] [nbsp]} e5 *";
		$strJson = Partiedaten::json(array('sprache' => 'de', 'pgn' => $strPgn, 'kopf' => array('weiss' => array('name' => "O'Neil"))));

		$this->assertStringNotContainsString('</script', $strJson);
		$this->assertStringNotContainsString('<', $strJson);
		$this->assertStringNotContainsString('{{', $strJson);
		$this->assertStringNotContainsString('[-]', $strJson);
		$this->assertStringNotContainsString('[lt]', $strJson);
		$this->assertStringNotContainsString('[nbsp]', $strJson);
		$this->assertStringContainsString('Weiß', $strJson, 'Umlaute bleiben lesbar');

		$arrZurueck = json_decode($strJson, true);
		$this->assertSame($strPgn, $arrZurueck['pgn']);
		$this->assertSame("O'Neil", $arrZurueck['kopf']['weiss']['name']);
	}

	/**
	 * Ungültiges UTF-8 bricht die Ausgabe nicht ab.
	 */
	public function testJsonMitUngueltigemUtf8(): void
	{
		$strJson = Partiedaten::json(array('pgn' => "1. e4 {M\xFCller} *"));

		$this->assertNotSame('{}', $strJson);
		$this->assertStringContainsString("\u{FFFD}", json_decode($strJson, true)['pgn']);
	}

	/**
	 * Schalter und Zeile gehören über die IDs zusammen und maskieren Namen.
	 */
	public function testSchalterUndZeile(): void
	{
		$strSchalter = Partiedaten::schalter('5-17', 'de', 'Anna "A" <M>', 'Bo', 3);

		$this->assertStringContainsString('aria-controls="tt-pgn-5-17"', $strSchalter);
		$this->assertStringContainsString('data-tt-pgn-huelle="tt-pgn-zeile-5-17"', $strSchalter);
		$this->assertStringContainsString(' hidden ', $strSchalter);
		$this->assertStringContainsString('aria-expanded="false"', $strSchalter);
		$this->assertStringContainsString('>Partie nachspielen</button>', $strSchalter);
		$this->assertStringContainsString('aria-label="Partie nachspielen: Brett 3, Anna &quot;A&quot; &lt;M&gt; gegen Bo"', $strSchalter);
		$this->assertStringNotContainsString('<M>', $strSchalter);

		$this->assertStringContainsString('>Replay game</button>', Partiedaten::schalter('1', 'en', 'A', 'B', 1));

		$strZeile = Partiedaten::zeile('5-17', 'de', array('pgn' => '1. e4 *'));

		$this->assertStringContainsString('id="tt-pgn-zeile-5-17" hidden', $strZeile);
		$this->assertStringContainsString('id="tt-pgn-5-17"', $strZeile);
		$this->assertStringContainsString('colspan="6"', $strZeile);
		$this->assertStringContainsString('<script type="application/json" class="tt-pgn-daten">{"pgn":"1. e4 *"}</script>', $strZeile);
	}

	/**
	 * Die Versionskennung ändert sich mit den gebauten Dateien.
	 */
	public function testVersion(): void
	{
		$strVerzeichnis = sys_get_temp_dir().'/tt-pgn-version-'.bin2hex(random_bytes(4));
		mkdir($strVerzeichnis);
		file_put_contents($strVerzeichnis.'/tt-pgnviewer.js', 'a');

		$strVorher = Partiedaten::version($strVerzeichnis);
		file_put_contents($strVerzeichnis.'/tt-pgnviewer.js', 'ab');
		$strNachher = Partiedaten::version($strVerzeichnis);

		unlink($strVerzeichnis.'/tt-pgnviewer.js');
		rmdir($strVerzeichnis);

		$this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $strVorher);
		$this->assertNotSame($strVorher, $strNachher);

		$this->assertStringContainsString(
			'<script type="module" src="bundles/contaoteamtournament/pgnviewer/tt-pgn-loader.js?v=',
			Partiedaten::laderSkript($strVerzeichnis)
		);
	}
}
