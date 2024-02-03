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

class LineUp extends \ContentElement
{

	protected $strTemplate = 'ce_tt-lineup';

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
		// Spieler der Mannschaft laden
		$objSpieler = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_players WHERE pid=?")
		                                      ->execute($this->teamtournament_lineup);
		
		$content ='';
		$content .= '<table>';
		$content .= '<tr>';
		if($objTurnier->language == 'de')
		{
			$content .= '<td>Foto</td>';
			$content .= '<td>Spieler</td>';
			$content .= '<td>Alter</td>';
			$content .= '<td>Elo</td>';
			$content .= '<td>Brett</td>';
			$content .= '<td>Weblinks</td>';
		}
		elseif($objTurnier->language == 'en')
		{
			$content .= '<td>Photo</td>';
			$content .= '<td>Player</td>';
			$content .= '<td>Age</td>';
			$content .= '<td>Elo</td>';
			$content .= '<td>Board</td>';
			$content .= '<td>Weblinks</td>';
		}
		$content .= '</tr>';
		// Spieler ausgeben
		while($objSpieler->next())
		{
			// Alter ermitteln anhand Turnierbeginn
			$geburtsdatum = \Schachbulle\ContaoHelperBundle\Classes\Helper::getDate($objSpieler->birthday);
			$turnierdatum = \Schachbulle\ContaoHelperBundle\Classes\Helper::getDate($objTurnier->fromDate);
			$alter = self::getAlter($geburtsdatum, $turnierdatum);
			// Name zusammensetzen
			$name = '';
			if($objSpieler->fide_title) $name .= $objSpieler->fide_title.' ';
			if($objSpieler->prename) $name .= $objSpieler->prename.' ';
			if($objSpieler->surname) $name .= $objSpieler->surname;
			// Weblinks erstellen
			$weblinks = unserialize($objSpieler->weblinks);
			$links = '';
			if(is_array($weblinks))
			{
				foreach($weblinks as $weblink)
				{
					if($weblink['url']) $links .= '<a href="'.$weblink['url'].'" target="_blank">'.$weblink['title'].'</a> ';
				}
			}
			// Foto erstellen
			$foto = '';
			if($objSpieler->singleSRC)
			{
				$objFile = \FilesModel::findByUuid($objSpieler->singleSRC);
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
			$content .= '<td><a href="https://ratings.fide.com/profile/'.$objSpieler->fide_id.'">'.$objSpieler->fide_elo.'</a></td>';
			$content .= '<td>'.($objSpieler->board ? $objSpieler->board : '').'</td>';
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
