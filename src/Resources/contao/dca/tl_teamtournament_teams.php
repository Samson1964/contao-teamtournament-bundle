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
 * Table tl_teamtournament_teams
 */
$GLOBALS['TL_DCA']['tl_teamtournament_teams'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_teamtournament',
		'ctable'                      => array('tl_teamtournament_players'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'    => 'primary',
				'pid'   => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('name ASC'),
			'flag'                    => 12,
			'headerFields'            => array('title'),
			'panelLayout'             => 'filter;sort;search,limit',
			'child_record_callback'   => array('tl_teamtournament_teams', 'listTeams'),
			'disableGrouping'         => true
		),
		'label' => array
		(
			'fields'                  => array('name', 'country'),
			'showColumns'             => true,
			'format'                  => '%s %s',
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
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['edit'],
				'href'                => 'table=tl_teamtournament_players',
				'icon'                => 'edit.gif'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
				'button_callback'     => array('tl_teamtournament_teams', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'button_callback'     => array('tl_teamtournament_teams', 'copyArchive')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_teamtournament_teams', 'deleteArchive')
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['toggle'],
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
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{name_legend},name,country,flag;{captain_legend:hide},prename,surname,birthday,fide_title,fide_id,fide_elo,dwz,singleSRC,weblinks;{publish_legend},published'
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
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['name'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => true, 
				'maxlength'           => 255,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'country' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['country'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options'                 => System::getCountries(),
			'eval'                    => array
			(
				'includeBlankOption'  => true, 
				'chosen'              => true, 
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(2) NOT NULL default ''"
		),  
		'flag' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['flag'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL",
		),  
		'surname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['surname'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>32, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'prename' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['prename'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>32, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'birthday' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['birthday'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => true,
			'flag'                    => 11,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'maxlength'           => 10,
				'tl_class'            => 'w50',
				'rgxp'                => 'alnum'
			),
			'load_callback'           => array
			(
				array('\Schachbulle\ContaoHelperBundle\Classes\Helper', 'getDate')
			),
			'save_callback' => array
			(
				array('\Schachbulle\ContaoHelperBundle\Classes\Helper', 'putDate')
			),
			'sql'                     => "int(8) unsigned NOT NULL default '0'"
		),
		'fide_id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['fide_id'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>10, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'fide_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['fide_title'],
			'inputType'               => 'select',
			'options'                 => array('GM', 'IM', 'FM', 'CM', 'WGM', 'WIM', 'WFM', 'WCM'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['fide_title_options'],
			'exclude'                 => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'filter'                  => true,
			'search'                  => true,
			'eval'                    => array
			(
				'includeBlankOption'  => true,
				'mandatory'           => false,
				'tl_class'            => 'w50',
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'fide_elo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['fide_elo'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>4, 'tl_class'=>'w50'),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'dwz' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['dwz'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>4, 'tl_class'=>'w50'),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL",
		),  
		'weblinks' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['weblinks'],
			'exclude'                 => true,
			'inputType'               => 'multiColumnWizard',
			'eval'                    => array
			(
				'tl_class'            => 'clr',
				'buttonPos'           => 'top',
				'columnFields'        => array
				(
					'title' => array
					(
						'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['weblinks_title'],
						'exclude'                 => true,
						'search'                  => false,
						'sorting'                 => true,
						'flag'                    => 12,
						'inputType'               => 'text',
						'eval'                    => array
						(
							'style'               => 'width:100%',
						),
					),
					'url' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['weblinks_url'],
						'exclude'               => true,
						'inputType'             => 'text',
						'eval'                  => array
						(
							'style'             => 'width:100%',
						),
					),
				)
			),
			'sql'                   => "blob NULL"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['published'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'default'                 => true,
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
 * Class tl_teamtournament_teams
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_teamtournament_teams extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Return the edit header button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return ($this->User->isAdmin || count(preg_grep('/^tl_teamtournament_teams::/', $this->User->alexf)) > 0) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
	}


	/**
	 * Return the copy archive button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function copyArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return ($this->User->isAdmin || $this->User->hasAccess('create', 'newp')) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
	}


	/**
	 * Return the delete archive button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return ($this->User->isAdmin || $this->User->hasAccess('delete', 'newp')) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
	}

	/**
	 * Return the link picker wizard
	 * @param \DataContainer
	 * @return string
	 */
	public function pagePicker(DataContainer $dc)
	{
		return ' <a href="contao/page.php?do=' . Input::get('do') . '&amp;table=' . $dc->table . '&amp;field=' . $dc->field . '&amp;value=' . str_replace(array('{{link_url::', '}}'), '', $dc->value) . '" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['pagepicker']) . '" onclick="Backend.getScrollOffset();Backend.openModalSelector({\'width\':765,\'title\':\'' . specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MOD']['page'][0])) . '\',\'url\':this.href,\'id\':\'' . $dc->field . '\',\'tag\':\'ctrl_'. $dc->field . ((Input::get('act') == 'editAll') ? '_' . $dc->id : '') . '\',\'self\':this});return false">' . Image::getHtml('pickpage.gif', $GLOBALS['TL_LANG']['MSC']['pagepicker'], 'style="vertical-align:top;cursor:pointer"') . '</a>';
	} 

	/**
	 * Datensätze auflisten
	 * @param array
	 * @return string
	 */
	public function listTeams($arrRow)
	{
		$temp = $arrRow['name']. ' ('.$arrRow['country'].')';
		return $temp;
	}

}