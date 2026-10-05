/*
 * PGN-Viewer der Mannschaftsturniere — Beschriftungen und Zugansage
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Alle sichtbaren und vorgelesenen Texte des Viewers, Deutsch und Englisch.
 *
 * Die Sprache kommt vom Turnier (Feld "language"), nicht vom Browser: Die
 * Rundenübersicht ist in genau dieser Sprache beschriftet, und der Viewer
 * soll nicht mitten in der Tabelle die Sprache wechseln.
 */
export const TEXTE = {
    de: {
        viewer: 'Partie nachspielen',
        weiss: 'Weiß',
        schwarz: 'Schwarz',
        spieler1: 'Spieler 1',
        spieler2: 'Spieler 2',
        farbeUnbekannt: 'Farbverteilung nicht erfasst',
        ergebnis: 'Ergebnis',
        runde: 'Runde',
        brett: 'Brett',
        partie: 'Partie',
        partieAuswahl: 'Partie auswählen',
        partieVonGesamt: (n, gesamt) => `Partie ${n} von ${gesamt}`,
        navigation: 'Zugnavigation',
        anfang: 'Zum Anfang',
        zurueck: 'Einen Halbzug zurück',
        vor: 'Einen Halbzug vor',
        ende: 'Zum Ende',
        zugliste: 'Zugliste',
        variante: 'Variante',
        varianteEnde: 'Ende der Variante',
        kommentar: 'Kommentar',
        ausgangsstellung: 'Ausgangsstellung',
        hilfe: 'Tastatur: Pfeil links und rechts für einen Halbzug, Pos1 und Ende für Anfang und Ende.',
        geladen: 'Partie geladen.',
        laedt: 'Partie wird geladen …',
        fehlerLesen: (zeile, spalte) => `Die Partie konnte nicht gelesen werden (Zeile ${zeile}, Spalte ${spalte}). Bitte die PGN-Daten prüfen.`,
        fehlerLeer: 'In den PGN-Daten wurde keine Partie gefunden.',
        fehlerZug: (zug) => `Der Zug „${zug}" ist in dieser Stellung nicht möglich. Die Partie lässt sich nur bis zu diesem Zug nachspielen.`,
        fehlerFen: 'Die Ausgangsstellung (FEN) ist ungültig. Die Partie beginnt deshalb in der Grundstellung.',
        fehlerLaden: 'Der Viewer konnte nicht geladen werden.',
        zugVonWeiss: (n) => `${n}. Weiß`,
        zugVonSchwarz: (n) => `${n}. Schwarz`,
        figuren: { K: 'König', Q: 'Dame', R: 'Turm', B: 'Läufer', N: 'Springer' },
        buchstaben: { K: 'K', Q: 'D', R: 'T', B: 'L', N: 'S' },
        bauer: 'Bauer',
        schlaegt: 'schlägt auf',
        nach: 'nach',
        schach: 'Schach',
        matt: 'Schachmatt',
        kurzeRochade: 'kurze Rochade',
        langeRochade: 'lange Rochade',
        umwandlung: 'wandelt um in',
        nags: {
            $1: 'guter Zug', $2: 'Fehler', $3: 'sehr guter Zug', $4: 'grober Fehler',
            $5: 'interessanter Zug', $6: 'zweifelhafter Zug', $10: 'ausgeglichen',
            $13: 'unklar', $14: 'Weiß steht leicht besser', $15: 'Schwarz steht leicht besser',
            $16: 'Weiß steht besser', $17: 'Schwarz steht besser',
            $18: 'Weiß steht auf Gewinn', $19: 'Schwarz steht auf Gewinn'
        }
    },
    en: {
        viewer: 'Replay game',
        weiss: 'White',
        schwarz: 'Black',
        spieler1: 'Player 1',
        spieler2: 'Player 2',
        farbeUnbekannt: 'Colours not recorded',
        ergebnis: 'Result',
        runde: 'Round',
        brett: 'Board',
        partie: 'Game',
        partieAuswahl: 'Choose game',
        partieVonGesamt: (n, gesamt) => `Game ${n} of ${gesamt}`,
        navigation: 'Move navigation',
        anfang: 'Go to start',
        zurueck: 'One move back',
        vor: 'One move forward',
        ende: 'Go to end',
        zugliste: 'Moves',
        variante: 'Variation',
        varianteEnde: 'End of variation',
        kommentar: 'Comment',
        ausgangsstellung: 'Starting position',
        hilfe: 'Keyboard: left and right arrow for one move, Home and End for start and end.',
        geladen: 'Game loaded.',
        laedt: 'Loading game …',
        fehlerLesen: (zeile, spalte) => `The game could not be read (line ${zeile}, column ${spalte}). Please check the PGN data.`,
        fehlerLeer: 'No game was found in the PGN data.',
        fehlerZug: (zug) => `The move "${zug}" is not possible in this position. The game can only be replayed up to this move.`,
        fehlerFen: 'The starting position (FEN) is invalid. The game therefore starts from the initial position.',
        fehlerLaden: 'The viewer could not be loaded.',
        zugVonWeiss: (n) => `${n}. White`,
        zugVonSchwarz: (n) => `${n}. Black`,
        figuren: { K: 'King', Q: 'Queen', R: 'Rook', B: 'Bishop', N: 'Knight' },
        buchstaben: { K: 'K', Q: 'Q', R: 'R', B: 'B', N: 'N' },
        bauer: 'Pawn',
        schlaegt: 'takes on',
        nach: 'to',
        schach: 'check',
        matt: 'checkmate',
        kurzeRochade: 'castles kingside',
        langeRochade: 'castles queenside',
        umwandlung: 'promotes to',
        nags: {
            $1: 'good move', $2: 'mistake', $3: 'brilliant move', $4: 'blunder',
            $5: 'interesting move', $6: 'dubious move', $10: 'equal position',
            $13: 'unclear', $14: 'White is slightly better', $15: 'Black is slightly better',
            $16: 'White is better', $17: 'Black is better',
            $18: 'White is winning', $19: 'Black is winning'
        }
    }
};

/**
 * Sichtbare Zeichen der gebräuchlichen NAGs.
 *
 * Nur diese werden als Zeichen hinter den Zug gesetzt; alle anderen NAGs
 * erscheinen allein in der Ansage, sofern TEXTE einen Wortlaut kennt.
 */
const NAG_ZEICHEN = {
    $1: '!', $2: '?', $3: '!!', $4: '??', $5: '!?', $6: '?!',
    $10: '=', $13: '∞', $14: '⩲', $15: '⩱', $16: '±', $17: '∓', $18: '+−', $19: '−+'
};

/**
 * Liefert die Texte einer Sprache.
 *
 * @param {string} sprache 'de' oder 'en'; jeder andere Wert fällt auf Deutsch
 *                         zurück, weil die Rundenübersicht ohne Angabe
 *                         ebenfalls deutsch beschriftet ist
 * @returns {object} Der Textsatz
 */
export function texte(sprache) {
    return TEXTE[sprache] || TEXTE.de;
}

/**
 * Wandelt einen Zug in Standardnotation in die sichtbare Schreibweise.
 *
 * PGN schreibt die Figuren immer englisch (N, B, R, Q, K). Deutschsprachige
 * Leser erwarten S, L, T, D, K. Figurinen (♘) wären sprachneutral, werden aber
 * von Bildschirmlesern uneinheitlich vorgelesen und fehlen in manchen
 * Schriften — die Buchstaben sind die robustere Wahl. Vorgelesen wird ohnehin
 * zugAnsage().
 *
 * Nur der erste Buchstabe und die Umwandlungsfigur werden ersetzt; die
 * Feldbezeichnungen (a–h) bleiben unangetastet, auch wenn ein "b" darin steht.
 *
 * @param {string} san     Zug in Standardnotation, etwa "Nxf3+" oder "e8=Q#"
 * @param {string} sprache 'de' oder 'en'
 * @returns {string} Der Zug für die Anzeige, etwa "Sxf3+" oder "e8=D#"
 */
export function zugAnzeige(san, sprache) {
    const t = texte(sprache);
    let ergebnis = san.replace(/^[KQRBN]/, (f) => t.buchstaben[f]);
    ergebnis = ergebnis.replace(/=([QRBN])/, (_, f) => '=' + t.buchstaben[f]);
    return ergebnis;
}

/**
 * Liefert das sichtbare Zeichen eines NAG.
 *
 * @param {string} nag NAG in der Form "$1"
 * @returns {string} Das Zeichen, oder eine leere Zeichenkette für NAGs ohne
 *                   gebräuchliches Zeichen
 */
export function nagZeichen(nag) {
    return NAG_ZEICHEN[nag] || '';
}

/**
 * Formuliert einen Zug so, wie ihn ein Bildschirmleser vorlesen soll.
 *
 * Aus "Nxf3+" wird "Springer schlägt auf f3, Schach". Die Kurzschreibweise ist
 * vorgelesen kaum verständlich ("N x f 3 plus"), und für Menschen, die das
 * Brett nicht sehen, ist die Ansage der einzige Zugang zur Partie.
 *
 * Die Feldangabe wird mit einem Leerzeichen zwischen Linie und Reihe
 * ausgegeben ("f 3"). So lesen die gängigen Bildschirmleser "f drei" statt
 * eines Wortes "f3", das je nach Stimme als "F-Dreier" herauskommt.
 *
 * @param {string}   san     Zug in Standardnotation
 * @param {string}   sprache 'de' oder 'en'
 * @param {string[]} nags    NAGs des Zuges in der Form "$1"; darf fehlen
 * @returns {string} Die Ansage
 */
export function zugAnsage(san, sprache, nags = []) {
    const t = texte(sprache);
    const teile = [];
    const feld = (f) => f.charAt(0) + ' ' + f.charAt(1);

    let rest = san.replace(/[!?]+$/, '');
    let zusatz = '';

    if (rest.endsWith('#')) {
        zusatz = t.matt;
        rest = rest.slice(0, -1);
    } else if (rest.endsWith('+')) {
        zusatz = t.schach;
        rest = rest.slice(0, -1);
    }

    if (/^O-O-O/.test(rest) || /^0-0-0/.test(rest)) {
        teile.push(t.langeRochade);
    } else if (/^O-O/.test(rest) || /^0-0/.test(rest)) {
        teile.push(t.kurzeRochade);
    } else {
        const treffer = rest.match(/^([KQRBN])?([a-h])?([1-8])?(x)?([a-h][1-8])(?:=([QRBN]))?$/);

        if (!treffer) {
            // Unerwartete Schreibweise: lieber wörtlich vorlesen als nichts
            teile.push(san);
        } else {
            const [, figur, linie, reihe, schlag, ziel, umwandlung] = treffer;
            let text = figur ? t.figuren[figur] : t.bauer;

            // Mehrdeutigkeit ("Sbd2") gehört zur Ansage, sonst ist der Zug
            // nicht eindeutig
            if (linie || reihe) {
                text += ' ' + [linie, reihe].filter(Boolean).join(' ');
            }

            text += ' ' + (schlag ? t.schlaegt : t.nach) + ' ' + feld(ziel);

            if (umwandlung) {
                text += ', ' + t.umwandlung + ' ' + t.figuren[umwandlung];
            }

            teile.push(text);
        }
    }

    if (zusatz) {
        teile.push(zusatz);
    }

    for (const nag of nags || []) {
        if (t.nags[nag]) {
            teile.push(t.nags[nag]);
        }
    }

    return teile.join(', ');
}
