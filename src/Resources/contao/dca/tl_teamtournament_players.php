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
use Contao\DC_Table;
use Schachbulle\ContaoHelperBundle\Classes\Helper;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer;

/*
 * Datenbereich tl_teamtournament_players
 */
$GLOBALS['TL_DCA']['tl_teamtournament_players'] = array
(
	// Grundeinstellungen
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_teamtournament_teams',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		// Trägt beim Anlegen die nächste freie Brettnummer ein. Ein 'default'
		// am Feld reicht dafür nicht: Der Wert hängt von der Mannschaft ab und
		// steht erst fest, wenn der Datensatz seine pid hat.
		'oncreate_callback'           => array
		(
			array('tl_teamtournament_players', 'setzeBrettnummer')
		),
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
			// Die Brettreihenfolge ist die natürliche Ordnung einer Aufstellung;
			// Spieler ohne Brettnummer (0) stehen damit oben und fallen auf
			'fields'                  => array('board ASC', 'surname ASC', 'prename ASC'),
			'flag'                    => DataContainer::SORT_ASC,
			'headerFields'            => array('name'),
			'panelLayout'             => 'filter;sort;search,limit',
			'child_record_callback'   => array('tl_teamtournament_players', 'listPlayers'),
			'disableGrouping'         => true
		),
		'label' => array
		(
			// Stand hier früher als array('name') mit dem Format '%s %s': Ein
			// Feld «name» gibt es in dieser Tabelle gar nicht, und für zwei
			// Platzhalter fehlte der zweite Wert
			'fields'                  => array('surname', 'prename'),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				// Der DcaLoader lädt die Sprachdateien noch nicht, deshalb der
				// abgesicherte Lesezugriff
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			// Umschalter ohne codefog/contao-haste, siehe tl_teamtournament
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Paletten
	'palettes' => array
	(
		'default'                     => '{name_legend},board,prename,surname,birthday,fide_title,fide_id,fide_elo,dwz,singleSRC,weblinks;{publish_legend},published'
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
			// Verwies früher auf tl_teamtournament_players.id, also auf die
			// eigene Tabelle. Richtig ist die Mannschaft: Spieler hängen an
			// tl_teamtournament_teams, wie ptable oben auch sagt.
			'foreignKey'              => 'tl_teamtournament_teams.name',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'board' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['board'],
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'maxlength'=>2, 'tl_class'=>'w50'),
			'sql'                     => "int(2) unsigned NOT NULL default '0'"
		),
		'surname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['surname'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['prename'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>false, 'maxlength'=>32, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'birthday' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['birthday'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['fide_id'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>10, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'fide_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['fide_title'],
			'inputType'               => 'select',
			'options'                 => array('GM', 'IM', 'FM', 'CM', 'WGM', 'WIM', 'WFM', 'WCM'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['fide_title_options'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['fide_elo'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>4, 'tl_class'=>'w50'),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'dwz' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['dwz'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>4, 'tl_class'=>'w50'),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL",
		),
		'weblinks' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['weblinks'],
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
						'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['weblinks_title'],
						'exclude'                 => true,
						'inputType'               => 'text',
						'eval'                    => array
						(
							'style'               => 'width:100%',
						),
					),
					'url' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['weblinks_url'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_players']['published'],
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
 * Rückrufe des Datenbereichs tl_teamtournament_players.
 *
 * Die Klasse erbt von Contao\Backend, weil Contao 5 keine globalen
 * Klassenaliasse mehr registriert.
 */
class tl_teamtournament_players extends Backend
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
	 * Trägt beim Anlegen eines Spielers die nächste freie Brettnummer ein.
	 *
	 * Gesucht wird die höchste bereits vergebene Nummer innerhalb derselben
	 * Mannschaft; der neue Spieler bekommt die nächste. Lücken werden nicht
	 * gefüllt — wer an Brett 3 nachträglich jemanden einsetzen will, trägt die
	 * Nummer ohnehin von Hand ein.
	 *
	 * Der Rückruf läuft in DC_Table unmittelbar nach dem INSERT und noch vor
	 * der Weiterleitung in die Eingabemaske; das nachgereichte UPDATE ist dort
	 * also schon sichtbar. Der frisch angelegte Datensatz wird dabei selbst
	 * nicht mitgezählt, weil seine Brettnummer noch 0 ist.
	 *
	 * @param string               $strTable  Name der Tabelle, hier immer
	 *                                        tl_teamtournament_players
	 * @param int                  $intId     Kennung des neuen Datensatzes
	 * @param array<string, mixed> $arrSet    Die eingefügten Werte; enthält die pid
	 * @param DataContainer        $dc        Der aufrufende Data Container, hier ungenutzt
	 */
	public function setzeBrettnummer($strTable, $intId, $arrSet, $dc = null): void
	{
		$intMannschaft = (int) ($arrSet['pid'] ?? 0);

		if (!$intMannschaft)
		{
			return;
		}

		$objMax = Database::getInstance()
			->prepare("SELECT MAX(board) AS maxBoard FROM tl_teamtournament_players WHERE pid=?")
			->execute($intMannschaft);

		$intBrett = (int) $objMax->maxBoard + 1;

		// Das Feld fasst nur zwei Stellen
		if ($intBrett > 99)
		{
			return;
		}

		Database::getInstance()
			->prepare("UPDATE tl_teamtournament_players SET board=? WHERE id=?")
			->execute($intBrett, $intId);
	}

	/**
	 * Beschriftet einen Spieler in der Listenansicht.
	 *
	 * Vorangestellt ist die Brettnummer in eckigen Klammern, dahinter steht das
	 * Foto als kleines Vorschaubild, sofern eines hinterlegt ist. Spieler ohne
	 * Brettnummer bekommen statt der Null einen Strich, damit sie auffallen.
	 *
	 * @param array<string, mixed> $arrRow Der Datensatz aus tl_teamtournament_players
	 *
	 * @return string Brettnummer, Name und Vorschaubild als Markup
	 */
	public function listPlayers($arrRow): string
	{
		$strName = trim($arrRow['surname'].', '.$arrRow['prename'], ', ');
		$strBrett = $arrRow['board'] ? (string) $arrRow['board'] : '–';

		return '['.$strBrett.'] '.$strName.' '.Helfer::miniatur($arrRow['singleSRC'] ?? null, 16, $strName);
	}
}
