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
| `tl_teamtournament_players` | Die Spieler einer Mannschaft mit Brett, Titel, Elo und DWZ. Beim Anlegen steht die nächste freie Brettnummer schon in der Maske |
| `tl_teamtournament_matches` | Die Wettkämpfe: Runde, Tisch, beide Mannschaften, Ergebnis |
| `tl_teamtournament_games` | Die Einzelpartien eines Wettkampfes mit Brett, Farbe und Ergebnis |

Datumsangaben dürfen unvollständig sein: `TT.MM.JJJJ`, `MM.JJJJ` oder nur `JJJJ`. Bei den
Spielern und Kapitänen ist das häufig nötig, weil von älteren Spielern oft nur das
Geburtsjahr bekannt ist.

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

Fünf Inhaltselemente in der Gruppe **Schach-Mannschaftsturnier**:

* **Mannschaftsturnier-Aufstellung** — alle Spieler einer Mannschaft mit Foto, Alter,
  Elo-Zahl, Brett und Weblinks.
* **Mannschaftsturnier-Kapitän** — dasselbe für den Mannschaftsführer.
* **Mannschaftsturnier-Runde** — alle Wettkämpfe einer Runde: je Wettkampf eine Kopfzeile
  mit den Mannschaften und dem Mannschaftsergebnis, darunter die Bretter.
* **Mannschaftsturnier-Tabelle** — die Mannschaften nach Rang, mit Kämpfen, Siegen,
  Unentschieden, Niederlagen, Mannschafts- und Brettpunkten.
* **Mannschaftsturnier-Kreuztabelle** — jede Mannschaft gegen jede, in der Reihenfolge der
  Tabelle.

Die Ausgabesprache (Deutsch oder Englisch) steht am Turnier, nicht am Inhaltselement.

Die Templates heißen `ce_tt-lineup`, `ce_tt-captain`, `ce_tt-round`, `ce_tt-standings` und
`ce_tt-crosstable`; sie enthalten nur den durchsuchbaren Block und geben die fertige
Tabelle aus.

### Tabelle und Kreuztabelle

Mannschaftspunkte stehen nirgends in der Datenbank — gespeichert sind nur die Brettpunkte
je Wettkampf. Beide Tabellen rechnen daraus bei jedem Aufruf neu:

* **Ein gewonnener Wettkampf zählt 2 Punkte**, ein Unentschieden 1, eine Niederlage 0.
* **Sortiert wird nach Mannschaftspunkten**, bei Gleichstand nach Brettpunkten, danach nach
  dem Namen. Wer in beidem gleichauf liegt, teilt sich den Rang (1., 1., 3.). Eine
  Feinwertung wie der direkte Vergleich oder Sonneborn-Berger ist bewusst nicht eingebaut —
  welche gilt, hängt an der Ausschreibung.
* **Nur gespielte Wettkämpfe zählen.** Als gespielt gilt ein Wettkampf, sobald mindestens
  ein Brett ein Ergebnis hat. Gibt es gar keine Bretter, weil nur Mannschaftsergebnisse
  gepflegt werden, entscheidet die Summe: alles über null ist ein Ergebnis. Ein 0:4 zählt
  also, ein 0:0 ohne Bretter nicht — in der Datenbank steht bei ungespielten Wettkämpfen
  nämlich fast immer `0.0 : 0.0` (siehe unten).
* **Die Runde grenzt ein.** Steht am Inhaltselement eine Runde, zählen alle Wettkämpfe bis
  einschließlich dieser Runde — die Tabelle zeigt dann den Stand nach dieser Runde. Ohne
  Angabe gelten alle Runden.

> **Warum ein 0:0 ohne Bretter nicht zählt:** Bei Turnieren mit errechneten
> Mannschaftspunkten trägt das Bundle in jeden Wettkampf eine Summe ein, auch in noch nicht
> gespielte; und bis 0.4.2 machte die Eingabemaske aus einem leeren Punktefeld bei jedem
> Speichern eine `0.0`. Würde man solche Wettkämpfe als Unentschieden werten, bekäme jede
> Mannschaft für jede noch nicht gespielte Begegnung einen Punkt. Nicht unterscheidbar
> bleibt ein von Hand eingetragenes 0:0 ganz ohne Bretter.

In einer Zelle der Kreuztabelle stehen die Brettpunkte aus Sicht der Zeilenmannschaft
(`2,5 : 1,5`). Haben zwei Mannschaften mehrfach gegeneinander gespielt, stehen beide
Ergebnisse untereinander. Die Spalten tragen nur die Platznummer, den vollen Namen nennt
das `title`-Attribut; bei vierzig Mannschaften wäre die Tabelle sonst nicht lesbar. Wird
sie breiter als die Seite, scrollt sie in sich, ohne das Layout zu sprengen.

## Partien nachspielen

Ist an einer Brettpaarung das Feld **PGN** gefüllt, zeigt die Rundenübersicht in der
Ergebnisspalte den Schalter **Partie nachspielen** (englisch *Replay game*). Er klappt
unter dem Brett einen Viewer auf, mit Brett, Zugliste und Navigation.

* **Kein Schalter** bei leerem PGN-Feld und bei kampflosen Ergebnissen (`+:-`, `-:+`, `-:-`);
  die Zeile sieht dann genauso aus wie ohne Viewer.
* **Der Kopf** — Namen, Titel, Elo, Mannschaften, Ergebnis, Runde, Brett — kommt aus den
  Datensätzen, nicht aus den PGN-Tags. Die Farben ergeben sich aus dem Feld *Farbe des
  1. Spielers*; ist dort nichts eingetragen, heißen die Zeilen „Spieler 1/2".
* **Mehrere Partien** in einem Feld (etwa Partie und Stichkampf) stehen im Viewer zur Auswahl.
* **Kommentare, Varianten und NAGs** werden angezeigt; Varianten sind eingerückt und
  eingeklammert.
* **Fehlerhafte Daten** führen zu einer Meldung statt zu einem Absturz: Bei unlesbarem PGN
  nennt sie Zeile und Spalte, bei einem unmöglichen Zug lässt sich die Partie bis dorthin
  nachspielen.

**Bedienung:** Pfeiltasten links und rechts für einen Halbzug, Pos1 und Ende für Anfang und
Ende; alternativ die vier Knöpfe unter dem Brett oder ein Klick auf einen Zug. „Ende" führt
in einer Variante an deren Ende.

**Barrierefreiheit:** Alles ist per Tastatur bedienbar, in der Zugliste mit nur einem
Tabstopp. Jeder Zug wird für Bildschirmleser ausgeschrieben angesagt („12. Weiß: Springer
schlägt auf f 3, Schach"), ebenso Kommentare und Varianten; das Brett steht zusätzlich als
Tabelle und Figurenliste bereit. Der aktuelle Zug ist fett, invertiert und eingerahmt, also
auch ohne Farbwahrnehmung erkennbar. Alle Texte und Bedienelemente erreichen mindestens 4,5:1
Kontrast, die Knöpfe sind 44×44 Pixel groß.

**Darstellung:** Ab mittlerer Breite stehen Brett und Zugliste nebeneinander, auf dem Handy
steht das Brett in voller Breite und die Zugliste darunter. Der Viewer bringt eigene Grund-
und Schriftfarben mit und gilt dadurch auch auf dunklen Seiten.

**Laden:** Seiten ohne Partie laden nichts vom Viewer. Seiten mit Partie laden einen kleinen
Lader (1,4 KB gzip); Brett, Parser und Stylesheet (rund 50 KB gzip) kommen erst beim ersten
Öffnen einer Partie. Alle Dateien liegen im Bundle, es gibt keine Anfragen an Dritte.

Eingesetzt werden [chess.js](https://github.com/jhlywa/chess.js) (BSD-2-Clause),
[@mliebelt/pgn-parser](https://github.com/mliebelt/pgn-parser) (Apache-2.0) und
[cm-chessboard](https://github.com/shaack/cm-chessboard) (MIT). Quellen, Build und die
Schnittstelle für andere Module beschreibt [assets/pgnviewer/README.md](assets/pgnviewer/README.md).

## Einstellungen

Unter *System → Einstellungen*, Bereich **Mannschaftsturniere**:

* **Standardbild Männer** und **Standardbild Frauen** — springen ein, wenn zu einem Spieler
  kein eigenes Foto hinterlegt ist. Welches der beiden gilt, entscheidet das Feld
  *Geschlecht* am Turnier.
* **Standard-CSS** — bindet das mitgelieferte `default.css` im Frontend ein. Wer die
  Tabellen selbst gestaltet, lässt den Haken weg.

Die Bildgrößen werden je Turnier eingestellt, getrennt für Mannschaftslogos, Aufstellungen
und Ergebnislisten. Daneben steht je Bereich ein Schalter **Bilder ausblenden** — damit
erscheinen in diesem Bereich weder die hinterlegten Bilder noch die Standardbilder aus den
Einstellungen.

## Abhängigkeiten

* `contao/core-bundle` ^4.13 || ^5.0
* `menatwork/contao-multicolumnwizard-bundle` — für die Weblink-Listen
* `schachbulle/contao-helper-bundle` — für die Datumsumwandlung
* `schachbulle/contao-flaggen-bundle` — für die Flaggen in der Turnierübersicht

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

Der PGN-Viewer hat eigene Tests und einen eigenen Build mit Node:

```bash
cd assets/pgnviewer
npm ci
npm test
npm run build
```

**Frank Hoppe**
