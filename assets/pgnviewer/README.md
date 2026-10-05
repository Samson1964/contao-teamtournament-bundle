# PGN-Viewer — Quellen und Build

Dieses Verzeichnis enthält die Quellen des PGN-Viewers der Rundenübersicht. Es gehört nicht
zum ausgelieferten Paket (`export-ignore`); ausgeliefert werden nur die gebauten Dateien unter
`src/Resources/public/pgnviewer/`. Eine Contao-Installation braucht also weder Node noch
einen Build.

## Bibliotheken

| Paket | Version | Lizenz | Aufgabe |
| --- | --- | --- | --- |
| [chess.js](https://github.com/jhlywa/chess.js) | 1.4.0 | BSD-2-Clause | Regeln, Zuglegalität, Stellung (FEN) nach jedem Zug |
| [@mliebelt/pgn-parser](https://github.com/mliebelt/pgn-parser) | 1.4.19 | Apache-2.0 | PGN lesen: mehrere Partien, Kommentare, Varianten, NAGs, Fehler mit Position |
| [cm-chessboard](https://github.com/shaack/cm-chessboard) | 8.14.0 | MIT | SVG-Brett, Markierungen, Bildschirmleser-Erweiterung |

Die Versionen sind in `package.json` fest angegeben. chessground und PgnViewerJS scheiden aus:
beide stehen unter GPL-3.0-or-later. chess.js allein genügt nicht, weil es Varianten beim
Einlesen verwirft.

Die Lizenztexte werden beim Build nach `src/Resources/public/pgnviewer/lizenzen/` kopiert.

## Aufbau

| Datei | Inhalt |
| --- | --- |
| `src/partie.js` | PGN bereinigen, lesen und zu einem Zugbaum nachspielen. Kennt weder DOM noch Contao. |
| `src/sprache.js` | Texte Deutsch/Englisch, Zuganzeige (S, L, T, D) und gesprochene Zugansage |
| `src/viewer.js` | Darstellung und Bedienung: Kopf, Brett, Navigation, Zugliste, aria-live-Ansage |
| `src/loader.js` | Kleiner Lader: blendet die Schalter ein und lädt den Viewer erst beim ersten Öffnen |
| `src/viewer.css` | Stylesheet des Viewers (holt die Stile von cm-chessboard mit herein) |
| `src/schalter.css` | Stylesheet des Schalters „Partie nachspielen" |
| `build.mjs` | Build mit esbuild |
| `test/*.test.js` | Tests für `partie.js` und `sprache.js` |
| `test/pgn/grenzfaelle.js` | Grenzfall-PGNs |
| `pruefseite.php` | Prüfseite im Browser, ohne Contao |

## Befehle

```bash
cd assets/pgnviewer
npm ci           # Abhängigkeiten laut package-lock.json
npm test         # Tests für Parser und Sprache (node --test)
npm run build    # nach src/Resources/public/pgnviewer/ bauen
```

Der Build legt das Zielverzeichnis vollständig neu an. Die gebauten Dateien werden mit
eingecheckt.

## Gebaute Dateien

| Datei | Größe | gzip | geladen |
| --- | --- | --- | --- |
| `tt-pgn-loader.js` | 2,9 KB | 1,4 KB | beim Seitenaufruf, nur wenn die Seite eine Partie mit PGN hat |
| `tt-pgn-schalter.css` | 0,6 KB | 0,3 KB | ebenso |
| `tt-pgnviewer.js` | 165 KB | 45 KB | beim ersten Klick auf „Partie nachspielen" |
| `tt-pgnviewer.css` | 11,6 KB | 2,2 KB | ebenso |
| `pieces/standard.svg` | 23 KB | 4,5 KB | beim ersten Aufbau eines Brettes |
| `extensions/markers/markers.svg` | 3,3 KB | 1,1 KB | ebenso |

Alles liegt lokal im Bundle, es gibt keine Anfrage an ein CDN. Der Lader hängt seine eigene
Versionsangabe (`?v=…`) an alle nachgeladenen Dateien, damit ein Update nicht an einem
zwischengespeicherten Viewer scheitert.

## Schnittstelle

Der Viewer ist nicht an die Rundenübersicht gebunden. Ein anderes Modul braucht nur dasselbe
Markup — am einfachsten über `Schachbulle\ContaoTeamtournamentBundle\Classes\Partiedaten`:

```php
$html  = Partiedaten::schalter($id, 'de', $name1, $name2, $brett);
$html .= Partiedaten::zeile($id, 'de', [
    'sprache' => 'de',
    'pgn'     => Partiedaten::pgnAusDatenbank($pgn),
    'kopf'    => Partiedaten::kopf($spieler1, $spieler2, $farbe, $ergebnis, $runde, $brett),
]);
$GLOBALS['TL_BODY']['tt-pgn-loader'] = Partiedaten::laderSkript($pfadZuPublicPgnviewer);
```

Außerhalb einer Tabelle genügt ein Element mit der ID `tt-pgn-{id}`, das den Datenblock
`<script type="application/json" class="tt-pgn-daten">` enthält; ohne
`data-tt-pgn-huelle` blendet der Lader dieses Element selbst ein und aus.

Direkt aus JavaScript: `starteViewer(element, {sprache, pgn, kopf}, {assetsUrl})` aus
`tt-pgnviewer.js`.

## Prüfseite

```bash
# im Wurzelverzeichnis des Bundles
php -S 127.0.0.1:8765 -t .
```

Dann `http://127.0.0.1:8765/assets/pgnviewer/pruefseite.php` (oder `?sprache=en`) öffnen. Die
Seite erzeugt Schalter und Daten über `Partiedaten`, genau wie die Rundenübersicht, und deckt
diese Fälle ab: leeres PGN, kampflose Partie, mehrere Partien, Kommentare, Varianten, NAGs,
Umlaute, Windows-Umbrüche, Altdaten mit HTML-Entitäten, fehlende Farbangabe, abgeschnittenes
und unzulässiges PGN.
