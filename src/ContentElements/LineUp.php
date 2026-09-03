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
use Contao\StringUtil;
use Schachbulle\ContaoHelperBundle\Classes\Helper;
use Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer;

/**
 * Inhaltselement „Aufstellung einer Mannschaft".
 *
 * Gibt alle Spieler einer Mannschaft mit Foto, Alter, Elo-Zahl, Brett und
 * Weblinks aus. Die Elternklasse wird voll qualifiziert angegeben, weil
 * Contao 5 keine globalen Klassenaliasse mehr registriert — ein blankes
 * "extends \ContentElement" bräche dort schon beim Laden der Klasse ab.
 */
class LineUp extends ContentElement
{
	/**
	 * Name des Frontend-Templates.
	 *
	 * Der Name enthält keinen Schrägstrich und gilt Contao 5 deshalb als
	 * Legacy-Template; die mitgelieferte .html5-Datei bedient damit beide
	 * Fassungen.
	 *
	 * @var string
	 */
	protected $strTemplate = 'ce_tt-lineup';

	/**
	 * Baut die Tabelle der Aufstellung zusammen.
	 *
	 * Gelesen werden die gewählte Mannschaft, das zugehörige Turnier und alle
	 * Spieler dieser Mannschaft. Das Ergebnis landet als fertiges Markup in
	 * der Template-Variablen „content"; das Template selbst enthält nur den
	 * durchsuchbaren Block.
	 *
	 * Fehlt die Mannschaft — etwa weil sie nach dem Anlegen des Inhaltselements
	 * gelöscht wurde —, bleibt die Ausgabe leer, statt mit einer Warnung
	 * abzubrechen.
	 */
	protected function compile()
	{
		$objMannschaft = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament_teams WHERE id=?")
			->execute($this->teamtournament_lineup);

		if (!$objMannschaft->numRows)
		{
			$this->Template->content = '';

			return;
		}

		$objTurnier = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament WHERE id=?")
			->execute($objMannschaft->pid);

		$objSpieler = Database::getInstance()
			->prepare("SELECT * FROM tl_teamtournament_players WHERE pid=?")
			->execute($this->teamtournament_lineup);

		$content = '<table>';
		$content .= '<tr>';

		if ($objTurnier->language == 'en')
		{
			$content .= '<th>Photo</th><th>Player</th><th>Age</th><th>Elo</th><th>Board</th><th>Weblinks</th>';
		}
		else
		{
			$content .= '<th>Foto</th><th>Spieler</th><th>Alter</th><th>Elo</th><th>Brett</th><th>Weblinks</th>';
		}

		$content .= '</tr>';

		while ($objSpieler->next())
		{
			// Alter zum ersten Turniertag; beide Datumswerte liegen in der
			// Datenbank als Zahl JJJJMMTT und müssen erst lesbar gemacht werden
			$alter = Helfer::alter(
				(string) Helper::getDate($objSpieler->birthday),
				(string) Helper::getDate($objTurnier->fromDate)
			);

			$name = trim($objSpieler->fide_title.' '.$objSpieler->prename.' '.$objSpieler->surname);

			// Weblinks aus dem MultiColumnWizard
			$weblinks = StringUtil::deserialize($objSpieler->weblinks);
			$links = '';

			if (\is_array($weblinks))
			{
				foreach ($weblinks as $weblink)
				{
					if (!empty($weblink['url']))
					{
						$links .= '<a href="'.$weblink['url'].'" target="_blank" rel="noopener">'.$weblink['title'].'</a> ';
					}
				}
			}

			// Foto: eigenes Bild des Spielers, sonst das Standardbild aus den
			// Einstellungen, passend zum Geschlecht des Turniers
			$bild_id = $objSpieler->singleSRC ?: Helfer::standardbild($objTurnier->gender);
			$bild = Helfer::bild($bild_id, $objTurnier->imageSize_lineup, 'tt'.$objSpieler->id);

			$content .= '<tr>';
			$content .= '<td>'.$bild.'</td>';
			$content .= '<td>'.$name.'</td>';
			$content .= '<td>'.$alter.'</td>';
			$content .= '<td><a href="https://ratings.fide.com/profile/'.$objSpieler->fide_id.'">'.$objSpieler->fide_elo.'</a></td>';
			$content .= '<td>'.($objSpieler->board ?: '').'</td>';
			$content .= '<td>'.trim($links).'</td>';
			$content .= '</tr>';
		}

		$content .= '</table>';

		$this->Template->content = $content;
	}
}
