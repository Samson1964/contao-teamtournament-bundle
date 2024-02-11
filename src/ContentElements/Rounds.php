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

class Rounds extends \ContentElement
{

	protected $strTemplate = 'ce_tt-round';

	/**
	 * Generate the module
	 */
	protected function compile()
	{

		// Symlink für das externe Bundle components/flag-icon-css erstellen, wenn noch nicht vorhanden
		if(!is_link(TL_ROOT.'/web/bundles/flag-icon-css')) symlink(TL_ROOT.'/vendor/components/flag-icon-css/', TL_ROOT.'/web/bundles/flag-icon-css'); // Ziel, Name

		// Turnier laden
		$objTurnier = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament WHERE id=?")
		                                      ->execute($this->teamtournament_turnier);
		// Mannschaften laden
		$objMannschaften = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_teams WHERE pid=?")
		                                           ->execute($this->teamtournament_turnier);
		while($objMannschaften->next())
		{
			$mannschaft[$objMannschaften->id] = array
			(
				'name'    => $objMannschaften->name,
				'country' => $objMannschaften->country,
				'flagge'  => '<span class="flag-icon flag-icon-'.$objMannschaften->country.'"></span>'
			);
		}
		// Spieler laden
		$objSpieler = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_players")
		                                      ->execute();
		while($objSpieler->next())
		{
			// Foto erstellen
			$bild = '';
			if($objSpieler->singleSRC)
			{
				$objFile = \FilesModel::findByUuid($objSpieler->singleSRC);
				$imageSize = unserialize($objTurnier->imageSize_results);
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
			$spieler[$objSpieler->id] = array
			(
				'name'       => $objSpieler->prename.' '.$objSpieler->surname,
				'fide_title' => $objSpieler->fide_title,
				'fide_elo'   => $objSpieler->fide_elo,
				'bild'       => $bild,
			);
		}
		// Wettkämpfe der Runde laden
		// $this->teamtournament_turnier = ID des Turniers
		// $this->teamtournament_runde = Nummer der Runde
		$objWettkaempfe = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_matches WHERE pid=? AND round=? ORDER BY team1 ASC")
		                                          ->execute($this->teamtournament_turnier, $this->teamtournament_runde);

		$content ='';
		$content .= '<table>';
		// Wettkämpfe durchgehen
		while($objWettkaempfe->next())
		{
			// Ergebnis bauen
			$ergebnis = self::getErgebnis($objWettkaempfe->resultTeam1, $objWettkaempfe->resultTeam2);
			// Kopfzeile mit den Mannschaften
			$content .= '<tr>';
			if($objTurnier->language == 'de')
			{
				$content .= '<th>Br.</th>';
				$content .= '<th>'.$mannschaft[$objWettkaempfe->team1]['flagge'].' '.$mannschaft[$objWettkaempfe->team1]['name'].'</th>';
				$content .= '<th>Elo</th>';
				$content .= '<th>'.$ergebnis.'</th>';
				$content .= '<th>'.$mannschaft[$objWettkaempfe->team2]['flagge'].' '.$mannschaft[$objWettkaempfe->team2]['name'].'</th>';
				$content .= '<th>Elo</th>';
			}
			elseif($objTurnier->language == 'en')
			{
				$content .= '<th>Bo.</th>';
				$content .= '<th>'.$mannschaft[$objWettkaempfe->team1]['flagge'].' '.$mannschaft[$objWettkaempfe->team1]['name'].'</th>';
				$content .= '<th>Elo</th>';
				$content .= '<th>'.$ergebnis.'</th>';
				$content .= '<th>'.$mannschaft[$objWettkaempfe->team2]['flagge'].' '.$mannschaft[$objWettkaempfe->team2]['name'].'</th>';
				$content .= '<th>Elo</th>';
			}
			$content .= '</tr>';
			
			// Bretter des Wettkampfes laden
			$objBretter = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_games WHERE pid=? ORDER BY board ASC")
			                                      ->execute($objWettkaempfe->id);
			while($objBretter->next())
			{
				$content .= '<tr>';
				$content .= '<td class="board">'.$objBretter->board.'</td>';
				$content .= '<td class="player'.($objBretter->colors == 'w' ? ' white' : ' black').'">'.trim($spieler[$objBretter->player1]['bild'].' '.$spieler[$objBretter->player1]['fide_title'].' '.$spieler[$objBretter->player1]['name']).'</td>';
				$content .= '<td class="elo">'.$spieler[$objBretter->player1]['fide_elo'].'</td>';
				$content .= '<td class="result">'.$objBretter->result.'</td>';
				$content .= '<td class="player'.($objBretter->colors == 'w' ? ' black' : ' white').'">'.trim($spieler[$objBretter->player2]['bild'].' '.$spieler[$objBretter->player2]['fide_title'].' '.$spieler[$objBretter->player2]['name']).'</td>';
				$content .= '<td class="elo">'.$spieler[$objBretter->player2]['fide_elo'].'</td>';
				$content .= '</tr>';
			}
		}
		$content .= '</table>';
		

		// Template ausgeben
		$this->Template->content = $content;
		return;

	}

	/**
	 * Formatiertes Ergebnis zurückgeben
	 */
	public function getErgebnis($erg1, $erg2)
	{
		if($erg1 || $erg2)
		{
			$ergebnis = str_replace('.', ',', sprintf('%0.1f',$erg1).' : '.sprintf('%0.1f',$erg2));
			return $ergebnis;
		}
		else
		{
			return '-';
		}
	}
}
