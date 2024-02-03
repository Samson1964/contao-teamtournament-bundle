<?php 

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 */

// Eingabemaske
$GLOBALS['TL_LANG']['tl_teamtournament']['title_legend'] = 'Titel und Art';
$GLOBALS['TL_LANG']['tl_teamtournament']['title'][0] = 'Name';
$GLOBALS['TL_LANG']['tl_teamtournament']['title'][1] = 'Name des Wettbewerbs';
$GLOBALS['TL_LANG']['tl_teamtournament']['type'][0] = 'Art';
$GLOBALS['TL_LANG']['tl_teamtournament']['type'][1] = 'Art des Wettbewerbs';
$GLOBALS['TL_LANG']['tl_teamtournament']['alias'][0] = 'Alias';
$GLOBALS['TL_LANG']['tl_teamtournament']['alias'][1] = 'Der Alias wird automatisch generiert, wenn das Feld leer ist';

$GLOBALS['TL_LANG']['tl_teamtournament']['place_legend'] = 'Ort';
$GLOBALS['TL_LANG']['tl_teamtournament']['place'][0] = 'Ort';
$GLOBALS['TL_LANG']['tl_teamtournament']['place'][1] = 'Ort in dem der Wettbewerb stattfand';
$GLOBALS['TL_LANG']['tl_teamtournament']['country'][0] = 'Land';
$GLOBALS['TL_LANG']['tl_teamtournament']['country'][1] = 'Land in dem der Wettbewerb stattfand';

$GLOBALS['TL_LANG']['tl_teamtournament']['date_legend'] = 'Zeitraum';
$GLOBALS['TL_LANG']['tl_teamtournament']['fromDate'][0] = 'Beginn';
$GLOBALS['TL_LANG']['tl_teamtournament']['fromDate'][1] = 'Beginndatum im Format JJJJ, MM.JJJJ oder TT.MM.JJJJ';
$GLOBALS['TL_LANG']['tl_teamtournament']['toDate'][0] = 'Ende';
$GLOBALS['TL_LANG']['tl_teamtournament']['toDate'][1] = 'Endedatum im Format JJJJ, MM.JJJJ oder TT.MM.JJJJ (kann leerbleiben bei einem eintägigen Wettbewerb)';

$GLOBALS['TL_LANG']['tl_teamtournament']['options_legend'] = 'Logo und Webadresse';
$GLOBALS['TL_LANG']['tl_teamtournament']['singleSRC'][0] = 'Datei';
$GLOBALS['TL_LANG']['tl_teamtournament']['singleSRC'][1] = 'Datei auswählen';
$GLOBALS['TL_LANG']['tl_teamtournament']['url'][0] = 'Homepage';
$GLOBALS['TL_LANG']['tl_teamtournament']['url'][1] = 'Internetadresse oder interne Seite';

$GLOBALS['TL_LANG']['tl_teamtournament']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_teamtournament']['complete'] = array('Komplett', 'Der Wettbewerb ist vollständig erfaßt und alle Daten der Kindtabellen sind komplett.');
$GLOBALS['TL_LANG']['tl_teamtournament']['published'] = array('Veröffentlicht', 'Wettbewerb veröffentlicht');

$GLOBALS['TL_LANG']['tl_teamtournament']['info_legend'] = 'Information';
$GLOBALS['TL_LANG']['tl_teamtournament']['info'] = array('Information', 'Anmerkungen zum Wettbewerb');
$GLOBALS['TL_LANG']['tl_teamtournament']['source'] = array('Quelle', 'Quelle der Daten dieses Wettbewerbs');

$GLOBALS['TL_LANG']['tl_teamtournament']['typen'] = array
(
	'WM' => 'Weltmeisterschaft',
	'EM' => 'Europameisterschaft',
	'OL' => 'Olympiade',
	'MC' => 'Mitropacup',
	'LT' => 'Länderturnier',
	'LK' => 'Länderkampf',
);

/**
 * Buttons für Operationen
 */

$GLOBALS['TL_LANG']['tl_teamtournament']['players'][0] = 'Spieler';
$GLOBALS['TL_LANG']['tl_teamtournament']['players'][1] = 'Spielerverwaltung für die auszuwertenden Nationalspieler';

$GLOBALS['TL_LANG']['tl_teamtournament']['new'][0] = 'Neuer Wettbewerb';
$GLOBALS['TL_LANG']['tl_teamtournament']['new'][1] = 'Neuen Wettbewerb anlegen';

$GLOBALS['TL_LANG']['tl_teamtournament']['edit'][0] = "Wettbewerb bearbeiten";
$GLOBALS['TL_LANG']['tl_teamtournament']['edit'][1] = "Wettbewerb %s bearbeiten";

$GLOBALS['TL_LANG']['tl_teamtournament']['copy'][0] = "Wettbewerb kopieren";
$GLOBALS['TL_LANG']['tl_teamtournament']['copy'][1] = "Wettbewerb %s kopieren";

$GLOBALS['TL_LANG']['tl_teamtournament']['delete'][0] = "Wettbewerb löschen";
$GLOBALS['TL_LANG']['tl_teamtournament']['delete'][1] = "Wettbewerb %s löschen";

$GLOBALS['TL_LANG']['tl_teamtournament']['toggle'][0] = "Wettbewerb aktivieren/deaktivieren";
$GLOBALS['TL_LANG']['tl_teamtournament']['toggle'][1] = "Wettbewerb %s aktivieren/deaktivieren";

$GLOBALS['TL_LANG']['tl_teamtournament']['show'][0] = "Wettbewerbdetails anzeigen";
$GLOBALS['TL_LANG']['tl_teamtournament']['show'][1] = "Details des Wettbewerbs %s anzeigen";
