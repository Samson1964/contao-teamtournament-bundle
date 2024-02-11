<?php

/**
 * Paletten
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['tt-lineup'] = '{type_legend},type,headline;{tt-lineup_legend},teamtournament_lineup;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['tt-captain'] = '{type_legend},type,headline;{tt-captain_legend},teamtournament_lineup;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['tt-round'] = '{type_legend},type,headline;{tt-pairings_legend},teamtournament_turnier,teamtournament_runde;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

/**
 * Felder
 */

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
		'submitOnChange'       => true,
		'tl_class'             => 'long'
	),
	'sql'                      => "int(10) unsigned NOT NULL default '0'"
);

// Turniere anzeigen
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

// Runden anzeigen
$GLOBALS['TL_DCA']['tl_content']['fields']['teamtournament_runde'] = array
(
	'label'                  => &$GLOBALS['TL_LANG']['tl_content']['teamtournament_runde'],
	'exclude'                => true,
	'options'                => array(1, 2, 3, 4, 5, 6, 7, 8, 9),
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


/*****************************************
 * Klasse tl_content_teamtournament
 *****************************************/

class tl_content_teamtournament extends \Backend
{

	var $turniere = array();
	var $mannschaften = array();
	var $wettkaempfe = array();

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function getTeams(\DataContainer $dc)
	{
		// Turniere laden
		$array = array();
		$objTurnier = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament ORDER BY toDate DESC")
		                                      ->execute();
		while($objTurnier->next())
		{
			// Mannschaften laden
			$objMannschaft = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_teams WHERE pid=? ORDER BY 'name' ASC")
			                                         ->execute($objTurnier->id);
			while($objMannschaft->next())
			{
				$array[$objMannschaft->id] = $objTurnier->title.' | '.$objMannschaft->name;
			}
		}
		
		return $array;

	}

	/**
	 * Turniere in das Formular laden
	 */
	public function getTurniere(\DataContainer $dc)
	{
		$array = array();
		// Turniere laden
		$objTurnier = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament ORDER BY toDate DESC")
		                                      ->execute();
		while($objTurnier->next())
		{
			$array[$objTurnier->id] = $objTurnier->title;
		}
		return $array;
	}


}
