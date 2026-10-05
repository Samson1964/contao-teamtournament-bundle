/*
 * PGN-Viewer der Mannschaftsturniere — Build
 *
 * Aufruf im Verzeichnis assets/pgnviewer:
 *
 *   npm ci
 *   npm run build
 *
 * Schreibt alle Dateien, die im Frontend gebraucht werden, nach
 * src/Resources/public/pgnviewer/ (im Contao-Frontend erreichbar unter
 * bundles/contaoteamtournament/pgnviewer/). Das Zielverzeichnis wird dabei
 * vollständig neu angelegt.
 *
 * Die gebauten Dateien werden mit eingecheckt: Eine Contao-Installation hat
 * kein Node und baut nichts. Wer die Quellen unter src/ ändert, muss den
 * Build also selbst anstoßen.
 */

import * as esbuild from 'esbuild';
import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';

const require = createRequire(import.meta.url);
const ziel = path.resolve('../../src/Resources/public/pgnviewer');

// Moderne Browser genügen: Der Lader ist ein ES-Modul und nutzt import(),
// ältere Browser bekämen den Schalter ohnehin nie zu sehen
const target = ['es2020', 'chrome90', 'firefox90', 'safari14'];

await rm(ziel, { recursive: true, force: true });
await mkdir(ziel, { recursive: true });

// Viewer mit allen drei Bibliotheken in einer Datei
await esbuild.build({
    entryPoints: ['src/viewer.js'],
    outfile: path.join(ziel, 'tt-pgnviewer.js'),
    bundle: true,
    format: 'esm',
    minify: true,
    target,
    charset: 'utf8',
    // Lizenzkommentare der Bibliotheken bleiben am Dateiende erhalten
    legalComments: 'eof'
});

// Lader ohne Abhängigkeiten
await esbuild.build({
    entryPoints: ['src/loader.js'],
    outfile: path.join(ziel, 'tt-pgn-loader.js'),
    bundle: false,
    format: 'esm',
    minify: true,
    target,
    charset: 'utf8'
});

// Stylesheets: der Viewer samt den Stilen von cm-chessboard, und der Schalter
await esbuild.build({
    entryPoints: ['src/viewer.css'],
    outfile: path.join(ziel, 'tt-pgnviewer.css'),
    bundle: true,
    minify: true,
    charset: 'utf8'
});

await esbuild.build({
    entryPoints: ['src/schalter.css'],
    outfile: path.join(ziel, 'tt-pgn-schalter.css'),
    bundle: true,
    minify: true,
    charset: 'utf8'
});

// Grafiken von cm-chessboard: cm-chessboard lädt sie zur Laufzeit relativ zu
// assetsUrl nach, deshalb gleiche Pfade wie im Paket
const cmChessboard = path.dirname(require.resolve('cm-chessboard/package.json'));
await mkdir(path.join(ziel, 'pieces'), { recursive: true });
await mkdir(path.join(ziel, 'extensions/markers'), { recursive: true });
await cp(path.join(cmChessboard, 'assets/pieces/standard.svg'), path.join(ziel, 'pieces/standard.svg'));
await cp(path.join(cmChessboard, 'assets/extensions/markers/markers.svg'), path.join(ziel, 'extensions/markers/markers.svg'));

// Lizenzen der gebündelten Bibliotheken mit ausliefern
const bibliotheken = ['chess.js', '@mliebelt/pgn-parser', 'cm-chessboard'];
const lizenzen = path.join(ziel, 'lizenzen');
await mkdir(lizenzen, { recursive: true });

const uebersicht = [
    'Im PGN-Viewer gebündelte Bibliotheken',
    '=====================================',
    ''
];

for (const name of bibliotheken) {
    const verzeichnis = path.dirname(require.resolve(name + '/package.json'));
    const paket = JSON.parse(await readFile(path.join(verzeichnis, 'package.json'), 'utf8'));
    const dateiname = name.replace('@', '').replace('/', '-') + '-LICENSE.txt';

    await cp(path.join(verzeichnis, 'LICENSE'), path.join(lizenzen, dateiname));

    const repo = paket.repository && paket.repository.url ? paket.repository.url.replace(/^git\+/, '') : '';
    uebersicht.push(`${name} ${paket.version} — Lizenz ${paket.license} — ${repo} — ${dateiname}`);
}

uebersicht.push('', 'Quellen und Build: assets/pgnviewer/ im Repository des Bundles.', '');
await writeFile(path.join(lizenzen, 'README.txt'), uebersicht.join('\n'), 'utf8');

console.log('PGN-Viewer gebaut nach ' + ziel);
