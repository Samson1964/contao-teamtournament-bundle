<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoTeamtournamentBundle\Classes;

use Contao\Controller;
use Contao\Database;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Backend-Maske für Aufstellung und Ergebnisse eines Wettkampfes.
 *
 * Erreichbar über das Backend-Modul mit „&key=results&id=<Wettkampf>". Contao
 * ruft dafür in beiden Fassungen dieselbe Stelle auf: System::importStatic()
 * erzeugt diese Klasse, das Ergebnis der Methode landet im Hauptbereich der
 * Backend-Seite.
 *
 * Die Maske erledigt zwei Schritte auf einer Seite, weil sie zusammengehören:
 * oben wird angehakt, wer spielt, unten stehen die Bretter mit den Namen links
 * und rechts und der Ergebnisauswahl in der Mitte. Die Bretter entstehen aus
 * den Haken in Brettreihenfolge; wer schon eingetragene Ergebnisse hat, behält
 * sie, solange sein Brett bestehen bleibt.
 */
class Ergebnismaske
{
	/**
	 * Kennung des Formulars, an der Contao das Absenden erkennt.
	 */
	private const FORMULAR = 'tl_teamtournament_ergebnisse';

	/**
	 * Baut die Maske auf und verarbeitet ihr Absenden.
	 *
	 * @param object|null $dc Der Data Container des Backend-Moduls; wird nicht
	 *                        gebraucht, weil die Kennung des Wettkampfes aus der
	 *                        Adresse kommt, gehört aber zur Aufrufsignatur
	 *
	 * @return string|RedirectResponse Das Markup der Maske, oder eine Umleitung
	 *                                 zurück zur Wettkampfliste nach dem Speichern
	 */
	public function maske($dc = null)
	{
		$intWettkampf = (int) Input::get('id');
		$objWettkampf = $this->getWettkampf($intWettkampf);

		if (null === $objWettkampf)
		{
			return '<p class="tl_empty">Der Wettkampf wurde nicht gefunden.</p>';
		}

		if (Input::post('FORM_SUBMIT') === self::FORMULAR)
		{
			$this->speichern($intWettkampf, (int) $objWettkampf->team1, (int) $objWettkampf->team2);

			// Nach dem Speichern zurück zur Liste der Wettkämpfe, damit ein
			// Neuladen der Seite die Bretter nicht ein zweites Mal abgleicht
			return new RedirectResponse($this->getUrlListe($intWettkampf, (int) $objWettkampf->pid));
		}

		$arrSpieler1 = $this->getSpieler((int) $objWettkampf->team1);
		$arrSpieler2 = $this->getSpieler((int) $objWettkampf->team2);
		$arrBretter = $this->getBretter($intWettkampf);

		$strReturn = Message::generate();
		$strReturn .= '<div id="tl_buttons"><a href="'.StringUtil::specialchars($this->getUrlListe($intWettkampf, (int) $objWettkampf->pid)).'" class="header_back" title="Zurück">Zurück</a></div>';
		$strReturn .= '<form method="post" action="'.StringUtil::specialchars(Controller::addToUrl('')).'" enctype="application/x-www-form-urlencoded">';
		$strReturn .= '<div class="tl_formbody_edit">';
		$strReturn .= '<input type="hidden" name="FORM_SUBMIT" value="'.self::FORMULAR.'">';
		$strReturn .= '<input type="hidden" name="REQUEST_TOKEN" value="'.StringUtil::specialchars($this->getToken()).'">';

		$strReturn .= $this->rendereAufstellung($objWettkampf, $arrSpieler1, $arrSpieler2, $arrBretter);
		$strReturn .= $this->rendereBretter($arrBretter, $arrSpieler1, $arrSpieler2);

		$strReturn .= '</div>';
		$strReturn .= '<div class="tl_formbody_submit"><div class="tl_submit_container">';
		$strReturn .= '<button type="submit" name="save" class="tl_submit" accesskey="s">Speichern</button>';
		$strReturn .= '</div></div>';
		$strReturn .= '</form>';

		return $strReturn;
	}

	/**
	 * Zeichnet den oberen Abschnitt: die Aufstellung beider Mannschaften.
	 *
	 * Je Mannschaft eine Spalte mit allen erfassten Spielern und einem Haken.
	 * Angehakt ist, wer schon an einem Brett steht.
	 *
	 * @param object                            $objWettkampf Der Wettkampf
	 * @param array<int, array<string, string>> $arrSpieler1  Spieler der ersten Mannschaft
	 * @param array<int, array<string, string>> $arrSpieler2  Spieler der zweiten Mannschaft
	 * @param array<int, array<string, mixed>>  $arrBretter   Die vorhandenen Bretter
	 *
	 * @return string Das Markup des Abschnitts
	 */
	private function rendereAufstellung($objWettkampf, array $arrSpieler1, array $arrSpieler2, array $arrBretter): string
	{
		$arrGesetzt1 = array_column($arrBretter, 'player1');
		$arrGesetzt2 = array_column($arrBretter, 'player2');

		$strReturn = '<fieldset class="tl_tbox"><legend>Aufstellung</legend>';
		$strReturn .= '<p>Angehakt wird, wer mitspielt. Die Bretter entstehen in der Reihenfolge der Brettnummern: Der erste angehakte Spieler der einen Mannschaft trifft auf den ersten der anderen.</p>';
		$strReturn .= '<div class="widget"><table class="tl_teamtournament_lineup" style="width:100%"><tr style="vertical-align:top">';

		foreach (array(array($objWettkampf->name1, $arrSpieler1, 'team1', $arrGesetzt1), array($objWettkampf->name2, $arrSpieler2, 'team2', $arrGesetzt2)) as $arrSeite)
		{
			$strReturn .= '<td style="width:50%"><h3>'.StringUtil::specialchars((string) $arrSeite[0]).'</h3>';

			if (!$arrSeite[1])
			{
				$strReturn .= '<p class="tl_empty">Für diese Mannschaft sind noch keine Spieler erfasst.</p>';
			}

			foreach ($arrSeite[1] as $intId => $arrSpieler)
			{
				$strChecked = \in_array($intId, $arrSeite[3]) ? ' checked' : '';
				$strReturn .= '<div><label><input type="checkbox" name="'.$arrSeite[2].'[]" value="'.$intId.'" class="tl_checkbox"'.$strChecked.'> ';
				$strReturn .= StringUtil::specialchars($arrSpieler['brett'].' '.$arrSpieler['name']).'</label></div>';
			}

			$strReturn .= '</td>';
		}

		return $strReturn.'</tr></table></div></fieldset>';
	}

	/**
	 * Zeichnet den unteren Abschnitt: die Bretter mit der Ergebniseingabe.
	 *
	 * @param array<int, array<string, mixed>>  $arrBretter  Die vorhandenen Bretter
	 * @param array<int, array<string, string>> $arrSpieler1 Spieler der ersten Mannschaft
	 * @param array<int, array<string, string>> $arrSpieler2 Spieler der zweiten Mannschaft
	 *
	 * @return string Das Markup des Abschnitts; ein Hinweis, solange es keine
	 *                Bretter gibt
	 */
	private function rendereBretter(array $arrBretter, array $arrSpieler1, array $arrSpieler2): string
	{
		$strReturn = '<fieldset class="tl_tbox"><legend>Ergebnisse</legend>';

		if (!$arrBretter)
		{
			return $strReturn.'<p class="tl_empty">Noch keine Bretter. Erst die Aufstellung anhaken und speichern.</p></fieldset>';
		}

		$strReturn .= '<table class="tl_teamtournament_results" style="width:100%">';

		foreach ($arrBretter as $arrBrett)
		{
			$strName1 = $arrSpieler1[$arrBrett['player1']]['name'] ?? '—';
			$strName2 = $arrSpieler2[$arrBrett['player2']]['name'] ?? '—';

			// Die Farbe gilt für den Spieler der ersten Mannschaft
			$strFarbe = 'w' === $arrBrett['colors'] ? '(w)' : ('s' === $arrBrett['colors'] ? '(s)' : '');

			$strReturn .= '<tr>';
			$strReturn .= '<td style="width:3em">'.$arrBrett['board'].'</td>';
			$strReturn .= '<td style="text-align:right">'.StringUtil::specialchars($strName1.' '.$strFarbe).'</td>';
			$strReturn .= '<td style="width:8em;text-align:center">'.$this->rendereAuswahl((int) $arrBrett['id'], (string) $arrBrett['result']).'</td>';
			$strReturn .= '<td>'.StringUtil::specialchars($strName2).'</td>';
			$strReturn .= '</tr>';
		}

		return $strReturn.'</table></fieldset>';
	}

	/**
	 * Zeichnet die Auswahlliste für ein Brettergebnis.
	 *
	 * @param int    $intBrett     Kennung der Partie
	 * @param string $strErgebnis  Das bisher eingetragene Ergebnis
	 *
	 * @return string Das Markup der Auswahlliste
	 */
	private function rendereAuswahl(int $intBrett, string $strErgebnis): string
	{
		$strReturn = '<select name="result['.$intBrett.']" class="tl_select">';
		$strReturn .= '<option value="">-</option>';

		foreach (array_keys(Wertung::ERGEBNISSE) as $strWert)
		{
			$strSelected = $strWert === $strErgebnis ? ' selected' : '';
			$strReturn .= '<option value="'.StringUtil::specialchars($strWert).'"'.$strSelected.'>'.$strWert.'</option>';
		}

		return $strReturn.'</select>';
	}

	/**
	 * Verarbeitet das abgesendete Formular.
	 *
	 * Erst wird die Aufstellung abgeglichen, dann werden die Ergebnisse
	 * geschrieben und zuletzt das Mannschaftsergebnis nachgerechnet. Die
	 * Reihenfolge ist wichtig: Ein Ergebnis lässt sich nur zu einem Brett
	 * eintragen, das es noch gibt.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes
	 * @param int $intTeam1     Kennung der ersten Mannschaft
	 * @param int $intTeam2     Kennung der zweiten Mannschaft
	 */
	private function speichern(int $intWettkampf, int $intTeam1, int $intTeam2): void
	{
		$arrErgebnisse = (array) Input::post('result');

		$this->gleicheBretterAb(
			$intWettkampf,
			$this->filtereSpieler((array) Input::post('team1'), $intTeam1),
			$this->filtereSpieler((array) Input::post('team2'), $intTeam2)
		);

		foreach ($this->getBretter($intWettkampf) as $arrBrett)
		{
			$strErgebnis = (string) ($arrErgebnisse[$arrBrett['id']] ?? '');

			// Nur bekannte Ergebnisse übernehmen; alles andere wird zur
			// ungespielten Partie, statt einen unsinnigen Wert abzulegen
			if ('' !== $strErgebnis && null === Wertung::punkte($strErgebnis))
			{
				$strErgebnis = '';
			}

			if ($strErgebnis !== (string) $arrBrett['result'])
			{
				Database::getInstance()
					->prepare("UPDATE tl_teamtournament_games SET result=?, tstamp=? WHERE id=?")
					->execute($strErgebnis, time(), $arrBrett['id']);
			}
		}

		Wertung::schreibeWettkampf($intWettkampf);

		Message::addConfirmation('Aufstellung und Ergebnisse wurden gespeichert.');
	}

	/**
	 * Bringt die Bretter des Wettkampfes mit der angehakten Aufstellung in
	 * Übereinstimmung.
	 *
	 * Gepaart wird der Reihe nach: erster gegen ersten, zweiter gegen zweiten.
	 * Die Farbe wechselt von Brett zu Brett, an Brett 1 hat die erste
	 * Mannschaft Weiß — so ist es im Mannschaftsschach üblich.
	 *
	 * Bestehende Bretter werden weiterverwendet, solange dieselben beiden
	 * Spieler dort stehen; nur dann bleibt ihr Ergebnis erhalten. Überzählige
	 * Bretter werden gelöscht.
	 *
	 * @param int             $intWettkampf Kennung des Wettkampfes
	 * @param array<int, int> $arrTeam1     Spielerkennungen der ersten Mannschaft
	 * @param array<int, int> $arrTeam2     Spielerkennungen der zweiten Mannschaft
	 */
	private function gleicheBretterAb(int $intWettkampf, array $arrTeam1, array $arrTeam2): void
	{
		$arrVorhanden = $this->getBretter($intWettkampf);
		$intAnzahl = max(\count($arrTeam1), \count($arrTeam2));
		$objDb = Database::getInstance();

		for ($i = 0; $i < $intAnzahl; ++$i)
		{
			$intBrett = $i + 1;
			$intSpieler1 = $arrTeam1[$i] ?? 0;
			$intSpieler2 = $arrTeam2[$i] ?? 0;
			$strFarbe = 0 === $i % 2 ? 'w' : 's';
			$arrAlt = $arrVorhanden[$intBrett] ?? null;

			if (null === $arrAlt)
			{
				$objDb->prepare("INSERT INTO tl_teamtournament_games (pid, tstamp, board, player1, player2, colors, published) VALUES (?, ?, ?, ?, ?, ?, ?)")
					->execute($intWettkampf, time(), $intBrett, $intSpieler1, $intSpieler2, $strFarbe, '1');

				continue;
			}

			// Ergebnis nur behalten, wenn dieselbe Paarung stehen bleibt
			$blnGleich = (int) $arrAlt['player1'] === $intSpieler1 && (int) $arrAlt['player2'] === $intSpieler2;

			$objDb->prepare("UPDATE tl_teamtournament_games SET tstamp=?, player1=?, player2=?, colors=?, result=? WHERE id=?")
				->execute(time(), $intSpieler1, $intSpieler2, $strFarbe, $blnGleich ? $arrAlt['result'] : '', $arrAlt['id']);
		}

		// Bretter jenseits der neuen Aufstellung entfernen
		foreach ($arrVorhanden as $intBrett => $arrBrett)
		{
			if ($intBrett > $intAnzahl)
			{
				$objDb->prepare("DELETE FROM tl_teamtournament_games WHERE id=?")->execute($arrBrett['id']);
			}
		}
	}

	/**
	 * Nimmt aus einer abgesendeten Auswahl nur die Spieler der Mannschaft an.
	 *
	 * Die Kennungen kommen aus dem Formular und sind damit ungeprüft; ohne
	 * diesen Abgleich ließe sich ein fremder Spieler an ein Brett setzen.
	 * Sortiert wird nach Brettnummer, wie sie am Spieler hinterlegt ist.
	 *
	 * @param array<int|string, mixed> $arrAuswahl Die angehakten Kennungen
	 * @param int                      $intTeam    Kennung der Mannschaft
	 *
	 * @return array<int, int> Die gültigen Spielerkennungen in Brettreihenfolge
	 */
	private function filtereSpieler(array $arrAuswahl, int $intTeam): array
	{
		$arrErlaubt = $this->getSpieler($intTeam);
		$arrGewaehlt = array();

		// Über die erlaubten Spieler laufen, nicht über die Auswahl: So steht
		// die Reihenfolge fest (nach Brettnummer) und Doppelungen fallen weg
		foreach (array_keys($arrErlaubt) as $intSpieler)
		{
			if (\in_array((string) $intSpieler, array_map('strval', $arrAuswahl), true))
			{
				$arrGewaehlt[] = $intSpieler;
			}
		}

		return $arrGewaehlt;
	}

	/**
	 * Liest den Wettkampf samt den Namen beider Mannschaften.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes
	 *
	 * @return object|null Der Datensatz mit den Zusatzspalten name1 und name2,
	 *                     oder null, wenn es den Wettkampf nicht gibt
	 */
	private function getWettkampf(int $intWettkampf)
	{
		if (!$intWettkampf)
		{
			return null;
		}

		$objWettkampf = Database::getInstance()
			->prepare("SELECT m.*, t1.name AS name1, t2.name AS name2 FROM tl_teamtournament_matches m LEFT JOIN tl_teamtournament_teams t1 ON t1.id=m.team1 LEFT JOIN tl_teamtournament_teams t2 ON t2.id=m.team2 WHERE m.id=?")
			->execute($intWettkampf);

		return $objWettkampf->numRows ? $objWettkampf : null;
	}

	/**
	 * Liest die Spieler einer Mannschaft.
	 *
	 * @param int $intTeam Kennung der Mannschaft
	 *
	 * @return array<int, array<string, string>> Spielerkennung => Name und
	 *                                           Brettnummer, nach Brett sortiert
	 */
	private function getSpieler(int $intTeam): array
	{
		$arrSpieler = array();

		if (!$intTeam)
		{
			return $arrSpieler;
		}

		$objSpieler = Database::getInstance()
			->prepare("SELECT id, board, prename, surname, fide_title FROM tl_teamtournament_players WHERE pid=? ORDER BY board ASC, surname ASC, prename ASC")
			->execute($intTeam);

		while ($objSpieler->next())
		{
			$arrSpieler[(int) $objSpieler->id] = array
			(
				'brett' => $objSpieler->board ? $objSpieler->board.'.' : '',
				'name'  => trim($objSpieler->fide_title.' '.$objSpieler->prename.' '.$objSpieler->surname),
			);
		}

		return $arrSpieler;
	}

	/**
	 * Liest die Bretter eines Wettkampfes.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes
	 *
	 * @return array<int, array<string, mixed>> Brettnummer => Datensatz der Partie
	 */
	private function getBretter(int $intWettkampf): array
	{
		$arrBretter = array();

		$objBretter = Database::getInstance()
			->prepare("SELECT id, board, player1, player2, colors, result FROM tl_teamtournament_games WHERE pid=? ORDER BY board ASC")
			->execute($intWettkampf);

		while ($objBretter->next())
		{
			$arrBretter[(int) $objBretter->board] = $objBretter->row();
		}

		return $arrBretter;
	}

	/**
	 * Baut die Adresse zurück zur Liste der Wettkämpfe.
	 *
	 * @param int $intWettkampf Kennung des Wettkampfes, nur für den Sprungpunkt
	 * @param int $intTurnier   Kennung des Turniers
	 *
	 * @return string Die Backend-Adresse der Wettkampfliste
	 */
	private function getUrlListe(int $intWettkampf, int $intTurnier): string
	{
		return System::getContainer()->get('router')->generate('contao_backend', array
		(
			'do'    => 'teamtournament',
			'table' => 'tl_teamtournament_matches',
			'id'    => $intTurnier,
		)).'#tl_teamtournament_matches_'.$intWettkampf;
	}

	/**
	 * Liefert das CSRF-Merkmal für das Formular.
	 *
	 * Der Dienst ist in Contao 4.13 wie in Contao 5 öffentlich; die früher
	 * übliche Konstante REQUEST_TOKEN gibt es in Contao 5 nicht mehr.
	 *
	 * @return string Das Merkmal
	 */
	private function getToken(): string
	{
		return System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
	}
}
