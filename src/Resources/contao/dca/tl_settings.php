<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   fen
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2013
 */

/**
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{teamtournament_legend:hide},teamtournament_defaultImageMen,teamtournament_defaultImageWomen';

/**
 * fields
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
	)
);
