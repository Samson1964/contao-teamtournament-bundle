<?php

declare(strict_types=1);

/*
 * Mannschaftsturniere für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Contao\StringUtil;
use Contao\Validator;

/*
 * Palette
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{teamtournament_legend:hide},teamtournament_defaultImageMen,teamtournament_defaultImageWomen,teamtournament_css';

/*
 * Felder
 */

$GLOBALS['TL_DCA']['tl_settings']['fields']['teamtournament_defaultImageMen'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['teamtournament_defaultImageMen'],
	'inputType'               => 'fileTree',
	'eval'                    => array
	(
		'filesOnly'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50 clr'
	),
	// Der Dateibaum liefert die UUID als 16 Byte Binärwert. Die Einstellungen
	// landen aber in system/config/localconfig.php, also in einer PHP-Datei,
	// die den Binärwert nicht unbeschadet übersteht: Nullbytes und Backslashes
	// gehen dabei verloren, und FilesModel::findByUuid() findet die Datei
	// später nicht mehr. Deshalb wird hier in die lesbare Schreibweise
	// umgewandelt, die findByUuid() ebenso versteht. Gilt für beide
	// Standardbild-Felder dieser Datei.
	'save_callback' => array
	(
		static function ($varValue)
		{
			return Validator::isBinaryUuid($varValue) ? StringUtil::binToUuid($varValue) : $varValue;
		}
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['teamtournament_defaultImageWomen'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['teamtournament_defaultImageWomen'],
	'inputType'               => 'fileTree',
	'eval'                    => array
	(
		'filesOnly'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50'
	),
	// Umwandlung der binären UUID, siehe Hinweis beim ersten Standardbild-Feld
	'save_callback' => array
	(
		static function ($varValue)
		{
			return Validator::isBinaryUuid($varValue) ? StringUtil::binToUuid($varValue) : $varValue;
		}
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['teamtournament_css'] = array
(
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['teamtournament_css'],
	'inputType'     => 'checkbox',
	'eval'          => array
	(
		'tl_class'  => 'w50 clr',
	)
);
