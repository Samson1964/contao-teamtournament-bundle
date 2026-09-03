# Mannschaftsturniere-Bundle Changelog

## Version 0.4.0 (2026-09-03)

**Wichtig beim Aktualisieren:** Es kommen zwei Datenbankfelder dazu
(`tl_teamtournament.calculateResults` und `tl_teamtournament_matches.overrideResult`).
Nach dem Update einmal die Datenbank aktualisieren (Contao Manager → Systemwartung oder
`contao:migrate`).

**Bestehende Turniere rechnen zunächst nicht.** Das neue Feld ist bei ihnen leer, die von
Hand eingetragenen Mannschaftspunkte bleiben also unangetastet. Wer die Berechnung will,
setzt den Haken am Turnier; neu angelegte Turniere rechnen von sich aus.

* Add: **Mannschaftspunkte werden aus den Brettpunkten errechnet.** Am Turnier steht die
  Vorgabe („Mannschaftspunkte aus den Brettpunkten errechnen"), am einzelnen Wettkampf
  hebt der Haken „Ergebnis überschreiben" sie auf — für kampflose Wertungen und
  Entscheidungen am grünen Tisch. Gerechnet wird nach jeder Änderung an einem Brett und
  nach dem Speichern des Wettkampfes. Solange gerechnet wird, sind die beiden Punktefelder
  gesperrt, statt beim Speichern kommentarlos überschrieben zu werden.
* Add: **Maske „Aufstellung und Ergebnisse"** je Wettkampf, erreichbar über ein eigenes
  Symbol in der Wettkampfliste. Oben werden die Spieler beider Mannschaften angehakt —
  daraus entstehen die Bretter in Brettreihenfolge, der erste angehakte Spieler der einen
  Mannschaft trifft auf den ersten der anderen, die Farbe wechselt von Brett zu Brett.
  Unten stehen die Bretter mit den Spielernamen links und rechts und der Ergebnisauswahl
  in der Mitte. Bretter, deren Paarung unverändert bleibt, behalten ihr Ergebnis.
* Fix: **Ein Wettkampf, der 0:4 ausging, zeigte in der Backend-Liste gar kein Ergebnis.**
  Geprüft wurde `if ($arrRow['resultTeam1'])` — und eine Null ist in PHP unwahr.
  Dieselbe Verwechslung steckte in der Rundenübersicht im Frontend.
* Fix: **Ein noch nicht gespielter Wettkampf zeigte „0,0" statt eines leeren Feldes.**
  Beim nächsten Speichern landete das als gewertetes 0:0 in der Datenbank. Ein leeres
  Ergebnis bleibt jetzt leer.
* Fix: **Eine unbrauchbare Eingabe im Punktefeld wurde stillschweigend zu „0,0".** Jetzt
  bleibt der bisherige Wert stehen und es erscheint eine Fehlermeldung. Angenommen werden
  Komma, Punkt und das Zeichen ½ (also auch „3½" für 3,5).
* Fix: **Die Kopfzeile der Paarungsliste zeigte die Kennung statt der Mannschaftsnamen.**
  Die beiden Mannschaftsfelder haben jetzt einen `foreignKey`; den löst Contao mit einer
  eigenen Abfrage auf, während es beim `options_callback` die Rückrufklasse mit einem
  fremden Data Container aufrief.
* Change: **Die Wettkämpfe stehen jetzt nach Runde absteigend und Tisch aufsteigend**,
  die jüngste Runde also oben. Dazu kommt ein Filter für die Runde in der Kopfleiste.
* Change: Die Auswahlliste der Brettergebnisse kommt aus derselben Stelle wie die
  Punktzuordnung (`Classes\Wertung`). Beide können damit nicht mehr auseinanderlaufen —
  ein auswählbares Ergebnis, das die Wertung nicht kennt, zählte sonst nicht mit.

## Version 0.3.0 (2026-09-03)

Diese Fassung läuft unter **Contao 4.13 und Contao 5** und unter **PHP bis 8.4**. Sie ist
gegen die Quellen von Contao 4.13.58 und Contao 5.7.7 mit PHP 8.4.24 geprüft; der dafür
gebaute Prüfstand liegt als `tools/pruefstand.php` bei.

**Wichtig beim Aktualisieren:** Zwei Abhängigkeiten sind entfallen
(`codefog/contao-haste`, `components/flag-icon-css`), eine ist dazugekommen
(`schachbulle/contao-helper-bundle`). Wer das Bundle über den Contao Manager aktualisiert,
merkt davon nichts; wer es von Hand einbindet, muss `composer update` laufen lassen.

* Add: Unterstützung für Contao 5 und PHP 8.4. Bisher verlangte die `composer.json`
  `contao/core-bundle: ^4` und `php: ^5.6`.
* Fix: Die Inhaltselemente erbten von `\ContentElement`, die DCA-Rückrufklassen von
  `Backend`, und im Code standen `\Database`, `\FilesModel`, `\Controller`, `\Config` und
  `\Input` ohne Namensraum. Contao 5 registriert keine globalen Klassenaliasse mehr — jede
  dieser Stellen hätte dort mit einem Fatal error abgebrochen.
* Fix: `TL_MODE` in der `config.php` gibt es in Contao 5 nicht mehr. Die Frage, ob gerade
  eine Frontend-Anfrage läuft, beantwortet jetzt `contao.routing.scope_matcher`. Der
  Schalter für das mitgelieferte CSS wird über `Config::get()` gelesen, nicht mehr direkt
  aus `$GLOBALS['TL_CONFIG']`.
* Fix: `Controller::addImageToTemplate()` ist in Contao 5 entfallen. Alle Bilder laufen
  jetzt über `contao.image.studio`, den es in beiden Fassungen gibt. Nebeneffekt: Ein
  gelöschtes Bild führt nicht mehr zu „Attempt to read property path on null", sondern
  einfach zu einer leeren Zelle.
* Fix: `System::getCountries()` ist in Contao 5 entfallen. Die Länderliste kommt jetzt aus
  `contao.intl.countries`. Die Schlüssel werden dabei klein geschrieben — der Dienst gibt
  sie groß zurück, gespeichert sind sie klein, sonst fände die Auswahlliste den
  hinterlegten Wert nicht wieder.
* Fix: `Controller::generateImage()` und `specialchars()` sind in Contao 5 entfallen;
  ersetzt durch `Image::getHtml()` und `StringUtil::specialchars()`.
* Fix: `$this->import('BackendUser', 'User')` in den fünf Rückrufklassen bricht unter
  Contao 5 ab, weil `System::import()` den unqualifizierten Klassennamen nicht auflöst. Die
  Rückrufe holen den Benutzer jetzt über `BackendUser::getInstance()`. Der Konstruktor
  selbst bleibt: Unter Contao 4.13 ist `Backend::__construct()` protected, ohne eigenen
  öffentlichen Konstruktor ließen sich die Klassen dort nicht von außen erzeugen.
* Fix: Der Kurzname `'Table'` als `dataContainer` ist in Contao 5 weg; überall steht jetzt
  `DC_Table::class`.
* Fix: Im Inhaltselement „Mannschaftsführer" verwies die Lightbox-Kennung auf
  `$objSpieler->id` — eine Variable, die es in dieser Klasse gar nicht gibt. In der
  Rundenübersicht stand dieselbe undefinierte Variable in der Schleife über die
  Mannschaften. Unter PHP 8 sind das Warnungen, in der Ausgabe fehlte die Gruppierung.
* Fix: In der Rundenübersicht wurde die Bildkennung `$bild_id` innerhalb der Spielerschleife
  nicht zurückgesetzt. Ein Spieler ohne eigenes Foto und ohne passendes Standardbild bekam
  deshalb das Bild seines Vorgängers.
* Fix: `pid` in `tl_teamtournament_players` verwies als `foreignKey` auf die eigene Tabelle
  statt auf `tl_teamtournament_teams`.
* Fix: Die Auswahllisten der Spieler in einer Paarung lasen `$dc->activeRecord->pid`. Bei
  einer neu angelegten Partie gibt es noch keinen Datensatz — dort brach die Maske ab; ab
  Contao 5 gilt der Zugriff außerdem als veraltet. Ermittelt wird der Wettkampf jetzt über
  den gespeicherten Datensatz beziehungsweise über den Parameter `pid` der Adresse.
* Fix: Die Mannschaftsliste eines Wettkampfes las die Turnierkennung aus `Input::get('id')`.
  Das stimmt nur in der Übersicht; beim Bearbeiten eines Wettkampfes steht dort dessen
  eigene Kennung, und die Liste blieb leer oder zeigte fremde Mannschaften.
* Fix: Der Wizard „Seite auswählen" am Feld Homepage zeigte auf `contao/page.php`, eine
  Adresse aus Contao 3. Ersetzt durch `'dcaPicker' => true`, das beide Fassungen kennen.
* Fix: In `tl_teamtournament_matches.php` war ein Kommentar in ISO-8859-1 kodiert; die Datei
  war damit als einzige im Bundle kein gültiges UTF-8.
* Fix: Fehlende Beschriftungen ergänzt: `editheader` in Turnier und Mannschaft, `cut` in
  Spieler, Wettkampf und Paarung, `tt-captain_legend` beim Inhaltselement
  „Mannschaftsführer".
* Change: Der Umschalter „veröffentlicht" kommt ohne `codefog/contao-haste` aus. Contao 4.13
  wie Contao 5 rendern `act=toggle&field=published` zusammen mit `'toggle' => true` am Feld
  selbsttätig als Ajax-Umschalter. Die Abhängigkeit ist damit entfallen.
* Change: `components/flag-icon-css` ist entfallen. Die drei Inhaltselemente legten dafür
  bei jedem Aufruf eine Verknüpfung unter `web/bundles/` an — ein Verzeichnis, das es seit
  Contao 5 nicht mehr gibt. Benutzt wurde die Bibliothek nirgends.
* Change: `schachbulle/contao-helper-bundle` steht jetzt in der `composer.json`. Die
  Datumsfelder der Spieler und Kapitäne haben es schon immer benutzt, ohne dass es als
  Abhängigkeit geführt war.
* Change: Die Rundenübersicht liest nur noch die Spieler des gewählten Turniers ein. Bisher
  wurde die gesamte Spielertabelle geladen und für jeden Spieler ein Bild erzeugt, auch für
  die fremder Turniere.
* Change: Die Wettkämpfe einer Runde erscheinen in der Frontend-Ausgabe wie im Backend nach
  Tischnummer sortiert. Bisher gab es dafür keine Sortierung, die Reihenfolge hing von der
  Datenbank ab.
* Change: Die gemeinsamen Teile der drei Inhaltselemente — Bilderzeugung, Standardbild und
  Altersberechnung — liegen jetzt in `Classes\Helfer` statt dreimal als Kopie. Für die
  Altersberechnung gibt es Unit-Tests unter `tests/`.
* Change: Jede PHP-Datei hat `declare(strict_types=1)` und einen deutschen Kommentarblock je
  Methode.

## Version 0.2.4 (2026-08-03)

**Wichtig beim Aktualisieren:** Die beiden Standardbilder in den Einstellungen (männlich und
weiblich) müssen einmal neu ausgewählt und gespeichert werden. Die bisher gespeicherten
Werte sind beschädigt und werden durch das Update nicht repariert.

* Fix: Die beiden Standardbilder (Einstellungen, Bereich Mannschaftsturniere) blieben in
  Aufstellung, Mannschaftsführer und Rundenübersicht wirkungslos. Der Dateibaum liefert die
  Kennung der Datei als 16 Byte langen Binärwert; die Einstellungen landen aber in
  `system/config/localconfig.php`, also in einer PHP-Datei mit einfach gequoteten
  Zeichenketten. Nullbytes und Backslashes überleben das nicht — aus 16 Byte wurden beim
  Zurücklesen 19, und `FilesModel::findByUuid()` fand die Datei nie. Ein `save_callback`
  legt die Kennung jetzt in der lesbaren Schreibweise ab, die dieselbe Methode ebenso
  versteht. Der Fehler fiel nicht auf, weil im Backend weiterhin ein Bild ausgewählt aussah.

## Version 0.2.3 (2026-08-02)

* Change: Die drei Auswahllisten der Bildgrößen im Mannschaftsturnier holen den Dienst
  jetzt unter seinem aktuellen Namen `contao.image.sizes`. Der bisher benutzte Name
  `contao.image.image_sizes` ist unter Contao 4.13 nur ein veralteter Alias auf denselben
  Dienst und in Contao 5 entfernt — dort bräche das Bearbeiten eines Turniers mit „You have
  requested a non-existent service“ ab.

## Version 0.2.2 (2026-07-29)

* Fix: Warning: Undefined array key "deleteConfirm" bei contao:migrate -> Lesezugriffe auf $GLOBALS['TL_LANG'] in den DCA-Dateien mit `?? null` bzw. `?? array()` abgesichert, da der DcaLoader die Sprachdateien noch nicht geladen hat
* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

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

