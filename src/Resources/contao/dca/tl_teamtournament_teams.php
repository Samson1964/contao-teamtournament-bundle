<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoHelperBundle\Classes\Helper;

/*
 * Datenbereich tl_teamtournament_teams
 */
$GLOBALS['TL_DCA']['tl_teamtournament_teams'] = array
(
	// Grundeinstellungen
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
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

	// Listenansicht
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('name ASC'),
			'flag'                    => DataContainer::SORT_DESC,
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
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_teamtournament_teams', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_teamtournament_teams', 'copyArchive')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				// Der DcaLoader lädt die Sprachdateien noch nicht, deshalb der
				// abgesicherte Lesezugriff
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_teamtournament_teams', 'deleteArchive')
			),
			// Umschalter ohne codefog/contao-haste, siehe tl_teamtournament
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_teams']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Paletten
	'palettes' => array
	(
		'default'                     => '{name_legend},name,country,flag;{captain_legend:hide},prename,surname,birthday,fide_title,fide_id,fide_elo,dwz,singleSRC,weblinks;{publish_legend},published'
	),

	// Felder
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
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
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
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament_teams', 'getLaender'),
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
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
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
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
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
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'maxlength'           => 10,
				'tl_class'            => 'w50',
				'rgxp'                => 'alnum'
			),
			'load_callback'           => array
			(
				array(Helper::class, 'getDate')
			),
			'save_callback' => array
			(
				array(Helper::class, 'putDate')
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
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
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
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'default'                 => 1,
			'inputType'               => 'checkbox',
			// Schaltet den Ajax-Umschalter in der Listenansicht frei
			'toggle'                  => true,
			'eval'                    => array
			(
				'doNotCopy'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
	)
);

/**
 * Rückrufe des Datenbereichs tl_teamtournament_teams.
 *
 * Die Klasse erbt von Contao\Backend, weil Contao 5 keine globalen
 * Klassenaliasse mehr registriert.
 */
class tl_teamtournament_teams extends Backend
{
	/**
	 * Erzeugt die Rückrufklasse.
	 *
	 * In Contao 4.13 ist Backend::__construct() als protected deklariert, erst
	 * Contao 5 macht ihn öffentlich; ohne diese Überschreibung ließe sich die
	 * Klasse dort von außerhalb der Contao-Klassenhierarchie nicht erzeugen.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Liefert die Länderliste für die Auswahl des Mannschaftslandes.
	 *
	 * Ersatz für das in Contao 5 entfallene System::getCountries(). Die
	 * Schlüssel werden klein geschrieben, weil die vorhandenen Datensätze das
	 * Länderkürzel so enthalten.
	 *
	 * @return array<string, string> Länderkürzel in Kleinschreibung => Name des
	 *                               Landes in der Sprache des Backends
	 */
	public function getLaender(): array
	{
		$arrCountries = System::getContainer()->get('contao.intl.countries')->getCountries();

		return array_combine(array_map('strtolower', array_keys($arrCountries)), $arrCountries);
	}

	/**
	 * Zeigt den Knopf „Mannschaft bearbeiten" nur bei ausreichenden Rechten.
	 *
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament_teams
	 * @param string               $href       Ziel der Operation
	 * @param string               $label      Beschriftung aus der Sprachdatei
	 * @param string               $title      Titel-Attribut des Verweises
	 * @param string               $icon       Pfad des Symbols aus der DCA
	 * @param string               $attributes Weitere Attribute des Verweises
	 *
	 * @return string Der Verweis als Markup, oder das ausgegraute Symbol
	 */
	public function editHeader($row, $href, $label, $title, $icon, $attributes): string
	{
		$objUser = BackendUser::getInstance();
		$blnErlaubt = $objUser->isAdmin || \count(preg_grep('/^tl_teamtournament_teams::/', (array) $objUser->alexf)) > 0;

		return $this->generateButton($blnErlaubt, $row, $href, $label, $title, $icon, $attributes);
	}

	/**
	 * Zeigt den Knopf „Mannschaft kopieren" nur bei ausreichenden Rechten.
	 *
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament_teams
	 * @param string               $href       Ziel der Operation
	 * @param string               $label      Beschriftung aus der Sprachdatei
	 * @param string               $title      Titel-Attribut des Verweises
	 * @param string               $icon       Pfad des Symbols aus der DCA
	 * @param string               $attributes Weitere Attribute des Verweises
	 *
	 * @return string Der Verweis als Markup, oder das ausgegraute Symbol
	 */
	public function copyArchive($row, $href, $label, $title, $icon, $attributes): string
	{
		$objUser = BackendUser::getInstance();

		return $this->generateButton($objUser->isAdmin || $objUser->hasAccess('create', 'newp'), $row, $href, $label, $title, $icon, $attributes);
	}

	/**
	 * Zeigt den Knopf „Mannschaft löschen" nur bei ausreichenden Rechten.
	 *
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament_teams
	 * @param string               $href       Ziel der Operation
	 * @param string               $label      Beschriftung aus der Sprachdatei
	 * @param string               $title      Titel-Attribut des Verweises
	 * @param string               $icon       Pfad des Symbols aus der DCA
	 * @param string               $attributes Weitere Attribute des Verweises
	 *
	 * @return string Der Verweis als Markup, oder das ausgegraute Symbol
	 */
	public function deleteArchive($row, $href, $label, $title, $icon, $attributes): string
	{
		$objUser = BackendUser::getInstance();

		return $this->generateButton($objUser->isAdmin || $objUser->hasAccess('delete', 'newp'), $row, $href, $label, $title, $icon, $attributes);
	}

	/**
	 * Baut das Markup einer rechteabhängigen Operation.
	 *
	 * @param bool                 $blnErlaubt Ergebnis der Rechteprüfung
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament_teams
	 * @param string               $href       Ziel der Operation
	 * @param string               $label      Beschriftung aus der Sprachdatei
	 * @param string               $title      Titel-Attribut des Verweises
	 * @param string               $icon       Pfad des Symbols aus der DCA
	 * @param string               $attributes Weitere Attribute des Verweises
	 *
	 * @return string Der anklickbare Verweis, oder das ausgegraute Symbol ohne
	 *                Verweis
	 */
	private function generateButton(bool $blnErlaubt, $row, $href, $label, $title, $icon, $attributes): string
	{
		if (!$blnErlaubt)
		{
			return Image::getHtml(preg_replace('/\.(gif|svg)$/i', '_.svg', $icon)).' ';
		}

		return '<a href="'.self::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}

	/**
	 * Beschriftet eine Mannschaft in der Listenansicht.
	 *
	 * @param array<string, mixed> $arrRow Der Datensatz aus tl_teamtournament_teams
	 *
	 * @return string Name der Mannschaft mit dem Länderkürzel in Klammern
	 */
	public function listTeams($arrRow): string
	{
		return $arrRow['name'].' ('.$arrRow['country'].')';
	}
}
