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
 * Table tl_teamtournament_games
 */
$GLOBALS['TL_DCA']['tl_teamtournament_games'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_teamtournament_matches',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
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
			'headerFields'            => array('team1', 'team2', 'round', 'board'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_teamtournament_games', 'listGames'),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.gif'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['toggle'],
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
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{player_legend},player1,player2;{results_legend},board,colors,result;{pgn_legend},pgn;{publish_legend},published'
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
			'foreignKey'              => 'tl_teamtournament_matches.id',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'player1' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['player1'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_games', 'getPlayerTeam1'),
			'eval'                    => array
			(
				'mandatory'           => false,
				'chosen'              => true,
				'includeBlankOption'  => true,
				'submitOnChange'      => false,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'player2' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['player2'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_games', 'getPlayerTeam2'),
			'eval'                    => array
			(
				'mandatory'           => false,
				'chosen'              => true,
				'includeBlankOption'  => true,
				'submitOnChange'      => false,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'board' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['board'],
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
		'colors' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['colors'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'default'                 => '',
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_games', 'getColors'),
			'eval'                    => array
			(
				'includeBlankOption'  => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'result' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['result'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => false,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_games', 'getResults'),
			'eval'                    => array
			(
				'includeBlankOption'  => true,
				'mandatory'           => false,
				'maxlength'           => 10,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'pgn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['pgn'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'                     => 'text NULL'
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['published'],
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
 * Class tl_teamtournament_games
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_teamtournament_games extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function listGames($arrRow)
	{
		$temp = '<div class="tl_content_left">';
		$temp .= '<b>'.$arrRow['board'].'</b> ';
		// Spielernamen
		$temp .= $this->getPlayerName($arrRow['player1']);
		$temp .= $arrRow['result'] ? ' '.$arrRow['result'].' ' : ' - ';
		$temp .= $this->getPlayerName($arrRow['player2']);
		return $temp.'</div>';
	}

	public function getPlayerName($id)
	{

		$objPlayer = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_players WHERE id=?")
		                                     ->execute($id);

		if($objPlayer->numRows == 1) return $objPlayer->prename.' '.$objPlayer->surname;
		else return $id;

	}

	public function getPlayerTeam1(\DataContainer $dc)
	{

		$arrForms = array();
		$objWettkampf = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_matches WHERE id=?")
		                                        ->execute($dc->activeRecord->pid);
		$objSpieler = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_players WHERE pid=?")
		                                      ->execute($objWettkampf->team1);

		while($objSpieler->next())
		{
			$arrForms[$objSpieler->id] = $objSpieler->prename.' '.$objSpieler->surname;
		}

		return $arrForms;
	}

	public function getPlayerTeam2(\DataContainer $dc)
	{

		$arrForms = array();
		$objWettkampf = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_matches WHERE id=?")
		                                        ->execute($dc->activeRecord->pid);
		$objSpieler = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_players WHERE pid=?")
		                                      ->execute($objWettkampf->team2);

		while($objSpieler->next())
		{
			$arrForms[$objSpieler->id] = $objSpieler->prename.' '.$objSpieler->surname;
		}

		return $arrForms;
	}

	public function getResults(\DataContainer $dc)
	{
		$arrForms = array
		(
			'1:0'  => '1:0',
			'0:1'  => '0:1',
			'½:½'  => '½:½',
			'+:-'  => '+:-',
			'-:+'  => '-:+',
			'-:-'  => '-:-'
		);
		return $arrForms;
	}

	public function getColors(\DataContainer $dc)
	{
		$arrForms = array
		(
			'w'   => 'Weiß',
			's'   => 'Schwarz'
		);
		return $arrForms;
	}

}
