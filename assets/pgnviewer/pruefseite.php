<?php

declare(strict_types=1);

/*
 * Prüfseite für den PGN-Viewer — ohne Contao.
 *
 * Aufruf aus dem Wurzelverzeichnis des Bundles:
 *
 *   php -S 127.0.0.1:8765 -t .
 *   http://127.0.0.1:8765/assets/pgnviewer/pruefseite.php
 *
 * Schalter und Datenzeilen entstehen über Classes\Partiedaten, also exakt so
 * wie in der Rundenübersicht. Die Tabelle ahmt deren Aufbau nach. Abgedeckt
 * sind die Grenzfälle aus dem Auftrag: leeres PGN, kampflose Partie, mehrere
 * Partien, Kommentare, Varianten, NAGs, Umlaute, verschiedene Umbrüche,
 * abgeschnittenes und unzulässiges PGN, fehlende Farbangabe und Altdaten mit
 * HTML-Entitäten.
 */

require __DIR__.'/../../src/Classes/Partiedaten.php';

use Schachbulle\ContaoTeamtournamentBundle\Classes\Partiedaten;

$sprache = 'en' === ($_GET['sprache'] ?? '') ? 'en' : 'de';

$pgnKommentiert = <<<'PGN'
[Event "Inklusions-Schacholympiade"]
[White "Falscher Tag-Name"]

{Eine lebhafte Partie mit Überraschungen.} 1. e4 {Königsbauer} e5 2. Nf3 (2. f4 {Königsgambit} exf4 (2... d5 $5 {Falkbeer}) 3. Nf3) 2... Nc6 3. Bc4!? Nf6?? 4. Ng5 d5 5. exd5 Nxd5?! $6 6. Nxf7! $1 Kxf7 7. Qf3+ Ke6 8. Nc3 {Die Figuren kommen mit Tempo ins Spiel. </script><b>kein HTML</b> [-] [lt]} Ncb4 9. O-O c6 10. d4 Kd7 11. Qf7+ Be7 12. Bxd5 cxd5 13. dxe5 Kc6 14. Bg5 1-0
PGN;

$pgnZweiPartien = "[Event \"Partie\"]\r\n\r\n1. d4 d5 2. c4 e6 3. Nc3 Nf6 1/2-1/2\r\n\r\n[Event \"Stichkampf\"]\r\n\r\n1. e4 c5 2. Nf3 d6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 a6 1-0\r\n";
$pgnAbgeschnitten = "[Event \"Kaputt\"]\n\n1. e4 e5 2. Nf3 {Hier bricht die Datei ab";
$pgnUnzulaessig = "[Event \"Unmöglich\"]\n\n1. e4 e5 2. Ke3 Nc6 *";
// Altdaten, wie sie der Eingabefilter bis 0.4.2 gespeichert hat
$pgnAltdaten = '[White &quot;Weiß &amp; Co&quot;]'."\n\n".'1. d4 Nf6 2. c4 g6 3. Nc3 Bg7 4. e4 d6 {Königsindisch &lt;klassisch&gt;} *';

$spieler = array
(
	'a' => array('name' => 'Anna Müller', 'titel' => 'WFM', 'elo' => 1890, 'mannschaft' => 'Deutschland'),
	'b' => array('name' => 'Gor Petrosjan', 'titel' => '', 'elo' => 1754, 'mannschaft' => 'Armenien'),
	'c' => array('name' => 'Jörg Weiß', 'titel' => 'FM', 'elo' => 2210, 'mannschaft' => 'Deutschland'),
	'd' => array('name' => 'Aram O&#039;Neil', 'titel' => '', 'elo' => 0, 'mannschaft' => 'Armenien'),
);

// Brett, Spieler 1, Spieler 2, Farbe von Spieler 1, Ergebnis, PGN
$bretter = array
(
	array(1, 'a', 'b', 'w', '1:0', $pgnKommentiert),
	array(2, 'b', 'c', 's', '½:½', $pgnZweiPartien),
	array(3, 'c', 'd', 'w', '1:0', ''),
	array(4, 'd', 'a', 'w', '+:-', $pgnKommentiert),
	array(5, 'a', 'c', '', '0:1', $pgnAltdaten),
	array(6, 'b', 'd', 's', '1:0', $pgnAbgeschnitten),
	array(7, 'c', 'a', 'w', '0:1', $pgnUnzulaessig),
);

$zeilen = '';

foreach ($bretter as $i => [$brett, $s1, $s2, $farbe, $ergebnis, $rohPgn])
{
	$pgn = Partiedaten::pgnAusDatenbank($rohPgn);
	$mitViewer = '' !== $pgn && !Partiedaten::istKampflos($ergebnis);
	$id = '99-'.$brett;
	$weiss = 'w' === $farbe;
	$name = static fn (string $k): string => html_entity_decode($spieler[$k]['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

	$zeilen .= '<tr>';
	$zeilen .= '<td class="board">'.$brett.'</td>';
	$zeilen .= '<td class="player'.($weiss ? ' white' : ' black').'">'.$spieler[$s1]['titel'].' '.$spieler[$s1]['name'].'</td>';
	$zeilen .= '<td class="rating">'.$spieler[$s1]['elo'].'</td>';
	$zeilen .= '<td class="result">'.$ergebnis.($mitViewer ? Partiedaten::schalter($id, $sprache, $name($s1), $name($s2), $brett) : '').'</td>';
	$zeilen .= '<td class="player'.($weiss ? ' black' : ' white').'">'.$spieler[$s2]['titel'].' '.$spieler[$s2]['name'].'</td>';
	$zeilen .= '<td class="rating">'.$spieler[$s2]['elo'].'</td>';
	$zeilen .= '</tr>';

	if ($mitViewer)
	{
		$zeilen .= Partiedaten::zeile($id, $sprache, array
		(
			'sprache' => $sprache,
			'pgn'     => $pgn,
			'kopf'    => Partiedaten::kopf($spieler[$s1], $spieler[$s2], $farbe, $ergebnis, 3, $brett),
		));
	}
}

$version = Partiedaten::version(__DIR__.'/../../src/Resources/public/pgnviewer');
?>
<!DOCTYPE html>
<html lang="<?= $sprache ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prüfseite PGN-Viewer</title>
<link rel="stylesheet" href="/src/Resources/public/default.css">
<link rel="stylesheet" href="/src/Resources/public/pgnviewer/tt-pgn-schalter.css?v=<?= $version ?>">
<style>
	body { font-family: system-ui, sans-serif; margin: 1rem; }
</style>
</head>
<body>
<h1>Prüfseite PGN-Viewer (<?= $sprache ?>)</h1>
<p><a href="?sprache=de">Deutsch</a> · <a href="?sprache=en">English</a></p>
<div class="ce_tt-round block">
<table>
<tr class="head"><th class="board">Br.</th><th class="team">Deutschland</th><th class="rating">Elo</th><th class="result">3,5 : 3,5</th><th class="team">Armenien</th><th class="rating">Elo</th></tr>
<?= $zeilen ?>
</table>
</div>
<p>Text nach der Tabelle, damit das Scrollen der Seite sichtbar wird.</p>
<script type="module" src="/src/Resources/public/pgnviewer/tt-pgn-loader.js?v=<?= $version ?>"></script>
</body>
</html>
