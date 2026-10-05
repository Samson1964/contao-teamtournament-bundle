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
 * Inhaltselement „Kreuztabelle".
 *
 * Zeigt jede Mannschaft gegen jede, die Zeilen in der Reihenfolge der
 * Tabelle. In einer Zelle stehen die Brettpunkte aus Sicht der
 * Zeilenmannschaft; mehrfache Begegnungen stehen untereinander.
 *
 * Gerechnet und gesetzt wird in Classes\Rangliste.
 */
class CrossTable extends ContentElement
{
	use TurniertabelleTrait;

	/**
	 * Name des Frontend-Templates.
	 *
	 * @var string
	 */
	protected $strTemplate = 'ce_tt-crosstable';

	/**
	 * Baut die Kreuztabelle zusammen.
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

		$arrDaten = Rangliste::kreuztabelle((int) $this->teamtournament_turnier);

		if (!$arrDaten['tabelle'])
		{
			$this->Template->content = '';

			return;
		}

		$this->Template->content = Rangliste::markupKreuztabelle(
			$arrDaten,
			$objTurnier->language == 'en',
			$this->getFlaggen($arrDaten['tabelle'], $objTurnier)
		);
	}
}
