/*
 * PGN-Viewer der Mannschaftsturniere — Lader
 *
 * Diese Datei ist das Einzige, was beim Seitenaufruf geladen wird, und sie
 * wird nur eingebunden, wenn auf der Seite mindestens eine Partie mit PGN
 * steht. Der eigentliche Viewer (Brett, Parser, Stylesheet) kommt erst beim
 * ersten Klick auf „Partie nachspielen" dazu — und danach für alle weiteren
 * Partien der Seite aus dem Browser-Cache.
 *
 * Erwartetes Markup (erzeugt von Classes\Partiedaten):
 *
 *   <button class="tt-pgn-schalter" hidden aria-expanded="false"
 *           aria-controls="tt-pgn-7" data-tt-pgn-huelle="tt-pgn-zeile-7">…</button>
 *   <tr id="tt-pgn-zeile-7" hidden><td>
 *     <div id="tt-pgn-7" class="tt-pgn" data-tt-pgn-laedt="…" data-tt-pgn-fehler="…">
 *       <script type="application/json" class="tt-pgn-daten">{…}</script>
 *     </div>
 *   </td></tr>
 *
 * data-tt-pgn-huelle ist optional; ohne sie wird das gesteuerte Element
 * selbst ein- und ausgeblendet. So lässt sich der Viewer auch außerhalb einer
 * Tabelle einsetzen.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

// Alle weiteren Dateien liegen neben dem Lader. Der Abfrageteil der eigenen
// Adresse (?v=…) wird weitergereicht, damit ein Update des Bundles nicht an
// einem zwischengespeicherten Viewer scheitert.
const basis = new URL('./', import.meta.url);
const version = new URL(import.meta.url).search;

let viewerModul = null;

/**
 * Lädt das Stylesheet des Viewers genau einmal.
 *
 * Der Viewer wird erst gestartet, wenn das Stylesheet da ist; sonst sähe man
 * für einen Augenblick ein ungestaltetes Brett in voller Seitenbreite.
 *
 * @returns {Promise<void>} Erfüllt, sobald das Stylesheet geladen ist oder
 *                          nicht geladen werden konnte
 */
function ladeStylesheet() {
    const vorhanden = document.getElementById('tt-pgnviewer-css');

    if (vorhanden) {
        return vorhanden.ttGeladen || Promise.resolve();
    }

    const link = document.createElement('link');
    link.id = 'tt-pgnviewer-css';
    link.rel = 'stylesheet';
    link.href = new URL('tt-pgnviewer.css' + version, basis).href;
    link.ttGeladen = new Promise((fertig) => {
        link.addEventListener('load', () => fertig(), { once: true });
        link.addEventListener('error', () => fertig(), { once: true });
    });
    document.head.append(link);

    return link.ttGeladen;
}

/**
 * Lädt Viewer-Modul und Stylesheet, beim zweiten Aufruf aus dem Zwischenspeicher.
 *
 * @returns {Promise<object>} Das Viewer-Modul
 */
function ladeViewer() {
    if (!viewerModul) {
        viewerModul = Promise.all([
            import(new URL('tt-pgnviewer.js' + version, basis).href),
            ladeStylesheet()
        ]).then(([modul]) => modul).catch((fehler) => {
            // Beim nächsten Klick erneut versuchen, etwa nach einem
            // Verbindungsabbruch
            viewerModul = null;
            throw fehler;
        });
    }

    return viewerModul;
}

/**
 * Zeigt die Schalter an.
 *
 * Sie stehen verborgen im Markup, damit Besucher ohne JavaScript keinen
 * Knopf sehen, der nichts tut.
 */
function zeigeSchalter() {
    for (const schalter of document.querySelectorAll('.tt-pgn-schalter[hidden]')) {
        schalter.hidden = false;
    }
}

/**
 * Öffnet oder schließt die Partie zu einem Schalter.
 *
 * Beim ersten Öffnen wird der Viewer gestartet. Schließen blendet ihn nur
 * aus; beim erneuten Öffnen steht er in derselben Stellung wie zuvor.
 *
 * @param {HTMLButtonElement} schalter Der angeklickte Schalter
 */
async function umschalten(schalter) {
    const ziel = document.getElementById(schalter.getAttribute('aria-controls') || '');

    if (!ziel) {
        return;
    }

    const huelle = schalter.dataset.ttPgnHuelle ? document.getElementById(schalter.dataset.ttPgnHuelle) : ziel;
    const offen = 'true' === schalter.getAttribute('aria-expanded');

    schalter.setAttribute('aria-expanded', offen ? 'false' : 'true');
    (huelle || ziel).hidden = offen;

    if (offen || ziel.dataset.ttPgnGestartet) {
        return;
    }

    ziel.dataset.ttPgnGestartet = '1';
    ziel.setAttribute('aria-busy', 'true');

    // Eine Fehlermeldung aus einem früheren, gescheiterten Versuch weicht
    for (const alt of ziel.querySelectorAll('.tt-pgn-meldung')) {
        alt.remove();
    }

    const meldung = document.createElement('p');
    meldung.className = 'tt-pgn-meldung';
    meldung.setAttribute('role', 'status');
    meldung.textContent = ziel.dataset.ttPgnLaedt || '…';
    ziel.append(meldung);

    try {
        const modul = await ladeViewer();
        const datenblock = ziel.querySelector('script.tt-pgn-daten');
        const daten = JSON.parse(datenblock ? datenblock.textContent : '{}');

        meldung.remove();
        modul.starteViewer(ziel, daten, { assetsUrl: basis.href });
    } catch (fehler) {
        delete ziel.dataset.ttPgnGestartet;
        meldung.setAttribute('role', 'alert');
        meldung.textContent = ziel.dataset.ttPgnFehler || 'Error';
        // Für die Fehlersuche; der Besucher sieht die Meldung oben
        console.error(fehler);
    } finally {
        ziel.removeAttribute('aria-busy');
    }
}

document.addEventListener('click', (e) => {
    const schalter = e.target instanceof Element ? e.target.closest('.tt-pgn-schalter') : null;

    if (schalter) {
        umschalten(schalter);
    }
});

// Module werden erst nach dem Aufbau des Dokuments ausgeführt; die Schalter
// stehen also schon im DOM
zeigeSchalter();
