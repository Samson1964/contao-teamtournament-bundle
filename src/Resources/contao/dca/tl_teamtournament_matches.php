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
use Contao\Input;

/*
 * Datenbereich tl_teamtournament_matches
 */
$GLOBALS['TL_DCA']['tl_teamtournament_matches'] = array
(
	// Grundeinstellungen
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_teamtournament',
		'ctable'                      => array('tl_teamtournament_games'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'onload_callback'             => array
		(
			array('tl_teamtournament_matches', 'loadTeams')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id'  => 'primary',
				'pid' => 'index',
			)
		)
	),

	// Listenansicht
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'disableGrouping'         => true,
			'fields'                  => array('round ASC', 'board ASC'),
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
				'icon'                => 'edit.svg'
			),
			'editHeader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['editHeader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				// Der DcaLoader lädt die Sprachdateien noch nicht, deshalb der
				// abgesicherte Lesezugriff
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			// Umschalter ohne codefog/contao-haste, siehe tl_teamtournament
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Paletten
	'palettes' => array
	(
		'default'                     => '{team_legend},team1,team2;{round_legend},round,board;{results_legend:hide},resultTeam1,resultTeam2;{publish_legend},published'
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
			'foreignKey'              => 'tl_teamtournament.title',
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
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
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
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
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
			'load_callback'           => array
			(
				array('tl_teamtournament_matches', 'getPoints')
			),
			'save_callback' => array
			(
				array('tl_teamtournament_matches', 'putPoints')
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
			'load_callback'           => array
			(
				array('tl_teamtournament_matches', 'getPoints')
			),
			'save_callback' => array
			(
				array('tl_teamtournament_matches', 'putPoints')
			),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['published'],
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
 * Rückrufe des Datenbereichs tl_teamtournament_matches.
 *
 * Die Klasse erbt von Contao\Backend, weil Contao 5 keine globalen
 * Klassenaliasse mehr registriert.
 */
class tl_teamtournament_matches extends Backend
{
	/**
	 * Die Mannschaften des gerade bearbeiteten Turniers.
	 *
	 * Kennung => Name. Wird einmal je Aufruf gefüllt, damit die Listenansicht
	 * nicht je Zeile eine Abfrage auslöst.
	 *
	 * @var array<int, string>
	 */
	private $teams = array();

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
	 * Lädt die Mannschaften des Turniers für die Listenansicht.
	 *
	 * Läuft als onload_callback, also im Konstruktor von DC_Table und damit
	 * bevor ein Datensatz feststeht. Deshalb wird die Turnierkennung hier aus
	 * der Adresse gelesen — in der Übersicht der Wettkämpfe steht dort die
	 * Kennung des Turniers.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container
	 */
	public function loadTeams($dc = null): void
	{
		$this->teams = $this->ladeMannschaften($this->getTurnierId($dc));
	}

	/**
	 * Beschriftet einen Wettkampf in der Listenansicht.
	 *
	 * @param array<string, mixed> $arrRow Der Datensatz aus tl_teamtournament_matches
	 *
	 * @return string Runde, Tisch, beide Mannschaften und — sofern gewertet —
	 *                das Mannschaftsergebnis
	 */
	public function listMatches($arrRow): string
	{
		$temp = '<div class="tl_content_left">';
		$temp .= $arrRow['round'].'.'.$arrRow['board'].' | '.($this->teams[$arrRow['team1']] ?? '?').' - '.($this->teams[$arrRow['team2']] ?? '?');

		if ($arrRow['resultTeam1'])
		{
			$temp .= ' | '.$arrRow['resultTeam1'].':'.$arrRow['resultTeam2'];
		}

		return $temp.'</div>';
	}

	/**
	 * Liefert die Mannschaften des Turniers als Auswahlliste.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container
	 *
	 * @return array<int, string> Mannschaftskennung => Name; leer, wenn sich das
	 *                            Turnier nicht ermitteln ließ
	 */
	public function getTeams(DataContainer $dc): array
	{
		if (!$this->teams)
		{
			$this->teams = $this->ladeMannschaften($this->getTurnierId($dc));
		}

		return $this->teams;
	}

	/**
	 * Ermittelt, zu welchem Turnier der aktuelle Aufruf gehört.
	 *
	 * Früher las das allein Input::get('id') — das stimmt aber nur in der
	 * Übersicht. Beim Bearbeiten eines Wettkampfes steht dort dessen eigene
	 * Kennung, und die Mannschaftsliste blieb leer oder zeigte die falschen
	 * Mannschaften.
	 *
	 * $dc->activeRecord wird bewusst nicht benutzt: Im onload_callback ist es
	 * unter Contao 4.13 noch nicht gesetzt, und ab Contao 5 gilt der Zugriff
	 * als veraltet.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container
	 *
	 * @return int Kennung des Turniers, oder 0, wenn sie sich nicht bestimmen ließ
	 */
	private function getTurnierId($dc = null): int
	{
		// Beim Bearbeiten eines Wettkampfes: Turnier über den Datensatz
		if (null !== $dc && $dc->id)
		{
			$objWettkampf = Database::getInstance()
				->prepare("SELECT pid FROM tl_teamtournament_matches WHERE id=?")
				->execute($dc->id);

			if ($objWettkampf->numRows)
			{
				return (int) $objWettkampf->pid;
			}
		}

		// Beim Anlegen eines Wettkampfes steht das Turnier im Parameter pid
		if (Input::get('pid'))
		{
			return (int) Input::get('pid');
		}

		// In der Übersicht ist die Kennung in der Adresse das Turnier
		if (!Input::get('act') && Input::get('id'))
		{
			return (int) Input::get('id');
		}

		return 0;
	}

	/**
	 * Liest die Mannschaften eines Turniers ein.
	 *
	 * @param int $intTurnier Kennung des Turniers; 0 liefert eine leere Liste
	 *
	 * @return array<int, string> Mannschaftskennung => Name, alphabetisch sortiert
	 */
	private function ladeMannschaften(int $intTurnier): array
	{
		$arrTeams = array();

		if (!$intTurnier)
		{
			return $arrTeams;
		}

		$objMannschaften = Database::getInstance()
			->prepare("SELECT id, name FROM tl_teamtournament_teams WHERE pid=? ORDER BY name ASC")
			->execute($intTurnier);

		while ($objMannschaften->next())
		{
			$arrTeams[$objMannschaften->id] = $objMannschaften->name;
		}

		return $arrTeams;
	}

	/**
	 * Wandelt einen Punktwert aus der Datenbank in die Anzeigeform um.
	 *
	 * In der Datenbank steht der Punkt als Dezimaltrennzeichen, in der Maske
	 * das hierzulande übliche Komma.
	 *
	 * @param mixed $varValue Der Wert aus der Datenbank
	 *
	 * @return string Der Punktwert mit einer Nachkommastelle, etwa '4,5'
	 */
	public function getPoints($varValue): string
	{
		return str_replace('.', ',', sprintf('%01.1f', (float) $varValue));
	}

	/**
	 * Wandelt einen eingegebenen Punktwert für die Datenbank um.
	 *
	 * @param mixed $varValue Die Eingabe aus der Maske, mit Komma oder Punkt
	 *
	 * @return string Der Punktwert mit Punkt als Dezimaltrennzeichen
	 */
	public function putPoints($varValue): string
	{
		return str_replace(',', '.', (string) $varValue);
	}
}
