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
 * Inhaltselement „Mannschaftsführer".
 *
 * Gibt den Kapitän einer Mannschaft mit Foto, Alter, Elo-Zahl und Weblinks
 * aus. Die Daten des Kapitäns stehen nicht in der Spielertabelle, sondern
 * unmittelbar am Datensatz der Mannschaft.
 */
class Captain extends ContentElement
{
	/**
	 * Name des Frontend-Templates.
	 *
	 * @var string
	 */
	protected $strTemplate = 'ce_tt-captain';

	/**
	 * Baut die Tabelle mit dem Mannschaftsführer zusammen.
	 *
	 * Ist an der Mannschaft kein Nachname hinterlegt, gilt der Kapitän als
	 * nicht benannt und es erscheint nur die Kopfzeile. Fehlt die Mannschaft
	 * ganz, bleibt die Ausgabe leer.
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

		$content = '<table>';
		$content .= '<tr>';

		if ($objTurnier->language == 'en')
		{
			$content .= '<th>Photo</th><th>Captain</th><th>Age</th><th>Elo</th><th>Weblinks</th>';
		}
		else
		{
			$content .= '<th>Foto</th><th>Kapitän</th><th>Alter</th><th>Elo</th><th>Weblinks</th>';
		}

		$content .= '</tr>';

		if ($objMannschaft->surname)
		{
			// Alter zum ersten Turniertag; beide Datumswerte liegen in der
			// Datenbank als Zahl JJJJMMTT und müssen erst lesbar gemacht werden
			$alter = Helfer::alter(
				(string) Helper::getDate($objMannschaft->birthday),
				(string) Helper::getDate($objTurnier->fromDate)
			);

			$name = trim($objMannschaft->fide_title.' '.$objMannschaft->prename.' '.$objMannschaft->surname);

			// Weblinks aus dem MultiColumnWizard
			$weblinks = StringUtil::deserialize($objMannschaft->weblinks);
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

			// Foto: eigenes Bild des Kapitäns, sonst das Standardbild aus den
			// Einstellungen, passend zum Geschlecht des Turniers
			$bild_id = $objMannschaft->singleSRC ?: Helfer::standardbild($objTurnier->gender);

			// Die Lightbox-Gruppe hieß hier früher 'tt'.$objSpieler->id — eine
			// Variable, die es in dieser Klasse gar nicht gibt. Richtig ist die
			// Kennung der Mannschaft.
			$bild = Helfer::bild($bild_id, $objTurnier->imageSize_lineup, 'tt'.$objMannschaft->id);

			$content .= '<tr>';
			$content .= '<td>'.$bild.'</td>';
			$content .= '<td>'.$name.'</td>';
			$content .= '<td>'.$alter.'</td>';
			$content .= '<td><a href="https://ratings.fide.com/profile/'.$objMannschaft->fide_id.'">'.$objMannschaft->fide_elo.'</a></td>';
			$content .= '<td>'.trim($links).'</td>';
			$content .= '</tr>';
		}

		$content .= '</table>';

		$this->Template->content = $content;
	}
}
