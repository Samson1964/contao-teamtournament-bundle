<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   bdf
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

if(TL_MODE == 'FE' && isset($GLOBALS['TL_CONFIG']['teamtournament_css']))
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaoteamtournament/default.css';
}

$GLOBALS['BE_MOD']['content']['teamtournament'] = array
(
	'tables'         => array('tl_teamtournament', 'tl_teamtournament_teams', 'tl_teamtournament_players', 'tl_teamtournament_matches', 'tl_teamtournament_games'),
	'icon'           => 'bundles/contaoteamtournament/images/icon.png',
);

/**
 * -------------------------------------------------------------------------
 * CONTENT ELEMENTS
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_CTE']['chess']['tt-lineup'] = 'Schachbulle\ContaoTeamtournamentBundle\ContentElements\LineUp';
$GLOBALS['TL_CTE']['chess']['tt-captain'] = 'Schachbulle\ContaoTeamtournamentBundle\ContentElements\Captain';
$GLOBALS['TL_CTE']['chess']['tt-round'] = 'Schachbulle\ContaoTeamtournamentBundle\ContentElements\Rounds';
