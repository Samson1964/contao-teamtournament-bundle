<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package News
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Table tl_teamtournament_matches
 */
$GLOBALS['TL_DCA']['tl_teamtournament_matches'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_teamtournament',
		'ctable'                      => 'tl_teamtournament_games',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'onload_callback'             => array
		(
			array('tl_teamtournament_matches', 'LoadTeams')
		),  
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'disableGrouping'         => true,
			'headerFields'            => array('title', 'fromDate', 'toDate', 'place', 'country'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_teamtournament_matches', 'listMatches'),
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['edit'],
				'href'                => 'table=tl_teamtournament_games',
				'icon'                => 'edit.gif'
			),
			'editHeader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['editHeader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.gif'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{team_legend},team1,team2;{round_legend},round,board;{results_legend:hide},resultTeam1,resultTeam2;{publish_legend},published'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_teamtournament.id',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'team1' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['team1'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_matches', 'getTeams'),
			'eval'                    => array
			(
				'mandatory'           => true,
				'chosen'              => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'team2' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['team2'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_matches', 'getTeams'),
			'eval'                    => array
			(
				'mandatory'           => true,
				'chosen'              => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'round' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['round'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'rgxp'                => 'digit',
				'tl_class'            => 'w50 clr',
				'mandatory'           => false,
				'maxlength'           => 2
			),
			'sql'                     => "smallint(2) unsigned NOT NULL default '0'"
		),
		'board' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['board'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'rgxp'                => 'digit',
				'tl_class'            => 'w50',
				'mandatory'           => false,
				'maxlength'           => 2
			),
			'sql'                     => "smallint(2) unsigned NOT NULL default '0'"
		),
		'resultTeam1' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['resultTeam1'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => false,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 10,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'resultTeam2' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['resultTeam2'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => false,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 10,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['published'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'default'                 => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				'doNotCopy'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
	)
);


/**
 * Class tl_teamtournament_matches
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_teamtournament_matches extends Backend
{

	var $teams = array();

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function listMatches($arrRow)
	{
		$temp = '<div class="tl_content_left">';
		$temp .= $arrRow['round'].'.'.$arrRow['board'].' | '.$this->teams[$arrRow['team1']].' - '.$this->teams[$arrRow['team2']];
		if($arrRow['resultTeam1'])
		{
			$temp .= ' | '.$arrRow['resultTeam1'].':'.$arrRow['resultTeam2'];
		}
		return $temp.'</div>';
	}

	public function LoadTeams()
	{
		$objForms = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_teams WHERE pid=? ORDER BY name ASC")
		                                    ->execute(\Input::get('id'));
		while($objForms->next())
		{
			$this->teams[$objForms->id] = $objForms->name;
		}
	}

	public function getTeams(\DataContainer $dc)
	{
		if(!$this->teams)
		{
			$objForms = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_teams WHERE pid=? ORDER BY name ASC")
			                                    ->execute($dc->activeRecord->pid);
			while($objForms->next())
			{
				$this->teams[$objForms->id] = $objForms->name;
			}
		}
		return $this->teams;

	}

}
