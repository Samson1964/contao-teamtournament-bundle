# Mannschaftsturniere-Bundle Changelog

## Version 0.2.1 (2024-09-17)

* Change: tl_content.teamtournament_runde von 9 auf 19 Runden erweitert
* Fix: Warning: Attempt to read property "pid" on null in /contao/dca/tl_teamtournament_matches.php (line 293) 
* Fix: Warning: Attempt to read property "path" on null in /ContentElements/Rounds.php (line 88) -> Bild fehlt
* Fix: Warning: Undefined array key "teamtournament_defaultImageMen" in ContentElements/Rounds.php (line 74) 
* Add: Funktion getPoints/putPoints in tl_teamtournament_matches um Eingaben mit Komma zu ermöglichen
* Add: tl_settings.teamtournament_css für das Aktivieren des Standard-CSS

## Version 0.2.0 (2024-09-17)

* Add: Abhängigkeit PHP 8

## Version 0.1.5 (2024-02-13)

* Fix: Bild des Kapitäns wurde nicht angezeigt.

## Version 0.1.4 (2024-02-12)

* Add: Standardbilder in Aufstellungen + Kapitän

## Version 0.1.3 (2024-02-12)

* Add: CSS-Klassen bei Paarungslisten erweitert
* Add: Leerzeile zwischen Paarungen
* Change: CSS-Klasse elo -> rating
* Change: Paarungsliste nach Tischreihenfolge statt alphabetisch nach Mannschaft 1
* Add: tl_settings mit Einstellungen Standardbild Frauen und Männer
* Add: tl_teamtournament.gender -> für Abfrage ob Männer- oder Frauenturnier
* Add: tl_teamtournament_teams.flag -> Dateiauswahl für ein Bild der Mannschaft, z.B. ein Flaggen-Symbol
* Add: Bilder für Mannschaften
* Add: Bildgröße tl_teamtournament.imageSize_flags für Flaggen der Mannschaften

## Version 0.1.2 (2024-02-11)

* Add: Tabellen für Wettkämpfe und die Bretter

## Version 0.1.1 (2024-02-03)

* Fix: Tabelle ohne Kopfzeile
* Fix: Bei Aufstellungen wurde das Bild wiederholt

## Version 0.1.0 (2024-02-03)

* Ausbau des Bundles, um Aufstellungen und den Kapitän auszugeben

## Version 0.0.1 (2024-02-03)

* Initialisierung der Erweiterung

