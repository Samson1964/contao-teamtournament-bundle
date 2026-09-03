<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\Config;
use Contao\System;
use Schachbulle\ContaoTeamtournamentBundle\ContentElements\Captain;
use Schachbulle\ContaoTeamtournamentBundle\ContentElements\LineUp;
use Schachbulle\ContaoTeamtournamentBundle\ContentElements\Rounds;

/*
 * Mitgeliefertes Stylesheet
 *
 * Die Abfrage lief früher über die Konstante TL_MODE, die es in Contao 5 nicht
 * mehr gibt. Dieselbe Frage beantwortet in beiden Fassungen der scope_matcher.
 * Die Prüfung auf eine vorhandene Anfrage ist nötig, weil diese Datei auch auf
 * der Kommandozeile gelesen wird, etwa bei contao:migrate — dort gibt es
 * keinen Request.
 *
 * Der Schalter kommt aus den Einstellungen (tl_settings.teamtournament_css).
 * Gelesen wird er über Config::get() statt direkt aus $GLOBALS['TL_CONFIG'],
 * weil der Feldwert vor dem ersten Speichern der Einstellungen gar nicht
 * existiert und der direkte Zugriff dann eine Warnung auslöst.
 */
$objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

if (null !== $objRequest && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($objRequest) && Config::get('teamtournament_css'))
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaoteamtournament/default.css';
}

/*
 * Backend-Modul
 */
$GLOBALS['BE_MOD']['content']['teamtournament'] = array
(
	'tables' => array('tl_teamtournament', 'tl_teamtournament_teams', 'tl_teamtournament_players', 'tl_teamtournament_matches', 'tl_teamtournament_games'),
	'icon'   => 'bundles/contaoteamtournament/images/icon.png',
);

/*
 * Inhaltselemente
 *
 * Klassische ContentElement-Klassen in $GLOBALS['TL_CTE'] wertet auch Contao 5
 * noch aus (ContentElement::findClass()); die Angabe als ::class statt als
 * Zeichenkette ist nur die ehrlichere Schreibweise.
 */
$GLOBALS['TL_CTE']['chess']['tt-lineup'] = LineUp::class;
$GLOBALS['TL_CTE']['chess']['tt-captain'] = Captain::class;
$GLOBALS['TL_CTE']['chess']['tt-round'] = Rounds::class;
