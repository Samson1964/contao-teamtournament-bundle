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
use Contao\StringUtil;
use Contao\System;

/**
 * Gemeinsame Hilfsfunktionen der drei Inhaltselemente.
 *
 * Aufstellung, Mannschaftsführer und Rundenübersicht brauchen dieselben zwei
 * Dinge: ein Bild aus dem Dateibaum und das Alter eines Spielers zum
 * Turnierbeginn. Beides lag früher als Kopie in jeder der drei Klassen.
 */
class Helfer
{
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
