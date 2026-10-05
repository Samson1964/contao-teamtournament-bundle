<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\Classes;

use Contao\Database;
use Contao\Input;
use Contao\System;

/**
 * Sorgt dafür, dass der Zurück-Knopf in den Kindlisten zur Elternliste führt.
 *
 * Hintergrund: In Contao 4.13 baut DC_Table den Knopf aus
 * System::getReferer(true, $ptable). Diese Methode sucht im Sitzungsspeicher
 * zuerst einen Eintrag für die genannte Tabelle, dann die zuletzt besuchte
 * Adresse — und wenn beides fehlt, landet sie bei
 * router->generate('contao_backend'), also im Dashboard. Genau das ist der
 * gemeldete Sprung ins Dashboard.
 *
 * Tabellenbezogene Einträge schreibt Contao 4.13 aber gar nicht mehr: Der
 * StoreRefererListener kennt nur noch „current" und „last". Damit hängt der
 * Zurück-Knopf allein daran, welche Seite zufällig vorher offen war — nach
 * einer Weiterleitung, einem Lesezeichen oder dem ersten Aufruf nach der
 * Anmeldung führt er ins Leere.
 *
 * Diese Klasse füllt den tabellenbezogenen Eintrag selbst. Der Zurück-Knopf
 * zeigt damit immer auf die Liste, aus der die Kindliste stammt.
 *
 * Unter Contao 5 passiert nichts: Dort rechnet der Dienst
 * contao.data_container.dca_url_analyzer den Weg aus der DCA aus und liest
 * den Sitzungsspeicher nicht mehr.
 */
final class Rueckweg
{
	/**
	 * Name des Backend-Moduls.
	 */
	public const MODUL = 'teamtournament';

	/**
	 * Zu welcher Liste eine Kindtabelle zurückführt.
	 *
	 * Wert ist die Tabelle der Elternliste, oder null für die Übersicht des
	 * Moduls (die ohne Tabellenparameter aufgerufen wird).
	 */
	private const ELTERN = array
	(
		'tl_teamtournament_teams'   => null,
		'tl_teamtournament_matches' => null,
		'tl_teamtournament_players' => 'tl_teamtournament_teams',
		'tl_teamtournament_games'   => 'tl_teamtournament_matches',
	);

	/**
	 * Baut die Adressparameter der Liste, zu der zurückgesprungen wird.
	 *
	 * @param string   $strTabelle Die gerade angezeigte Kindtabelle
	 * @param int|null $intElternId Kennung des Datensatzes, dessen Liste das Ziel
	 *                              ist — bei den Spielern die Mannschaft, bei den
	 *                              Partien der Wettkampf; für die Modulübersicht
	 *                              ohne Bedeutung
	 *
	 * @return string Die Parameter ohne führendes Fragezeichen, etwa
	 *                'do=teamtournament&table=tl_teamtournament_teams&id=7';
	 *                eine leere Zeichenkette, wenn die Tabelle nicht zum Modul
	 *                gehört oder die nötige Kennung fehlt
	 */
	public static function parameter(string $strTabelle, ?int $intElternId): string
	{
		if (!\array_key_exists($strTabelle, self::ELTERN))
		{
			return '';
		}

		$strEltern = self::ELTERN[$strTabelle];

		if (null === $strEltern)
		{
			return 'do='.self::MODUL;
		}

		if (!$intElternId)
		{
			return '';
		}

		return 'do='.self::MODUL.'&table='.$strEltern.'&id='.$intElternId;
	}

	/**
	 * Hinterlegt den Rückweg im Sitzungsspeicher (onload_callback).
	 *
	 * Läuft nur in Listenansichten: Beim Bearbeiten eines Datensatzes baut
	 * Contao den Zurück-Knopf aus einer anderen Quelle, und dort stimmt er.
	 *
	 * @param mixed $dc Der aufrufende Data Container
	 */
	public static function merken($dc = null): void
	{
		// Contao 5 rechnet den Weg selbst aus
		if (System::getContainer()->has('contao.data_container.dca_url_analyzer'))
		{
			return;
		}

		if (null === $dc || Input::get('act'))
		{
			return;
		}

		$strTabelle = (string) $dc->table;
		$strEltern = self::ELTERN[$strTabelle] ?? null;

		if (!\array_key_exists($strTabelle, self::ELTERN))
		{
			return;
		}

		$strParameter = self::parameter($strTabelle, self::elternId($strTabelle, (int) $dc->currentPid));

		if ('' === $strParameter)
		{
			return;
		}

		$objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

		if (null === $objRequest || !$objRequest->hasSession())
		{
			return;
		}

		$objSession = $objRequest->getSession();
		$strSchluessel = Input::get('popup') ? 'popupReferer' : 'referer';
		$strKennung = (string) $objRequest->attributes->get('_contao_referer_id');
		$arrReferer = $objSession->get($strSchluessel);

		if (!\is_array($arrReferer) || '' === $strKennung || !isset($arrReferer[$strKennung]))
		{
			return;
		}

		// Die Zieltabelle ist die Elterntabelle: Mit ihr fragt DC_Table den
		// Rückweg ab (getReferer(true, $this->ptable)). Für die beiden obersten
		// Kindtabellen ist das tl_teamtournament, dessen Liste ohne
		// Tabellenparameter aufgerufen wird.
		$strZieltabelle = $strEltern ?? 'tl_teamtournament';

		$arrReferer[$strKennung][$strZieltabelle] = System::getContainer()->get('router')->generate('contao_backend').'?'.$strParameter;

		$objSession->set($strSchluessel, $arrReferer);
	}

	/**
	 * Ermittelt die Kennung des Datensatzes, dessen Liste das Ziel ist.
	 *
	 * Bei den Spielern ist die angezeigte Liste die einer Mannschaft; zurück
	 * geht es zur Mannschaftsliste des Turniers, also zur Kennung des Turniers.
	 * Bei den Partien entsprechend zum Wettkampf des Turniers.
	 *
	 * @param string $strTabelle  Die angezeigte Kindtabelle
	 * @param int    $intCurrentPid Kennung des Elterndatensatzes der Liste
	 *
	 * @return int|null Die gesuchte Kennung, oder null, wenn sie sich nicht
	 *                  ermitteln ließ
	 */
	private static function elternId(string $strTabelle, int $intCurrentPid): ?int
	{
		if (null === (self::ELTERN[$strTabelle] ?? null))
		{
			return null;
		}

		if (!$intCurrentPid)
		{
			return null;
		}

		$strQuelle = 'tl_teamtournament_players' === $strTabelle ? 'tl_teamtournament_teams' : 'tl_teamtournament_matches';

		$objEltern = Database::getInstance()
			->prepare("SELECT pid FROM ".$strQuelle." WHERE id=?")
			->limit(1)
			->execute($intCurrentPid);

		return $objEltern->numRows ? (int) $objEltern->pid : null;
	}
}
