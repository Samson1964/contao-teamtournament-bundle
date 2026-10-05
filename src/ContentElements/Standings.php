<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\ContentElements;

use Contao\ContentElement;
use Contao\Database;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Rangliste;

/**
 * Inhaltselement „Tabelle".
 *
 * Gibt die Mannschaften eines Turniers nach Rang sortiert aus, mit Kämpfen,
 * Siegen, Unentschieden, Niederlagen, Mannschafts- und Brettpunkten.
 *
 * Gerechnet und gesetzt wird in Classes\Rangliste; hier werden nur das
 * Turnier geladen und die Flaggen vorbereitet, für die es Contao braucht.
 */
class Standings extends ContentElement
{
	use TurniertabelleTrait;

	/**
	 * Name des Frontend-Templates.
	 *
	 * @var string
	 */
	protected $strTemplate = 'ce_tt-standings';

	/**
	 * Baut die Tabelle zusammen.
	 */
	protected function compile()
	{
		$objTurnier = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament WHERE id=?")
			->execute($this->teamtournament_turnier);

		if (!$objTurnier->numRows)
		{
			$this->Template->content = '';

			return;
		}

		$arrTabelle = Rangliste::fuerTurnier((int) $this->teamtournament_turnier, (int) $this->teamtournament_runde ?: null);

		if (!$arrTabelle)
		{
			$this->Template->content = '';

			return;
		}

		$this->Template->content = Rangliste::markupTabelle(
			$arrTabelle,
			$objTurnier->language == 'en',
			$this->getFlaggen($arrTabelle, $objTurnier)
		);
	}
}
