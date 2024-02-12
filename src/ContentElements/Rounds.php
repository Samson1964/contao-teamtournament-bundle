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
			// Logo/Bild der Mannschaft generieren
			$bild = '';
			if($objMannschaften->flag)
			{
				$objFile = \FilesModel::findByUuid($objMannschaften->flag);
				$imageSize = unserialize($objTurnier->imageSize_flags);
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
			$mannschaft[$objMannschaften->id] = array
			(
				'name'    => $objMannschaften->name,
				'country' => $objMannschaften->country,
				'flagge'  => $bild
			);
		}
		// Spieler laden
		$objSpieler = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_players")
		                                      ->execute();
		while($objSpieler->next())
		{
			// Foto erstellen
			if($objSpieler->singleSRC)
			{
				$bild_id = $objSpieler->singleSRC;
			}
			elseif($objTurnier->gender == 'm')
			{
				$bild_id = $GLOBALS['TL_CONFIG']['teamtournament_defaultImageMen'];
			}
			elseif($objTurnier->gender == 'w')
			{
				$bild_id = $GLOBALS['TL_CONFIG']['teamtournament_defaultImageWomen'];
			}

			// Foto generieren
			$bild = '';
			if($bild_id)
			{
				$objFile = \FilesModel::findByUuid($bild_id);
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

			// Spielerdaten sichern
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
		$objWettkaempfe = \Database::getInstance()->prepare("SELECT * FROM tl_teamtournament_matches WHERE pid=? AND round=? ORDER BY round ASC")
		                                          ->execute($this->teamtournament_turnier, $this->teamtournament_runde);

		$content ='';
		$content .= '<table>';
		$tisch = 0;
		// Wettkämpfe durchgehen
		while($objWettkaempfe->next())
		{
			$tisch++;
			if($tisch > 1) {
				// Leerzeile einbauen
				$content .= '<tr class="empty">';
				$content .= '<td class="empty" colspan="6">&nbsp;</td>';
				$content .= '</tr>';
			}
			// Ergebnis bauen
			$ergebnis = self::getErgebnis($objWettkaempfe->resultTeam1, $objWettkaempfe->resultTeam2);
			// Kopfzeile mit den Mannschaften
			$content .= '<tr class="head">';
			if($objTurnier->language == 'de')
			{
				$content .= '<th class="board">Br.</th>';
				$content .= '<th class="team">'.$mannschaft[$objWettkaempfe->team1]['flagge'].' '.$mannschaft[$objWettkaempfe->team1]['name'].'</th>';
				$content .= '<th class="rating">Elo</th>';
				$content .= '<th class="result">'.$ergebnis.'</th>';
				$content .= '<th class="team">'.$mannschaft[$objWettkaempfe->team2]['flagge'].' '.$mannschaft[$objWettkaempfe->team2]['name'].'</th>';
				$content .= '<th class="rating">Elo</th>';
			}
			elseif($objTurnier->language == 'en')
			{
				$content .= '<th class="board">Bo.</th>';
				$content .= '<th class="team">'.$mannschaft[$objWettkaempfe->team1]['flagge'].' '.$mannschaft[$objWettkaempfe->team1]['name'].'</th>';
				$content .= '<th class="rating">Elo</th>';
				$content .= '<th class="result">'.$ergebnis.'</th>';
				$content .= '<th class="team">'.$mannschaft[$objWettkaempfe->team2]['flagge'].' '.$mannschaft[$objWettkaempfe->team2]['name'].'</th>';
				$content .= '<th class="rating">Elo</th>';
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
				$content .= '<td class="rating">'.$spieler[$objBretter->player1]['fide_elo'].'</td>';
				$content .= '<td class="result">'.$objBretter->result.'</td>';
				$content .= '<td class="player'.($objBretter->colors == 'w' ? ' black' : ' white').'">'.trim($spieler[$objBretter->player2]['bild'].' '.$spieler[$objBretter->player2]['fide_title'].' '.$spieler[$objBretter->player2]['name']).'</td>';
				$content .= '<td class="rating">'.$spieler[$objBretter->player2]['fide_elo'].'</td>';
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
