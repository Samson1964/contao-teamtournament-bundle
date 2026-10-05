/*
 * Tests für src/sprache.js — Aufruf: npm test (node --test test/)
 */

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { zugAnzeige, zugAnsage, nagZeichen, texte } from '../src/sprache.js';

test('Anzeige ersetzt nur Figurenbuchstaben, nicht die Linie b', () => {
    assert.equal(zugAnzeige('Nxf3+', 'de'), 'Sxf3+');
    assert.equal(zugAnzeige('Bb5', 'de'), 'Lb5');
    assert.equal(zugAnzeige('bxc6', 'de'), 'bxc6');
    assert.equal(zugAnzeige('e8=Q#', 'de'), 'e8=D#');
    assert.equal(zugAnzeige('Rbd1', 'de'), 'Tbd1');
    assert.equal(zugAnzeige('Nxf3+', 'en'), 'Nxf3+');
    assert.equal(zugAnzeige('O-O-O', 'de'), 'O-O-O');
});

test('Ansage deutsch', () => {
    assert.equal(zugAnsage('e4', 'de'), 'Bauer nach e 4');
    assert.equal(zugAnsage('Nxf3+', 'de'), 'Springer schlägt auf f 3, Schach');
    assert.equal(zugAnsage('exd5', 'de'), 'Bauer e schlägt auf d 5');
    assert.equal(zugAnsage('Rbd1', 'de'), 'Turm b nach d 1');
    assert.equal(zugAnsage('e8=Q#', 'de'), 'Bauer nach e 8, wandelt um in Dame, Schachmatt');
    assert.equal(zugAnsage('O-O', 'de'), 'kurze Rochade');
    assert.equal(zugAnsage('O-O-O+', 'de'), 'lange Rochade, Schach');
    assert.equal(zugAnsage('Bc4', 'de', ['$5']), 'Läufer nach c 4, interessanter Zug');
});

test('Ansage englisch', () => {
    assert.equal(zugAnsage('Qxh7#', 'en'), 'Queen takes on h 7, checkmate');
    assert.equal(zugAnsage('O-O', 'en', ['$2']), 'castles kingside, mistake');
});

test('Unbekannte Schreibweise wird wörtlich angesagt statt verschluckt', () => {
    assert.equal(zugAnsage('--', 'de'), '--');
});

test('NAG-Zeichen und Sprachrückfall', () => {
    assert.equal(nagZeichen('$1'), '!');
    assert.equal(nagZeichen('$4'), '??');
    assert.equal(nagZeichen('$146'), '');
    assert.equal(texte('fr').weiss, 'Weiß');
});
