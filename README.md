# Mannschaftsturniere

Erweiterung für Contao, um Schach-Mannschaftsturniere zu verwalten und im Frontend
darzustellen: Mannschaften mit Kapitän und Logo, deren Spieler, die Wettkämpfe je Runde und
die Einzelpartien an den Brettern.

Läuft unter **Contao 4.13** und **Contao 5**, mit **PHP 7.4 bis 8.4**.

## Installation

Über den Contao Manager das Paket `schachbulle/contao-teamtournament-bundle` suchen und
installieren, oder auf der Kommandozeile:

```bash
composer require schachbulle/contao-teamtournament-bundle
```

Anschließend die Datenbank aktualisieren (Contao Manager → Systemwartung, oder
`vendor/bin/contao-console contao:migrate`).

## Datenbereiche

Das Backend-Modul **Mannschaftsturniere** liegt im Bereich *Inhalte* und führt fünf
ineinander verschachtelte Tabellen:

| Tabelle | Inhalt |
| --- | --- |
| `tl_teamtournament` | Das Turnier: Name, Ort, Land, Zeitraum, Sprache der Ausgabe, Bildgrößen |
| `tl_teamtournament_teams` | Die Mannschaften eines Turniers samt Kapitän und Logo |
| `tl_teamtournament_players` | Die Spieler einer Mannschaft mit Brett, Titel, Elo und DWZ |
| `tl_teamtournament_matches` | Die Wettkämpfe: Runde, Tisch, beide Mannschaften, Ergebnis |
| `tl_teamtournament_games` | Die Einzelpartien eines Wettkampfes mit Brett, Farbe und Ergebnis |

Datumsangaben dürfen unvollständig sein: `TT.MM.JJJJ`, `MM.JJJJ` oder nur `JJJJ`. Bei den
Spielern und Kapitänen ist das häufig nötig, weil von älteren Spielern oft nur das
Geburtsjahr bekannt ist.

## Aufstellung und Ergebnisse erfassen

In der Liste der Wettkämpfe führt ein eigenes Symbol in die Maske **Aufstellung und
Ergebnisse**. Sie erledigt beides auf einer Seite:

1. **Aufstellung** — die Spieler beider Mannschaften stehen nebeneinander, angehakt wird,
   wer mitspielt. Aus den Haken entstehen beim Speichern die Bretter: der erste angehakte
   Spieler der einen Mannschaft gegen den ersten der anderen, in der Reihenfolge der
   Brettnummern, die an den Spielern hinterlegt sind. Die Farbe wechselt von Brett zu
   Brett; an Brett 1 hat die erste Mannschaft Weiß.
2. **Ergebnisse** — je Brett die Namen links und rechts, dazwischen die Auswahl. Wer die
   Aufstellung nachträglich ändert, behält die Ergebnisse aller Bretter, deren Paarung
   gleich bleibt.

Die Bretter lassen sich daneben weiterhin einzeln bearbeiten, etwa um eine PGN zu
hinterlegen.

## Wertung

Am Turnier steht der Haken **Mannschaftspunkte aus den Brettpunkten errechnen**. Ist er
gesetzt, ergibt sich das Ergebnis eines Wettkampfs aus den Einzelpartien — Sieg ein Punkt,
Remis ein halber, kampflos gewonnen (`+:-`) ebenfalls ein Punkt. Die beiden Punktefelder
am Wettkampf sind dann gesperrt.

Ein einzelner Wettkampf lässt sich mit **Ergebnis überschreiben** davon ausnehmen. Das ist
der Weg für Entscheidungen, die sich aus den Brettern nicht ergeben.

Ist der Haken am Turnier nicht gesetzt, werden die Punkte wie bisher von Hand eingetragen;
Komma und Punkt sind dabei gleichermaßen erlaubt (`4,5` wie `4.5`), ebenso `3½`.

## Inhaltselemente

Drei Inhaltselemente in der Gruppe *Schach*:

* **Mannschaftsturnier-Aufstellung** — alle Spieler einer Mannschaft mit Foto, Alter,
  Elo-Zahl, Brett und Weblinks.
* **Mannschaftsturnier-Kapitän** — dasselbe für den Mannschaftsführer.
* **Mannschaftsturnier-Runde** — alle Wettkämpfe einer Runde: je Wettkampf eine Kopfzeile
  mit den Mannschaften und dem Mannschaftsergebnis, darunter die Bretter.

Die Ausgabesprache (Deutsch oder Englisch) steht am Turnier, nicht am Inhaltselement.

Die Templates heißen `ce_tt-lineup`, `ce_tt-captain` und `ce_tt-round`; sie enthalten nur
den durchsuchbaren Block und geben die fertige Tabelle aus.

## Einstellungen

Unter *System → Einstellungen*, Bereich **Mannschaftsturniere**:

* **Standardbild Männer** und **Standardbild Frauen** — springen ein, wenn zu einem Spieler
  kein eigenes Foto hinterlegt ist. Welches der beiden gilt, entscheidet das Feld
  *Geschlecht* am Turnier.
* **Standard-CSS** — bindet das mitgelieferte `default.css` im Frontend ein. Wer die
  Tabellen selbst gestaltet, lässt den Haken weg.

Die Bildgrößen werden je Turnier eingestellt, getrennt für Mannschaftslogos, Aufstellungen
und Ergebnislisten.

## Abhängigkeiten

* `contao/core-bundle` ^4.13 || ^5.0
* `menatwork/contao-multicolumnwizard-bundle` — für die Weblink-Listen
* `schachbulle/contao-helper-bundle` — für die Datumsumwandlung

## Entwicklung

Unit-Tests:

```bash
vendor/bin/phpunit
```

Der Prüfstand `tools/pruefstand.php` prüft das Bundle gegen eine vorhandene
Contao-Installation, ohne es dort installieren zu müssen — er stellt dem Autoloader der
Installation einen eigenen voran, lädt Sprach- und DCA-Dateien über ihren Pfad und meldet,
welche Kern-Klassen und Dienste in dieser Contao-Fassung fehlen:

```bash
php tools/pruefstand.php /pfad/zur/contao-installation
```

**Frank Hoppe**
