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

/*
 * Datenbereich tl_teamtournament
 */
$GLOBALS['TL_DCA']['tl_teamtournament'] = array
(
	// Grundeinstellungen
	'config' => array
	(
		// Der Kurzname 'Table' ist in Contao 5 entfallen; der voll
		// qualifizierte Klassenname gilt in beiden Fassungen
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_teamtournament_teams', 'tl_teamtournament_matches'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'    => 'primary'
			)
		)
	),

	// Listenansicht
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('toDate DESC'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'sort,filter;search,limit',
		),
		'label' => array
		(
			'fields'                  => array('toDate', 'title', 'place', 'country', 'complete'),
			'showColumns'             => true,
			'format'                  => '%s %s %s %s %s',
			'label_callback'          => array('tl_teamtournament', 'listTournaments')
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
			// Der Datensatz selbst steht voran, danach folgen die Kindtabellen in
			// ihrer natürlichen Reihenfolge: erst die Mannschaften, dann die
			// Wettkämpfe. Die Kindtabellen tragen ein eigenes Symbol, damit sie
			// sich nicht gegenseitig und nicht dem Bleistift gleichen.
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_teamtournament', 'editHeader')
			),
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['edit'],
				'href'                => 'table=tl_teamtournament_teams',
				'icon'                => 'bundles/contaoteamtournament/images/teams.png'
			),
			'matches' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['matches'],
				'href'                => 'table=tl_teamtournament_matches',
				'icon'                => 'bundles/contaoteamtournament/images/match.png',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_teamtournament', 'copyArchive')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				// Der DcaLoader lädt die Sprachdateien noch nicht, deshalb der
				// abgesicherte Lesezugriff — sonst meldet contao:migrate eine
				// fehlende Feldbelegung
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_teamtournament', 'deleteArchive')
			),
			// Der Umschalter kommt ohne codefog/contao-haste aus: "act=toggle"
			// zusammen mit 'toggle' => true am Feld published wird von
			// Contao 4.13 wie von Contao 5 selbsttätig als Ajax-Umschalter mit
			// wechselndem Symbol gerendert
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['toggle'],
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Paletten
	'palettes' => array
	(
		'default'                     => '{title_legend},title,gender;{place_legend},place,country;{date_legend},fromDate,toDate;{language_legend},language;{results_legend},calculateResults;{info_legend:hide},info,source;{options_legend:hide},singleSRC,url;{imageSize_legend:hide},imageSize_flags,imageSize_lineup,imageSize_results;{publish_legend},complete,published'
	),

	// Felder
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['title'],
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
		'gender' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['gender'],
			'exclude'                 => true,
			'filter'                  => true,
			'default'                 => 'm',
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'select',
			'options'                 => array('m' => 'Männlich', 'w' => 'Weiblich'),
			'eval'                    => array
			(
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(1) NOT NULL default 'm'"
		),
		'place' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['place'],
			'exclude'                 => true,
			'search'                  => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 255,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'country' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['country'],
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'select',
			'options_callback'        => array('tl_teamtournament', 'getLaender'),
			'eval'                    => array
			(
				'includeBlankOption'  => true,
				'chosen'              => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(2) NOT NULL default ''"
		),
		'fromDate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['fromDate'],
			'default'                 => date('Ymd'),
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => true,
				'maxlength'           => 10,
				'tl_class'            => 'w50',
				'rgxp'                => 'alnum'
			),
			'load_callback'           => array
			(
				array('tl_teamtournament', 'getDate')
			),
			'save_callback' => array
			(
				array('tl_teamtournament', 'putDate')
			),
			'sql'                     => "int(8) unsigned NOT NULL default '0'"
		),
		'toDate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['toDate'],
			'default'                 => date('Ymd'),
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_DESC,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'maxlength'           => 10,
				'tl_class'            => 'w50',
				'rgxp'                => 'alnum'
			),
			'load_callback'           => array
			(
				array('tl_teamtournament', 'getDate')
			),
			'save_callback' => array
			(
				array('tl_teamtournament', 'putDate')
			),
			'sql'                     => "int(8) unsigned NOT NULL default '0'"
		),
		'info' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['info'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true, 'tl_class'=>'clr'),
			'explanation'             => 'insertTags',
			'sql'                     => "mediumtext NULL"
		),
		'source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['source'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL",
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			// 'dcaPicker' ersetzt den früheren pagePicker-Wizard. Der zeigte auf
			// contao/page.php — eine Adresse aus Contao 3, die es seit Contao 4
			// nicht mehr gibt; der Knopf führte also ins Leere. Den Schalter
			// kennen Contao 4.13 und Contao 5 gleichermaßen.
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'tl_class'=>'clr w50 wizard'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'language' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['language'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'select',
			'options'                 => array('de', 'en'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_teamtournament']['language_options'],
			'eval'                    => array
			(
				'mandatory'           => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(2) NOT NULL default ''"
		),
		'calculateResults' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['calculateResults'],
			'exclude'                 => true,
			'filter'                  => true,
			// Neue Turniere rechnen von sich aus, bestehende nicht: Deren
			// Spalte ist nach der Migration leer, und die von Hand
			// eingetragenen Mannschaftspunkte bleiben damit unangetastet, bis
			// der Haken hier ausdrücklich gesetzt wird
			'default'                 => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				'tl_class'            => 'w50'
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'complete' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['complete'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'default'                 => '',
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'imageSize_flags' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['imageSize_flags'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array
			(
				'rgxp'                => 'natural',
				'includeBlankOption'  => true,
				'nospace'             => true,
				'helpwizard'          => true,
				'tl_class'            => 'w50'
			),
			'options_callback'        => array('tl_teamtournament', 'getBildgroessen'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageSize_lineup' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['imageSize_lineup'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array
			(
				'rgxp'                => 'natural',
				'includeBlankOption'  => true,
				'nospace'             => true,
				'helpwizard'          => true,
				'tl_class'            => 'w50'
			),
			'options_callback'        => array('tl_teamtournament', 'getBildgroessen'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageSize_results' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['imageSize_results'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array
			(
				'rgxp'                => 'natural',
				'includeBlankOption'  => true,
				'nospace'             => true,
				'helpwizard'          => true,
				'tl_class'            => 'w50 clr'
			),
			'options_callback'        => array('tl_teamtournament', 'getBildgroessen'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_teamtournament']['published'],
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
 * Rückrufe des Datenbereichs tl_teamtournament.
 *
 * Die Klasse erbt von Contao\Backend, weil Contao 5 keine globalen
 * Klassenaliasse mehr registriert; ein blankes "extends Backend" bräche dort
 * mit einem Fatal error ab.
 */
class tl_teamtournament extends Backend
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
	 * Klassennamen ab. Die Rückrufe holen den Benutzer jetzt selbst über
	 * BackendUser::getInstance().
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Liefert die Länderliste für die Auswahl des Austragungslandes.
	 *
	 * Ersetzt das frühere System::getCountries(), das es in Contao 5 nicht mehr
	 * gibt. Wichtig ist das Kleinschreiben der Schlüssel: Der Dienst gibt sie
	 * groß zurück ('DE'), die alte Methode hatte sie klein gemacht ('de') — und
	 * genau so stehen sie in den vorhandenen Datensätzen. Ohne die Umwandlung
	 * fände die Auswahlliste den gespeicherten Wert nicht wieder.
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
	 * Liefert die im System hinterlegten Bildgrößen als Auswahlliste.
	 *
	 * Beschränkt auf die Größen, die der angemeldete Benutzer sehen darf. Der
	 * Dienst heißt seit Contao 5 "contao.image.sizes"; unter Contao 4.13 ist
	 * "contao.image.image_sizes" nur noch ein Alias darauf, der alte Name führt
	 * in Contao 5 dagegen zu einem Fehler.
	 *
	 * @return array<string, mixed> Die Bildgrößen, nach Gruppen sortiert
	 */
	public function getBildgroessen(): array
	{
		return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
	}

	/**
	 * Zeigt den Knopf „Turnier bearbeiten" nur bei ausreichenden Rechten.
	 *
	 * Sichtbar ist er für Administratoren und für Benutzer, denen mindestens
	 * ein Feld dieser Tabelle freigegeben ist. Andernfalls erscheint das
	 * ausgegraute Symbol, das beide Contao-Fassungen als «header_.svg»
	 * mitbringen.
	 *
	 * Die alte Rückruf-Signatur wird von Contao 5 weiterhin bedient: Dort prüft
	 * der Operations-Erzeuger, ob der Rückruf genau einen Parameter vom Typ
	 * DataContainerOperation erwartet, und geht sonst den bisherigen Weg mit
	 * den Einzelwerten.
	 *
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament
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
		$blnErlaubt = $objUser->isAdmin || \count(preg_grep('/^tl_teamtournament::/', (array) $objUser->alexf)) > 0;

		return $this->generateButton($blnErlaubt, $row, $href, $label, $title, $icon, $attributes);
	}

	/**
	 * Zeigt den Knopf „Turnier kopieren" nur bei ausreichenden Rechten.
	 *
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament
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
	 * Zeigt den Knopf „Turnier löschen" nur bei ausreichenden Rechten.
	 *
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament
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
	 * Die drei Rückrufe oben unterschieden sich nur in der Rechteprüfung; der
	 * Rest war dreimal dieselbe lange Zeile.
	 *
	 * @param bool                 $blnErlaubt Ergebnis der Rechteprüfung
	 * @param array<string, mixed> $row        Der Datensatz aus tl_teamtournament
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
			// Beide Fassungen bringen zu jedem Operationssymbol eine
			// ausgegraute Ausführung mit angehängtem Unterstrich mit
			return Image::getHtml(preg_replace('/\.(gif|svg)$/i', '_.svg', $icon)).' ';
		}

		return '<a href="'.self::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}

	/**
	 * Wandelt einen Datumswert aus der Datenbank in die Anzeigeform um.
	 *
	 * Die Turnierdaten liegen als Zahl JJJJMMTT vor, unvollständige Angaben
	 * entsprechend kürzer (JJJJMM oder JJJJ).
	 *
	 * Bewusst nicht über Schachbulle\ContaoHelperBundle\Classes\Helper: Dessen
	 * getDate()/putDate() füllen fehlende Stellen mit Nullen auf (2026 wird zu
	 * 20260000), während hier seit jeher die kürzere Zahl gespeichert wurde.
	 * Ein Wechsel würde die vorhandenen Turnierdaten falsch lesen.
	 *
	 * @param mixed $varValue Der Wert aus der Datenbank
	 *
	 * @return string Das Datum als 'TT.MM.JJJJ', 'MM.JJJJ' oder 'JJJJ'; eine
	 *                leere Zeichenkette bei jeder anderen Länge
	 */
	public function getDate($varValue): string
	{
		// Je nach Datenbanktreiber kommt der Wert als int oder als String;
		// strlen() und substr() verlangen unter strict_types einen String
		$strRoh = (string) $varValue;

		switch (\strlen($strRoh))
		{
			case 8: // JJJJMMTT
				return substr($strRoh, 6, 2).'.'.substr($strRoh, 4, 2).'.'.substr($strRoh, 0, 4);

			case 6: // JJJJMM
				return substr($strRoh, 4, 2).'.'.substr($strRoh, 0, 4);

			case 4: // JJJJ
				return $strRoh;

			default:
				return '';
		}
	}

	/**
	 * Wandelt ein eingegebenes Datum in die Datenbankschreibweise um.
	 *
	 * Gegenstück zu getDate(); erkannt wird die Form allein an der Länge.
	 *
	 * @param mixed $varValue Die Eingabe aus der Maske
	 *
	 * @return string Das Datum als JJJJMMTT, JJJJMM oder JJJJ; '0' bei jeder
	 *                anderen Länge, also auch bei leerer Eingabe
	 */
	public function putDate($varValue): string
	{
		$strRoh = trim((string) $varValue);

		switch (\strlen($strRoh))
		{
			case 10: // TT.MM.JJJJ
				return substr($strRoh, 6, 4).substr($strRoh, 3, 2).substr($strRoh, 0, 2);

			case 7: // MM.JJJJ
				return substr($strRoh, 3, 4).substr($strRoh, 0, 2);

			case 4: // JJJJ
				return $strRoh;

			default:
				return '0';
		}
	}

	/**
	 * Bereitet die Spalten der Listenansicht auf.
	 *
	 * Das Enddatum steht in der Datenbank als Zahl und wird lesbar gemacht, der
	 * Titel fett gesetzt und der Haken „Wettbewerb komplett" als Symbol
	 * ausgegeben.
	 *
	 * @param array<string, mixed> $row   Der Datensatz aus tl_teamtournament
	 * @param string               $label Die vorbereitete Beschriftung, hier ungenutzt
	 * @param DataContainer        $dc    Der aufrufende Data Container, hier ungenutzt
	 * @param array<int, string>   $args  Die Spalteninhalte in der Reihenfolge der
	 *                                    list.label.fields
	 *
	 * @return array<int, string> Die geänderten Spalteninhalte
	 */
	public function listTournaments($row, $label, DataContainer $dc, $args): array
	{
		$args[0] = $this->getDate($args[0]);
		$args[1] = '<b>'.$args[1].'</b>';

		// Controller::generateImage() gibt es in Contao 5 nicht mehr,
		// Image::getHtml() dagegen in beiden Fassungen
		$args[4] = $row['complete']
			? Image::getHtml('ok.svg', 'Wettbewerb komplett')
			: Image::getHtml('delete.svg', 'Wettbewerb nicht komplett');

		return $args;
	}
}
