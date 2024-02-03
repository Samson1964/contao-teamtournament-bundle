<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   chesstable
 * Version    1.0.0
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2013
 */

namespace Schachbulle\ContaoTeamtournamentBundle\ContentElements;

class Captain extends \ContentElement
{

	protected $strTemplate = 'ce_tt-captain';

	/**
	 * Generate the module
	 */
	protected function compile()
	{

		// Symlink für das externe Bundle components/flag-icon-css erstellen, wenn noch nicht vorhanden
		if(!is_link(TL_ROOT.'/web/bundles/flag-icon-css')) symlink(TL_ROOT.'/vendor/components/flag-icon-css/', TL_ROOT.'/web/bundles/flag-icon-css'); // Ziel, Name

		// Mannschaft laden
		$objMannschaft = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_teams WHERE id=?")
		                                         ->execute($this->teamtournament_lineup);
		// Turnier laden
		$objTurnier = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament WHERE id=?")
		                                      ->execute($objMannschaft->pid);
		
		$content ='';
		$content .= '<table>';
		$content .= '<tr>';
		if($objTurnier->language == 'de')
		{
			$content .= '<th>Foto</th>';
			$content .= '<th>Kapitän</th>';
			$content .= '<th>Alter</th>';
			$content .= '<th>Elo</th>';
			$content .= '<th>Weblinks</th>';
		}
		elseif($objTurnier->language == 'en')
		{
			$content .= '<th>Photo</th>';
			$content .= '<th>Captain</th>';
			$content .= '<th>Age</th>';
			$content .= '<th>Elo</th>';
			$content .= '<th>Weblinks</th>';
		}
		$content .= '</tr>';
		// Kapitän ausgeben
		if($objMannschaft->surname)
		{
			// Alter ermitteln anhand Turnierbeginn
			$geburtsdatum = \Schachbulle\ContaoHelperBundle\Classes\Helper::getDate($objMannschaft->birthday);
			$turnierdatum = \Schachbulle\ContaoHelperBundle\Classes\Helper::getDate($objTurnier->fromDate);
			$alter = self::getAlter($geburtsdatum, $turnierdatum);
			// Name zusammensetzen
			$name = '';
			if($objMannschaft->fide_title) $name .= $objMannschaft->fide_title.' ';
			if($objMannschaft->prename) $name .= $objMannschaft->prename.' ';
			if($objMannschaft->surname) $name .= $objMannschaft->surname;
			// Weblinks erstellen
			$weblinks = unserialize($objMannschaft->weblinks);
			$links = '';
			if(is_array($weblinks))
			{
				foreach($weblinks as $weblink)
				{
					if($weblink['url']) $links .= '<a href="'.$weblink['url'].'" target="_blank">'.$weblink['title'].'</a> ';
				}
			}
			// Foto erstellen
			$bild = '';
			if($objMannschaft->singleSRC)
			{
				$objFile = \FilesModel::findByUuid($objMannschaft->singleSRC);
				$imageSize = unserialize($objTurnier->imageSize_lineup);
				$objBild = new \stdClass();
				\Controller::addImageToTemplate($objBild, array('singleSRC' => $objFile->path, 'size' => $imageSize), \Config::get('maxImageWidth'), null, $objFile);
				$bild = '<figure class="image_container">';
				$bild .= '<a href="'.$objBild->singleSRC.'" data-lightbox="tt'.$objSpieler->id.'"><img src="'.$objBild->src.'" alt="'.$objBild->alt.'" title="'.$objBild->imageTitle.'"></a>';
				if($objBild->caption)
				{
					$bild .= '<figcaption class="caption">'.$objBild->caption.'</figcaption>';
				}
				$bild .= '</figure>';
			}
		
			$content .= '<tr>';
			$content .= '<td>'.$bild.'</td>';
			$content .= '<td>'.trim($name).'</td>';
			$content .= '<td>'.$alter.'</td>';
			$content .= '<td><a href="https://ratings.fide.com/profile/'.$objMannschaft->fide_id.'">'.$objMannschaft->fide_elo.'</a></td>';
			$content .= '<td>'.$links.'</td>';
			$content .= '</tr>';
		}
		$content .= '</table>';

		// Template ausgeben
		$this->Template->content = $content;
		return;

	}

	/**
	 * Funktion getAlter
	 *
	 * Ermittelt das Alter in Jahren vom Geburtsdatum bis zum Referenzdatum
	 *
	 * @geburtsdatum   string       TT.MM.JJJJ oder MM.JJJJ oder JJJJ
	 * @referenzdatum  string       TT.MM.JJJJ
	 * @return         integer      Alter in Jahren
	 */
	function getAlter($geburtsdatum, $referenzdatum)
	{
		// Geburtsdatum analysieren
		$col = explode('.', trim($geburtsdatum)); // String mit Datum zerlegen
		if(count($col) == 1)
		{
			// Nur JJJJ übergeben
			$geburtstag = $col[0].'0101';
		}
		elseif(count($col) == 2)
		{
			// Nur MM.JJJJ übergeben
			$geburtstag = $col[1].$col[0].'01';
		}
		elseif(count($col) == 3)
		{
			// TT.MM.JJJJ übergeben
			$geburtstag = $col[2].$col[1].$col[0];
		}
		else
		{
			return false;
		}

		// Referenzdatum konvertieren
		$col = explode('.', trim($referenzdatum)); // String mit Datum zerlegen
		$referenztag = $col[2].$col[1].$col[0];

		//$geburtstag = date('Ymd', mktime(0, 0, 0, (int)substr($string, 3, 2), (int)substr($string, 0, 2), (int)substr($string, 6, 4)));
		$alter = floor(($referenztag - $geburtstag) / 10000);
		return $alter;
	}

	
}
