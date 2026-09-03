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
use Schachbulle\ContaoTeamtournamentBundle\Classes\Wertung;

/*
 * Datenbereich tl_teamtournament_games
 */
$GLOBALS['TL_DCA']['tl_teamtournament_games'] = array
(
	// Grundeinstellungen
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_teamtournament_matches',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		// Jede Änderung an einem Brett kann das Mannschaftsergebnis verschieben
		'onsubmit_callback'           => array
		(
			array('tl_teamtournament_games', 'rechneErgebnis')
		),
		'ondelete_callback'           => array
		(
			array('tl_teamtournament_games', 'rechneErgebnisOhne')
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
			'fields'                  => array('board ASC'),
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
				'icon'                => 'edit.svg',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				// Der DcaLoader lädt die Sprachdateien noch nicht, deshalb der
				// abgesicherte Lesezugriff
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			// Umschalter ohne codefog/contao-haste, siehe tl_teamtournament
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Paletten
	'palettes' => array
	(
		'default'                     => '{player_legend},player1,player2;{results_legend},board,colors,result;{pgn_legend},pgn;{publish_legend},published'
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
		'colors' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_games']['colors'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
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
 * Rückrufe des Datenbereichs tl_teamtournament_games.
 *
 * Die Klasse erbt von Contao\Backend, weil Contao 5 keine globalen
 * Klassenaliasse mehr registriert.
 */
class tl_teamtournament_games extends Backend
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
	 * Rechnet das Mannschaftsergebnis nach einer Änderung am Brett neu.
	 *
	 * Läuft als onsubmit_callback, also nach dem Schreiben der Partie.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container
	 */
	public function rechneErgebnis($dc = null): void
	{
		if (null !== $dc && $dc->id)
		{
			Wertung::schreibeWettkampf(Wertung::getWettkampf((int) $dc->id));
		}
	}

	/**
	 * Rechnet das Mannschaftsergebnis vor dem Löschen einer Partie neu.
	 *
	 * Der ondelete_callback läuft, solange der Datensatz noch in der Tabelle
	 * steht — ein „danach" gibt es beim DC_Table nicht. Die zu löschende Partie
	 * wird deshalb ausdrücklich von der Summe ausgenommen.
	 *
	 * @param DataContainer|null $dc      Der aufrufende Data Container
	 * @param int                $undoId  Kennung des Undo-Eintrags, hier ungenutzt
	 */
	public function rechneErgebnisOhne($dc = null, $undoId = 0): void
	{
		if (null !== $dc && $dc->id)
		{
			Wertung::schreibeWettkampf(Wertung::getWettkampf((int) $dc->id), (int) $dc->id);
		}
	}

	/**
	 * Beschriftet eine Partie in der Listenansicht.
	 *
	 * @param array<string, mixed> $arrRow Der Datensatz aus tl_teamtournament_games
	 *
	 * @return string Brettnummer, beide Spieler und das Ergebnis
	 */
	public function listGames($arrRow): string
	{
		$temp = '<div class="tl_content_left">';
		$temp .= '<b>'.$arrRow['board'].'</b> ';
		$temp .= $this->getPlayerName($arrRow['player1']);
		$temp .= $arrRow['result'] ? ' '.$arrRow['result'].' ' : ' - ';
		$temp .= $this->getPlayerName($arrRow['player2']);

		return $temp.'</div>';
	}

	/**
	 * Liefert den Namen eines Spielers.
	 *
	 * @param mixed $id Kennung des Spielers aus tl_teamtournament_players
	 *
	 * @return string Vor- und Nachname; ist der Spieler unbekannt, die
	 *                übergebene Kennung, damit in der Liste wenigstens etwas steht
	 */
	public function getPlayerName($id): string
	{
		$objPlayer = Database::getInstance()
			->prepare("SELECT prename, surname FROM tl_teamtournament_players WHERE id=?")
			->execute($id);

		if (!$objPlayer->numRows)
		{
			return (string) $id;
		}

		return trim($objPlayer->prename.' '.$objPlayer->surname);
	}

	/**
	 * Liefert die Spieler der ersten Mannschaft als Auswahlliste.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container
	 *
	 * @return array<int, string> Spielerkennung => Name
	 */
	public function getPlayerTeam1(DataContainer $dc): array
	{
		return $this->ladeSpieler($dc, 'team1');
	}

	/**
	 * Liefert die Spieler der zweiten Mannschaft als Auswahlliste.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container
	 *
	 * @return array<int, string> Spielerkennung => Name
	 */
	public function getPlayerTeam2(DataContainer $dc): array
	{
		return $this->ladeSpieler($dc, 'team2');
	}

	/**
	 * Liest die Spieler einer der beiden Mannschaften des Wettkampfes ein.
	 *
	 * Der Wettkampf wird über den Datensatz der Partie ermittelt, beim Anlegen
	 * über den Parameter pid der Adresse. Der frühere Weg über
	 * $dc->activeRecord->pid lief bei einer neu angelegten Partie in einen
	 * Fehler, weil dort noch kein Datensatz vorliegt; ab Contao 5 gilt der
	 * Zugriff außerdem als veraltet.
	 *
	 * @param DataContainer $dc      Der aufrufende Data Container
	 * @param string        $strFeld 'team1' oder 'team2'
	 *
	 * @return array<int, string> Spielerkennung => Name; leer, wenn sich der
	 *                            Wettkampf nicht ermitteln ließ
	 */
	private function ladeSpieler(DataContainer $dc, string $strFeld): array
	{
		$arrSpieler = array();
		$intWettkampf = 0;

		if ($dc->id)
		{
			$objPartie = Database::getInstance()
				->prepare("SELECT pid FROM tl_teamtournament_games WHERE id=?")
				->execute($dc->id);

			if ($objPartie->numRows)
			{
				$intWettkampf = (int) $objPartie->pid;
			}
		}

		if (!$intWettkampf && Input::get('pid'))
		{
			$intWettkampf = (int) Input::get('pid');
		}

		if (!$intWettkampf)
		{
			return $arrSpieler;
		}

		$objWettkampf = Database::getInstance()
			->prepare("SELECT team1, team2 FROM tl_teamtournament_matches WHERE id=?")
			->execute($intWettkampf);

		if (!$objWettkampf->numRows)
		{
			return $arrSpieler;
		}

		$objSpieler = Database::getInstance()
			->prepare("SELECT id, prename, surname FROM tl_teamtournament_players WHERE pid=? ORDER BY board ASC, surname ASC")
			->execute($objWettkampf->$strFeld);

		while ($objSpieler->next())
		{
			$arrSpieler[$objSpieler->id] = trim($objSpieler->prename.' '.$objSpieler->surname);
		}

		return $arrSpieler;
	}

	/**
	 * Liefert die möglichen Partieergebnisse.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container, hier ungenutzt
	 *
	 * @return array<string, string> Ergebnis => Beschriftung
	 */
	public function getResults($dc = null): array
	{
		// Die Liste kommt aus der Wertung, damit Auswahl und Punktzuordnung
		// nicht auseinanderlaufen können: Ein hier angebotenes Ergebnis, das
		// die Wertung nicht kennt, zählte beim Mannschaftsergebnis nicht mit
		$arrErgebnisse = array_keys(Wertung::ERGEBNISSE);

		return array_combine($arrErgebnisse, $arrErgebnisse);
	}

	/**
	 * Liefert die Farbverteilung am ersten Brett.
	 *
	 * Die Angabe gilt für den Spieler der ersten Mannschaft; der Gegner hat
	 * jeweils die andere Farbe.
	 *
	 * @param DataContainer $dc Der aufrufende Data Container, hier ungenutzt
	 *
	 * @return array<string, string> Kürzel => Beschriftung
	 */
	public function getColors($dc = null): array
	{
		return array
		(
			'w' => 'Weiß',
			's' => 'Schwarz'
		);
	}
}
