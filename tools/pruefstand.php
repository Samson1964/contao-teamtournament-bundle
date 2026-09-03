<?php

declare(strict_types=1);

/*
 * Pruefstand fuer das Mannschaftsturniere-Bundle.
 *
 * Aufruf:  php pruefstand.php <pfad-zur-contao-installation>
 *
 * Der Pruefstand kommt ohne Composer-Installation des Bundles aus: Er stellt
 * dem Autoloader der Testinstallation einen eigenen voran, laedt die DCA- und
 * Konfigurationsdateien ueber ihren Pfad und prueft anschliessend, ob alle
 * benutzten Kern-Klassen und -Methoden in dieser Contao-Fassung vorhanden sind.
 */

$bundle = str_replace('\\', '/', dirname(__DIR__));
$helper = dirname($bundle).'/contao-helper-bundle';
$root = str_replace('\\', '/', $argv[1] ?? '');

if (!is_dir($root))
{
	fwrite(STDERR, "Installation nicht gefunden: $root\n");
	exit(1);
}

// Eigener Autoloader, vorangestellt, damit die Arbeitsfassung gewinnt
spl_autoload_register(
	static function (string $class) use ($bundle, $helper): void
	{
		$map = array(
			'Schachbulle\\ContaoTeamtournamentBundle\\' => $bundle.'/src/',
			'Schachbulle\\ContaoHelperBundle\\' => $helper.'/src/',
		);

		foreach ($map as $prefix => $dir)
		{
			if (0 === strncmp($class, $prefix, strlen($prefix)))
			{
				$file = $dir.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

				if (is_file($file))
				{
					require $file;
				}

				return;
			}
		}
	},
	true,
	true
);

require $root.'/vendor/autoload.php';

$fehler = array();
$hinweise = array();

// Nur Meldungen aus dem eigenen Bundle sollen zaehlen
set_error_handler(
	static function (int $no, string $str, string $file = '', int $line = 0) use (&$fehler, $bundle): bool
	{
		if (false !== stripos(str_replace('\\', '/', $file), 'contao-teamtournament-bundle'))
		{
			$fehler[] = sprintf('%s in %s:%d', $str, $file, $line);
		}

		return true;
	}
);

/**
 * Meldet das Ergebnis einer Einzelpruefung.
 */
function pruefe(string $was, bool $ok, array &$fehler): void
{
	printf("  [%s] %s\n", $ok ? ' ok ' : 'FEHL', $was);

	if (!$ok)
	{
		$fehler[] = $was;
	}
}

echo "Contao-Quellen: $root\n";
echo "PHP: ".PHP_VERSION."\n\n";

// 1. Kern-Klassen und -Methoden, die das Bundle benutzt
echo "Kern-API\n";
$api = array(
	'Contao\Backend' => null,
	'Contao\BackendUser' => 'getInstance',
	'Contao\Config' => 'get',
	'Contao\ContentElement' => null,
	'Contao\DataContainer' => null,
	'Contao\Database' => 'getInstance',
	'Contao\DC_Table' => null,
	'Contao\Image' => 'getHtml',
	'Contao\Input' => 'get',
	'Contao\StringUtil' => 'deserialize',
	'Contao\System' => 'getContainer',
	'Contao\Validator' => 'isBinaryUuid',
	'Contao\CoreBundle\Image\Studio\Studio' => 'createFigureBuilder',
	'Contao\CoreBundle\Image\Studio\FigureBuilder' => 'buildIfResourceExists',
	'Contao\CoreBundle\Image\Studio\Figure' => 'applyLegacyTemplateData',
	'Contao\CoreBundle\Intl\Countries' => 'getCountries',
);

foreach ($api as $klasse => $methode)
{
	$ok = class_exists($klasse) && (null === $methode || method_exists($klasse, $methode));
	pruefe($klasse.($methode ? '::'.$methode.'()' : ''), $ok, $fehler);
}

foreach (array('from', 'setSize', 'enableLightbox', 'setLightboxGroupIdentifier') as $m)
{
	pruefe('FigureBuilder::'.$m.'()', method_exists('Contao\CoreBundle\Image\Studio\FigureBuilder', $m), $fehler);
}

pruefe('StringUtil::specialchars()', method_exists('Contao\StringUtil', 'specialchars'), $fehler);
pruefe('StringUtil::binToUuid()', method_exists('Contao\StringUtil', 'binToUuid'), $fehler);

// Dinge, die es unter Contao 5 nicht mehr geben darf und die das Bundle
// deshalb nicht mehr benutzt
echo "\nAbgeloeste Kern-API (nur zur Information)\n";
printf("  System::getCountries()          %s\n", method_exists('Contao\System', 'getCountries') ? 'vorhanden' : 'entfallen');
printf("  Controller::addImageToTemplate()%s\n", method_exists('Contao\Controller', 'addImageToTemplate') ? ' vorhanden' : ' entfallen');
printf("  Controller::generateImage()     %s\n", method_exists('Contao\Controller', 'generateImage') ? 'vorhanden' : 'entfallen');
printf("  Konstante TL_MODE               %s\n", defined('TL_MODE') ? 'vorhanden' : 'entfallen');

// 2. Konstanten der Listenansicht
echo "\nKonstanten\n";
pruefe('DataContainer::MODE_SORTABLE == 2', 2 === Contao\DataContainer::MODE_SORTABLE, $fehler);
pruefe('DataContainer::MODE_PARENT == 4', 4 === Contao\DataContainer::MODE_PARENT, $fehler);
pruefe('DataContainer::SORT_INITIAL_LETTER_ASC == 1', 1 === Contao\DataContainer::SORT_INITIAL_LETTER_ASC, $fehler);
pruefe('DataContainer::SORT_INITIAL_LETTER_DESC == 2', 2 === Contao\DataContainer::SORT_INITIAL_LETTER_DESC, $fehler);
pruefe('DataContainer::SORT_ASC == 11', 11 === Contao\DataContainer::SORT_ASC, $fehler);
pruefe('DataContainer::SORT_DESC == 12', 12 === Contao\DataContainer::SORT_DESC, $fehler);

// 3. Sprachdateien laden (die DCA-Dateien verweisen darauf)
echo "\nSprachdateien\n";
$GLOBALS['TL_LANG'] = array();

foreach (glob($bundle.'/src/Resources/contao/languages/de/*.php') as $datei)
{
	require $datei;
	pruefe(basename($datei), true, $fehler);
}

// 4. DCA-Dateien laden. Dabei werden DC_Table::class, die DataContainer-
//    Konstanten und die Rueckrufklassen (extends Backend) tatsaechlich
//    aufgeloest — genau daran waere die alte Fassung unter Contao 5
//    gescheitert.
echo "\nDCA-Dateien\n";
$GLOBALS['TL_DCA'] = array('tl_settings' => array('palettes' => array('default' => '{title_legend},foo')));

foreach (array('tl_settings', 'tl_content', 'tl_teamtournament', 'tl_teamtournament_teams', 'tl_teamtournament_players', 'tl_teamtournament_matches', 'tl_teamtournament_games') as $tabelle)
{
	require $bundle.'/src/Resources/contao/dca/'.$tabelle.'.php';
	pruefe($tabelle.'.php geladen', isset($GLOBALS['TL_DCA'][$tabelle]) || 'tl_settings' === $tabelle || 'tl_content' === $tabelle, $fehler);
}

// 5. Rueckrufe, die ohne Datenbank auskommen
echo "\nRueckrufe ohne Datenbank\n";
pruefe('Klasse tl_teamtournament vorhanden', class_exists('tl_teamtournament', false), $fehler);
pruefe('Klasse tl_teamtournament_teams vorhanden', class_exists('tl_teamtournament_teams', false), $fehler);
pruefe('Klasse tl_teamtournament_players vorhanden', class_exists('tl_teamtournament_players', false), $fehler);
pruefe('Klasse tl_teamtournament_matches vorhanden', class_exists('tl_teamtournament_matches', false), $fehler);
pruefe('Klasse tl_teamtournament_games vorhanden', class_exists('tl_teamtournament_games', false), $fehler);
pruefe('Klasse tl_content_teamtournament vorhanden', class_exists('tl_content_teamtournament', false), $fehler);

// 6. Die Inhaltselemente laden (prueft "extends Contao\ContentElement")
echo "\nInhaltselemente\n";

foreach (array('LineUp', 'Captain', 'Rounds') as $element)
{
	$klasse = 'Schachbulle\\ContaoTeamtournamentBundle\\ContentElements\\'.$element;
	pruefe($klasse, class_exists($klasse), $fehler);
}

pruefe('Rounds::getErgebnis(4, 4) == "4,0 : 4,0"', '4,0 : 4,0' === Schachbulle\ContaoTeamtournamentBundle\ContentElements\Rounds::getErgebnis(4, 4), $fehler);
pruefe('Rounds::getErgebnis(0, 0) == "-"', '-' === Schachbulle\ContaoTeamtournamentBundle\ContentElements\Rounds::getErgebnis(0, 0), $fehler);

// 7. Altersberechnung
echo "\nAltersberechnung\n";
pruefe('Helfer::alter(15.03.2000, 01.07.2020) == 20', 20 === Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer::alter('15.03.2000', '01.07.2020'), $fehler);
pruefe('Helfer::alter(2000, 01.07.2020) == 20', 20 === Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer::alter('2000', '01.07.2020'), $fehler);
pruefe('Helfer::alter(leer) == null', null === Schachbulle\ContaoTeamtournamentBundle\Classes\Helfer::alter('', '01.07.2020'), $fehler);

// 8. Datumsumwandlung der Turnierdaten
echo "\nDatumsumwandlung tl_teamtournament\n";
$refGet = new ReflectionMethod('tl_teamtournament', 'getDate');
$refPut = new ReflectionMethod('tl_teamtournament', 'putDate');
$dummy = (new ReflectionClass('tl_teamtournament'))->newInstanceWithoutConstructor();

pruefe('getDate(20200315) == 15.03.2020', '15.03.2020' === $refGet->invoke($dummy, 20200315), $fehler);
pruefe('getDate(202003) == 03.2020', '03.2020' === $refGet->invoke($dummy, 202003), $fehler);
pruefe('getDate(2020) == 2020', '2020' === $refGet->invoke($dummy, 2020), $fehler);
pruefe('getDate(0) == leer', '' === $refGet->invoke($dummy, 0), $fehler);
pruefe('putDate(15.03.2020) == 20200315', '20200315' === $refPut->invoke($dummy, '15.03.2020'), $fehler);
pruefe('putDate(03.2020) == 202003', '202003' === $refPut->invoke($dummy, '03.2020'), $fehler);
pruefe('putDate(leer) == 0', '0' === $refPut->invoke($dummy, ''), $fehler);

// 9. Dienste, die das Bundle zur Laufzeit holt
echo "\nDienste im kompilierten Behaelter\n";
// Der eigentliche Behaelter mit der methodMap liegt im Unterverzeichnis
// var/cache/prod/Container<zufall>/, nicht in der gleichnamigen Datei daneben
$container = null;
$treffer = glob($root.'/var/cache/prod/Container*/*Container.php');

if (!$treffer)
{
	$treffer = glob($root.'/var/cache/prod/*Container.php');
}

if ($treffer)
{
	$container = $treffer[0];
}

if (null === $container)
{
	$hinweise[] = 'Kein kompilierter Behaelter unter var/cache/prod gefunden — Dienste nicht geprueft.';
	echo "  (uebersprungen)\n";
}
else
{
	$inhalt = file_get_contents($container);

	foreach (array('contao.image.studio', 'contao.image.sizes', 'contao.intl.countries', 'contao.routing.scope_matcher', 'request_stack') as $dienst)
	{
		pruefe($dienst.' oeffentlich', false !== strpos($inhalt, "'".$dienst."' =>"), $fehler);
	}
}

restore_error_handler();

echo "\n";

foreach ($hinweise as $hinweis)
{
	echo "Hinweis: $hinweis\n";
}

if ($fehler)
{
	echo "\nFEHLGESCHLAGEN (".count($fehler)."):\n";

	foreach ($fehler as $f)
	{
		echo "  - $f\n";
	}

	exit(1);
}

echo "Alles in Ordnung.\n";
exit(0);
