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
use Contao\Message;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Wertung;

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
			array('tl_teamtournament_matches', 'sperreErgebnisfelder')
		),
		// Läuft nach dem Schreiben und vor Versions::create(); hier ist der
		// richtige Ort für das eigene UPDATE des gerechneten Ergebnisses
		'onsubmit_callback'           => array
		(
			array('tl_teamtournament_matches', 'rechneErgebnis')
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
			// Die jüngste Runde zuerst, darin die Tische aufsteigend. Die
			// Richtung steht hier ausgeschrieben, weil parentView() sonst die
			// Richtung aus dem 'flag' des Feldes ableitet
			'fields'                  => array('round DESC', 'board ASC'),
			'headerFields'            => array('title', 'fromDate', 'toDate', 'place', 'country'),
			// Formatiert die Datumswerte im Kopf, siehe Helfer::kopfWettbewerb()
			'header_callback'         => array('tl_teamtournament_matches', 'kopfzeile'),
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
			// Eigene Maske für Aufstellung und Ergebnisse, siehe
			// Classes\Ergebnismaske. Der Schlüssel "results" ist im
			// Backend-Modul hinterlegt (config.php)
			'results' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['results'],
				'href'                => 'key=results',
				'icon'                => 'bundles/contaoteamtournament/images/players.png',
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
		'default'                     => '{team_legend},team1,team2;{round_legend},round,board;{results_legend},overrideResult,resultTeam1,resultTeam2;{publish_legend},published'
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
			// Der foreignKey ist nicht für die Eingabemaske da — die füllt der
			// options_callback —, sondern für die Kopfzeile der Paarungsliste
			// und für "Details anzeigen". Beide lösen einen foreignKey mit
			// einer einfachen Abfrage auf, während sie beim options_callback
			// die Rückrufklasse mit einem fremden Data Container aufrufen und
			// dort im Zweifel die Kennung statt des Namens stehen bleibt.
			'foreignKey'              => 'tl_teamtournament_teams.name',
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
			// Auflösung des Namens für Kopfzeile und Detailansicht, siehe team1
			'foreignKey'              => 'tl_teamtournament_teams.name',
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
			'filter'                  => true,
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
		'overrideResult' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament_matches']['overrideResult'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				// Beim Umschalten neu laden, damit die beiden Punktefelder
				// sofort schreibbar werden statt erst nach dem Speichern
				'submitOnChange'      => true,
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "char(1) NOT NULL default ''"
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
	 * Die Mannschaften des Turniers für die Auswahlliste der Eingabemaske.
	 *
	 * Kennung => Name. Wird beim ersten Aufruf von getTeams() gefüllt.
	 *
	 * @var array<int, string>
	 */
	private $teams = array();

	/**
	 * Zwischenspeicher der Mannschaftsnamen für die Listenansicht.
	 *
	 * Kennung => Name, oder null für eine Kennung, zu der es keinen Datensatz
	 * gibt. Getrennt von $teams, weil die Liste über den Primärschlüssel
	 * auflöst und nicht über das Turnier aus der Adresse.
	 *
	 * @var array<int, string|null>
	 */
	private $namen = array();

	/**
	 * Wettbewerbe, deren Mannschaften schon in $namen stehen.
	 *
	 * @var array<int, bool>
	 */
	private $geladeneTurniere = array();

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
	 * Baut den Kopfbereich über der Wettkampfliste.
	 *
	 * Contao gibt Beginn und Ende dort roh aus („20260916"), weil die Felder
	 * Zahlen in der Form JJJJMMTT sind und keine rgxp 'date' tragen. Der Kopf
	 * kommt deshalb aus Helfer::kopfWettbewerb(), das auch die Mannschaftsliste
	 * benutzt — beide Kindlisten eines Wettbewerbs sehen damit gleich aus.
	 *
	 * Die Kennung des Wettbewerbs liefert $dc->currentPid. Das ist in beiden
	 * Contao-Fassungen genau der Wert, mit dem Contao den Elterndatensatz für
	 * den Kopf selbst lädt (4.13: CURRENT_ID, 5.7: intCurrentPid).
	 *
	 * @param array<string, string> $arrKopf Der von Contao vorbereitete Kopf,
	 *                                       Beschriftung => Wert
	 * @param DataContainer         $dc      Der Data Container der Kindliste
	 *
	 * @return array<string, string> Der neu gebaute Kopf; lässt sich der
	 *                               Wettbewerb nicht laden, Contaos eigener
	 */
	public function kopfzeile($arrKopf, $dc): array
	{
		return Helfer::kopfWettbewerb((int) $dc->currentPid) ?: (array) $arrKopf;
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
		$temp .= $arrRow['round'].'.'.$arrRow['board'].' | '.$this->getMannschaftsname($arrRow['team1'], (int) $arrRow['pid']).' - '.$this->getMannschaftsname($arrRow['team2'], (int) $arrRow['pid']);

		// Geprüft wurde hier früher nur "if ($arrRow['resultTeam1'])". Ein
		// Wettkampf, der 0:4 ausgegangen ist, hat dort aber eine Null stehen —
		// und die ist in PHP unwahr. Das Ergebnis fehlte deshalb in der Liste,
		// obwohl es erfasst war. Maßgeblich ist, ob überhaupt etwas eingetragen
		// wurde, und das entscheidet der Vergleich mit der leeren Zeichenkette.
		$erg1 = (string) $arrRow['resultTeam1'];
		$erg2 = (string) $arrRow['resultTeam2'];

		if ('' !== $erg1 || '' !== $erg2)
		{
			$temp .= ' | '.$this->getPoints($erg1).' : '.$this->getPoints($erg2);
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
	 * Liefert den Namen einer Mannschaft für die Listenansicht.
	 *
	 * Aufgelöst wird über den Primärschlüssel, nicht über eine Liste, die
	 * vorab für „das" Turnier der Seite geladen wurde. Genau daran hing der
	 * Fehler „? - ?": Die Liste kam aus getTurnierId(), und die hielt in der
	 * Übersicht die Kennung des Wettbewerbs für die eines Wettkampfes (siehe
	 * dort).
	 *
	 * Damit die Liste nicht je Zeile zwei Abfragen auslöst, werden beim ersten
	 * Zugriff alle Mannschaften des Wettbewerbs geladen, zu dem die Zeile
	 * selbst gehört ($arrRow['pid']). Fehlt eine Kennung darin — etwa weil die
	 * Mannschaft nachträglich einem anderen Wettbewerb zugeordnet wurde —, wird
	 * sie einzeln nachgeschlagen.
	 *
	 * @param mixed $varId      Kennung aus team1 oder team2
	 * @param int   $intTurnier Kennung des Wettbewerbs, zu dem der Wettkampf gehört
	 *
	 * @return string Der Mannschaftsname; gibt es keinen Datensatz zu der
	 *                Kennung, die Kennung selbst — nie nur ein Fragezeichen
	 */
	private function getMannschaftsname($varId, int $intTurnier): string
	{
		$intId = (int) $varId;

		if ($intTurnier && !isset($this->geladeneTurniere[$intTurnier]))
		{
			$this->geladeneTurniere[$intTurnier] = true;
			$this->namen += $this->ladeMannschaften($intTurnier);
		}

		if (!\array_key_exists($intId, $this->namen))
		{
			$objMannschaft = Database::getInstance()
				->prepare("SELECT name FROM tl_teamtournament_teams WHERE id=?")
				->limit(1)
				->execute($intId);

			$this->namen[$intId] = $objMannschaft->numRows ? (string) $objMannschaft->name : null;
		}

		return $this->namen[$intId] ?? (string) $varId;
	}

	/**
	 * Ermittelt, zu welchem Turnier der aktuelle Aufruf gehört.
	 *
	 * Früher las das allein Input::get('id') — das stimmt aber nur in der
	 * Übersicht. Beim Bearbeiten eines Wettkampfes steht dort dessen eigene
	 * Kennung, und die Mannschaftsliste blieb leer oder zeigte die falschen
	 * Mannschaften.
	 *
	 * Vorsicht mit $dc->id: DC_Table setzt die Eigenschaft im Konstruktor
	 * schlicht auf Input::get('id') (4.13 Zeile 116). In der Übersicht ist das
	 * die Kennung des **Wettbewerbs**, erst beim Bearbeiten die eines
	 * Wettkampfes. Bis 0.4.1 wurde $dc->id hier ohne Rücksicht darauf als
	 * Wettkampf nachgeschlagen: In der Übersicht von Wettbewerb 6 lud das den
	 * Wettkampf mit der Kennung 6, dessen pid auf einen ganz anderen
	 * Wettbewerb zeigen kann — und damit dessen Mannschaften. Gab es keinen
	 * Wettkampf mit dieser Kennung, fiel die Abfrage durch und das Ergebnis
	 * stimmte zufällig. Deshalb gilt $dc->id nur noch dann als Wettkampf, wenn
	 * eine Aktion läuft.
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
		// Beim Bearbeiten eines Wettkampfes: Turnier über den Datensatz. Ohne
		// Aktion und in der Auswahlansicht (act=select) ist $dc->id der
		// Wettbewerb selbst, beim Anlegen steht der Wettbewerb in pid.
		if (null !== $dc && $dc->id && !\in_array((string) Input::get('act'), array('', 'select', 'create'), true))
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

		// In der Übersicht und in der Auswahlansicht ist die Kennung in der
		// Adresse das Turnier
		if (\in_array((string) Input::get('act'), array('', 'select'), true) && Input::get('id'))
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
	 * Sperrt die beiden Punktefelder, solange gerechnet wird.
	 *
	 * Läuft als onload_callback und ändert die DCA zur Laufzeit. Rechnet das
	 * Turnier die Mannschaftspunkte aus den Brettern, wären schreibbare Felder
	 * eine Falle: Der eingetragene Wert würde beim Speichern sofort wieder
	 * überschrieben. Wer von Hand eintragen will, setzt den Haken
	 * „Ergebnis überschreiben".
	 *
	 * Die Prüfung auf act=edit ist nötig, weil derselbe Rückruf auch in der
	 * Listenansicht läuft; dort steht in $dc->id die Kennung des Turniers, und
	 * die würde zufällig auf einen gleichnamigen Wettkampf passen können.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container
	 */
	public function sperreErgebnisfelder($dc = null): void
	{
		if (null === $dc || !$dc->id || 'edit' !== Input::get('act') || !Wertung::wirdGerechnet((int) $dc->id))
		{
			return;
		}

		foreach (array('resultTeam1', 'resultTeam2') as $strFeld)
		{
			$GLOBALS['TL_DCA']['tl_teamtournament_matches']['fields'][$strFeld]['eval']['readonly'] = true;
		}
	}

	/**
	 * Schreibt das aus den Brettpunkten gerechnete Mannschaftsergebnis.
	 *
	 * Läuft als onsubmit_callback, also nach dem Speichern des Wettkampfes.
	 * Tut nichts, wenn das Turnier die Berechnung nicht vorsieht oder der
	 * Wettkampf sie mit „Ergebnis überschreiben" aufhebt.
	 *
	 * @param DataContainer|null $dc Der aufrufende Data Container
	 */
	public function rechneErgebnis($dc = null): void
	{
		if (null !== $dc && $dc->id)
		{
			Wertung::schreibeWettkampf((int) $dc->id);
		}
	}

	/**
	 * Wandelt einen Punktwert aus der Datenbank in die Anzeigeform um.
	 *
	 * In der Datenbank steht der Punkt als Dezimaltrennzeichen, in der Maske
	 * das hierzulande übliche Komma. Ein leeres Ergebnis bleibt leer — bisher
	 * stand hier für einen noch nicht gespielten Wettkampf „0,0", das beim
	 * nächsten Speichern als gewertetes 0:0 in der Datenbank landete.
	 *
	 * @param mixed $varValue Der Wert aus der Datenbank
	 *
	 * @return string Der Punktwert mit einer Nachkommastelle, etwa '4,5'
	 */
	public function getPoints($varValue): string
	{
		return Wertung::ausZahl($varValue);
	}

	/**
	 * Wandelt einen eingegebenen Punktwert für die Datenbank um.
	 *
	 * Angenommen werden Komma und Punkt sowie das Zeichen ½. Lässt sich aus der
	 * Eingabe keine Zahl lesen, bleibt der bisherige Wert stehen und es
	 * erscheint eine Fehlermeldung — stillschweigend eine 0,0 daraus zu machen,
	 * wie es die frühere Fassung tat, sähe im Backend wie ein Ergebnis aus.
	 *
	 * Eine Ausnahme zu werfen wäre der naheliegende Weg, ist hier aber nicht
	 * gangbar: Contao 5 fängt Ausnahmen aus einem save_callback ab und macht
	 * eine Meldung daraus, Contao 4.13 nicht — dort gäbe es eine Fehlerseite.
	 *
	 * @param mixed              $varValue Die Eingabe aus der Maske
	 * @param DataContainer|null $dc       Der aufrufende Data Container
	 *
	 * @return string Der Punktwert mit Punkt als Dezimaltrennzeichen
	 */
	public function putPoints($varValue, $dc = null): string
	{
		$strWert = Wertung::inZahl($varValue);

		if (null !== $strWert)
		{
			return $strWert;
		}

		Message::addError(sprintf('Der Wert „%s" ist keine Punktzahl. Erlaubt sind Zahlen mit Komma oder Punkt, etwa 4,5. Der bisherige Wert bleibt stehen.', $varValue));

		// $dc->value hält den Wert, der vor dem Absenden in der Maske stand
		return (string) Wertung::inZahl(null !== $dc ? $dc->value : '');
	}
}
