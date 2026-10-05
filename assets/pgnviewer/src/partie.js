/*
 * PGN-Viewer der Mannschaftsturniere — PGN lesen und nachspielen
 *
 * Dieses Modul kennt weder das DOM noch Contao. Es macht aus einem PGN-Text
 * einen Zugbaum mit der Stellung nach jedem Zug. Dadurch lässt es sich ohne
 * Browser prüfen (node --test) und von anderen Modulen wiederverwenden.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

import pgnParser from '@mliebelt/pgn-parser';
import { Chess, DEFAULT_POSITION } from 'chess.js';

/**
 * Bringt einen PGN-Text in eine Form, die der Parser sicher liest.
 *
 * - Zeilenumbrüche aus Windows (CRLF) und dem alten Mac-Format (CR) werden
 *   zu LF. Der Parser verkraftet CRLF zwar, gemischte Umbrüche aus
 *   zusammenkopierten Dateien aber nicht in jedem Fall.
 * - Ein vorangestelltes Byte-Order-Mark (aus Dateien, die in Windows-Editoren
 *   gespeichert wurden) wird entfernt; es stünde sonst vor dem ersten Tag und
 *   brächte den Parser zum Abbruch.
 * - Geschützte Leerzeichen (U+00A0) werden zu normalen. Sie gelangen beim
 *   Kopieren aus Webseiten und Textverarbeitungen in die Daten, und der
 *   Parser erkennt sie nicht als Trenner zwischen Zügen.
 *
 * @param {string} text Der PGN-Text, wie er aus der Datenbank kommt
 * @returns {string} Der bereinigte Text, ohne führende und abschließende Leerzeichen
 */
export function normalisierePgn(text) {
    return String(text ?? '')
        .replace(new RegExp('^' + String.fromCharCode(0xFEFF)), '')
        .replace(/\r\n?/g, '\n')
        .replace(new RegExp(String.fromCharCode(0xA0), 'g'), ' ')
        .trim();
}

/**
 * Liest alle Partien eines PGN-Textes und spielt sie nach.
 *
 * Ein Lesefehler betrifft den ganzen Text: Der Parser bricht an der ersten
 * unlesbaren Stelle ab und liefert keine Teilergebnisse. Ein unzulässiger Zug
 * betrifft dagegen nur seine Partie — sie bleibt bis vor diesen Zug
 * nachspielbar und trägt den Fehler in "fehler".
 *
 * @param {string} text Der PGN-Text
 * @returns {{partien: object[], fehler: null|{art: string, zeile?: number, spalte?: number, meldung: string}}}
 *          Die gelesenen Partien (siehe baueBaum()); "fehler" ist gesetzt, wenn
 *          der Text nicht lesbar war (art "lesen") oder keine Partie enthielt
 *          (art "leer"). In beiden Fällen ist "partien" leer.
 */
export function lesePartien(text) {
    const bereinigt = normalisierePgn(text);

    if ('' === bereinigt) {
        return { partien: [], fehler: { art: 'leer', meldung: 'leer' } };
    }

    let roh;

    try {
        roh = pgnParser.parse(bereinigt, { startRule: 'games' });
    } catch (e) {
        const start = e && e.location ? e.location.start : null;

        return {
            partien: [],
            fehler: {
                art: 'lesen',
                zeile: start ? start.line : 0,
                spalte: start ? start.column : 0,
                meldung: e && e.message ? String(e.message) : 'Syntaxfehler'
            }
        };
    }

    // Eine "Partie" ohne jeden Zug und ohne Tags entsteht, wenn der Text nur
    // aus Leerraum zwischen zwei Partien besteht; sie wird nicht mitgezählt
    const partien = (roh || [])
        .filter((p) => (p.moves && p.moves.length) || (p.tags && Object.keys(p.tags).some((k) => 'messages' !== k)))
        .map((p) => baueBaum(p));

    if (!partien.length) {
        return { partien: [], fehler: { art: 'leer', meldung: 'leer' } };
    }

    return { partien, fehler: null };
}

/**
 * Baut aus einer geparsten Partie den Zugbaum.
 *
 * Jeder Knoten steht für die Stellung **nach** einem Zug. Der Wurzelknoten
 * steht für die Ausgangsstellung und hat keinen Zug. Varianten hängen an dem
 * Zug, zu dem sie eine Alternative sind; ihr erster Knoten hat deshalb
 * denselben Vorgänger wie dieser Zug.
 *
 * Die Ausgangsstellung kommt aus dem FEN-Tag, sofern einer da ist. Das ist die
 * einzige Stelle, an der der Viewer den Tags vertraut: Ohne sie ließe sich
 * eine Partie, die nicht in der Grundstellung beginnt, gar nicht nachspielen.
 * Ist der FEN-Tag ungültig, beginnt die Partie in der Grundstellung und der
 * Fehler wird gemeldet.
 *
 * @param {object} roh Eine Partie aus pgn-parser ({tags, gameComment, moves})
 * @returns {object} {wurzel, knoten: Map<number, object>, fehler: null|object}
 */
export function baueBaum(roh) {
    const tags = roh.tags || {};
    const zaehler = { naechsteId: 0 };
    const knoten = new Map();
    let startFen = DEFAULT_POSITION;
    let fehler = null;

    if (tags.FEN) {
        try {
            new Chess(tags.FEN);
            startFen = tags.FEN;
        } catch (e) {
            fehler = { art: 'fen', zug: String(tags.FEN) };
        }
    }

    const wurzel = neuerKnoten(zaehler, knoten, {
        san: null,
        fen: startFen,
        vorher: null,
        tiefe: 0,
        kommentarNach: roh.gameComment && roh.gameComment.comment ? roh.gameComment.comment : ''
    });

    const zeilenFehler = spieleZeile(roh.moves || [], wurzel, 0, zaehler, knoten);

    return { wurzel, knoten, fehler: fehler || zeilenFehler };
}

/**
 * Spielt eine Zugfolge ab einem Knoten nach und hängt sie in den Baum.
 *
 * Rekursiv für Varianten: Eine Variante zu Zug M beginnt in der Stellung vor
 * M, also bei M.vorher, und bekommt eine um eins größere Tiefe.
 *
 * chess.js bestimmt dabei für jeden Zug die Stellung und die gültige
 * Schreibweise. Der Zug wird im nachsichtigen Modus gelesen, damit auch
 * Schreibweisen wie "Ng1f3" oder fehlende Schlagzeichen durchgehen, wie sie
 * in handgeschriebenen PGN-Dateien vorkommen.
 *
 * @param {object[]} zuege     Die Züge aus pgn-parser
 * @param {object}   start     Knoten, dessen Stellung vor dem ersten Zug gilt
 * @param {number}   tiefe     0 für die Hauptvariante, sonst die Schachtelung
 * @param {object}   zaehler   Laufende Knotennummer, wird fortgeschrieben
 * @param {Map}      knoten    Alle Knoten nach Nummer, wird ergänzt
 * @returns {null|object} Der erste Zugfehler in dieser Zeile oder einer ihrer
 *                        Varianten, sonst null
 */
function spieleZeile(zuege, start, tiefe, zaehler, knoten) {
    let aktuell = start;
    let ersterFehler = null;

    for (const zug of zuege) {
        const san = zug.notation && zug.notation.notation ? zug.notation.notation : '';
        const brett = new Chess(aktuell.fen);
        let ergebnis;

        try {
            ergebnis = brett.move(san, { strict: false });
        } catch (e) {
            ergebnis = null;
        }

        if (!ergebnis) {
            return ersterFehler || { art: 'zug', zug: san, knoten: aktuell.id };
        }

        const neu = neuerKnoten(zaehler, knoten, {
            san: ergebnis.san,
            fen: brett.fen(),
            von: ergebnis.from,
            nach: ergebnis.to,
            farbe: ergebnis.color,
            // chess.js zählt die Zugnummer nach dem Zug weiter, wenn Schwarz
            // gezogen hat; vor dem Zug gilt die Nummer aus der alten Stellung
            zugnummer: Number(aktuell.fen.split(' ')[5]) || 1,
            // Doppelte NAGs entfernen: "5... Nxd5?! $6" liefert $6 zweimal —
            // angezeigt als "?!?!", und aus "! $1" würde "!!", was als
            // "sehr guter Zug" gelesen wird
            nags: Array.isArray(zug.nag) ? [...new Set(zug.nag)] : [],
            kommentarVor: zug.commentMove || '',
            kommentarNach: zug.commentAfter || '',
            vorher: aktuell,
            tiefe
        });

        // Der Hauptzug wird Nachfolger, bevor die Varianten gespielt werden.
        // Die Varianten beginnen am selben Vorgänger und hängen sich dort
        // deshalb nicht mehr als "weiter" ein (siehe die ||-Zuweisung).
        aktuell.weiter = aktuell.weiter || neu;

        for (const variante of zug.variations || []) {
            if (!variante || !variante.length) {
                continue;
            }

            // Die Variante ist eine Alternative zu diesem Zug und beginnt
            // deshalb in der Stellung davor. Ihr erster Knoten bekommt die
            // nächste freie Nummer — sofern ihr erster Zug überhaupt gültig ist.
            const ersteNummer = zaehler.naechsteId;
            const fehler = spieleZeile(variante, aktuell, tiefe + 1, zaehler, knoten);

            if (zaehler.naechsteId > ersteNummer) {
                neu.varianten.push(knoten.get(ersteNummer));
            }

            ersterFehler = ersterFehler || fehler;
        }

        aktuell = neu;
    }

    return ersterFehler;
}

/**
 * Legt einen Knoten an und trägt ihn in das Verzeichnis ein.
 *
 * @param {object} zaehler Laufende Knotennummer
 * @param {Map}    knoten  Verzeichnis aller Knoten
 * @param {object} werte   Eigenschaften des Knotens
 * @returns {object} Der neue Knoten
 */
function neuerKnoten(zaehler, knoten, werte) {
    const k = Object.assign({
        id: zaehler.naechsteId++,
        san: null,
        fen: '',
        von: null,
        nach: null,
        farbe: null,
        zugnummer: 0,
        nags: [],
        kommentarVor: '',
        kommentarNach: '',
        vorher: null,
        weiter: null,
        varianten: [],
        tiefe: 0
    }, werte);

    knoten.set(k.id, k);

    return k;
}

/**
 * Liefert den letzten Knoten der Zeile, in der ein Knoten steht.
 *
 * "Zum Ende" folgt bewusst der aktuellen Zeile: Wer in einer Variante steht
 * und ans Ende springt, landet am Ende dieser Variante, nicht am Ende der
 * Hauptpartie.
 *
 * @param {object} k Ein Knoten
 * @returns {object} Der letzte Knoten dieser Zeile
 */
export function zeilenende(k) {
    let aktuell = k;

    while (aktuell.weiter) {
        aktuell = aktuell.weiter;
    }

    return aktuell;
}
