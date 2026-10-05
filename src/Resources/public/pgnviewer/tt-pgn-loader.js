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
 *       <script type="application/json" class="tt-pgn-daten">{…}<\/script>
 *     </div>
 *   </td></tr>
 *
 * data-tt-pgn-huelle ist optional; ohne sie wird das gesteuerte Element
 * selbst ein- und ausgeblendet. So lässt sich der Viewer auch außerhalb einer
 * Tabelle einsetzen.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */const o=new URL("./",import.meta.url),c=new URL(import.meta.url).search;let s=null;function u(){const t=document.getElementById("tt-pgnviewer-css");if(t)return t.ttGeladen||Promise.resolve();const e=document.createElement("link");return e.id="tt-pgnviewer-css",e.rel="stylesheet",e.href=new URL("tt-pgnviewer.css"+c,o).href,e.ttGeladen=new Promise(a=>{e.addEventListener("load",()=>a(),{once:!0}),e.addEventListener("error",()=>a(),{once:!0})}),document.head.append(e),e.ttGeladen}function m(){return s||(s=Promise.all([import(new URL("tt-pgnviewer.js"+c,o).href),u()]).then(([t])=>t).catch(t=>{throw s=null,t})),s}function g(){for(const t of document.querySelectorAll(".tt-pgn-schalter[hidden]"))t.hidden=!1}async function f(t){const e=document.getElementById(t.getAttribute("aria-controls")||"");if(!e)return;const a=t.dataset.ttPgnHuelle?document.getElementById(t.dataset.ttPgnHuelle):e,l=t.getAttribute("aria-expanded")==="true";if(t.setAttribute("aria-expanded",l?"false":"true"),(a||e).hidden=l,l||e.dataset.ttPgnGestartet)return;e.dataset.ttPgnGestartet="1",e.setAttribute("aria-busy","true");for(const r of e.querySelectorAll(".tt-pgn-meldung"))r.remove();const n=document.createElement("p");n.className="tt-pgn-meldung",n.setAttribute("role","status"),n.textContent=e.dataset.ttPgnLaedt||"…",e.append(n);try{const r=await m(),d=e.querySelector("script.tt-pgn-daten"),i=JSON.parse(d?d.textContent:"{}");n.remove(),r.starteViewer(e,i,{assetsUrl:o.href})}catch(r){delete e.dataset.ttPgnGestartet,n.setAttribute("role","alert"),n.textContent=e.dataset.ttPgnFehler||"Error",console.error(r)}finally{e.removeAttribute("aria-busy")}}document.addEventListener("click",t=>{const e=t.target instanceof Element?t.target.closest(".tt-pgn-schalter"):null;e&&f(e)}),g();
