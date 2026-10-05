<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\ContentElements;

use Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer;

/**
 * Gemeinsames der beiden Tabellen-Inhaltselemente.
 *
 * Tabelle und Kreuztabelle brauchen beide die Logos der Mannschaften in
 * derselben Form. Die Bilderzeugung selbst steht in Classes\Helfer; hier wird
 * nur entschieden, ob und in welcher Größe sie überhaupt entstehen.
 */
trait TurniertabelleTrait
{
	/**
	 * Erzeugt die Logos der Mannschaften für eine Tabelle.
	 *
	 * Am Turnier lassen sich die Mannschaftsbilder abschalten; dann bleibt das
	 * Feld leer und in der Tabelle steht nur der Name.
	 *
	 * @param array<int, array<string, mixed>> $arrTabelle Die gerechnete Tabelle
	 * @param object                           $objTurnier Der Turnierdatensatz
	 *
	 * @return array<int, string> Mannschaftskennung => Bild-Markup
	 */
	private function getFlaggen(array $arrTabelle, $objTurnier): array
	{
		if ($objTurnier->hideImages_flags)
		{
			return array();
		}

		$arrFlaggen = array();

		foreach ($arrTabelle as $arrZeile)
		{
			$arrFlaggen[$arrZeile['id']] = Helfer::bild($arrZeile['flag'], $objTurnier->imageSize_flags, 'tt'.$arrZeile['id']);
		}

		return $arrFlaggen;
	}
}
