<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\Database;

/*
 * Paletten der drei Inhaltselemente
 *
 * Das frühere Feld «guests» steht hier nicht mehr: Contao 5 kennt es in
 * tl_content gar nicht, und schon in Contao 4.13 war es zugunsten von
 * «protected» abgekündigt.
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['tt-lineup'] = '{type_legend},type,headline;{tt-lineup_legend},teamtournament_lineup;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['tt-captain'] = '{type_legend},type,headline;{tt-captain_legend},teamtournament_lineup;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['tt-round'] = '{type_legend},type,headline;{tt-pairings_legend},teamtournament_turnier,teamtournament_runde;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

/*
 * Felder
 */

// Mannschaft auswählen (Aufstellung und Mannschaftsführer)
$GLOBALS['TL_DCA']['tl_content']['fields']['teamtournament_lineup'] = array
(
	'label'                    => &$GLOBALS['TL_LANG']['tl_content']['teamtournament_lineup'],
	'exclude'                  => true,
	'options_callback'         => array('tl_content_teamtournament', 'getTeams'),
	'inputType'                => 'select',
	'eval'                     => array
	(
		'mandatory'            => false,
		'multiple'             => false,
		'chosen'               => true,
		'includeBlankOption'   => true,
		'tl_class'             => 'long'
	),
	'sql'                      => "int(10) unsigned NOT NULL default '0'"
);

// Turnier auswählen (Rundenübersicht)
$GLOBALS['TL_DCA']['tl_content']['fields']['teamtournament_turnier'] = array
(
	'label'                  => &$GLOBALS['TL_LANG']['tl_content']['teamtournament_turnier'],
	'exclude'                => true,
	'options_callback'       => array('tl_content_teamtournament', 'getTurniere'),
	'inputType'              => 'select',
	'eval'                   => array
	(
		'includeBlankOption' => true,
		'mandatory'          => false,
		'multiple'           => false,
		'chosen'             => true,
		'tl_class'           => 'long'
	),
	'sql'                    => "int(10) unsigned NOT NULL default '0'"
);

// Runde auswählen (Rundenübersicht)
$GLOBALS['TL_DCA']['tl_content']['fields']['teamtournament_runde'] = array
(
	'label'                  => &$GLOBALS['TL_LANG']['tl_content']['teamtournament_runde'],
	'exclude'                => true,
	'options'                => range(1, 19),
	'inputType'              => 'select',
	'eval'                   => array
	(
		'includeBlankOption' => true,
		'mandatory'          => false,
		'multiple'           => false,
		'chosen'             => true,
		'submitOnChange'     => false,
		'tl_class'           => 'w50'
	),
	'sql'                    => "int(3) unsigned NOT NULL default '0'"
);

/**
 * Rückrufe der Inhaltselemente in tl_content.
 *
 * Die Klasse erbt von Contao\Backend, weil Contao 5 keine globalen
 * Klassenaliasse mehr registriert; ein blankes "extends \Backend" bräche dort
 * mit einem Fatal error ab.
 */
class tl_content_teamtournament extends Backend
{
	/**
	 * Erzeugt die Rückrufklasse.
	 *
	 * Der Konstruktor sieht überflüssig aus, ist es aber nicht: In Contao 4.13
	 * ist Backend::__construct() als protected deklariert, erst Contao 5 macht
	 * ihn öffentlich. Ohne diese Überschreibung ließe sich die Klasse unter
	 * Contao 4.13 von außerhalb der Contao-Klassenhierarchie nicht erzeugen.
	 *
	 * Der frühere Aufruf $this->import('BackendUser', 'User') ist entfallen:
	 * Unter Contao 5 bricht System::import() mit einem unqualifizierten
	 * Klassennamen ab, und das Objekt wurde hier ohnehin nie benutzt.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Liefert alle Mannschaften aller Turniere als Auswahlliste.
	 *
	 * Die Einträge sind dem Turnier vorangestellt («Turnier | Mannschaft»),
	 * damit gleichnamige Mannschaften verschiedener Turniere unterscheidbar
	 * bleiben. Sortiert wird nach dem Enddatum des Turniers, das jüngste
	 * zuerst.
	 *
	 * Die Abfrage lief früher als Schleife: eine Abfrage je Turnier. Der
	 * Verbund erledigt dasselbe mit einer einzigen.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container, hier ungenutzt
	 *
	 * @return array<int, string> Mannschaftskennung => Beschriftung; leer, wenn
	 *                            noch keine Mannschaft angelegt ist
	 */
	public function getTeams(DataContainer $dc): array
	{
		$arrOptions = array();

		$objMannschaft = Database::getInstance()
			->prepare("SELECT m.id, m.name, t.title FROM tl_teamtournament_teams m LEFT JOIN tl_teamtournament t ON t.id=m.pid ORDER BY t.toDate DESC, m.name ASC")
			->execute();

		while ($objMannschaft->next())
		{
			$arrOptions[$objMannschaft->id] = $objMannschaft->title.' | '.$objMannschaft->name;
		}

		return $arrOptions;
	}

	/**
	 * Liefert alle Turniere als Auswahlliste.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container, hier ungenutzt
	 *
	 * @return array<int, string> Turnierkennung => Titel, das jüngste Turnier
	 *                            zuerst
	 */
	public function getTurniere(DataContainer $dc): array
	{
		$arrOptions = array();

		$objTurnier = Database::getInstance()
			->prepare("SELECT id, title FROM tl_teamtournament ORDER BY toDate DESC")
			->execute();

		while ($objTurnier->next())
		{
			$arrOptions[$objTurnier->id] = $objTurnier->title;
		}

		return $arrOptions;
	}
}
