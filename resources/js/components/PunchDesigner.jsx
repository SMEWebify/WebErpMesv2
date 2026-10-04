import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import {
    IFACE, PRESETS, DEFAULTS, PROFILE_PRM, PART_PRM, MFG_PRM, DIE_PRM, BEND_MIN, BEND_MAX, DENSITY,
    clone, clampPrm, clampBend, bendA, seqOf, normBends, build, usablePunches, dieCatalog,
} from '../lib/pressbrake/geometry';
import {
    drawing, PAPER, SVG_W, SVG_H, fmt,
} from '../lib/pressbrake/drawing';

const ZOOM_MAX = 20;
const BRAND_KEY = 'wem.punchDesigner.brand';

/** Cadre visible du dessin : z = facteur de zoom, (x, y) = coin haut gauche en unités SVG. */
function clampView(z, x, y) {
    z = Math.max(1, Math.min(ZOOM_MAX, z));
    const w = SVG_W / z, h = SVG_H / z;
    return { z, x: Math.max(0, Math.min(SVG_W - w, x)), y: Math.max(0, Math.min(SVG_H - h, y)) };
}
const toSvgPoint = (inv, clientX, clientY) => new DOMPoint(clientX, clientY).matrixTransform(inv);

// ─── PDF ────────────────────────────────────────────────────────────────────
function svgToPng(svg, scale = 2) {
    return new Promise((res, rej) => {
        const url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' }));
        const img = new Image();
        img.onload = () => {
            const c = document.createElement('canvas');
            c.width = img.width * scale; c.height = img.height * scale;
            const cx = c.getContext('2d'); cx.scale(scale, scale); cx.drawImage(img, 0, 0);
            URL.revokeObjectURL(url);
            res(c.toDataURL('image/png'));
        };
        img.onerror = rej;
        img.src = url;
    });
}

async function buildPdf(s, g, brand) {
    const { jsPDF } = await import('jspdf');
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });
    const M = 16; let y = M;
    const ref = 'PNC-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.floor(1000 + Math.random() * 9000);
    doc.setFont('helvetica', 'bold'); doc.setFontSize(18); doc.text('Poinçon de presse plieuse sur mesure', M, y); y += 7;
    doc.setFont('helvetica', 'normal'); doc.setFontSize(10); doc.setTextColor(90);
    doc.text(`${brand} · Réf. ${ref} · ${new Date().toLocaleDateString('fr-FR')}`, M, y); y += 4;
    doc.setDrawColor(26, 32, 41); doc.setLineWidth(0.6); doc.line(M, y, 210 - M, y); y += 4; doc.setTextColor(0);
    const img = await svgToPng(drawing(s, g).svg.replace(/<circle class="pd-hd"[^>]*\/>/g, ''), 2);
    doc.addImage(img, 'PNG', M, y, 178, 178 * SVG_H / SVG_W); y += 178 * SVG_H / SVG_W + 8;
    const mass = fmt(g.area * s.L * DENSITY, 1) + ' kg', section = fmt(g.area / 100, 1) + ' cm²';
    const partRow = ['Pièce', `tôle ${s.t} mm · côtés ${s.part.legs.join(' / ')} mm`];
    const seqRow = ['Séquence de pliage', g.seq.map((i) => 'pli ' + (i + 1)).join(' → ') + (g.seq.length > 1 ? ` (étape ${g.step + 1}/${g.seq.length} représentée)` : '')];
    const dieRows = g.die ? [['Matrice', `${g.die.label} · V${fmt(g.die.v)} · ${fmt(g.die.a)}° · R${fmt(g.die.r)} · H${fmt(g.die.h)}`]] : [];
    const clearRow = ['Jeu minimal', g.collide ? 'collision' : fmt(g.clear, 1) + ' mm'];
    const rows = g.lib
        ? [['Poinçon de bibliothèque', g.lib.brand + ' ' + g.lib.name], ['Angle de pointe', g.toolA ? g.toolA + '°' : '—'], ['Angle de pliage', bendA(s) + '°'], ['Rayon', g.R != null ? g.R + ' mm' : '—'],
           ['Hauteur', fmt(g.Hb) + ' mm'], ['Longueur', s.L + ' mm'], ['Masse estimée', mass], ['Aire de section', section], partRow, seqRow, ...dieRows, clearRow]
        : [['Interface', g.f.label + ' (' + g.f.w + ' mm)'], ['Hauteur totale', s.H + ' mm'], ['Angle de pointe', s.A + '°'], ['Angle de pliage', bendA(s) + '°'], ['Rayon de pointe', g.R + ' mm'],
           ['Épaisseur totale', g.T + ' mm'], ['Épaisseur du corps', g.b + ' mm'], ['Épaisseur de pointe', g.tp + ' mm'], ['Longueur', s.L + ' mm'],
           ['Masse estimée', mass], ['Aire de section', section], partRow, seqRow, ...dieRows, clearRow];
    doc.setFont('helvetica', 'bold'); doc.text('Caractéristiques', M, y); doc.setFont('helvetica', 'normal');
    rows.forEach((r) => {
        y += 6;
        doc.setDrawColor(200); doc.setLineWidth(0.2); doc.line(M, y + 1.5, 210 - M, y + 1.5);
        doc.text(r[0], M, y); doc.text(String(r[1]), 210 - M, y, { align: 'right' });
    });
    y += 10; doc.setFontSize(8.5); doc.setTextColor(110);
    doc.text(doc.splitTextToSize("Conception préliminaire. Le serrage, la résistance au tonnage et la fabricabilité doivent être validés par le bureau d'études avant toute commande. Cotes de queue indicatives selon le système d'attache.", 178), M, y);
    doc.save(`poincon-${ref}.pdf`);
}

// ─── Sous-composants ────────────────────────────────────────────────────────
/** Champ numérique avec −/+ ; la saisie clavier n'est validée qu'à la sortie du champ ou sur Entrée. */
function Stepper({ value, step, unit, onCommit, min, max, inputWidth = 70, ariaLabel }) {
    const [draft, setDraft] = useState(String(value));
    useEffect(() => { setDraft(String(value)); }, [value]);
    const commit = () => {
        const v = parseFloat(String(draft).replace(',', '.'));
        if (Number.isFinite(v)) onCommit(v); else setDraft(String(value));
    };
    return (
        <div className="input-group input-group-sm flex-nowrap" style={{ width: 'auto' }}>
            <button type="button" className="btn btn-outline-secondary" aria-label="moins" onClick={() => onCommit(value - step)}>−</button>
            <input
                type="number" className="form-control text-end fw-semibold" style={{ width: inputWidth }}
                min={min} max={max} step={step} value={draft} aria-label={ariaLabel}
                onChange={(e) => setDraft(e.target.value)} onBlur={commit}
                onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); commit(); } }}
            />
            {unit && <span className="input-group-text">{unit}</span>}
            <button type="button" className="btn btn-outline-secondary" aria-label="plus" onClick={() => onCommit(value + step)}>+</button>
        </div>
    );
}

function ParamRow({ prm, value, onSet }) {
    const [k, label, sym, min, max, step, unit] = prm;
    return (
        <div className="d-flex align-items-center justify-content-between gap-2 mb-2">
            <label className="mb-0 small">{label}{sym && <span className="text-muted ms-1">{sym}</span>}</label>
            <Stepper value={value} step={step} unit={unit} min={min} max={max} ariaLabel={label} onCommit={(v) => onSet(k, v)} />
        </div>
    );
}

function IfaceIcon({ k }) {
    const f = IFACE[k];
    return (
        <svg viewBox="-2 -2 44 34" width="22" height="30" className="flex-shrink-0">
            <path d={`M${f.off} 0 H${f.off + f.w} V12 H40 V20 H0 V12 H${f.off} Z`} fill="none" stroke="currentColor" strokeWidth="2" />
            <path d="M8 20 V28 L14 28 L24 20" fill="none" stroke="currentColor" strokeWidth="1.5" />
        </svg>
    );
}

function LibThumb({ poly }) {
    const xs = poly.map((p) => p[0]), ys = poly.map((p) => p[1]);
    const x0 = Math.min(...xs), y1 = Math.max(...ys), w = Math.max(...xs) - x0, h = y1 - Math.min(...ys), mm = Math.max(w, h) * 0.06;
    return (
        <svg viewBox={`${x0 - mm} ${-y1 - mm} ${w + 2 * mm} ${h + 2 * mm}`} width="44" height="44" className="flex-shrink-0">
            <path d={'M' + poly.map((p) => p[0] + ' ' + (-p[1])).join('L') + 'Z'} fill="#1F5C99" fillOpacity=".15" stroke="currentColor" strokeWidth={Math.max(w, h) / 60} />
        </svg>
    );
}

// ─── Composant principal ────────────────────────────────────────────────────
export default function PunchDesigner({ brand = 'Votre atelier', libraryUrl, catalogUrl }) {
    const [s, setS] = useState(() => ({ ...clone(DEFAULTS), src: 'lib', libId: null, mirror: false }));
    const [lib, setLib] = useState(null);
    const [libError, setLibError] = useState(null);
    // Marque partagée par les deux listes (poinçons et matrices), mémorisée dans le navigateur.
    const [toolBrand, setBrandRaw] = useState(() => { try { return localStorage.getItem(BRAND_KEY) || ''; } catch { return ''; } });
    const setBrand = (b) => {
        setBrandRaw(b);
        try { if (b) localStorage.setItem(BRAND_KEY, b); else localStorage.removeItem(BRAND_KEY); } catch { /* stockage indisponible */ }
    };
    const [libQ, setLibQ] = useState('');
    const [status, setStatus] = useState('');
    const [busy, setBusy] = useState(false);

    // La bibliothèque (~1,3 Mo) n'est chargée qu'à l'ouverture de l'onglet Bibliothèque.
    useEffect(() => {
        if (s.src !== 'lib' || lib) return;
        fetch(libraryUrl, { headers: { Accept: 'application/json' } })
            .then(async (res) => {
                const data = await res.json().catch(() => null);
                if (!res.ok) throw new Error(data?.message || `Erreur ${res.status}`);
                setLib(usablePunches(data));
            })
            .catch((e) => {
                setLibError(e.message || String(e));
                // bibliothèque absente sur cette instance : on retombe sur le profil paramétrique
                setS((prev) => ({ ...prev, src: 'param' }));
            });
    }, [s.src, lib]);

    const libTool = s.src === 'lib' && lib && s.libId != null ? lib.find((t) => t.id === s.libId) : null;
    // Bascule sur la bibliothèque : sélectionne le premier poinçon une fois les données arrivées.
    useEffect(() => {
        if (s.src === 'lib' && lib && s.libId == null && lib.length) pickLib(lib.find((t) => t.brand === toolBrand) || lib[0]);
    }, [s.src, lib]); // eslint-disable-line react-hooks/exhaustive-deps

    // Catalogue des matrices (~2,8 Mo, fichier partagé avec le catalogue d'outillage) : chargé à la
    // première sélection « Catalogue », réduit aux matrices en V dont les cotes sont renseignées.
    const [dies, setDies] = useState(null);
    const [dieError, setDieError] = useState(null);
    const [dieQ, setDieQ] = useState('');
    const [dieFit, setDieFit] = useState(true);
    useEffect(() => {
        if (s.dieSrc !== 'cat' || dies || !catalogUrl) return;
        fetch(catalogUrl, { headers: { Accept: 'application/json' } })
            .then(async (res) => {
                const data = await res.json().catch(() => null);
                if (!res.ok) throw new Error(data?.message || `Erreur ${res.status}`);
                setDies(dieCatalog(data));
            })
            .catch((e) => {
                setDieError(e.message || String(e));
                setS((prev) => ({ ...prev, dieSrc: 'manual' }));
            });
    }, [s.dieSrc, dies, catalogUrl]);
    const dieTool = s.dieSrc === 'cat' && dies && s.dieId != null ? dies.find((d) => d.id === s.dieId) : null;
    const dieSpec = useMemo(() => {
        if (s.dieSrc === 'manual') return { v: s.dieV, a: s.dieA, r: s.dieR, h: s.dieH, label: 'Matrice' };
        if (dieTool) return { v: dieTool.v, a: dieTool.a, r: dieTool.r, h: dieTool.h, label: `${dieTool.brand} ${dieTool.name}` };
        return null;
    }, [s.dieSrc, s.dieV, s.dieA, s.dieR, s.dieH, dieTool]);
    const dieBrands = useMemo(() => (dies ? [...new Set(dies.map((d) => d.brand))].sort() : []), [dies]);
    // marque absente de ce côté (ex. poinçons UKB, pas de matrice UKB) : la liste reste sur « Toutes marques »
    const dieBrand = dieBrands.includes(toolBrand) ? toolBrand : '';
    const dieItems = useMemo(() => {
        if (!dies) return [];
        const q = dieQ.trim().toLowerCase();
        return dies
            .filter((d) => (!dieBrand || d.brand === dieBrand) && (!dieFit || (d.v >= 6 * s.t && d.v <= 12 * s.t))
                && (!q || `${d.brand} ${d.name} v${d.v} ${d.a}°`.toLowerCase().includes(q)))
            .sort((a, b) => a.v - b.v || a.brand.localeCompare(b.brand))
            .slice(0, 150);
    }, [dies, dieBrand, dieQ, dieFit, s.t]);
    // première ouverture du catalogue : la matrice la plus proche de V = 8 × t, dans la marque choisie s'il y en a
    useEffect(() => {
        if (s.dieSrc !== 'cat' || !dies || s.dieId != null || !dies.length) return;
        const ofBrand = dies.filter((d) => d.brand === toolBrand);
        const best = (ofBrand.length ? ofBrand : dies).slice().sort((a, b) => Math.abs(a.v - 8 * s.t) - Math.abs(b.v - 8 * s.t))[0];
        setS((prev) => ({ ...prev, dieId: best.id }));
    }, [s.dieSrc, dies]); // eslint-disable-line react-hooks/exhaustive-deps

    const g = useMemo(() => build(s, s.src === 'lib' ? libTool : null, dieSpec), [s, libTool, dieSpec]);

    const { svg, tf } = useMemo(() => drawing(s, g), [s, g]);

    const setVal = useCallback((k, v) => setS((prev) => ({ ...prev, [k]: clampPrm(k, v) })), []);
    const setPart = (fn) => setS((prev) => ({ ...prev, part: normBends(fn(clone(prev.part))) }));
    function pickLib(t) {
        setS((prev) => ({ ...prev, libId: t.id }));
    }

    const brands = useMemo(() => (lib ? [...new Set(lib.map((t) => t.brand))].sort() : []), [lib]);
    const libBrand = brands.includes(toolBrand) ? toolBrand : '';
    const libItems = useMemo(() => {
        if (!lib) return [];
        const q = libQ.trim().toLowerCase();
        return lib.filter((t) => (!libBrand || t.brand === libBrand) && (!q || `${t.brand} ${t.name} ${t.angle ?? ''}`.toLowerCase().includes(q))).slice(0, 150);
    }, [lib, libBrand, libQ]);

    // ── Zoom (molette centrée sur le pointeur, boutons) ─────────────────────
    const [view, setView] = useState({ z: 1, x: 0, y: 0 });
    const zoomAt = useCallback((factor, ux, uy) => setView((v) => {
        const z2 = Math.max(1, Math.min(ZOOM_MAX, v.z * factor)), r = v.z / z2;
        return clampView(z2, ux - (ux - v.x) * r, uy - (uy - v.y) * r);
    }), []);
    const zoomCenter = (factor) => zoomAt(factor, view.x + SVG_W / view.z / 2, view.y + SVG_H / view.z / 2);
    const viewBox = `${view.x} ${view.y} ${SVG_W / view.z} ${SVG_H / view.z}`;

    // ── Glisser les poignées du dessin / déplacer la vue ────────────────────
    const hostRef = useRef(null);
    const dragRef = useRef(null);
    const live = useRef({});
    live.current = { s, g, tf, view };

    useEffect(() => {
        const host = hostRef.current; if (!host) return undefined;
        // écouteur non passif : sans preventDefault la molette ferait défiler la page
        const wheel = (e) => {
            const svgEl = host.querySelector('svg'); if (!svgEl) return;
            e.preventDefault();
            const p = toSvgPoint(svgEl.getScreenCTM().inverse(), e.clientX, e.clientY);
            zoomAt(e.deltaY < 0 ? 1.2 : 1 / 1.2, p.x, p.y);
        };
        host.addEventListener('wheel', wheel, { passive: false });
        return () => host.removeEventListener('wheel', wheel);
    }, [zoomAt]);

    useEffect(() => {
        const move = (e) => {
            const drag = dragRef.current; if (!drag) return;
            if (drag.pan) {
                setView(clampView(drag.z, drag.vx - (e.clientX - drag.x0) * drag.upp, drag.vy - (e.clientY - drag.y0) * drag.upp));
                return;
            }
            const dx = (e.clientX - drag.x0) * drag.pxmm, dy = -(e.clientY - drag.y0) * drag.pxmm;
            const g0 = drag.g, k = drag.k;
            const dot = (u) => dx * u[0] + dy * u[1];
            if (k.startsWith('leg')) {
                const i = +k.slice(3), sgn = i <= g0.k ? -1 : 1;
                const nv = Math.max(2, Math.min(500, Math.round(drag.v0 + sgn * dot(g0.dir[i]))));
                setS((prev) => (prev.part.legs[i] === nv ? prev : { ...prev, part: { ...prev.part, legs: prev.part.legs.map((l, j) => (j === i ? nv : l)) } }));
                return;
            }
            let v;
            switch (k) {
                case 'H':  v = drag.v0 + dy; break;
                case 'T':  v = drag.v0 + dx; break;
                case 'b':  v = drag.v0 - dx; break;
                case 'tp': v = drag.v0 - 2 * dx; break;
                case 'R':  v = drag.v0 + dy * 0.5; break;
                case 't':  v = drag.v0 + dot(g0.rn[g0.k]); break;
                case 'A':
                case 'Ap': { // angle lu depuis la position du pointeur par rapport à l'intersection des flancs (outil ou pièce)
                    const p = toSvgPoint(drag.inv, e.clientX, e.clientY), I = k === 'A' ? g0.It : g0.Ip;
                    const mx = (p.x - drag.tf.ox) / drag.tf.sc - I[0], my = -(p.y - drag.tf.oy) / drag.tf.sc - I[1];
                    v = 2 * Math.atan2(Math.abs(mx), Math.max(0.1, my)) * 180 / Math.PI;
                    break;
                }
                default: return;
            }
            if (k === 'Ap') { // angle du pli actif : stocké sur le pli lui-même
                const nv = clampBend(v);
                setS((prev) => (bendA(prev) === nv ? prev : { ...prev, part: { ...prev.part, bends: prev.part.bends.map((bd, j) => (j === prev.part.k ? { ...bd, a: nv } : bd)) } }));
                return;
            }
            setVal(k, v);
        };
        const up = () => { dragRef.current = null; };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
        return () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); };
    }, [setVal]);

    const onPointerDown = (e) => {
        const svgEl = hostRef.current?.querySelector('svg'); if (!svgEl || e.button !== 0) return;
        const ctm = svgEl.getScreenCTM(); // pixels écran par unité SVG, zoom compris
        const { s: cs, g: cg, tf: ctf, view: cv } = live.current;
        const c = e.target.closest('.pd-hd');
        e.preventDefault();
        if (!c) { // hors poignée : déplacement de la vue zoomée
            if (cv.z > 1) dragRef.current = { pan: true, x0: e.clientX, y0: e.clientY, vx: cv.x, vy: cv.y, z: cv.z, upp: 1 / ctm.a };
            return;
        }
        const key = c.dataset.k;
        dragRef.current = {
            k: key, x0: e.clientX, y0: e.clientY, g: cg, tf: ctf, inv: ctm.inverse(),
            v0: key.startsWith('leg') ? cs.part.legs[+key.slice(3)] : cs[key],
            pxmm: 1 / (ctm.a * ctf.sc),
        };
    };

    // ── Actions ─────────────────────────────────────────────────────────────
    const reset = () => setS((prev) => ({ ...clone(DEFAULTS), src: prev.src, libId: prev.libId, mirror: false, dieSrc: prev.dieSrc, dieId: prev.dieId }));
    const exportPdf = async () => {
        setBusy(true); setStatus('Génération du PDF…');
        try { await buildPdf(s, g, brand); setStatus('PDF généré.'); }
        catch (e) { setStatus('Échec de la génération : ' + (e?.message || e)); }
        setBusy(false);
    };

    const mass = g.area * s.L * DENSITY;
    const facts = [
        [fmt(mass, 1), 'kg', 'masse estimée'],
        [fmt(g.area / 100, 1), 'cm²', 'aire de section'],
        [s.L, 'mm', 'longueur du poinçon'],
        [g.collide ? 'Collision' : fmt(g.clear, 1), g.collide ? '' : 'mm', 'jeu minimal avec la pièce', g.collide],
    ];
    const notices = [...g.notes];
    if (g.dieCollide) notices.unshift('La pièce pliée touche la matrice : changez de V, de hauteur de matrice ou de séquence.');
    if (g.collide) notices.unshift('La pièce pliée touche le poinçon : réduisez les ailes, augmentez le col ou changez de profil.');
    const pp = s.part, n = pp.legs.length, seq = g.seq;
    const isLib = s.src === 'lib';

    return (
        <div className="row g-3">
            {/* ── Dessin ── */}
            <div className="col-lg-8">
                {/* collant : le dessin reste visible pendant qu'on fait défiler les paramètres */}
                <div className="card mb-0" style={{ position: 'sticky', top: '0.5rem' }}>
                    <div className="card-header d-flex align-items-center py-2">
                        <h3 className="card-title mb-0"><i className="fas fa-drafting-compass me-2" />Profil et pièce pliée</h3>
                        <button type="button" className="btn btn-sm btn-link ms-auto" onClick={reset}><i className="fas fa-undo me-1" />Réinitialiser</button>
                    </div>
                    <div className="card-body py-2">
                        <div className="position-relative">
                            <div
                                ref={hostRef} onPointerDown={onPointerDown}
                                onDoubleClick={(e) => { if (!e.target.closest('.pd-hd')) setView({ z: 1, x: 0, y: 0 }); }}
                                style={{
                                    touchAction: 'none', userSelect: 'none', border: '1px solid var(--bs-border-color)', background: PAPER,
                                    height: 'calc(100vh - 290px)', minHeight: 280, cursor: view.z > 1 ? 'grab' : 'default',
                                }}
                                dangerouslySetInnerHTML={{
                                    __html: svg.replace(
                                        `width="${SVG_W}" height="${SVG_H}" viewBox="0 0 ${SVG_W} ${SVG_H}"`,
                                        `width="100%" height="100%" viewBox="${viewBox}" style="display:block"`,
                                    ),
                                }}
                            />
                            <div className="btn-group-vertical btn-group-sm position-absolute shadow-sm" style={{ top: 8, right: 8 }}>
                                <button type="button" className="btn btn-light border" title="Zoom avant" onClick={() => zoomCenter(1.5)} disabled={view.z >= ZOOM_MAX}><i className="fas fa-search-plus" /></button>
                                <button type="button" className="btn btn-light border" title="Zoom arrière" onClick={() => zoomCenter(1 / 1.5)} disabled={view.z <= 1}><i className="fas fa-search-minus" /></button>
                                <button type="button" className="btn btn-light border" title="Ajuster" onClick={() => setView({ z: 1, x: 0, y: 0 })} disabled={view.z <= 1}><i className="fas fa-expand" /></button>
                            </div>
                            {view.z > 1 && (
                                <span className="badge bg-secondary position-absolute" style={{ bottom: 8, right: 8 }}>{Math.round(view.z * 100)} %</span>
                            )}
                        </div>
                        <p className="small text-muted mt-1 mb-1">
                            Cotes en mm. Glissez les cercles pour modifier les cotes · molette pour zoomer, glisser le fond pour se déplacer, double-clic pour ajuster.
                        </p>
                        <div className="row g-2">
                            {facts.map(([v, u, l, bad]) => (
                                <div key={l} className="col-6 col-md-3">
                                    <div className="border-top border-2 pt-1" style={{ borderColor: 'currentColor' }}>
                                        <div className={`fs-5 fw-semibold lh-1 ${bad ? 'text-danger' : ''}`}>{v}<small className="fs-6 text-muted ms-1">{u}</small></div>
                                        <div className="small text-muted">{l}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {notices.length > 0 && (
                            <div className={`alert ${g.collide || g.dieCollide ? "alert-danger" : "alert-warning"} py-1 px-2 small mt-2 mb-0`}>{notices.join(' ')}</div>
                        )}
                    </div>
                </div>
            </div>

            {/* ── Paramètres ── */}
            <div className="col-lg-4">
                <div className="card">
                    <div className="card-body">
                        <h6 className="text-muted">Source du poinçon</h6>
                        <div className="btn-group w-100 mb-3" role="group">
                            {[['param', 'Paramétrique'], ['lib', 'Bibliothèque']].map(([v, l]) => (
                                <button key={v} type="button" className={`btn btn-sm ${s.src === v ? 'btn-primary' : 'btn-outline-primary'}`}
                                    aria-pressed={s.src === v} onClick={() => setS((prev) => ({ ...prev, src: v }))}>{l}</button>
                            ))}
                        </div>

                        {libError && <div className={`alert ${isLib ? 'alert-danger' : 'alert-warning'} py-2 small`}>Bibliothèque indisponible : {libError}</div>}
                        {isLib ? (
                            <>
                                {!lib && !libError && <div className="small text-muted mb-2"><i className="fas fa-spinner fa-spin me-1" />Chargement de la bibliothèque…</div>}
                                {lib && (
                                    <>
                                        <div className="d-flex gap-2 mb-2">
                                            <select className="form-select form-select-sm" style={{ maxWidth: 140 }} aria-label="Marque" value={libBrand} onChange={(e) => setBrand(e.target.value)}>
                                                <option value="">Toutes marques</option>
                                                {brands.map((b) => <option key={b} value={b}>{b}</option>)}
                                            </select>
                                            <input type="search" className="form-control form-control-sm" placeholder="Référence, angle…" autoComplete="off" value={libQ} onChange={(e) => setLibQ(e.target.value)} />
                                        </div>
                                        <div className="list-group list-group-flush border" style={{ maxHeight: 260, overflowY: 'auto' }}>
                                            {libItems.length === 0 && <div className="p-2 small text-muted">Aucun poinçon.</div>}
                                            {libItems.map((t) => (
                                                <button key={t.id} type="button" onClick={() => pickLib(t)}
                                                    className={`list-group-item list-group-item-action d-flex align-items-center gap-2 py-1 px-2 ${t.id === s.libId ? 'active' : ''}`}>
                                                    <LibThumb poly={t.poly} />
                                                    <span className="lh-sm">
                                                        <b className="d-block small">{t.name}</b>
                                                        <span className="small opacity-75">{t.brand}{t.angle != null && ` · ${t.angle}°`}{t.radius != null && ` · R${t.radius}`}{t.height != null && ` · H${t.height}`}</span>
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                        <div className="small text-muted mt-1">{libItems.length === 150 ? '150 premiers résultats — affinez la recherche.' : `${libItems.length} poinçon(s)`} · {lib.length} au total</div>
                                        {libTool && (
                                            <div className="d-flex align-items-center flex-wrap gap-2 mt-2 small">
                                                <span><b>{libTool.brand} {libTool.name}</b>{libTool.height != null && ` · H ${libTool.height}`}{libTool.force ? ` · ${libTool.force} t/m` : ''}</span>
                                                <button type="button" className={`btn btn-sm ${s.mirror ? 'btn-secondary' : 'btn-outline-secondary'}`} onClick={() => setS((prev) => ({ ...prev, mirror: !prev.mirror }))}>
                                                    Miroir{s.mirror && ' ✓'}
                                                </button>
                                            </div>
                                        )}
                                    </>
                                )}
                            </>
                        ) : (
                            <>
                                <h6 className="text-muted">Interface du poinçon</h6>
                                <div className="row g-2">
                                    {Object.keys(IFACE).map((k) => (
                                        <div key={k} className="col-6">
                                            <button type="button" aria-pressed={s.iface === k} onClick={() => setS((prev) => ({ ...prev, iface: k }))}
                                                className={`btn btn-sm w-100 d-flex align-items-center gap-2 text-start ${s.iface === k ? 'btn-primary' : 'btn-outline-secondary'}`}>
                                                <IfaceIcon k={k} />
                                                <span className="lh-sm"><b className="d-block">{IFACE[k].label}</b><span className="small opacity-75">{IFACE[k].w} mm</span></span>
                                            </button>
                                        </div>
                                    ))}
                                </div>
                                <p className="small mt-2 mb-3">
                                    <button type="button" className="btn btn-link btn-sm p-0 align-baseline" onClick={() => setS((prev) => ({ ...prev, H: 150, A: 88, R: 0.5 }))}>Utiliser les cotes de référence</button>
                                    <span className="text-muted ms-1">H150 · 88° · R0.5</span>
                                </p>
                                <h6 className="text-muted">Paramètres du profil</h6>
                                {PROFILE_PRM.map((p) => <ParamRow key={p[0]} prm={p} value={s[p[0]]} onSet={setVal} />)}
                            </>
                        )}

                        <h6 className="text-muted mt-3">Pièce pliée</h6>
                        <div className="d-flex align-items-center gap-1 mb-2 small text-muted">
                            Forme :
                            {Object.keys(PRESETS).map((p) => (
                                <button key={p} type="button" className="btn btn-sm btn-outline-secondary py-0 px-3 fw-semibold" onClick={() => setS((prev) => ({ ...prev, part: normBends(PRESETS[p]) }))}>{p}</button>
                            ))}
                        </div>
                        {PART_PRM.map((p) => <ParamRow key={p[0]} prm={p} value={s[p[0]]} onSet={setVal} />)}
                        <div className="border-top">
                            {pp.legs.map((leg, i) => (
                                <React.Fragment key={i}>
                                    <div className="d-flex align-items-center gap-2 py-1 border-bottom">
                                        <label className="mb-0 small flex-grow-1">Côté {i + 1}</label>
                                        <Stepper value={leg} step={1} unit="mm" min={2} max={500} inputWidth={60} ariaLabel={`Côté ${i + 1}`}
                                            onCommit={(v) => setPart((p) => { p.legs[i] = Math.max(2, Math.min(500, Math.round(v))); return p; })} />
                                        <button type="button" className="btn btn-sm btn-link text-muted p-0" disabled={n <= 2} aria-label="supprimer"
                                            onClick={() => setPart((p) => {
                                                const bi = Math.min(i, p.bends.length - 1), seq = seqOf(p);
                                                p.legs.splice(i, 1); p.bends.splice(bi, 1);
                                                p.seq = seq.filter((x) => x !== bi).map((x) => (x > bi ? x - 1 : x));
                                                return p;
                                            })}>×</button>
                                    </div>
                                    {i < n - 1 && (
                                        <div className="d-flex align-items-center gap-2 py-1 ps-3 border-bottom small text-muted" style={{ borderBottomStyle: 'dashed' }}>
                                            <span>Pli {i + 1}</span>
                                            {/* chaque pli garde son angle : déplacer le poinçon ne le modifie pas */}
                                            <Stepper value={pp.bends[i].a} step={1} unit="°" min={BEND_MIN} max={BEND_MAX} inputWidth={50} ariaLabel={`Angle du pli ${i + 1}`}
                                                onCommit={(v) => setPart((p) => { p.bends[i].a = clampBend(v); return p; })} />
                                            {i === pp.k ? (
                                                <span className="fw-semibold text-primary">formé par le poinçon</span>
                                            ) : (
                                                <button type="button" className="btn btn-sm btn-outline-secondary py-0" title="sens du pli"
                                                    onClick={() => setPart((p) => { p.bends[i].s *= -1; return p; })}>{pp.bends[i].s > 0 ? 'même sens' : 'inverse'}</button>
                                            )}
                                            <span className={`badge ms-auto ${i === pp.k ? 'bg-primary' : g.flat[i] ? 'bg-light text-muted border' : 'bg-secondary'}`}
                                                title={g.flat[i] ? 'pas encore plié à cette étape' : ''}>
                                                étape {seq.indexOf(i) + 1}{g.flat[i] ? ' · à plat' : ''}
                                            </span>
                                        </div>
                                    )}
                                </React.Fragment>
                            ))}
                        </div>
                        <button type="button" className="btn btn-sm btn-outline-primary w-100 mt-2" style={{ borderStyle: 'dashed' }}
                            onClick={() => setPart((p) => { p.legs.push(30); return p; })}>+ Ajouter un côté</button>

                        {seq.length > 1 && (
                            <>
                                <h6 className="text-muted mt-3">Séquence de pliage</h6>
                                <p className="small text-muted mb-1">Cliquez une étape pour la représenter : les plis précédents sont faits, les suivants encore à plat.</p>
                                <div className="list-group">
                                    {seq.map((bi, j) => (
                                        <div key={bi} className={`list-group-item d-flex align-items-center gap-2 py-1 px-2 ${bi === pp.k ? 'active' : ''}`}>
                                            <button type="button" className="btn btn-link btn-sm p-0 text-reset text-decoration-none flex-grow-1 text-start"
                                                aria-pressed={bi === pp.k} onClick={() => setPart((p) => { p.k = bi; return p; })}>
                                                <b>Étape {j + 1}</b> · pli {bi + 1} à {pp.bends[bi].a}°
                                            </button>
                                            <div className="btn-group btn-group-sm">
                                                {[[-1, 'fa-arrow-up', 'plus tôt'], [1, 'fa-arrow-down', 'plus tard']].map(([dlt, ic, lbl]) => (
                                                    <button key={dlt} type="button" className={`btn py-0 ${bi === pp.k ? 'btn-light' : 'btn-outline-secondary'}`}
                                                        title={lbl} aria-label={lbl} disabled={j + dlt < 0 || j + dlt >= seq.length}
                                                        onClick={() => setPart((p) => { const q = seqOf(p); [q[j], q[j + dlt]] = [q[j + dlt], q[j]]; p.seq = q; return p; })}>
                                                        <i className={`fas ${ic}`} />
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}

                        <h6 className="text-muted mt-3">Matrice</h6>
                        <div className="btn-group w-100 mb-2" role="group">
                            {[['none', 'Aucune'], ['cat', 'Catalogue'], ['manual', 'Manuelle']].map(([v, l]) => (
                                <button key={v} type="button" className={`btn btn-sm ${s.dieSrc === v ? 'btn-primary' : 'btn-outline-primary'}`}
                                    aria-pressed={s.dieSrc === v} disabled={v === 'cat' && !catalogUrl}
                                    onClick={() => setS((prev) => ({ ...prev, dieSrc: v }))}>{l}</button>
                            ))}
                        </div>
                        {dieError && <div className="alert alert-warning py-2 small">Catalogue indisponible : {dieError}</div>}
                        {s.dieSrc === 'cat' && (
                            <>
                                {!dies && !dieError && <div className="small text-muted mb-2"><i className="fas fa-spinner fa-spin me-1" />Chargement du catalogue…</div>}
                                {dies && (
                                    <>
                                        <div className="d-flex gap-2 mb-2">
                                            <select className="form-select form-select-sm" style={{ maxWidth: 140 }} aria-label="Marque de matrice" value={dieBrand} onChange={(e) => setBrand(e.target.value)}>
                                                <option value="">Toutes marques</option>
                                                {dieBrands.map((b) => <option key={b} value={b}>{b}</option>)}
                                            </select>
                                            <input type="search" className="form-control form-control-sm" placeholder="Référence, V…" autoComplete="off" value={dieQ} onChange={(e) => setDieQ(e.target.value)} />
                                        </div>
                                        <div className="form-check form-switch small mb-2">
                                            <input className="form-check-input" type="checkbox" id="pd-die-fit" checked={dieFit} onChange={(e) => setDieFit(e.target.checked)} />
                                            <label className="form-check-label" htmlFor="pd-die-fit">
                                                V adaptés à {s.t} mm (V{fmt(6 * s.t)} à V{fmt(12 * s.t)})
                                            </label>
                                        </div>
                                        <div className="list-group list-group-flush border" style={{ maxHeight: 220, overflowY: 'auto' }}>
                                            {dieItems.length === 0 && <div className="p-2 small text-muted">Aucune matrice{dieFit ? ' adaptée — désactivez le filtre V' : ''}.</div>}
                                            {dieItems.map((d) => (
                                                <button key={d.id} type="button" onClick={() => setS((prev) => ({ ...prev, dieId: d.id }))}
                                                    className={`list-group-item list-group-item-action d-flex align-items-center gap-2 py-1 px-2 ${d.id === s.dieId ? 'active' : ''}`}>
                                                    <span className="badge bg-secondary flex-shrink-0" style={{ minWidth: 44 }}>V{fmt(d.v)}</span>
                                                    <span className="lh-sm">
                                                        <b className="d-block small">{d.name}</b>
                                                        <span className="small opacity-75">{d.brand} · {d.a}° · R{d.r} · H{fmt(d.h)}{d.subtype && d.subtype !== 'Standard die' ? ` · ${d.subtype}` : ''}</span>
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                        <div className="small text-muted mt-1">{dieItems.length === 150 ? '150 premiers résultats — affinez la recherche.' : `${dieItems.length} matrice(s)`} · {dies.length} au total</div>
                                    </>
                                )}
                            </>
                        )}
                        {s.dieSrc === 'manual' && DIE_PRM.map((p) => <ParamRow key={p[0]} prm={p} value={s[p[0]]} onSet={setVal} />)}
                        {s.dieSrc !== 'none' && (
                            <p className="small text-muted mb-0">Matrice schématisée d'après ses cotes, posée sous le pli au contact de la tôle.</p>
                        )}

                        <h6 className="text-muted mt-3">Fabrication</h6>
                        {MFG_PRM.map((p) => <ParamRow key={p[0]} prm={p} value={s[p[0]]} onSet={setVal} />)}

                        <button type="button" className="btn btn-primary w-100 mt-3" disabled={busy} onClick={exportPdf}>
                            <i className="fas fa-file-pdf me-1" />Exporter le PDF
                        </button>
                        <div className="small text-muted mt-1" style={{ minHeight: '1.2em' }}>{status}</div>
                        <p className="small text-muted mt-2 mb-0 border-start border-3 ps-2">
                            Conception préliminaire : le serrage, la résistance au tonnage et la fabricabilité doivent être validés par le bureau d'études avant commande.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
