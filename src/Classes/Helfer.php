<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\Classes;

use Contao\Config;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Schachbulle\ContaoFlaggenBundle\Classes\Flaggen;

/**
 * Gemeinsame Hilfsfunktionen für Inhaltselemente und Backend.
 *
 * Aufstellung, Mannschaftsführer und Rundenübersicht brauchen dieselben zwei
 * Dinge: ein Bild aus dem Dateibaum und das Alter eines Spielers zum
 * Turnierbeginn. Beides lag früher als Kopie in jeder der drei Klassen.
 *
 * Dazu kommen die Datumsformatierung und der Kopfbereich eines Wettbewerbs,
 * die in mehreren Backend-Listen gleich aussehen sollen.
 */
class Helfer
{
	/**
	 * Öffentlicher Pfad der Flaggensymbole (schachbulle/contao-flaggen-bundle).
	 */
	public const FLAGGENPFAD = 'bundles/contaoflaggen/flags/';

	/**
	 * Formatiert ein gespeichertes Turnierdatum für die Anzeige.
	 *
	 * Die Datumsfelder von tl_teamtournament sind ganze Zahlen und dürfen laut
	 * Feldhilfe unvollständig sein. Im Bestand kommen zwei Schreibweisen vor:
	 *
	 * - verkürzt, wie sie tl_teamtournament::putDate() schreibt: JJJJMMTT,
	 *   JJJJMM oder JJJJ
	 * - mit Nullen aufgefüllt, wie sie das Helper-Bundle schreibt: JJJJMM00
	 *   oder JJJJ0000
	 *
	 * Beide ergeben dieselbe Anzeige: so viele Bestandteile, wie gefüllt sind.
	 * Die Backend-Liste der Wettbewerbe und der Kopf der Kindlisten gehen
	 * beide durch diese Funktion, damit sie nicht auseinanderlaufen.
	 *
	 * @param mixed $varWert Der Wert aus der Datenbank, als Zahl oder Zeichenkette
	 *
	 * @return string 'TT.MM.JJJJ', 'MM.JJJJ' oder 'JJJJ'; eine leere Zeichenkette
	 *                für 0, null und ''. Ein Wert, der keiner der Schreibweisen
	 *                entspricht, kommt unverändert zurück, damit er nicht
	 *                stillschweigend aus der Anzeige verschwindet.
	 */
	public static function datum($varWert): string
	{
		$strRoh = trim((string) $varWert);

		if ('' === $strRoh || '0' === $strRoh)
		{
			return '';
		}

		if (!ctype_digit($strRoh))
		{
			return $strRoh;
		}

		switch (\strlen($strRoh))
		{
			case 8: // JJJJMMTT, Monat und Tag dürfen 00 sein
				$strJahr = substr($strRoh, 0, 4);
				$strMonat = substr($strRoh, 4, 2);
				$strTag = substr($strRoh, 6, 2);
				break;

			case 6: // JJJJMM, der Monat darf 00 sein
				$strJahr = substr($strRoh, 0, 4);
				$strMonat = substr($strRoh, 4, 2);
				$strTag = '00';
				break;

			case 4: // JJJJ
				$strJahr = $strRoh;
				$strMonat = '00';
				$strTag = '00';
				break;

			default:
				return $strRoh;
		}

		if ('00' === $strMonat)
		{
			return $strJahr;
		}

		if ('00' === $strTag)
		{
			return $strMonat.'.'.$strJahr;
		}

		return $strTag.'.'.$strMonat.'.'.$strJahr;
	}

	/**
	 * Gibt das Land als Flaggensymbol mit dem Landesnamen als Tooltip aus.
	 *
	 * Die Symbole stammen aus dem Bundle schachbulle/contao-flaggen-bundle.
	 * Dessen eigene Methode getFlagge() wird bewusst nicht benutzt: Sie
	 * erwartet dreistellige Kürzel, und zwar in der Schreibweise der
	 * Schachverbände ('GER'), während Contao das Land zweistellig nach ISO
	 * ablegt ('de'). Eine Umrechnung über symfony/intl liefert dagegen 'DEU'
	 * und findet die deutsche Flagge nicht.
	 *
	 * Die Dateinamen des Bundles sind aber genau die zweistelligen Kürzel.
	 * Deshalb wird die Datei unmittelbar angesprochen und der Name aus Contaos
	 * Länderdienst geholt — der ihn in der Sprache des Backends liefert.
	 *
	 * Gibt es zu einem Land kein Symbol oder fehlt das Flaggen-Bundle, erscheint
	 * der ausgeschriebene Landesname. Die Spalte bleibt so in jedem Fall lesbar.
	 *
	 * @param string|null $strLand   Länderkürzel aus der Datenbank, zweistellig
	 * @param int         $intBreite Breite der Flagge in Bildpunkten
	 *
	 * @return string Das Markup der Flagge, sonst der Landesname oder — wenn
	 *                auch der unbekannt ist — das Kürzel; leer ohne Land
	 */
	public static function flagge(?string $strLand, int $intBreite = 20): string
	{
		$strKuerzel = strtolower(trim((string) $strLand));

		if ('' === $strKuerzel)
		{
			return '';
		}

		$strName = self::landesname(strtoupper($strKuerzel));

		if (null === self::flaggendatei($strKuerzel))
		{
			return $strName;
		}

		return sprintf(
			'<img src="%s%s.svg" width="%d" alt="%s" title="%s">',
			self::FLAGGENPFAD,
			$strKuerzel,
			$intBreite,
			StringUtil::specialchars($strName),
			StringUtil::specialchars($strName)
		);
	}

	/**
	 * Sucht die Flaggendatei eines Landes im Flaggen-Bundle.
	 *
	 * Gesucht wird im Paketverzeichnis, nicht unter public/: Ob Contao die
	 * Bundle-Dateien nach web/ oder public/ verknüpft hat, ist von der
	 * Installation abhängig, das Paketverzeichnis findet sich dagegen immer
	 * über die Klasse selbst.
	 *
	 * @param string $strKuerzel Länderkürzel, zweistellig und klein
	 *
	 * @return string|null Der Pfad der SVG-Datei, oder null, wenn das Bundle
	 *                     fehlt oder zu diesem Land kein Symbol mitbringt
	 */
	private static function flaggendatei(string $strKuerzel): ?string
	{
		if (!class_exists(Flaggen::class) || !preg_match('/^[a-z]{2}$/', $strKuerzel))
		{
			return null;
		}

		try
		{
			$strKlasse = (new \ReflectionClass(Flaggen::class))->getFileName();
		}
		catch (\ReflectionException $e)
		{
			return null;
		}

		$strPfad = \dirname((string) $strKlasse).'/../Resources/public/flags/'.$strKuerzel.'.svg';

		return is_file($strPfad) ? $strPfad : null;
	}

	/**
	 * Liefert den ausgeschriebenen Namen eines Landes.
	 *
	 * @param string $strKuerzel Länderkürzel, zweistellig und groß
	 *
	 * @return string Der Name in der Sprache des Backends; das Kürzel, wenn das
	 *                Land unbekannt ist oder der Dienst nicht bereitsteht (etwa
	 *                in einem Prüfstand ohne Contao-Behälter)
	 */
	public static function landesname(string $strKuerzel): string
	{
		// class_exists(), damit die Methode auch in Unit-Tests ohne Contao läuft
		if (!class_exists(System::class))
		{
			return $strKuerzel;
		}

		$objContainer = System::getContainer();

		if (null === $objContainer || !$objContainer->has('contao.intl.countries'))
		{
			return $strKuerzel;
		}

		$arrLaender = $objContainer->get('contao.intl.countries')->getCountries();

		return $arrLaender[$strKuerzel] ?? $strKuerzel;
	}

	/**
	 * Baut den Kopfbereich für die Kindlisten eines Wettbewerbs.
	 *
	 * Mannschaftsliste und Wettkampfliste hängen beide an tl_teamtournament
	 * und zeigen darum denselben Kopf: Turniername, Beginn, Ende, Ort und
	 * Land. Contao selbst gibt die Datumsfelder dort roh aus („20260916"),
	 * weil sie keine rgxp 'date' tragen — es sind ja keine Zeitstempel,
	 * sondern Zahlen in der Form JJJJMMTT.
	 *
	 * Der Kopf wird deshalb aus dem Datensatz neu gebaut, statt die von Contao
	 * vorbereiteten Werte umzuschreiben: Deren Schlüssel sind die übersetzten
	 * Feldbeschriftungen, nicht die Feldnamen, und ließen sich nur über den
	 * deutschen Wortlaut zuordnen.
	 *
	 * @param int $intTurnier Kennung des Wettbewerbs
	 *
	 * @return array<string, string> Beschriftung => Wert, leere Werte ausgelassen;
	 *                               ein leeres Feld, wenn der Wettbewerb nicht
	 *                               gefunden wurde
	 */
	public static function kopfWettbewerb(int $intTurnier): array
	{
		if (!$intTurnier)
		{
			return array();
		}

		$objTurnier = Database::getInstance()
			->prepare("SELECT title, fromDate, toDate, place, country FROM tl_teamtournament WHERE id=?")
			->limit(1)
			->execute($intTurnier);

		if (!$objTurnier->numRows)
		{
			return array();
		}

		// Die Beschriftungen stammen aus der Sprachdatei der Elterntabelle
		System::loadLanguageFile('tl_teamtournament');

		$strLand = (string) $objTurnier->country;

		if ('' !== $strLand)
		{
			// Der Dienst führt die Kürzel groß, gespeichert sind sie klein
			$arrLaender = System::getContainer()->get('contao.intl.countries')->getCountries();
			$strLand = $arrLaender[strtoupper($strLand)] ?? $strLand;
		}

		$arrWerte = array
		(
			'title'    => (string) $objTurnier->title,
			'fromDate' => self::datum($objTurnier->fromDate),
			'toDate'   => self::datum($objTurnier->toDate),
			'place'    => (string) $objTurnier->place,
			'country'  => $strLand,
		);

		$arrKopf = array();

		foreach ($arrWerte as $strFeld => $strWert)
		{
			if ('' === $strWert)
			{
				continue;
			}

			$strBeschriftung = $GLOBALS['TL_LANG']['tl_teamtournament'][$strFeld][0] ?? $strFeld;
			$arrKopf[$strBeschriftung] = $strWert;
		}

		return $arrKopf;
	}

	/**
	 * Erzeugt das Markup eines Bildes aus dem Dateibaum.
	 *
	 * Früher lief das über Controller::addImageToTemplate(). Die Methode gibt
	 * es in Contao 5 nicht mehr; ihr Nachfolger ist der Bilderdienst
	 * "contao.image.studio", den es in Contao 4.13 wie in Contao 5 gibt und
	 * der in beiden Fassungen öffentlich ist.
	 *
	 * buildIfResourceExists() liefert null, wenn die Datei nicht mehr im
	 * Dateisystem liegt oder die Kennung unbekannt ist. Damit entfällt die
	 * frühere Prüfung über FilesModel::findByUuid(), die bei einem gelöschten
	 * Bild eine Warnung „Attempt to read property path on null" auslöste.
	 *
	 * @param mixed  $varBild    Kennung der Datei aus dem Dateibaum, entweder als
	 *                           16 Byte langer Binärwert oder in der lesbaren
	 *                           Schreibweise; auch ein Pfad oder eine ID wird
	 *                           erkannt. Ein leerer Wert liefert eine leere
	 *                           Zeichenkette
	 * @param mixed  $varGroesse Bildgröße, wie sie im Turnier hinterlegt ist:
	 *                           serialisiertes Feld, Feld oder Name einer
	 *                           Bildgröße; null nimmt die Originalgröße
	 * @param string $strGruppe  Kennung der Lightbox-Gruppe, damit die Bilder
	 *                           eines Datensatzes zusammen geblättert werden
	 *
	 * @return string Das fertige <figure>-Element, oder eine leere Zeichenkette,
	 *                wenn kein Bild hinterlegt ist oder die Datei fehlt
	 */
	public static function bild($varBild, $varGroesse, string $strGruppe): string
	{
		if (!$varBild)
		{
			return '';
		}

		// Die Bildgröße liegt in der Datenbank als serialisiertes Feld vor
		$varGroesse = \is_string($varGroesse) ? StringUtil::deserialize($varGroesse) : $varGroesse;

		$objFigure = System::getContainer()
			->get('contao.image.studio')
			->createFigureBuilder()
			->from($varBild)
			->setSize($varGroesse)
			->enableLightbox(true)
			->setLightboxGroupIdentifier($strGruppe)
			->buildIfResourceExists();

		if (null === $objFigure)
		{
			return '';
		}

		// applyLegacyTemplateData() füllt dieselben Eigenschaften, die früher
		// addImageToTemplate() gesetzt hat (src, alt, imageTitle, caption, href)
		$objBild = new \stdClass();
		$objFigure->applyLegacyTemplateData($objBild);

		$strMarkup = '<figure class="image_container">';
		$strMarkup .= '<a href="'.($objBild->href ?? $objBild->src).'" data-lightbox="'.StringUtil::specialchars($strGruppe).'">';
		$strMarkup .= '<img src="'.$objBild->src.'" alt="'.($objBild->alt ?? '').'" title="'.($objBild->imageTitle ?? '').'">';
		$strMarkup .= '</a>';

		if (!empty($objBild->caption))
		{
			$strMarkup .= '<figcaption class="caption">'.$objBild->caption.'</figcaption>';
		}

		return $strMarkup.'</figure>';
	}

	/**
	 * Erzeugt ein quadratisches Vorschaubild für die Backend-Listen.
	 *
	 * Anders als bild() kommt hier kein <figure> und keine Lightbox heraus,
	 * sondern ein einzelnes <img> in fester Kantenlänge. Zugeschnitten wird
	 * mittig ('crop'), damit die Zeilenhöhe der Liste gleich bleibt, egal ob
	 * das Foto hoch oder quer ist.
	 *
	 * Der zurückgegebene Pfad ist relativ zum Projektverzeichnis. Im Backend
	 * genügt das, weil die Seite ein <base>-Element trägt.
	 *
	 * @param mixed $varBild   Kennung der Datei aus dem Dateibaum, binär oder in
	 *                         lesbarer Schreibweise; ein leerer Wert liefert eine
	 *                         leere Zeichenkette
	 * @param int   $intKante  Kantenlänge in Bildpunkten
	 * @param string $strTitel Titel-Attribut, etwa der Name des Spielers
	 *
	 * @return string Das <img>-Element, oder eine leere Zeichenkette, wenn kein
	 *                Bild hinterlegt ist oder die Datei fehlt
	 */
	public static function miniatur($varBild, int $intKante = 16, string $strTitel = ''): string
	{
		if (!$varBild)
		{
			return '';
		}

		$objFigure = System::getContainer()
			->get('contao.image.studio')
			->createFigureBuilder()
			->from($varBild)
			->setSize(array($intKante, $intKante, 'crop'))
			->buildIfResourceExists();

		if (null === $objFigure)
		{
			return '';
		}

		// getImageSrc() gibt es in beiden Fassungen und liefert die fertige
		// Adresse, notfalls mit dem eingestellten Vorspann für statische Dateien
		$strQuelle = $objFigure->getImage()->getImageSrc();

		if ('' === $strQuelle)
		{
			return '';
		}

		return sprintf(
			'<img src="%s" width="%d" height="%d" alt="" title="%s" style="vertical-align:middle">',
			$strQuelle,
			$intKante,
			$intKante,
			StringUtil::specialchars($strTitel)
		);
	}

	/**
	 * Liefert die Kennung des Standardbildes aus den Einstellungen.
	 *
	 * Turniere der Frauen und der Männer haben je ein eigenes Ersatzbild, das
	 * einspringt, wenn zu einem Spieler kein Foto hinterlegt ist. Gelesen wird
	 * über Config::get() statt direkt aus $GLOBALS['TL_CONFIG'], weil die
	 * beiden Felder vor dem ersten Speichern der Einstellungen gar nicht
	 * vorhanden sind und der direkte Zugriff dann eine Warnung auslöst.
	 *
	 * @param string|null $strGeschlecht 'm' für ein Männer-, 'w' für ein
	 *                                   Frauenturnier; jeder andere Wert liefert
	 *                                   kein Bild
	 *
	 * @return mixed Die Dateikennung aus den Einstellungen, oder null, wenn dort
	 *               keine hinterlegt ist
	 */
	public static function standardbild($strGeschlecht)
	{
		if ('m' === $strGeschlecht)
		{
			return Config::get('teamtournament_defaultImageMen');
		}

		if ('w' === $strGeschlecht)
		{
			return Config::get('teamtournament_defaultImageWomen');
		}

		return null;
	}

	/**
	 * Ermittelt das Alter in vollen Jahren zu einem Stichtag.
	 *
	 * Gerechnet wird nicht mit Zeitstempeln, sondern mit der Zahl JJJJMMTT:
	 * Die Differenz zweier solcher Zahlen, ganzzahlig durch 10000 geteilt,
	 * ergibt genau die Zahl der vollendeten Lebensjahre. Das funktioniert auch
	 * für Geburtsdaten vor 1970, bei denen mktime() unter 32 Bit versagte.
	 *
	 * Unvollständige Geburtsdaten sind ausdrücklich erlaubt: Bei den
	 * Schachverbänden ist von älteren Spielern oft nur Monat und Jahr oder gar
	 * nur das Jahr bekannt. Fehlende Teile werden auf den 1. Januar gesetzt,
	 * das Alter ist dann die Obergrenze.
	 *
	 * @param string $strGeburtsdatum  Geburtsdatum als 'TT.MM.JJJJ', 'MM.JJJJ'
	 *                                 oder 'JJJJ'
	 * @param string $strReferenzdatum Stichtag als 'TT.MM.JJJJ', in der Regel der
	 *                                 erste Turniertag
	 *
	 * @return int|null Das Alter in Jahren, oder null, wenn eines der beiden
	 *                  Datumsangaben nicht in einer der genannten Formen vorliegt
	 */
	public static function alter($strGeburtsdatum, $strReferenzdatum): ?int
	{
		$arrGeburt = explode('.', trim((string) $strGeburtsdatum));

		switch (\count($arrGeburt))
		{
			case 1: // Nur JJJJ
				$strGeburtstag = $arrGeburt[0].'0101';
				break;

			case 2: // MM.JJJJ
				$strGeburtstag = $arrGeburt[1].$arrGeburt[0].'01';
				break;

			case 3: // TT.MM.JJJJ
				$strGeburtstag = $arrGeburt[2].$arrGeburt[1].$arrGeburt[0];
				break;

			default:
				return null;
		}

		$arrReferenz = explode('.', trim((string) $strReferenzdatum));

		switch (\count($arrReferenz))
		{
			case 1:
				$strReferenztag = $arrReferenz[0].'0101';
				break;

			case 2:
				$strReferenztag = $arrReferenz[1].$arrReferenz[0].'01';
				break;

			case 3:
				$strReferenztag = $arrReferenz[2].$arrReferenz[1].$arrReferenz[0];
				break;

			default:
				return null;
		}

		// Ohne Jahreszahl auf beiden Seiten ist die Rechnung sinnlos
		if (!ctype_digit($strGeburtstag) || !ctype_digit($strReferenztag) || 8 !== \strlen($strGeburtstag) || 8 !== \strlen($strReferenztag))
		{
			return null;
		}

		return intdiv((int) $strReferenztag - (int) $strGeburtstag, 10000);
	}
}
