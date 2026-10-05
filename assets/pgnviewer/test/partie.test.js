/*
 * Tests für src/partie.js — Aufruf: npm test (node --test test/)
 */

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { lesePartien, normalisierePgn, zeilenende } from '../src/partie.js';
import { GRENZFAELLE } from './pgn/grenzfaelle.js';

/**
 * Sammelt die Züge einer Zeile ab dem Wurzelknoten als SAN-Liste.
 */
function hauptzuege(partie) {
    const zuege = [];
    let k = partie.wurzel.weiter;

    while (k) {
        zuege.push(k.san);
        k = k.weiter;
    }

    return zuege;
}

test('Zeilenumbrüche, BOM und geschützte Leerzeichen werden bereinigt', () => {
    assert.equal(normalisierePgn(String.fromCharCode(0xFEFF) + 'a\r\nb\rc' + String.fromCharCode(0xA0) + 'd  '), 'a\nb\nc d');
    assert.equal(normalisierePgn(null), '');
});

test('Eine einfache Partie wird vollständig nachgespielt', () => {
    const { partien, fehler } = lesePartien(GRENZFAELLE.einfach);

    assert.equal(fehler, null);
    assert.equal(partien.length, 1);
    assert.equal(partien[0].fehler, null);
    assert.equal(hauptzuege(partien[0]).length, 16);
    assert.equal(hauptzuege(partien[0])[8], 'O-O');

    const letzter = zeilenende(partien[0].wurzel);
    assert.equal(letzter.san, 'O-O');
    assert.equal(letzter.farbe, 'b');
    assert.equal(letzter.zugnummer, 8);
});

test('Kommentare, NAGs und verschachtelte Varianten landen im Baum', () => {
    const { partien, fehler } = lesePartien(GRENZFAELLE.kommentiert);

    assert.equal(fehler, null);
    const p = partien[0];
    assert.equal(p.fehler, null);
    assert.equal(p.wurzel.kommentarNach, 'Eine lebhafte Partie mit Überraschungen.');

    const e4 = p.wurzel.weiter;
    assert.equal(e4.san, 'e4');
    assert.equal(e4.kommentarNach, 'Königsbauer');

    // 2. Nf3 trägt die Variante 2. f4 …
    const nf3 = e4.weiter.weiter;
    assert.equal(nf3.san, 'Nf3');
    assert.equal(nf3.varianten.length, 1);

    const f4 = nf3.varianten[0];
    assert.equal(f4.san, 'f4');
    assert.equal(f4.tiefe, 1);
    assert.equal(f4.vorher, e4.weiter, 'Variante beginnt in der Stellung vor dem Hauptzug');
    assert.equal(f4.kommentarNach, 'Königsgambit');

    // … und darin 2... d5 als Unter-Variante zu 2... exf4
    const exf4 = f4.weiter;
    assert.equal(exf4.san, 'exf4');
    assert.equal(exf4.varianten.length, 1);
    assert.equal(exf4.varianten[0].san, 'd5');
    assert.equal(exf4.varianten[0].tiefe, 2);
    assert.deepEqual(exf4.varianten[0].nags, ['$5']);

    // Die Hauptpartie läuft nach der Variante unverändert weiter
    assert.equal(nf3.weiter.san, 'Nc6');

    // !? und ?? werden vom Parser als NAG geliefert, nicht als Teil des Zuges
    const bc4 = nf3.weiter.weiter;
    assert.equal(bc4.san, 'Bc4');
    assert.deepEqual(bc4.nags, ['$5']);
    assert.deepEqual(bc4.weiter.nags, ['$4']);

    // "5... Nxd5?! $6" und "6. Nxf7! $1": Zeichen und Nummer meinen dasselbe
    // NAG, es darf nur einmal ankommen
    const nxd5 = bc4.weiter.weiter.weiter.weiter.weiter;
    assert.equal(nxd5.san, 'Nxd5');
    assert.deepEqual(nxd5.nags, ['$6']);
    assert.equal(nxd5.weiter.san, 'Nxf7');
    assert.deepEqual(nxd5.weiter.nags, ['$1']);
});

test('Mehrere Partien mit Windows-Umbrüchen werden getrennt gelesen', () => {
    const { partien, fehler } = lesePartien(GRENZFAELLE.zweiPartien);

    assert.equal(fehler, null);
    assert.equal(partien.length, 2);
    assert.deepEqual(hauptzuege(partien[0]), ['d4', 'd5', 'c4', 'e6', 'Nc3', 'Nf6']);
    assert.equal(hauptzuege(partien[1])[0], 'e4');
});

test('FEN-Tag, Umwandlung und Matt', () => {
    const { partien } = lesePartien(GRENZFAELLE.umwandlung);
    const p = partien[0];

    assert.equal(p.wurzel.fen, '7k/P7/6K1/8/8/8/8/8 w - - 0 1');
    assert.equal(p.wurzel.weiter.san, 'a8=Q#');
    assert.equal(p.wurzel.weiter.von, 'a7');
    assert.equal(p.wurzel.weiter.nach, 'a8');
});

test('Alte Mac-Umbrüche und BOM stören nicht', () => {
    const { partien, fehler } = lesePartien(GRENZFAELLE.macUmbrueche);

    assert.equal(fehler, null);
    assert.deepEqual(hauptzuege(partien[0]), ['e4', 'e5', 'Nf3']);
});

test('Abgeschnittenes PGN liefert einen Lesefehler mit Position statt einer Ausnahme', () => {
    const { partien, fehler } = lesePartien(GRENZFAELLE.abgeschnitten);

    assert.equal(partien.length, 0);
    assert.equal(fehler.art, 'lesen');
    assert.ok(fehler.zeile >= 1);
    assert.ok(fehler.spalte >= 1);
});

test('Ein unzulässiger Zug beendet die Partie dort, ohne die Züge davor zu verlieren', () => {
    const { partien, fehler } = lesePartien(GRENZFAELLE.unzulaessig);

    assert.equal(fehler, null, 'Der Text war lesbar');
    assert.equal(partien[0].fehler.art, 'zug');
    assert.equal(partien[0].fehler.zug, 'Ke3');
    assert.deepEqual(hauptzuege(partien[0]), ['e4', 'e5']);
});

test('Leere Daten melden "leer"', () => {
    assert.equal(lesePartien(GRENZFAELLE.leer).fehler.art, 'leer');
    assert.equal(lesePartien('').fehler.art, 'leer');
});

test('Ein ungültiger FEN-Tag fällt auf die Grundstellung zurück und wird gemeldet', () => {
    const { partien } = lesePartien('[FEN "unsinn"]\n\n1. e4 *');

    assert.equal(partien[0].fehler.art, 'fen');
    assert.equal(partien[0].wurzel.weiter.san, 'e4');
});
