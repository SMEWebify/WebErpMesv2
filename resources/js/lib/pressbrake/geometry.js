/**
 * Presse plieuse : géométrie du poinçon (paramétrique ou de bibliothèque), de la tôle pliée et
 * de la matrice, détection de collision et séquence de pliage. Fonctions pures, sans DOM :
 * utilisées par PunchDesigner et couvertes par resources/js/tests/pressbrake/.
 */

// ─── Données ────────────────────────────────────────────────────────────────
// Interfaces de serrage (queue du poinçon) : largeur, décalage, hauteur, encoches de sécurité.
export const IFACE = {
    amada:     { label: 'Amada',         w: 13, off: 7,  h: 30, notch: ['right'] },
    trumpf:    { label: 'Trumpf · Wila', w: 20, off: 10, h: 30, notch: ['left', 'right'] },
    lvd:       { label: 'LVD',           w: 13, off: 7,  h: 30, notch: [] },
    bystronic: { label: 'Bystronic',     w: 20, off: 10, h: 30, notch: ['left'] },
};
// bends[i] = pli entre côté i et côté i+1 ; k = pli formé par le poinçon (son angle reste le sien)
export const PRESETS = {
    L: { legs: [30, 60], bends: [{ a: 90, s: 1 }], k: 0 },
    U: { legs: [30, 60, 30], bends: [{ a: 90, s: 1 }, { a: 90, s: 1 }], k: 0 },
    Z: { legs: [30, 40, 30], bends: [{ a: 90, s: 1 }, { a: 90, s: -1 }], k: 0 },
};
// A = angle de pointe du poinçon (outil), indépendant de l'angle du pli qu'il forme :
// un poinçon à 30° plie aussi bien à 90° (pliage en l'air), seul un pli plus fermé que A est impossible.
export const DEFAULTS = {
    iface: 'amada', H: 150, A: 88, R: 0.5, T: 40, b: 18, tp: 7.6, t: 1.2, L: 835, part: PRESETS.L,
    // matrice : 'none' | 'cat' (catalogue constructeurs) | 'manual' (cotes saisies)
    dieSrc: 'none', dieId: null, dieV: 10, dieA: 88, dieR: 1, dieH: 50,
};
// [clé, libellé, symbole, min, max, pas, unité]
export const PROFILE_PRM = [
    ['H', 'Hauteur totale', 'H', 60, 250, 1, 'mm'],
    ['A', 'Angle de pointe', '', 20, 90, 1, '°'],
    ['R', 'Rayon de pointe', 'R', 0.2, 5, 0.1, 'mm'],
    ['T', 'Épaisseur totale', '', 15, 60, 1, 'mm'],
    ['b', 'Épaisseur du corps', '', 6, 40, 0.5, 'mm'],
    ['tp', 'Épaisseur de pointe', '', 3, 20, 0.2, 'mm'],
];
export const PART_PRM = [['t', 'Épaisseur de tôle', '', 0.5, 8, 0.1, 'mm']];
export const BEND_MIN = 20, BEND_MAX = 179;
export const MFG_PRM  = [['L', 'Longueur du poinçon', '', 20, 4000, 5, 'mm']];
export const DIE_PRM  = [
    ['dieV', 'Ouverture du V', 'V', 4, 250, 1, 'mm'],
    ['dieA', 'Angle du V', '', 26, 90, 1, '°'],
    ['dieR', "Rayon d'épaule", '', 0.2, 25, 0.1, 'mm'],
    ['dieH', 'Hauteur', '', 20, 200, 1, 'mm'],
];
export const PRM = [...PROFILE_PRM, ...PART_PRM, ...DIE_PRM, ...MFG_PRM];
export const DENSITY = 7.85e-6; // acier, kg/mm³

export const clone = (o) => JSON.parse(JSON.stringify(o));

export function clampPrm(k, v) {
    const p = PRM.find((x) => x[0] === k);
    v = Math.max(p[3], Math.min(p[4], v));
    v = Math.round(v / p[5]) * p[5];
    return +v.toFixed(2);
}

/** Angle du pli formé par le poinçon. */
export const bendA = (s) => s.part.bends[s.part.k].a;
export const clampBend = (v) => Math.max(BEND_MIN, Math.min(BEND_MAX, Math.round(v)));

/**
 * Séquence de pliage : seq = indices des plis dans l'ordre d'exécution. Le pli sous le poinçon (k)
 * fixe l'étape : les plis qui le précèdent dans la séquence sont faits, les suivants encore à plat.
 */
export function seqOf(pp) {
    const nb = pp.bends.length, seen = new Set();
    const seq = (pp.seq || []).filter((i) => Number.isInteger(i) && i >= 0 && i < nb && !seen.has(i) && seen.add(i));
    for (let i = 0; i < nb; i++) if (!seen.has(i)) seq.push(i);
    return seq;
}

export function normBends(part) {
    const pp = clone(part);
    while (pp.bends.length < pp.legs.length - 1) pp.bends.push({ a: 90, s: 1 });
    pp.bends.length = pp.legs.length - 1;
    pp.k = Math.max(0, Math.min(pp.k, pp.legs.length - 2));
    pp.seq = seqOf(pp);
    return pp;
}

export const polyArea = (pts) => {
    let a = 0;
    for (let i = 0; i < pts.length; i++) {
        const [x1, y1] = pts[i], [x2, y2] = pts[(i + 1) % pts.length];
        a += x1 * y2 - x2 * y1;
    }
    return Math.abs(a) / 2;
};

export const inPoly = (p, poly) => {
    let c = false;
    for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
        const a = poly[i], bb = poly[j];
        if ((a[1] > p[1]) !== (bb[1] > p[1]) && p[0] < (bb[0] - a[0]) * (p[1] - a[1]) / (bb[1] - a[1]) + a[0]) c = !c;
    }
    return c;
};

/** Point d'intersection des segments [a,b] et [c,d], ou null. */
export function segInter(a, b, c, d) {
    const r = [b[0] - a[0], b[1] - a[1]], q = [d[0] - c[0], d[1] - c[1]];
    const den = r[0] * q[1] - r[1] * q[0];
    if (Math.abs(den) < 1e-12) return null;
    const ac = [c[0] - a[0], c[1] - a[1]];
    const u = (ac[0] * q[1] - ac[1] * q[0]) / den, v = (ac[0] * r[1] - ac[1] * r[0]) / den;
    return u >= 0 && u <= 1 && v >= 0 && v <= 1 ? [a[0] + r[0] * u, a[1] + r[1] * u] : null;
}

/**
 * Vrai si une arête du polygone fermé P coupe une arête du polygone fermé Q en un point retenu par
 * keep (tous par défaut). Préfiltre par boîtes englobantes : les deux contours font quelques
 * centaines d'arêtes et le contrôle est refait à chaque modification de la pièce.
 */
export function edgesCross(P, Q, keep = () => true) {
    const box = (a, b) => [Math.min(a[0], b[0]), Math.min(a[1], b[1]), Math.max(a[0], b[0]), Math.max(a[1], b[1])];
    const qe = Q.map((c, j) => { const d = Q[(j + 1) % Q.length]; return [c, d, box(c, d)]; });
    const all = box([Math.min(...Q.map((p) => p[0])), Math.min(...Q.map((p) => p[1]))], [Math.max(...Q.map((p) => p[0])), Math.max(...Q.map((p) => p[1]))]);
    for (let i = 0; i < P.length; i++) {
        const a = P[i], b = P[(i + 1) % P.length], bb = box(a, b);
        if (bb[2] < all[0] || bb[0] > all[2] || bb[3] < all[1] || bb[1] > all[3]) continue;
        for (const [c, d, cb] of qe) {
            if (bb[2] < cb[0] || bb[0] > cb[2] || bb[3] < cb[1] || bb[1] > cb[3]) continue;
            const x = segInter(a, b, c, d);
            if (x && keep(x)) return true;
        }
    }
    return false;
}

/** Ordonnées extrêmes d'un polygone fermé sur la verticale x (null si x est hors du polygone). */
export function yRangeAt(poly, x) {
    let lo = Infinity, hi = -Infinity;
    for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
        const a = poly[j], b = poly[i];
        if ((a[0] - x) * (b[0] - x) > 0 || a[0] === b[0]) continue;
        const y = a[1] + (b[1] - a[1]) * (x - a[0]) / (b[0] - a[0]);
        lo = Math.min(lo, y); hi = Math.max(hi, y);
    }
    return lo === Infinity ? null : [lo, hi];
}

// ─── Géométrie ──────────────────────────────────────────────────────────────
// Repère : origine = apex de la pointe, x vers la droite, y vers le haut (mm).
export function build(s, libt, die) {
    const g = libt ? buildLib(s, libt) : buildParam(s);
    if (die) Object.assign(g, dieGeom(die, g, s.t));
    return g;
}

/**
 * Matrice en V schématisée d'après ses cotes (ouverture V, angle, rayon d'épaule, hauteur) : les
 * profils SVG du catalogue sont inégaux (gravures, origines variables), les cotes sont fiables.
 * Posée sous le pli actif et remontée jusqu'au contact de la tôle, comme en pliage en l'air.
 */
export function dieGeom(d, g, t) {
    const notes = [];
    const beta = d.a / 2 * Math.PI / 180, hv = d.v / 2;
    const depth = hv / Math.tan(beta), floor = d.h - 6;
    const W = hv + Math.max(6, 0.35 * d.v + d.r);
    const open = [[-W, -d.h], [-W, 0], [-hv, 0]];
    if (depth <= floor) open.push([0, -depth]);
    else { const xb = hv - floor * Math.tan(beta); open.push([-xb, -floor], [xb, -floor]); }
    open.push([hv, 0], [W, 0], [W, -d.h]);
    const radii = open.map((p, i) => (i === 0 || i === open.length - 1 ? 0 : Math.abs(Math.abs(p[0]) - hv) < 1e-9 && p[1] === 0 ? d.r : p[1] === 0 ? 1 : 0));
    const poly0 = fillet(open, radii);
    // contact : la matrice monte tant que ni ses points (zone du V et des épaules) ne touchent le
    // dessous de la tôle, ni les points de la tôle le dessus de la matrice — un contact entre deux
    // polylignes passe toujours par un sommet de l'une d'elles
    const zone = hv + d.r + 0.01;
    let dy = Infinity;
    poly0.filter((p) => Math.abs(p[0]) <= zone && p[1] > -depth - 1).forEach((p) => {
        const r = yRangeAt(g.part, p[0]); if (r) dy = Math.min(dy, r[0] - p[1]);
    });
    g.part.filter((q) => Math.abs(q[0]) <= zone).forEach((q) => {
        const r = yRangeAt(poly0, q[0]); if (r) dy = Math.min(dy, q[1] - r[1]);
    });
    if (dy === Infinity) dy = Math.min(...g.part.map((p) => p[1]));
    const poly = poly0.map((p) => [p[0], p[1] + dy]);
    // collision hors contact : matrice abaissée de 0,05 mm pour ne pas compter l'appui lui-même
    const probe = poly.map((p) => [p[0], p[1] - 0.05]);
    const dieCollide = g.part.some((q) => inPoly(q, probe)) || probe.some((p) => inPoly(p, g.part)) || edgesCross(probe, g.part);
    const xs = g.part.map((p) => p[0]);
    if (Math.max(...xs) < hv || Math.min(...xs) > -hv) notes.push(`Aile trop courte pour V${d.v} : la tôle ne porte pas sur les deux épaules et tombe dans le V.`);
    if (d.v < 6 * t) notes.push(`V${d.v} étroit pour ${t} mm de tôle (règle courante : V ≈ 6 à 10 × t).`);
    else if (d.v > 12 * t) notes.push(`V${d.v} large pour ${t} mm de tôle (règle courante : V ≈ 6 à 10 × t) : rayon intérieur et retour élastique plus forts.`);
    return { die: { ...d, poly, dy, hv, W, depth: Math.min(depth, floor) }, dieCollide, notes: [...g.notes, ...notes] };
}

export function buildParam(s) {
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
    const pg = partGeom(s, pts, yT, 0.05, { c: [0, cy], r: R });
    if (bendA(s) < s.A) notes.push(`Pliage à ${bendA(s)}° plus fermé que la pointe (${s.A}°) : la pièce se referme sur le poinçon.`);
    return { ...pg, pts, area: polyArea(pts), notes, theta, toolA: s.A, It: [0, 0], ht, yT, yN: yN2, yN1, yN2, XL, T, b, tp, R, cy, f, sx, sw, yBlockTop, yBlockBot };
}

/**
 * Arrondit les angles d'une polyligne ouverte. radii[i] = rayon au sommet i (0 = vif).
 * Le rayon est réduit si l'arrondi ne tient pas dans les côtés adjacents.
 */
export function fillet(poly, radii) {
    const out = [poly[0]];
    for (let i = 1; i < poly.length - 1; i++) {
        const p = poly[i], a = poly[i - 1], c = poly[i + 1];
        const l1 = Math.hypot(p[0] - a[0], p[1] - a[1]), l2 = Math.hypot(c[0] - p[0], c[1] - p[1]);
        const d1 = [(p[0] - a[0]) / l1, (p[1] - a[1]) / l1], d2 = [(c[0] - p[0]) / l2, (c[1] - p[1]) / l2];
        const cross = d1[0] * d2[1] - d1[1] * d2[0];
        const phi = Math.acos(Math.max(-1, Math.min(1, d1[0] * d2[0] + d1[1] * d2[1])));   // angle de déviation
        let r = radii[i] || 0;
        if (r <= 0 || phi < 1e-4 || !l1 || !l2) { out.push(p); continue; }
        let tl = r * Math.tan(phi / 2);
        const tmax = 0.5 * Math.min(l1, l2);
        if (tl > tmax) { tl = tmax; r = tl / Math.tan(phi / 2); }
        const nIn = cross > 0 ? [-d1[1], d1[0]] : [d1[1], -d1[0]];                          // vers l'intérieur du pli
        const t1 = [p[0] - d1[0] * tl, p[1] - d1[1] * tl];
        const ctr = [t1[0] + nIn[0] * r, t1[1] + nIn[1] * r];
        const a0 = Math.atan2(t1[1] - ctr[1], t1[0] - ctr[0]), sw = Math.sign(cross) * phi;
        const m = Math.max(3, Math.ceil(phi / (Math.PI / 36)));
        for (let j = 0; j <= m; j++) { const an = a0 + sw * j / m; out.push([ctr[0] + r * Math.cos(an), ctr[1] + r * Math.sin(an)]); }
    }
    out.push(poly[poly.length - 1]);
    return out;
}

/** Polyligne V décalée de d le long des normales rn (angles vifs : intersection des côtés décalés). */
export function offsetLine(V, dir, rn, d) {
    const n = dir.length, out = [];
    for (let i = 0; i <= n; i++) {
        if (i === 0) out.push([V[0][0] + rn[0][0] * d, V[0][1] + rn[0][1] * d]);
        else if (i === n) out.push([V[n][0] + rn[n - 1][0] * d, V[n][1] + rn[n - 1][1] * d]);
        else {
            const p1 = [V[i][0] + rn[i - 1][0] * d, V[i][1] + rn[i - 1][1] * d], d1 = dir[i - 1];
            const p2 = [V[i][0] + rn[i][0] * d, V[i][1] + rn[i][1] * d], d2 = dir[i];
            const den = d1[0] * d2[1] - d1[1] * d2[0];
            if (Math.abs(den) < 1e-6) out.push(p2);
            else { const a = ((p2[0] - p1[0]) * d2[1] - (p2[1] - p1[1]) * d2[0]) / den; out.push([p1[0] + d1[0] * a, p1[1] + d1[1] * a]); }
        }
    }
    return out;
}

export function partGeom(s, pts, yExclude, tol, nose) {
    // pièce pliée : polyligne intérieure (angles vifs virtuels), épaisseur t vers l'extérieur.
    // Au pli actif, la tôle épouse le rayon de pointe puis s'ouvre à l'angle de pliage Ap,
    // indépendant de l'angle de l'outil : l'angle vif virtuel de la pièce est à R / sin(Ap/2)
    // sous le centre du rayon.
    const theta = bendA(s) / 2 * Math.PI / 180;
    const Rn = Math.max(0, nose.r || 0);
    const I = [nose.c[0], nose.c[1] - Rn / Math.sin(theta)];
    const uL = [-Math.sin(theta), Math.cos(theta)];
    const t = s.t, pp = s.part, n = pp.legs.length, k = Math.min(pp.k, n - 2);
    const ang = new Array(n);
    ang[k] = Math.atan2(-uL[1], -uL[0]);                         // côté k arrive à l'apex
    // le poinçon attaque toujours par l'intérieur du pli : si le pli actif est « inverse »,
    // la tôle est retournée dans la presse, donc tous les sens s'inversent
    const flip = pp.bends[k].s;
    const bs = pp.bends.map((bd, i) => (i === k ? 1 : bd.s * flip));
    // plis pas encore faits à cette étape de la séquence : à plat (180°)
    const seq = seqOf(pp), step = seq.indexOf(k);
    const flat = pp.bends.map((_, i) => seq.indexOf(i) > step);
    const bendAt = (i) => ({ a: flat[i] ? 180 : pp.bends[i].a, s: bs[i] });
    for (let i = k; i < n - 1; i++) { const bd = bendAt(i); ang[i + 1] = ang[i] + bd.s * (Math.PI - bd.a * Math.PI / 180); }
    for (let i = k; i > 0; i--) { const bd = bendAt(i - 1); ang[i - 1] = ang[i] - bd.s * (Math.PI - bd.a * Math.PI / 180); }
    const dir = ang.map((a) => [Math.cos(a), Math.sin(a)]);
    const V = new Array(n + 1); V[k + 1] = I;                   // V[i] = début du côté i, V[n] = fin
    for (let i = k + 1; i < n; i++) V[i + 1] = [V[i][0] + dir[i][0] * pp.legs[i], V[i][1] + dir[i][1] * pp.legs[i]];
    for (let i = k; i >= 0; i--) V[i] = [V[i + 1][0] - dir[i][0] * pp.legs[i], V[i + 1][1] - dir[i][1] * pp.legs[i]];
    const rn = dir.map((dd) => [dd[1], -dd[0]]);                 // normale droite = face extérieure
    const outer = offsetLine(V, dir, rn, t);
    // rayons : pli actif = rayon de pointe, autres plis = t (règle d'atelier) ; la face côté
    // extérieur du pli prend r + t, les deux arcs sont concentriques
    const rIn = [], rOut = [];
    for (let i = 1; i < n; i++) {
        const ri = i - 1 === k ? Rn : t;
        const cr = dir[i - 1][0] * dir[i][1] - dir[i - 1][1] * dir[i][0];
        rIn[i] = cr > 0 ? ri : ri + t;
        rOut[i] = cr > 0 ? ri + t : ri;
    }
    const Vf = fillet(V, rIn), outerF = fillet(outer, rOut);
    const part = [...Vf, ...outerF.slice().reverse()];
    // collision si un point du poinçon est dans la tôle (pointe comprise : Ap < A referme la pièce
    // sur l'outil) ; jeu = distance à la face intérieure, mesuré hors V de pointe
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
    samp.forEach((p) => {
        let dmin = Infinity;
        for (let i = 0; i < Vf.length - 1; i++) dmin = Math.min(dmin, segD(p, Vf[i], Vf[i + 1]));
        if (dmin < tol) return;
        if (inPoly(p, part)) collide = true;
        else if (p[1] > yExclude - 0.01) clear = Math.min(clear, dmin);
    });
    // Une aile peut traverser le poinçon entre deux points échantillonnés (tôle de 1 mm, arêtes de
    // 100 mm). On suit donc la fibre à mi-épaisseur, échantillonnée tous les 0,5 mm : elle reste à
    // t/2 du poinçon en appui sur le nez ou un flanc, et y entre dès qu'une aile le traverse.
    // Test intérieur/extérieur plutôt que croisement d'arêtes : des profils de bibliothèque
    // portent des gravures (lettrage) dans leur contour, qui fausseraient un croisement.
    if (!collide) {
        const half = t / 2, mid = fillet(offsetLine(V, dir, rn, half), rIn.map((r, i) => (r == null ? r : r - Math.sign(rIn[i] - rOut[i]) * half)));
        const xs = pts.map((p) => p[0]), ys = pts.map((p) => p[1]);
        const bx = [Math.min(...xs), Math.max(...xs), Math.min(...ys), Math.max(...ys)];
        for (let i = 0; i < mid.length - 1 && !collide; i++) {
            const a = mid[i], c = mid[i + 1], m = Math.max(1, Math.ceil(Math.hypot(c[0] - a[0], c[1] - a[1]) / 0.5));
            for (let j = 0; j <= m; j++) {
                const p = [a[0] + (c[0] - a[0]) * j / m, a[1] + (c[1] - a[1]) * j / m];
                if (p[0] < bx[0] || p[0] > bx[1] || p[1] < bx[2] || p[1] > bx[3]) continue;
                if (inPoly(p, pts)) { collide = true; break; }
            }
        }
    }
    if (clear === Infinity) clear = 0;
    return { part, clear, collide, V, dir, rn, n, k, bs, flat, seq, step, Ip: I, thetaP: theta, C: nose.c, Rn, outerEnd: outer[n] };
}

export function buildLib(s, lt) {
    const notes = [];
    let pts = lt.poly.map((p) => [s.mirror ? -p[0] : p[0], p[1]]);
    if (s.mirror) pts = pts.slice().reverse();
    // angle de pointe = celui de l'outil ; absent ou nul pour les poinçons à rayon (flancs parallèles)
    const toolA = lt.angle > 0 ? lt.angle : null;
    const theta = toolA ? toolA / 2 * Math.PI / 180 : null;
    const xs = pts.map((p) => p[0]), ys = pts.map((p) => p[1]);
    // l'origine des profils de bibliothèque est au point bas du rayon de pointe, mais pas toujours
    // au milieu : sur certains profils le bas du rayon est un palier (ex. de x = 0 à x = 0,99), et
    // son centre est au milieu de ce palier. Centre du rayon à (cx, R).
    const low = pts.filter((p) => p[1] < 1e-3).map((p) => p[0]);
    const cx = low.length ? (Math.min(...low) + Math.max(...low)) / 2 : 0;
    // Rayon absent de la bibliothèque (une grande partie du catalogue UKB) : mesuré sur le profil.
    // Cercle de centre (cx, r) passant par le point bas et le premier point de chaque côté :
    // r = (dx² + y²) / 2y. Sans ça la tôle prend un angle vif qui entre dans le vrai rayon du nez.
    let R = lt.radius > 0 ? lt.radius : 0, Rest = false;
    if (!R) {
        const side = (sg) => pts.filter((p) => sg * (p[0] - cx) > 1e-3 && p[1] > 0.01 && p[1] < 1).sort((a, b) => a[1] - b[1])[0];
        const est = [side(-1), side(1)].filter(Boolean).map((p) => ((p[0] - cx) ** 2 + p[1] ** 2) / (2 * p[1]));
        if (est.length) { R = +(est.reduce((a, b) => a + b, 0) / est.length).toFixed(2); Rest = true; }
    }
    const pg = partGeom(s, pts, 1.5, 0.35, { c: [cx, R], r: R });
    if (toolA && bendA(s) < toolA) notes.push(`Pliage à ${bendA(s)}° plus fermé que la pointe (${toolA}°) : la pièce se referme sur le poinçon.`);
    const It = theta ? [cx, R - R / Math.sin(theta)] : null;    // intersection des flancs de l'outil
    return { ...pg, pts, area: polyArea(pts), notes, theta, toolA, It, lib: lt, R: Rest ? '≈' + R : lt.radius, ht: 0, yT: 0, XL: Math.min(...xs), T: Math.max(...xs) - Math.min(...xs), Hb: Math.max(...ys), cy: R };
}

// ─── Données de bibliothèque ────────────────────────────────────────────────
/**
 * Poinçons simulables. Les références sans profil portent un gabarit commun (le texte
 * « Missing Profile » dessiné en contour), partagé à l'identique : un même contour sur 10
 * références ou plus n'est pas un vrai profil, il n'y a rien à simuler.
 */
export function usablePunches(data) {
    const seen = new Map();
    data.forEach((t) => { const k = JSON.stringify(t.poly); seen.set(k, (seen.get(k) || 0) + 1); });
    return data.filter((t) => seen.get(JSON.stringify(t.poly)) < 10);
}

/** Matrices en V du catalogue d'outillage dont l'ouverture et l'angle sont renseignés. */
export function dieCatalog(data) {
    return data.filter((t) => t.type === 'die' && t.v > 0 && t.angle > 0).map((t) => ({
        id: t.id, brand: t.brand, name: t.name, v: t.v, a: t.angle, r: t.radius > 0 ? t.radius : 1,
        h: t.height > 0 ? t.height : 50, subtype: t.subtype || '', clamp: t.clamp || '',
    }));
}
