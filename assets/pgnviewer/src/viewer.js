/*
 * PGN-Viewer der Mannschaftsturniere — Darstellung und Bedienung
 *
 * Wird erst geladen, wenn die erste Partie auf einer Seite geöffnet wird
 * (siehe loader.js). Jeder Aufruf von starteViewer() erzeugt eine eigene,
 * unabhängige Instanz; es gibt keinen gemeinsamen Zustand zwischen den
 * Viewern einer Seite.
 *
 * Das Modul kennt Contao nicht. Es braucht nur ein Zielelement und ein
 * Datenobjekt {sprache, pgn, kopf} und lässt sich deshalb auch von anderen
 * Modulen verwenden.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

import { Chessboard, COLOR, BORDER_TYPE } from 'cm-chessboard/src/Chessboard.js';
import { Markers, MARKER_TYPE } from 'cm-chessboard/src/extensions/markers/Markers.js';
import { Accessibility } from 'cm-chessboard/src/extensions/accessibility/Accessibility.js';
import { lesePartien, zeilenende } from './partie.js';
import { texte, zugAnzeige, zugAnsage, nagZeichen } from './sprache.js';

/**
 * Laufende Nummer für eindeutige IDs, wenn mehrere Viewer auf einer Seite sind.
 */
let naechsteViewerNummer = 1;

/**
 * Symbole der Navigationsknöpfe als SVG-Pfade.
 *
 * Bewusst keine Unicode-Zeichen wie ⏮: Die werden auf manchen Systemen als
 * farbiges Emoji dargestellt, haben dann keinen verlässlichen Kontrast und
 * werden von Bildschirmlesern mit ihrem Unicode-Namen vorgelesen.
 */
const SYMBOLE = {
    anfang: 'M5 4h2v16H5zM20 4v16L9 12z',
    zurueck: 'M18 4v16L6 12z',
    vor: 'M6 4v16l12-8z',
    ende: 'M17 4h2v16h-2zM4 4v16l11-8z'
};

/**
 * Startet einen Viewer im Zielelement.
 *
 * @param {HTMLElement} ziel     Das Element, in das der Viewer geschrieben wird;
 *                               sein bisheriger Inhalt außer den Datenblöcken
 *                               wird ersetzt
 * @param {object}      daten    {sprache: 'de'|'en', pgn: string, kopf: object}
 * @param {object}      optionen {assetsUrl: string} Adresse des Ordners mit
 *                               pieces/ und extensions/, mit abschließendem /
 * @returns {PgnViewer} Die Instanz
 */
export function starteViewer(ziel, daten, optionen) {
    return new PgnViewer(ziel, daten, optionen);
}

/**
 * Ein einzelner Viewer: Kopf, Brett, Navigation, Zugliste und Ansage.
 */
export class PgnViewer {
    /**
     * Baut den Viewer auf und zeigt die Ausgangsstellung der ersten Partie.
     *
     * @param {HTMLElement} ziel     Zielelement
     * @param {object}      daten    {sprache, pgn, kopf}
     * @param {object}      optionen {assetsUrl}
     */
    constructor(ziel, daten, optionen = {}) {
        this.ziel = ziel;
        this.sprache = 'en' === daten.sprache ? 'en' : 'de';
        this.t = texte(this.sprache);
        this.kopf = daten.kopf || {};
        this.assetsUrl = optionen.assetsUrl || './';
        this.nummer = naechsteViewerNummer++;
        this.brett = null;
        this.partie = null;
        this.aktuell = null;
        this.zugKnoepfe = new Map();
        this.animiert = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.baueGeruest();

        const gelesen = lesePartien(daten.pgn || '');

        if (gelesen.fehler) {
            // Die Meldung steht in einer role="alert"-Zeile und wird dadurch
            // ohnehin vorgelesen; eine zusätzliche Ansage wäre doppelt
            this.zeigeFehler('lesen' === gelesen.fehler.art
                ? this.t.fehlerLesen(gelesen.fehler.zeile, gelesen.fehler.spalte)
                : this.t.fehlerLeer);
            this.inhalt.hidden = true;
            return;
        }

        this.partien = gelesen.partien;
        this.baueAuswahl();
        this.waehlePartie(0);
    }

    /**
     * Legt die feste Struktur an: Kopf, Auswahl, Fehlerzeile, Brett mit
     * Navigation, Zugliste und die unsichtbare Ansage.
     *
     * Die Ansage ist eine aria-live-Region mit "polite": Sie unterbricht den
     * Bildschirmleser nicht mitten im Satz, wenn man schnell durch die Züge
     * blättert, sondern liest die jeweils letzte Stellung vor.
     */
    baueGeruest() {
        const id = 'tt-pgnv-' + this.nummer;

        // Die Datenblöcke (script) bleiben stehen, alles andere wird ersetzt
        for (const kind of Array.from(this.ziel.childNodes)) {
            if (!(kind instanceof HTMLScriptElement)) {
                kind.remove();
            }
        }

        this.wurzel = element('div', { class: 'tt-pgnv', lang: this.sprache, role: 'group', 'aria-labelledby': id + '-kopf' });

        this.wurzel.append(this.baueKopf(id + '-kopf'));

        this.auswahlBereich = element('div', { class: 'tt-pgnv__auswahl', hidden: '' });
        this.fehler = element('p', { class: 'tt-pgnv__fehler', role: 'alert', hidden: '' });
        this.hinweis = element('p', { class: 'tt-pgnv-unsichtbar', id: id + '-hilfe' }, this.t.hilfe);

        this.inhalt = element('div', { class: 'tt-pgnv__inhalt' });

        const brettBereich = element('div', { class: 'tt-pgnv__brettbereich' });
        this.brettElement = element('div', { class: 'tt-pgnv__brett' });

        const steuerung = element('div', { class: 'tt-pgnv__steuerung', role: 'toolbar', 'aria-label': this.t.navigation, 'aria-describedby': id + '-hilfe' });
        this.knopfAnfang = this.baueKnopf(this.t.anfang, SYMBOLE.anfang, () => this.gehe(this.partie.wurzel));
        this.knopfZurueck = this.baueKnopf(this.t.zurueck, SYMBOLE.zurueck, () => this.gehe(this.aktuell.vorher));
        this.knopfVor = this.baueKnopf(this.t.vor, SYMBOLE.vor, () => this.gehe(this.aktuell.weiter));
        this.knopfEnde = this.baueKnopf(this.t.ende, SYMBOLE.ende, () => this.gehe(zeilenende(this.aktuell)));
        steuerung.append(this.knopfAnfang, this.knopfZurueck, this.knopfVor, this.knopfEnde);

        brettBereich.append(this.brettElement, steuerung);

        this.zugliste = element('div', { class: 'tt-pgnv__zuege', role: 'region', 'aria-label': this.t.zugliste });

        this.inhalt.append(brettBereich, this.zugliste);

        this.ansageElement = element('p', { class: 'tt-pgnv-unsichtbar', 'aria-live': 'polite', 'aria-atomic': 'true' });

        this.wurzel.append(this.auswahlBereich, this.fehler, this.hinweis, this.inhalt, this.ansageElement);
        this.wurzel.addEventListener('keydown', (e) => this.taste(e));

        this.ziel.append(this.wurzel);
    }

    /**
     * Baut den Kopf mit Spielern, Ergebnis, Runde und Brett.
     *
     * Alle Angaben stammen aus den Datensätzen der Rundenübersicht, nicht aus
     * den PGN-Tags — die sind in den Turnierdateien nicht verlässlich gefüllt.
     * Die Farbe steht als Wort da und nicht nur als Farbfeld, damit der Kopf
     * auch ohne Farbwahrnehmung eindeutig ist.
     *
     * @param {string} id ID des Kopfes, auf die sich das aria-labelledby der
     *                    Viewer-Gruppe bezieht
     * @returns {HTMLElement} Der Kopf
     */
    baueKopf(id) {
        const k = this.kopf;
        const bekannt = false !== k.farbeBekannt;
        const kopf = element('div', { class: 'tt-pgnv__kopf', id });

        const zeile = (farbe, spieler, beschriftung) => {
            const p = element('p', { class: 'tt-pgnv__spieler tt-pgnv__spieler--' + farbe });
            if (bekannt) {
                p.append(element('span', { class: 'tt-pgnv__farbfeld', 'aria-hidden': 'true' }));
            }
            p.append(element('span', { class: 'tt-pgnv__farbe' }, beschriftung + ':'), ' ');
            const name = [spieler.titel, spieler.name].filter(Boolean).join(' ');
            p.append(element('strong', {}, name || '–'));
            if (spieler.elo) {
                p.append(' ', element('span', { class: 'tt-pgnv__elo' }, '(' + spieler.elo + ')'));
            }
            if (spieler.mannschaft) {
                p.append(' ', element('span', { class: 'tt-pgnv__mannschaft' }, '– ' + spieler.mannschaft));
            }
            return p;
        };

        kopf.append(
            zeile('weiss', k.weiss || {}, bekannt ? this.t.weiss : this.t.spieler1),
            zeile('schwarz', k.schwarz || {}, bekannt ? this.t.schwarz : this.t.spieler2)
        );

        const meta = [];
        if (k.ergebnis) {
            meta.push(this.t.ergebnis + ' ' + k.ergebnis);
        }
        if (k.runde) {
            meta.push(this.t.runde + ' ' + k.runde);
        }
        if (k.brett) {
            meta.push(this.t.brett + ' ' + k.brett);
        }
        if (!bekannt) {
            meta.push(this.t.farbeUnbekannt);
        }

        if (meta.length) {
            kopf.append(element('p', { class: 'tt-pgnv__meta' }, meta.join(' · ')));
        }

        return kopf;
    }

    /**
     * Legt einen Navigationsknopf an.
     *
     * Ein Knopf, der gerade nichts bewirken kann (etwa "zurück" in der
     * Ausgangsstellung), wird nicht mit disabled gesperrt, sondern mit
     * aria-disabled markiert. disabled würde den Fokus vom Knopf nehmen: Wer
     * mit der Tastatur bis zum Anfang zurückblättert, stünde danach irgendwo
     * auf der Seite.
     *
     * @param {string}   beschriftung Zugänglicher Name und Tooltip
     * @param {string}   pfad         SVG-Pfad des Symbols
     * @param {Function} aktion       Wird beim Aktivieren ausgeführt
     * @returns {HTMLButtonElement} Der Knopf
     */
    baueKnopf(beschriftung, pfad, aktion) {
        const knopf = element('button', { type: 'button', class: 'tt-pgnv__knopf', 'aria-label': beschriftung, title: beschriftung });
        knopf.innerHTML = '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="' + pfad + '"/></svg>';
        knopf.addEventListener('click', () => {
            if ('true' !== knopf.getAttribute('aria-disabled')) {
                aktion();
            }
        });
        return knopf;
    }

    /**
     * Zeigt die Auswahlliste, wenn das PGN-Feld mehrere Partien enthält.
     *
     * Die Einträge nennen nur die Nummer ("Partie 1 von 2"). Namen aus den
     * Tags wären zwar sprechender, sind aber nicht verlässlich — und der Kopf
     * mit den Namen aus dem Datensatz gilt ohnehin für alle Partien des Bretts.
     */
    baueAuswahl() {
        if (this.partien.length < 2) {
            return;
        }

        const id = 'tt-pgnv-' + this.nummer + '-auswahl';
        const label = element('label', { for: id }, this.t.partieAuswahl + ' ');
        const auswahl = element('select', { id, class: 'tt-pgnv__partiewahl' });

        this.partien.forEach((_, i) => {
            auswahl.append(element('option', { value: String(i) }, this.t.partieVonGesamt(i + 1, this.partien.length)));
        });

        auswahl.addEventListener('change', () => this.waehlePartie(Number(auswahl.value)));

        this.auswahlBereich.append(label, auswahl);
        this.auswahlBereich.hidden = false;
    }

    /**
     * Wechselt zu einer Partie und zeigt deren Ausgangsstellung.
     *
     * Beim ersten Aufruf wird dabei das Brett erzeugt. Die Stellung beginnt
     * vor dem ersten Zug, damit sich auch der erste Zug von Weiß ansehen lässt.
     *
     * @param {number} index Nummer der Partie, ab 0
     */
    waehlePartie(index) {
        this.partie = this.partien[index];
        this.zeigeFehler('');

        if (this.partie.fehler) {
            if ('zug' === this.partie.fehler.art) {
                this.zeigeFehler(this.t.fehlerZug(this.partie.fehler.zug));
            } else if ('fen' === this.partie.fehler.art) {
                this.zeigeFehler(this.t.fehlerFen);
            }
        }

        if (!this.brett) {
            this.brett = new Chessboard(this.brettElement, {
                position: this.partie.wurzel.fen,
                orientation: COLOR.white,
                assetsUrl: this.assetsUrl,
                style: {
                    cssClass: 'default-contrast',
                    showCoordinates: true,
                    borderType: BORDER_TYPE.frame,
                    animationDuration: this.animiert ? 200 : 0
                },
                extensions: [
                    { class: Markers, props: { autoMarkers: null } },
                    {
                        class: Accessibility,
                        props: {
                            language: this.sprache,
                            movePieceForm: false,
                            boardAsTable: true,
                            piecesAsList: true,
                            visuallyHidden: true
                        }
                    }
                ]
            });
        }

        this.baueZugliste();
        this.gehe(this.partie.wurzel, { ansagen: false });
        this.ansage(this.t.geladen + ' ' + this.t.ausgangsstellung + '.');
    }

    /**
     * Baut die Zugliste der aktuellen Partie neu auf.
     */
    baueZugliste() {
        this.zugKnoepfe.clear();
        this.zugliste.replaceChildren();

        const hauptzeile = element('div', { class: 'tt-pgnv__zeile' });

        if (this.partie.wurzel.kommentarNach) {
            hauptzeile.append(this.baueKommentar(this.partie.wurzel.kommentarNach), ' ');
        }

        if (this.partie.wurzel.weiter) {
            this.schreibeZeile(this.partie.wurzel.weiter, hauptzeile);
        }

        this.zugliste.append(hauptzeile);
    }

    /**
     * Schreibt eine Zugfolge samt Kommentaren und Varianten in ein Element.
     *
     * Die Varianten stehen, wie in der PGN-Schreibweise üblich, unmittelbar
     * hinter dem Zug, zu dem sie eine Alternative sind. Sie werden als eigener
     * eingerückter Block mit Klammern gesetzt und für Bildschirmleser als
     * Gruppe "Variante" ausgezeichnet — erkennbar also an Einrückung, Klammer,
     * Schriftschnitt und Ansage, nicht an einer Farbe.
     *
     * @param {object}      start    Erster Knoten der Zeile
     * @param {HTMLElement} behaelter Element, das die Zeile aufnimmt
     */
    schreibeZeile(start, behaelter) {
        let k = start;
        let nummerNoetig = true;

        while (k) {
            if (k.kommentarVor) {
                behaelter.append(this.baueKommentar(k.kommentarVor), ' ');
                nummerNoetig = true;
            }

            // Vor einem Zug von Weiß steht immer die Nummer, vor einem Zug von
            // Schwarz nur am Zeilenanfang und nach Kommentaren oder Varianten
            if ('w' === k.farbe) {
                behaelter.append(element('span', { class: 'tt-pgnv__nummer', 'aria-hidden': 'true' }, k.zugnummer + '.'), ' ');
            } else if (nummerNoetig) {
                behaelter.append(element('span', { class: 'tt-pgnv__nummer', 'aria-hidden': 'true' }, k.zugnummer + '…'), ' ');
            }

            behaelter.append(this.baueZugknopf(k), ' ');
            nummerNoetig = false;

            if (k.kommentarNach) {
                behaelter.append(this.baueKommentar(k.kommentarNach), ' ');
                nummerNoetig = true;
            }

            for (const variante of k.varianten) {
                const block = element('div', { class: 'tt-pgnv__variante', role: 'group', 'aria-label': this.t.variante });
                block.append(element('span', { class: 'tt-pgnv__klammer', 'aria-hidden': 'true' }, '('));
                this.schreibeZeile(variante, block);
                block.append(
                    element('span', { class: 'tt-pgnv__klammer', 'aria-hidden': 'true' }, ')'),
                    element('span', { class: 'tt-pgnv-unsichtbar' }, this.t.varianteEnde)
                );
                behaelter.append(block);
                nummerNoetig = true;
            }

            k = k.weiter;
        }
    }

    /**
     * Legt den Knopf für einen Zug in der Zugliste an.
     *
     * Die Zugliste nutzt einen wandernden Tabindex: Nur der aktuelle Zug ist
     * mit Tab erreichbar, die übrigen über die Pfeiltasten. Sonst müsste man
     * sich bei einer langen Partie durch über hundert Tabstopps kämpfen, um
     * am Viewer vorbeizukommen.
     *
     * @param {object} k Der Knoten des Zuges
     * @returns {HTMLButtonElement} Der Knopf
     */
    baueZugknopf(k) {
        const zeichen = k.nags.map(nagZeichen).join('');
        const knopf = element('button', {
            type: 'button',
            class: 'tt-pgnv__zug' + (k.tiefe ? ' tt-pgnv__zug--variante' : ''),
            tabindex: '-1',
            'aria-label': this.zugBeschreibung(k)
        }, zugAnzeige(k.san, this.sprache) + zeichen);

        knopf.addEventListener('click', () => this.gehe(k, { fokus: true }));
        this.zugKnoepfe.set(k.id, knopf);

        return knopf;
    }

    /**
     * Legt einen Kommentar an.
     *
     * Der Text wird ausschließlich als Textknoten eingesetzt. PGN-Kommentare
     * stammen aus fremden Dateien; als HTML eingesetzt könnten sie Markup oder
     * Skript in die Seite tragen.
     *
     * @param {string} text Der Kommentar
     * @returns {HTMLElement} Das Kommentarelement
     */
    baueKommentar(text) {
        const span = element('span', { class: 'tt-pgnv__kommentar' });
        span.append(element('span', { class: 'tt-pgnv-unsichtbar' }, this.t.kommentar + ': '), text);
        return span;
    }

    /**
     * Beschreibt einen Zug für Bildschirmleser.
     *
     * @param {object} k Der Knoten des Zuges
     * @returns {string} Etwa "12. Weiß: Springer schlägt auf f 3, Schach"
     */
    zugBeschreibung(k) {
        const wer = 'w' === k.farbe ? this.t.zugVonWeiss(k.zugnummer) : this.t.zugVonSchwarz(k.zugnummer);
        const praefix = k.tiefe ? this.t.variante + ', ' : '';
        return praefix + wer + ': ' + zugAnsage(k.san, this.sprache, k.nags);
    }

    /**
     * Geht zu einer Stellung: Brett, Zugliste, Knöpfe und Ansage.
     *
     * @param {object|null} k        Ziel-Knoten; null wird ignoriert (etwa
     *                               "vor" am Ende der Partie)
     * @param {object}      optionen {fokus: bool} Fokus auf den Zug in der
     *                               Liste setzen; {ansagen: bool} Stellung
     *                               vorlesen, Vorgabe true
     */
    gehe(k, optionen = {}) {
        if (!k) {
            return;
        }

        const vorher = this.aktuell;
        this.aktuell = k;

        // Animation nur für einen einzelnen Schritt; bei Sprüngen über viele
        // Züge würden die Figuren quer über das Brett fliegen
        const nachbar = vorher && (vorher.weiter === k || k.weiter === vorher);
        this.brett.setPosition(k.fen, this.animiert && nachbar);

        this.brett.removeMarkers();
        if (k.von && k.nach) {
            this.brett.addMarker(MARKER_TYPE.frame, k.von);
            this.brett.addMarker(MARKER_TYPE.frame, k.nach);
        }

        if (vorher && this.zugKnoepfe.has(vorher.id)) {
            const alt = this.zugKnoepfe.get(vorher.id);
            alt.removeAttribute('aria-current');
            alt.classList.remove('ist-aktuell');
            alt.tabIndex = -1;
        }

        const knopf = this.zugKnoepfe.get(k.id);

        if (knopf) {
            knopf.setAttribute('aria-current', 'step');
            knopf.classList.add('ist-aktuell');
            knopf.tabIndex = 0;
            this.inListeSichtbar(knopf);

            if (optionen.fokus) {
                knopf.focus({ preventScroll: true });
            }
        } else {
            // Ausgangsstellung: Der erste Zug wird zum Tabstopp der Liste
            const erster = this.partie.wurzel.weiter ? this.zugKnoepfe.get(this.partie.wurzel.weiter.id) : null;
            if (erster) {
                erster.tabIndex = 0;
            }
            this.zugliste.scrollTop = 0;
        }

        this.setzeGesperrt(this.knopfAnfang, k === this.partie.wurzel);
        this.setzeGesperrt(this.knopfZurueck, !k.vorher);
        this.setzeGesperrt(this.knopfVor, !k.weiter);
        this.setzeGesperrt(this.knopfEnde, !k.weiter);

        if (false !== optionen.ansagen) {
            let text = k.san ? this.zugBeschreibung(k) : this.t.ausgangsstellung;
            if (k.kommentarNach) {
                text += '. ' + this.t.kommentar + ': ' + k.kommentarNach;
            }
            this.ansage(text);
        }
    }

    /**
     * Setzt oder löst die Sperre eines Navigationsknopfes (siehe baueKnopf()).
     *
     * @param {HTMLButtonElement} knopf    Der Knopf
     * @param {boolean}           gesperrt true, wenn der Knopf nichts bewirken kann
     */
    setzeGesperrt(knopf, gesperrt) {
        if (gesperrt) {
            knopf.setAttribute('aria-disabled', 'true');
        } else {
            knopf.removeAttribute('aria-disabled');
        }
    }

    /**
     * Scrollt die Zugliste so, dass ein Zug sichtbar ist.
     *
     * Nicht über scrollIntoView(): Das scrollt auch die Seite mit, und auf dem
     * Handy spränge die Ansicht bei jedem Zug vom Brett weg zur Liste.
     *
     * @param {HTMLElement} knopf Der Zug
     */
    inListeSichtbar(knopf) {
        const liste = this.zugliste;
        // Die Liste ist im CSS positioniert (position: relative) und damit
        // offsetParent jedes Zuges, auch in verschachtelten Varianten
        const oben = knopf.offsetTop;
        const unten = oben + knopf.offsetHeight;

        if (oben < liste.scrollTop) {
            liste.scrollTop = oben - 8;
        } else if (unten > liste.scrollTop + liste.clientHeight) {
            liste.scrollTop = unten - liste.clientHeight + 8;
        }
    }

    /**
     * Wertet die Tastatur aus, solange der Fokus im Viewer steht.
     *
     * Die Tasten wirken nur innerhalb des Viewers, nicht auf der ganzen Seite:
     * Mehrere Viewer auf einer Seite laufen unabhängig, und die Pfeiltasten
     * sollen außerhalb weiter die Seite scrollen. In der Partieauswahl bleiben
     * die Pfeiltasten der Auswahlliste vorbehalten.
     *
     * @param {KeyboardEvent} e Das Tastaturereignis
     */
    taste(e) {
        if (!this.partie || e.altKey || e.ctrlKey || e.metaKey || e.target instanceof HTMLSelectElement) {
            return;
        }

        const ziele = {
            ArrowLeft: () => this.aktuell.vorher,
            ArrowRight: () => this.aktuell.weiter,
            Home: () => this.partie.wurzel,
            End: () => zeilenende(this.aktuell)
        };

        if (!ziele[e.key]) {
            return;
        }

        e.preventDefault();

        // Steht der Fokus in der Zugliste, wandert er mit; auf den
        // Navigationsknöpfen bleibt er, wo er ist
        const inListe = this.zugliste.contains(document.activeElement);
        this.gehe(ziele[e.key](), { fokus: inListe });

        if (inListe && this.aktuell === this.partie.wurzel && this.partie.wurzel.weiter) {
            this.zugKnoepfe.get(this.partie.wurzel.weiter.id).focus({ preventScroll: true });
        }
    }

    /**
     * Setzt den Text der Ansage.
     *
     * Der Text wird kurz geleert und etwas später gesetzt. Sonst liest ein
     * Bildschirmleser eine unveränderte Ansage nicht erneut vor — etwa wenn
     * man zweimal hintereinander zur Ausgangsstellung springt.
     *
     * Bewusst setTimeout statt requestAnimationFrame: Letzteres läuft nicht,
     * solange die Seite nicht sichtbar ist, und die Ansage bliebe dann leer
     * (im Test in einem verdeckten Browserfenster beobachtet). Ein schneller
     * Klick nach dem anderen ersetzt den noch ausstehenden Text.
     *
     * @param {string} text Der vorzulesende Text
     */
    ansage(text) {
        window.clearTimeout(this.ansageTimer);
        this.ansageElement.textContent = '';
        this.ansageTimer = window.setTimeout(() => {
            this.ansageElement.textContent = text;
        }, 50);
    }

    /**
     * Zeigt eine Fehlermeldung oder blendet sie aus.
     *
     * @param {string} text Die Meldung; eine leere Zeichenkette blendet aus
     */
    zeigeFehler(text) {
        this.fehler.textContent = text;
        this.fehler.hidden = '' === text;
    }
}

/**
 * Legt ein Element mit Attributen und Textinhalt an.
 *
 * Text wird immer als Textknoten eingesetzt, nie als HTML.
 *
 * @param {string} name     Elementname
 * @param {object} attribute Attribute; der Wert '' setzt ein boolesches Attribut
 * @param {string} [text]   Textinhalt
 * @returns {HTMLElement} Das Element
 */
function element(name, attribute = {}, text) {
    const el = document.createElement(name);

    for (const [schluessel, wert] of Object.entries(attribute)) {
        el.setAttribute(schluessel, wert);
    }

    if (undefined !== text) {
        el.textContent = text;
    }

    return el;
}
