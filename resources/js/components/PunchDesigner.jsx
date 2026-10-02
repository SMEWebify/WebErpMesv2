import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';

// ─── Données ────────────────────────────────────────────────────────────────
// Interfaces de serrage (queue du poinçon) : largeur, décalage, hauteur, encoches de sécurité.
const IFACE = {
    amada:     { label: 'Amada',         w: 13, off: 7,  h: 30, notch: ['right'] },
    trumpf:    { label: 'Trumpf · Wila', w: 20, off: 10, h: 30, notch: ['left', 'right'] },
    lvd:       { label: 'LVD',           w: 13, off: 7,  h: 30, notch: [] },
    bystronic: { label: 'Bystronic',     w: 20, off: 10, h: 30, notch: ['left'] },
};
// bends[i] = pli entre côté i et côté i+1 (sauf le pli actif k, formé par le poinçon à l'angle A)
const PRESETS = {
    L: { legs: [30, 60], bends: [{ a: 90, s: 1 }], k: 0 },
    U: { legs: [30, 60, 30], bends: [{ a: 90, s: 1 }, { a: 90, s: 1 }], k: 0 },
    Z: { legs: [30, 40, 30], bends: [{ a: 90, s: 1 }, { a: 90, s: -1 }], k: 0 },
};
const DEFAULTS = { iface: 'amada', H: 150, A: 88, R: 0.5, T: 40, b: 18, tp: 7.6, t: 1.2, L: 835, part: PRESETS.L };
// [clé, libellé, symbole, min, max, pas, unité]
const PROFILE_PRM = [
    ['H', 'Hauteur totale', 'H', 60, 250, 1, 'mm'],
    ['A', 'Angle de pointe', '', 20, 90, 1, '°'],
    ['R', 'Rayon de pointe', 'R', 0.2, 5, 0.1, 'mm'],
    ['T', 'Épaisseur totale', '', 15, 60, 1, 'mm'],
    ['b', 'Épaisseur du corps', '', 6, 40, 0.5, 'mm'],
    ['tp', 'Épaisseur de pointe', '', 3, 20, 0.2, 'mm'],
];
const PART_PRM = [['t', 'Épaisseur de tôle', '', 0.5, 8, 0.1, 'mm']];
const MFG_PRM  = [['L', 'Longueur du poinçon', '', 20, 4000, 5, 'mm']];
const PRM = [...PROFILE_PRM, ...PART_PRM, ...MFG_PRM];
const DENSITY = 7.85e-6; // acier, kg/mm³

const clone = (o) => JSON.parse(JSON.stringify(o));
const fmt = (n, d = 1) => Number(n).toLocaleString('fr-FR', { maximumFractionDigits: d });
const esc = (x) => String(x ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

function clampPrm(k, v) {
    const p = PRM.find((x) => x[0] === k);
    v = Math.max(p[3], Math.min(p[4], v));
    v = Math.round(v / p[5]) * p[5];
    return +v.toFixed(2);
}

function normBends(part) {
    const pp = clone(part);
    while (pp.bends.length < pp.legs.length - 1) pp.bends.push({ a: 90, s: 1 });
    pp.bends.length = pp.legs.length - 1;
    pp.k = Math.max(0, Math.min(pp.k, pp.legs.length - 2));
    return pp;
}

const polyArea = (pts) => {
    let a = 0;
    for (let i = 0; i < pts.length; i++) {
        const [x1, y1] = pts[i], [x2, y2] = pts[(i + 1) % pts.length];
        a += x1 * y2 - x2 * y1;
    }
    return Math.abs(a) / 2;
};

// ─── Géométrie ──────────────────────────────────────────────────────────────
// Repère : origine = apex de la pointe, x vers la droite, y vers le haut (mm).
function build(s, libt) {
    if (libt) return buildLib(s, libt);
    const f = IFACE[s.iface];
    const notes = [];
    const T = s.T, b = Math.min(s.b, T - 2), tp = Math.min(s.tp, b);
    if (b !== s.b) notes.push(`Corps limité à ${b} mm (épaisseur totale ${T} mm).`);
    if (tp !== s.tp) notes.push(`Pointe limitée à ${tp} mm (corps ${b} mm).`);
    const theta = s.A / 2 * Math.PI / 180;
    const ht = (tp / 2) / Math.tan(theta);           // hauteur du V de pointe
    const R = Math.min(s.R, 0.9 * (tp / 2) / Math.cos(theta));
    if (R !== s.R) notes.push(`Rayon limité à ${R.toFixed(2)} mm.`);
    const block = 15;
    const rest = s.H - f.h - block - ht;
    if (rest < 30) notes.push('Hauteur très faible pour cette interface : col raccourci.');
    const lt = Math.max(4, Math.min(15, rest * 0.25));        // partie droite de la pointe
    const XL = -tp / 2;                                       // bord gauche de la pointe
    const yBlockTop = s.H - f.h, yBlockBot = yBlockTop - block;
    const yT = ht + lt;
    const yN1 = yBlockBot - Math.min(25, (yBlockBot - yT) * 0.2); // bas du chanfrein sous le bloc
    const yN2 = yT + (yN1 - yT) * 0.45;                           // bas du col (face gauche)
    // face droite : parallèle à l'aile de la pièce (flanc prolongé) jusqu'au bord droit
    let yN2R = (XL + T) / Math.tan(theta);
    if (yN2R > yBlockBot - 5) {
        yN2R = yBlockBot - 5;
        notes.push('Angle trop fermé pour une face droite dans le prolongement du flanc.');
    }
    const pts = [];
    const P = (x, y) => pts.push([x, y]);
    // queue
    const sx = XL + f.off, sw = f.w;
    P(sx, s.H); P(sx + sw, s.H);
    if (f.notch.includes('right')) { P(sx + sw, s.H - 17.5); P(sx + sw - 3, s.H - 17.5); P(sx + sw - 3, s.H - 25.5); P(sx + sw, s.H - 25.5); }
    P(sx + sw, yBlockTop); P(XL + T, yBlockTop);
    // face droite : verticale puis biais vers la pointe
    P(XL + T, yN2R);
    // pointe arrondie
    const d = R / Math.tan(theta), cy = R / Math.sin(theta);
    const t1 = [d * Math.sin(theta), d * Math.cos(theta)], t2 = [-d * Math.sin(theta), d * Math.cos(theta)];
    P(t1[0], t1[1]);
    const a0 = Math.atan2(t1[1] - cy, t1[0]), a1 = Math.atan2(t2[1] - cy, t2[0]);
    for (let i = 1; i < 10; i++) { const a = a0 + (a1 - a0) * i / 10; P(R * Math.cos(a), cy + R * Math.sin(a)); }
    P(t2[0], t2[1]);
    // face gauche : pointe, biais, col vertical, chanfrein sous le bloc
    P(XL, ht); P(XL, yT); P(XL + T - b, yN2); P(XL + T - b, yN1); P(XL, yBlockBot); P(XL, yBlockTop); P(sx, yBlockTop);
    if (f.notch.includes('left')) { P(sx, s.H - 25.5); P(sx + 3, s.H - 25.5); P(sx + 3, s.H - 17.5); P(sx, s.H - 17.5); }
    const pg = partGeom(s, pts, theta, yT, 0.05);
    return { ...pg, pts, area: polyArea(pts), notes, theta, ht, yT, yN: yN2, yN1, yN2, XL, T, b, tp, R, cy, f, sx, sw, yBlockTop, yBlockBot };
}

function partGeom(s, pts, theta, yExclude, tol) {
    // pièce pliée : polyligne intérieure passant par l'apex au pli actif, épaisseur t vers l'extérieur
    const uL = [-Math.sin(theta), Math.cos(theta)];
    const t = s.t, pp = s.part, n = pp.legs.length, k = Math.min(pp.k, n - 2);
    const ang = new Array(n);
    ang[k] = Math.atan2(-uL[1], -uL[0]);                         // côté k arrive à l'apex
    const bendAt = (i) => (i === k ? { a: s.A, s: 1 } : pp.bends[i]);
    for (let i = k; i < n - 1; i++) { const bd = bendAt(i); ang[i + 1] = ang[i] + bd.s * (Math.PI - bd.a * Math.PI / 180); }
    for (let i = k; i > 0; i--) { const bd = bendAt(i - 1); ang[i - 1] = ang[i] - bd.s * (Math.PI - bd.a * Math.PI / 180); }
    const dir = ang.map((a) => [Math.cos(a), Math.sin(a)]);
    const V = new Array(n + 1); V[k + 1] = [0, 0];              // V[i] = début du côté i, V[n] = fin
    for (let i = k + 1; i < n; i++) V[i + 1] = [V[i][0] + dir[i][0] * pp.legs[i], V[i][1] + dir[i][1] * pp.legs[i]];
    for (let i = k; i >= 0; i--) V[i] = [V[i + 1][0] - dir[i][0] * pp.legs[i], V[i + 1][1] - dir[i][1] * pp.legs[i]];
    const rn = dir.map((dd) => [dd[1], -dd[0]]);                 // normale droite = face extérieure
    const outer = [];
    for (let i = 0; i <= n; i++) {
        if (i === 0) outer.push([V[0][0] + rn[0][0] * t, V[0][1] + rn[0][1] * t]);
        else if (i === n) outer.push([V[n][0] + rn[n - 1][0] * t, V[n][1] + rn[n - 1][1] * t]);
        else { // intersection des deux faces extérieures décalées
            const p1 = [V[i][0] + rn[i - 1][0] * t, V[i][1] + rn[i - 1][1] * t], d1 = dir[i - 1];
            const p2 = [V[i][0] + rn[i][0] * t, V[i][1] + rn[i][1] * t], d2 = dir[i];
            const den = d1[0] * d2[1] - d1[1] * d2[0];
            if (Math.abs(den) < 1e-6) outer.push(p2);
            else { const a = ((p2[0] - p1[0]) * d2[1] - (p2[1] - p1[1]) * d2[0]) / den; outer.push([p1[0] + d1[0] * a, p1[1] + d1[1] * a]); }
        }
    }
    const part = [...V, ...outer.slice().reverse()];
    // jeu : distance des points du poinçon (hors V de pointe) à la face intérieure ; collision si un point est dans la tôle
    const inPoly = (p, poly) => {
        let c = false;
        for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
            const a = poly[i], bb = poly[j];
            if ((a[1] > p[1]) !== (bb[1] > p[1]) && p[0] < (bb[0] - a[0]) * (p[1] - a[1]) / (bb[1] - a[1]) + a[0]) c = !c;
        }
        return c;
    };
    const segD = (p, a, bb) => {
        const dx = bb[0] - a[0], dy = bb[1] - a[1], l2 = dx * dx + dy * dy;
        let q = l2 ? ((p[0] - a[0]) * dx + (p[1] - a[1]) * dy) / l2 : 0;
        q = Math.max(0, Math.min(1, q));
        return Math.hypot(p[0] - a[0] - dx * q, p[1] - a[1] - dy * q);
    };
    let clear = Infinity, collide = false;
    const samp = [];
    for (let i = 0; i < pts.length; i++) {
        const a = pts[i], c = pts[(i + 1) % pts.length];
        for (let kk = 0; kk < 6; kk++) { const q = kk / 6; samp.push([a[0] + (c[0] - a[0]) * q, a[1] + (c[1] - a[1]) * q]); }
    }
    samp.filter((p) => p[1] > yExclude - 0.01).forEach((p) => {
        let dmin = Infinity;
        for (let i = 0; i < n; i++) dmin = Math.min(dmin, segD(p, V[i], V[i + 1]));
        if (dmin < tol) return;
        if (inPoly(p, part)) collide = true; else clear = Math.min(clear, dmin);
    });
    if (clear === Infinity) clear = 0;
    return { part, clear, collide, V, dir, rn, n, k };
}

function buildLib(s, lt) {
    const notes = [];
    let pts = lt.poly.map((p) => [s.mirror ? -p[0] : p[0], p[1]]);
    if (s.mirror) pts = pts.slice().reverse();
    const theta = s.A / 2 * Math.PI / 180;
    const xs = pts.map((p) => p[0]), ys = pts.map((p) => p[1]);
    if (lt.angle != null && Math.abs(lt.angle - s.A) > 0.5) notes.push(`Angle de pointe de l'outil : ${lt.angle}°, pièce simulée à ${s.A}°.`);
    const pg = partGeom(s, pts, theta, 1.5, 0.35);
    return { ...pg, pts, area: polyArea(pts), notes, theta, lib: lt, R: lt.radius, ht: 0, yT: 0, XL: Math.min(...xs), T: Math.max(...xs) - Math.min(...xs), Hb: Math.max(...ys), cy: 0 };
}

// ─── Dessin (SVG en chaîne : réutilisé tel quel pour le PDF) ────────────────
const INK = '#1A2029', DIM = '#1F5C99', FILL = '#1F5C99', PAPER = '#F4F6F8', PART = '#C8541C';
const SVG_W = 640, SVG_H = 560;
const ZOOM_MAX = 20;

/** Cadre visible du dessin : z = facteur de zoom, (x, y) = coin haut gauche en unités SVG. */
function clampView(z, x, y) {
    z = Math.max(1, Math.min(ZOOM_MAX, z));
    const w = SVG_W / z, h = SVG_H / z;
    return { z, x: Math.max(0, Math.min(SVG_W - w, x)), y: Math.max(0, Math.min(SVG_H - h, y)) };
}
const toSvgPoint = (inv, clientX, clientY) => new DOMPoint(clientX, clientY).matrixTransform(inv);

function arrow(x, y, d) {
    const p = {
        0: `${x},${y} ${x - 3},${y + 8} ${x + 3},${y + 8}`,
        1: `${x},${y} ${x - 3},${y - 8} ${x + 3},${y - 8}`,
        2: `${x},${y} ${x + 8},${y - 3} ${x + 8},${y + 3}`,
        3: `${x},${y} ${x - 8},${y - 3} ${x - 8},${y + 3}`,
    }[d];
    return `<polygon points="${p}" fill="${DIM}"/>`;
}
function dimV(x, y1, y2, label, font, xa, xb) {
    return `<line x1="${xa}" y1="${y1}" x2="${x + 5}" y2="${y1}" stroke="${DIM}" stroke-width=".6"/><line x1="${xb}" y1="${y2}" x2="${x + 5}" y2="${y2}" stroke="${DIM}" stroke-width=".6"/><line x1="${x}" y1="${y1}" x2="${x}" y2="${y2}" stroke="${DIM}" stroke-width=".9"/>`
        + arrow(x, y1, 0) + arrow(x, y2, 1) + `<text x="${x + 7}" y="${(y1 + y2) / 2 + 4}" fill="${DIM}" ${font}>${label}</text>`;
}
function dimH(y, x1, x2, label, font, above) {
    return `<line x1="${x1}" y1="${y}" x2="${x2}" y2="${y}" stroke="${DIM}" stroke-width=".9"/>`
        + arrow(x1, y, 2) + arrow(x2, y, 3) + `<text x="${(x1 + x2) / 2}" y="${above ? y - 4 : y + 14}" text-anchor="middle" fill="${DIM}" ${font}>${label}</text>`;
}

function partDims(s, g, X, Y, font) {
    let o = '';
    // cotes de la pièce : longueur de chaque côté le long de la face extérieure, angle des plis non actifs
    for (let i = 0; i < g.n; i++) {
        const off = s.t + 9, a = g.V[i], c = g.V[i + 1], nn = g.rn[i], d = g.dir[i];
        const p0 = [a[0] + nn[0] * off, a[1] + nn[1] * off], p1 = [c[0] + nn[0] * off, c[1] + nn[1] * off];
        const mx = (X(p0[0]) + X(p1[0])) / 2, my = (Y(p0[1]) + Y(p1[1])) / 2;
        let an = -Math.atan2(d[1], d[0]) * 180 / Math.PI;
        if (an > 90 || an < -90) an += 180;
        o += `<line x1="${X(p0[0])}" y1="${Y(p0[1])}" x2="${X(p1[0])}" y2="${Y(p1[1])}" stroke="${PART}" stroke-width=".9"/><text transform="translate(${mx} ${my}) rotate(${an})" dy="-4" text-anchor="middle" fill="${PART}" ${font}>${s.part.legs[i]}</text>`;
        if (i < g.n - 1 && i !== g.k) {
            const bd = s.part.bends[i], v = g.V[i + 1];
            o += `<text x="${X(v[0]) + (bd.s > 0 ? -8 : 8)}" y="${Y(v[1]) + (bd.s > 0 ? -8 : 14)}" text-anchor="${bd.s > 0 ? 'end' : 'start'}" fill="${PART}" ${font}>${bd.a}°</text>`;
        }
    }
    o += `<text x="${X(g.part[g.n + 1][0]) + 6}" y="${Y(g.part[g.n + 1][1]) + 4}" fill="${PART}" ${font}>ép. ${s.t}</text>`;
    return o;
}

function handle(key, cx, cy, cursor) {
    return `<circle class="pd-hd" data-k="${key}" cx="${cx}" cy="${cy}" r="6" fill="#fff" stroke="${DIM}" stroke-width="1.6" style="cursor:${cursor}"/>`;
}

/** Renvoie { svg, tf } — tf = transformation écran ↔ mm utilisée par le glisser-déposer. */
function drawing(s, g) {
    const all = [...g.pts, ...g.part];
    const minx = Math.min(...all.map((p) => p[0])) - 22, maxx = Math.max(...all.map((p) => p[0])) + 40;
    const miny = Math.min(...all.map((p) => p[1])) - 16, maxy = Math.max(...all.map((p) => p[1])) + 22;
    const m = 36;
    const sc = Math.min((SVG_W - 2 * m) / (maxx - minx), (SVG_H - 2 * m) / (maxy - miny));
    const ox = m - minx * sc + ((SVG_W - 2 * m) - (maxx - minx) * sc) / 2;
    const oy = SVG_H - m + miny * sc - ((SVG_H - 2 * m) - (maxy - miny) * sc) / 2;
    const X = (x) => +(ox + x * sc).toFixed(2), Y = (y) => +(oy - y * sc).toFixed(2);
    const path = (pts) => pts.map((p, i) => (i ? 'L' : 'M') + X(p[0]) + ' ' + Y(p[1])).join(' ') + ' Z';
    const font = 'font-family="Arial, sans-serif" font-size="12"';
    const H = g.lib ? g.Hb : s.H;

    let o = `<defs><pattern id="pd-h" width="6" height="6" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><line x1="0" y1="0" x2="0" y2="6" stroke="${FILL}" stroke-opacity=".3"/></pattern></defs>`;
    o += `<path d="${path(g.part)}" fill="${PART}" fill-opacity=".18" stroke="${PART}" stroke-width="1.2" stroke-linejoin="round"/>`;
    o += `<path d="${path(g.pts)}" fill="url(#pd-h)" stroke="${INK}" stroke-width="1.6" stroke-linejoin="round"/>`;
    o += `<line x1="${X(0)}" y1="${Y(-8)}" x2="${X(0)}" y2="${Y(H + 6)}" stroke="${INK}" stroke-width=".6" stroke-dasharray="10 3 2 3"/>`;

    const xr = X(g.XL + g.T) + 34;
    const ar = 36, ax = X(0), ay = Y(0), a1 = -Math.PI / 2 - g.theta, a2 = -Math.PI / 2 + g.theta;
    const angleArc = `<path d="M ${ax + ar * Math.cos(a1)} ${ay + ar * Math.sin(a1)} A ${ar} ${ar} 0 0 1 ${ax + ar * Math.cos(a2)} ${ay + ar * Math.sin(a2)}" fill="none" stroke="${DIM}"/><text x="${ax}" y="${ay - ar - 5}" text-anchor="middle" fill="${DIM}" ${font}>${s.A}°</text>`;
    const radiusTag = g.R != null ? `<line x1="${ax}" y1="${ay}" x2="${ax + 34}" y2="${ay + 30}" stroke="${DIM}"/><text x="${ax + 37}" y="${ay + 34}" fill="${DIM}" ${font}>R${g.R}</text>` : '';
    const tHandle = ['t', (g.V[g.k][0] + g.V[g.k + 1][0]) / 2 + g.rn[g.k][0] * s.t, (g.V[g.k][1] + g.V[g.k + 1][1]) / 2 + g.rn[g.k][1] * s.t, 'move'];
    const legHandles = [];
    for (let i = 0; i < g.n; i++) { const v = i <= g.k ? g.V[i] : g.V[i + 1]; legHandles.push(['leg' + i, v[0], v[1], 'move']); }
    const angleHandle = handle('A', ax + ar * Math.cos(a1), ay + ar * Math.sin(a1), 'ew-resize');

    let hs, caption;
    if (g.lib) {
        o += dimV(xr, Y(g.Hb), Y(0), 'H ' + fmt(g.Hb), font, X(g.XL + g.T) + 4, X(0) + 4);
        o += dimH(Y(g.Hb) - 14, X(g.XL), X(g.XL + g.T), fmt(g.T), font, true);
        hs = [tHandle, ...legHandles];
        caption = `${esc(g.lib.brand)} ${esc(g.lib.name)} · ${s.A}°${g.R != null ? ' · R' + g.R : ''} · L ${s.L} mm`;
    } else {
        o += dimV(xr, Y(s.H), Y(0), 'H ' + s.H, font, X(g.XL + g.T) + 4, X(0) + 4);
        o += dimH(Y(s.H) - 30, X(g.XL), X(g.XL + g.T), g.T, font, true);
        o += dimH(Y(s.H) - 12, X(g.XL), X(g.sx), g.f.off, font, true) + dimH(Y(s.H) - 12, X(g.sx), X(g.sx + g.sw), g.f.w, font, true);
        const xl = X(g.XL) - 26;
        o += dimV(xl, Y(s.H), Y(g.yBlockTop), g.f.h, font, X(g.sx) - 4, X(g.XL) - 4) + dimV(xl, Y(g.yBlockTop), Y(g.yBlockBot), 15, font, X(g.XL) - 4, X(g.XL) - 4);
        o += dimH(Y(g.yN) + 10, X(g.XL + g.T - g.b), X(g.XL + g.T), g.b, font);
        o += dimH(Y(g.ht) + 1, X(g.XL), X(g.tp / 2), g.tp, font, true);
        hs = [
            ['H', g.sx + g.sw / 2, s.H, 'ns-resize'], ['T', g.XL + g.T, (g.yBlockTop + g.yBlockBot) / 2, 'ew-resize'],
            ['b', g.XL + g.T - g.b, (g.yN1 + g.yN2) / 2, 'ew-resize'], ['tp', g.XL, (g.ht + g.yT) / 2, 'ew-resize'],
            ['R', 0, g.cy - g.R, 'ns-resize'], tHandle, ...legHandles,
        ];
        caption = `${g.f.label} · H${s.H} · ${s.A}° · R${g.R} · L ${s.L} mm`;
    }
    o += angleArc + radiusTag + partDims(s, g, X, Y, font);
    o += hs.map((h) => handle(h[0], X(h[1]), Y(h[2]), h[3])).join('') + angleHandle;
    o += `<text x="${m}" y="${SVG_H - 12}" fill="${INK}" ${font}>${caption}</text>`;
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${SVG_W}" height="${SVG_H}" viewBox="0 0 ${SVG_W} ${SVG_H}"><rect width="100%" height="100%" fill="${PAPER}"/>${o}</svg>`;
    return { svg, tf: { ox, oy, sc } };
}

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
    const clearRow = ['Jeu minimal', g.collide ? 'collision' : fmt(g.clear, 1) + ' mm'];
    const rows = g.lib
        ? [['Poinçon de bibliothèque', g.lib.brand + ' ' + g.lib.name], ['Angle de pointe simulé', s.A + '°'], ['Rayon', g.R != null ? g.R + ' mm' : '—'],
           ['Hauteur', fmt(g.Hb) + ' mm'], ['Longueur', s.L + ' mm'], ['Masse estimée', mass], ['Aire de section', section], partRow, clearRow]
        : [['Interface', g.f.label + ' (' + g.f.w + ' mm)'], ['Hauteur totale', s.H + ' mm'], ['Angle de pointe', s.A + '°'], ['Rayon de pointe', g.R + ' mm'],
           ['Épaisseur totale', g.T + ' mm'], ['Épaisseur du corps', g.b + ' mm'], ['Épaisseur de pointe', g.tp + ' mm'], ['Longueur', s.L + ' mm'],
           ['Masse estimée', mass], ['Aire de section', section], partRow, clearRow];
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
export default function PunchDesigner({ brand = 'Votre atelier', libraryUrl }) {
    const [s, setS] = useState(() => ({ ...clone(DEFAULTS), src: 'param', libId: null, mirror: false }));
    const [lib, setLib] = useState(null);
    const [libError, setLibError] = useState(null);
    const [libBrand, setLibBrand] = useState('');
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
                setLib(data);
            })
            .catch((e) => setLibError(e.message || String(e)));
    }, [s.src, lib]);

    const libTool = s.src === 'lib' && lib && s.libId != null ? lib.find((t) => t.id === s.libId) : null;
    // Bascule sur la bibliothèque : sélectionne le premier poinçon une fois les données arrivées.
    useEffect(() => {
        if (s.src === 'lib' && lib && s.libId == null && lib.length) pickLib(lib[0]);
    }, [s.src, lib]); // eslint-disable-line react-hooks/exhaustive-deps

    const g = useMemo(() => build(s, s.src === 'lib' ? libTool : null), [s, libTool]);
    const { svg, tf } = useMemo(() => drawing(s, g), [s, g]);

    const setVal = useCallback((k, v) => setS((prev) => ({ ...prev, [k]: clampPrm(k, v) })), []);
    const setPart = (fn) => setS((prev) => ({ ...prev, part: normBends(fn(clone(prev.part))) }));
    function pickLib(t) {
        setS((prev) => ({ ...prev, libId: t.id, ...(t.angle != null ? { A: clampPrm('A', t.angle) } : {}) }));
    }

    const brands = useMemo(() => (lib ? [...new Set(lib.map((t) => t.brand))].sort() : []), [lib]);
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
                case 'A': { // angle lu depuis la position du pointeur par rapport à l'apex
                    const p = toSvgPoint(drag.inv, e.clientX, e.clientY);
                    const mx = (p.x - drag.tf.ox) / drag.tf.sc, my = -(p.y - drag.tf.oy) / drag.tf.sc;
                    v = 2 * Math.atan2(Math.abs(mx), Math.max(0.1, my)) * 180 / Math.PI;
                    break;
                }
                default: return;
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
    const reset = () => setS((prev) => ({ ...clone(DEFAULTS), src: prev.src, libId: prev.libId, mirror: false, ...(libTool?.angle != null ? { A: clampPrm('A', libTool.angle) } : {}) }));
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
    if (g.collide) notices.unshift('La pièce pliée touche le poinçon : réduisez les ailes, augmentez le col ou changez de profil.');
    const pp = s.part, n = pp.legs.length;
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
                            <div className={`alert ${g.collide ? 'alert-danger' : 'alert-warning'} py-1 px-2 small mt-2 mb-0`}>{notices.join(' ')}</div>
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

                        {isLib ? (
                            <>
                                {libError && <div className="alert alert-danger py-2 small">Bibliothèque indisponible : {libError}</div>}
                                {!lib && !libError && <div className="small text-muted mb-2"><i className="fas fa-spinner fa-spin me-1" />Chargement de la bibliothèque…</div>}
                                {lib && (
                                    <>
                                        <div className="d-flex gap-2 mb-2">
                                            <select className="form-select form-select-sm" style={{ maxWidth: 140 }} aria-label="Marque" value={libBrand} onChange={(e) => setLibBrand(e.target.value)}>
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
                                        <div className="mt-3"><ParamRow prm={PROFILE_PRM[1]} value={s.A} onSet={setVal} /></div>
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
                                            onClick={() => setPart((p) => { p.legs.splice(i, 1); p.bends.splice(Math.min(i, p.bends.length - 1), 1); return p; })}>×</button>
                                    </div>
                                    {i < n - 1 && (
                                        <div className="d-flex align-items-center gap-2 py-1 ps-3 border-bottom small text-muted" style={{ borderBottomStyle: 'dashed' }}>
                                            <span>Pli {i + 1}</span>
                                            {i === pp.k ? (
                                                <span className="fw-semibold text-primary flex-grow-1">formé par le poinçon · {s.A}°</span>
                                            ) : (
                                                <>
                                                    <Stepper value={pp.bends[i].a} step={1} unit="°" min={30} max={179} inputWidth={50} ariaLabel={`Angle du pli ${i + 1}`}
                                                        onCommit={(v) => setPart((p) => { p.bends[i].a = Math.max(30, Math.min(179, Math.round(v))); return p; })} />
                                                    <button type="button" className="btn btn-sm btn-outline-secondary py-0" title="sens du pli"
                                                        onClick={() => setPart((p) => { p.bends[i].s *= -1; return p; })}>{pp.bends[i].s > 0 ? 'même sens' : 'inverse'}</button>
                                                </>
                                            )}
                                            <button type="button" className={`btn btn-sm py-0 ms-auto ${i === pp.k ? 'btn-primary' : 'btn-outline-secondary'}`} aria-pressed={i === pp.k}
                                                onClick={() => setPart((p) => { p.k = i; return p; })}>poinçon ici</button>
                                        </div>
                                    )}
                                </React.Fragment>
                            ))}
                        </div>
                        <button type="button" className="btn btn-sm btn-outline-primary w-100 mt-2" style={{ borderStyle: 'dashed' }}
                            onClick={() => setPart((p) => { p.legs.push(30); return p; })}>+ Ajouter un côté</button>

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
