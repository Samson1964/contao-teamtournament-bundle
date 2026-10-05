/*
 * Grenzfall-PGNs für die Tests und die Prüfseite.
 *
 * Die Olympiade-Partien (inklusionsolympiade2026_r1.pgn … r7) liegen nur auf
 * dem Server von schachbund.de. Diese Beispiele decken dieselben Eigenschaften
 * ab: mehrere Partien, Kommentare, Varianten, NAGs, Umlaute, verschiedene
 * Zeilenumbrüche, ungültige und abgeschnittene Daten.
 */

export const GRENZFAELLE = {
    // Eine ganz gewöhnliche Partie mit Tags, die dem Datensatz widersprechen —
    // der Kopf muss trotzdem aus dem Datensatz kommen
    einfach: `[Event "Irgendein Turnier"]
[White "Falscher Name"]
[Black "Auch falsch"]
[Result "0-1"]

1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 d6 8. c3 O-O 1-0`,

    // Kommentare mit Umlauten, NAGs als Zeichen und als $-Nummern, verschachtelte
    // Varianten, ein Kommentar vor dem ersten Zug
    kommentiert: `[Event "Inklusions-Schacholympiade"]
[White "Jürgen Weiß"]
[Black "Björn Schröder"]

{Eine lebhafte Partie mit Überraschungen.} 1. e4 {Königsbauer} e5 2. Nf3 (2. f4 {Königsgambit} exf4 (2... d5 $5 {Falkbeer}) 3. Nf3) 2... Nc6 3. Bc4!? Nf6?? 4. Ng5 d5 5. exd5 Nxd5?! $6 6. Nxf7! $1 Kxf7 7. Qf3+ Ke6 8. Nc3 {Die Figuren kommen mit Tempo ins Spiel.} 1-0`,

    // Zwei Partien in einem Feld, etwa Partie und Stichkampf; Windows-Umbrüche
    zweiPartien: '[Event "Partie"]\r\n[Result "1/2-1/2"]\r\n\r\n1. d4 d5 2. c4 e6 3. Nc3 Nf6 1/2-1/2\r\n\r\n[Event "Stichkampf"]\r\n[Result "1-0"]\r\n\r\n1. e4 c5 2. Nf3 d6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 a6 1-0\r\n',

    // Stellung aus FEN-Tag, Umwandlung mit Matt
    umwandlung: `[FEN "7k/P7/6K1/8/8/8/8/8 w - - 0 1"]
[SetUp "1"]

1. a8=Q# 1-0`,

    // Nur alte Mac-Zeilenumbrüche und ein Byte-Order-Mark am Anfang
    macUmbrueche: String.fromCharCode(0xFEFF) + '[Event "Alt"]\r\r1. e4 e5 2. Nf3 *\r',

    // Syntaxfehler: nicht geschlossener Kommentar (abgeschnittene Datei)
    abgeschnitten: `[Event "Kaputt"]

1. e4 e5 2. Nf3 {Hier bricht die Datei ab`,

    // Formal lesbar, aber der zweite Zug von Weiß ist unmöglich (König zwei Felder)
    unzulaessig: `[Event "Unmöglich"]

1. e4 e5 2. Ke3 Nc6 3. Qh5 *`,

    // Nur Leerraum
    leer: '   \n\n  '
};
