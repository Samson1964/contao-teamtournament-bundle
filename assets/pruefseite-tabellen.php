<?php

declare(strict_types=1);

/*
 * Prüfseite für Tabelle und Kreuztabelle — ohne Contao.
 *
 * Aufruf aus dem Wurzelverzeichnis des Bundles:
 *
 *   php -S 127.0.0.1:8765 -t .
 *   http://127.0.0.1:8765/assets/pruefseite-tabellen.php
 *
 * Gerechnet und gesetzt wird über Classes\Rangliste, also genau wie in den
 * Inhaltselementen; nur die Datensätze kommen hier aus dem Quelltext statt aus
 * der Datenbank. Geprüft werden damit: Reihenfolge, geteilte Ränge, halbe
 * Brettpunkte, nicht gespielte Wettkämpfe, doppelte Begegnungen
 * (Hin- und Rückrunde) und eine Mannschaft ohne jeden Kampf.
 */

require __DIR__.'/../src/Classes/Wertung.php';
require __DIR__.'/../src/Classes/Rangliste.php';

use Schachbulle\ContaoTeamtournamentBundle\Classes\Rangliste;

$en = 'en' === ($_GET['sprache'] ?? '');
$viele = isset($_GET['viele']);
$runde = isset($_GET['runde']) ? (int) $_GET['runde'] : null;

$mannschaften = array
(
	1 => array('name' => 'Armenien', 'country' => 'am', 'flag' => null),
	2 => array('name' => 'Deutschland', 'country' => 'de', 'flag' => null),
	3 => array('name' => 'Costa Rica', 'country' => 'cr', 'flag' => null),
	4 => array('name' => 'Bulgarien', 'country' => 'bg', 'flag' => null),
	5 => array('name' => 'Österreich & Co', 'country' => 'at', 'flag' => null),
	6 => array('name' => 'Noch ohne Kampf', 'country' => '', 'flag' => null),
);

// Breite Tabelle zum Prüfen des Scrollrahmens
if ($viele)
{
	for ($i = 7; $i <= 41; $i++)
	{
		$mannschaften[$i] = array('name' => 'Mannschaft '.$i, 'country' => '', 'flag' => null);
	}
}

// 'bretter' ist die Anzahl der Bretter mit Ergebnis — daran hängt, ob ein
// Wettkampf als gespielt gilt
$wettkaempfe = array
(
	array('round' => 1, 'team1' => 2, 'team2' => 1, 'resultTeam1' => '3',   'resultTeam2' => '1',   'bretter' => 4),
	array('round' => 1, 'team1' => 3, 'team2' => 4, 'resultTeam1' => '2',   'resultTeam2' => '2',   'bretter' => 4),
	array('round' => 2, 'team1' => 1, 'team2' => 3, 'resultTeam1' => '2.5', 'resultTeam2' => '1.5', 'bretter' => 4),
	array('round' => 2, 'team1' => 2, 'team2' => 4, 'resultTeam1' => '0',   'resultTeam2' => '4',   'bretter' => 4),
	array('round' => 3, 'team1' => 5, 'team2' => 1, 'resultTeam1' => '2',   'resultTeam2' => '2',   'bretter' => 4),
	// Rückrunde gegen dieselbe Mannschaft
	array('round' => 3, 'team1' => 1, 'team2' => 2, 'resultTeam1' => '2,5', 'resultTeam2' => '1,5', 'bretter' => 4),
	// Echtes 0:0 — an allen Brettern beidseitig kampflos
	array('round' => 3, 'team1' => 3, 'team2' => 6, 'resultTeam1' => '0.0', 'resultTeam2' => '0.0', 'bretter' => 4),
	// Noch nicht gespielt, Feld leer
	array('round' => 4, 'team1' => 5, 'team2' => 4, 'resultTeam1' => '',    'resultTeam2' => '',    'bretter' => 0),
	// Noch nicht gespielt, aber 0.0 in der Datenbank — der gemeldete Fehler
	array('round' => 4, 'team1' => 1, 'team2' => 4, 'resultTeam1' => '0.0', 'resultTeam2' => '0.0', 'bretter' => 0),
);

$tabelle = Rangliste::ausWettkaempfen($mannschaften, $wettkaempfe, $runde);
$zellen = Rangliste::zellen($wettkaempfe, $runde);

// Flaggen wie im Inhaltselement, hier aus dem Flaggen-Bundle direkt
$flaggen = array();

foreach ($tabelle as $zeile)
{
	$datei = __DIR__.'/../../contao-flaggen-bundle/src/Resources/public/flags/'.$zeile['country'].'.svg';

	// Als Daten-URI eingebettet: Die Prüfseite liegt im Bundle, das
	// Flaggen-Bundle daneben — über den Dokumentstamm des Testservers wäre es
	// nicht erreichbar. Im Frontend steht dort bundles/contaoflaggen/flags/.
	if ($zeile['country'] && is_file($datei))
	{
		$flaggen[$zeile['id']] = '<img src="data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($datei)).'" width="20" alt="" title="'.$zeile['country'].'">';
	}
}
?>
<!DOCTYPE html>
<html lang="<?= $en ? 'en' : 'de' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prüfseite Tabellen</title>
<link rel="stylesheet" href="/src/Resources/public/default.css">
<style>body { font-family: system-ui, sans-serif; margin: 1rem; max-width: 70rem; }</style>
</head>
<body>
<h1>Prüfseite Tabelle und Kreuztabelle</h1>
<p>
	<a href="?">Deutsch</a> ·
	<a href="?sprache=en">English</a> ·
	<a href="?<?= $viele ? '' : 'viele=1' ?>"><?= $viele ? '6 Mannschaften' : '41 Mannschaften' ?></a>
</p>
<p>
	Stand nach Runde:
	<a href="?runde=1">1</a> ·
	<a href="?runde=2">2</a> ·
	<a href="?runde=3">3</a> ·
	<a href="?runde=4">4</a> ·
	<a href="?">alle</a>
	<?= null === $runde ? '(derzeit alle Runden)' : '(derzeit nach Runde '.$runde.')' ?>
</p>

<h2>Tabelle</h2>
<div class="ce_tt-standings block">
<?= Rangliste::markupTabelle($tabelle, $en, $flaggen) ?>
</div>

<h2>Kreuztabelle</h2>
<div class="ce_tt-crosstable block">
<?= Rangliste::markupKreuztabelle(array('tabelle' => $tabelle, 'zellen' => $zellen), $en, $flaggen) ?>
</div>
</body>
</html>
