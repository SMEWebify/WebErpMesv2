/**
 * Presse plieuse : dessin coté du poinçon, de la tôle et de la matrice, en SVG sous forme de
 * chaîne (affiché tel quel et rastérisé pour le PDF).
 */
import { bendA } from './geometry';

// ─── Dessin (SVG en chaîne : réutilisé tel quel pour le PDF) ────────────────
export const INK = '#1A2029', DIM = '#1F5C99', FILL = '#1F5C99', PAPER = '#F4F6F8', PART = '#C8541C';
export const SVG_W = 640, SVG_H = 560;

export const fmt = (n, d = 1) => Number(n).toLocaleString('fr-FR', { maximumFractionDigits: d });
export const esc = (x) => String(x ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

export function arrow(x, y, d) {
    const p = {
        0: `${x},${y} ${x - 3},${y + 8} ${x + 3},${y + 8}`,
        1: `${x},${y} ${x - 3},${y - 8} ${x + 3},${y - 8}`,
        2: `${x},${y} ${x + 8},${y - 3} ${x + 8},${y + 3}`,
        3: `${x},${y} ${x - 8},${y - 3} ${x - 8},${y + 3}`,
    }[d];
    return `<polygon points="${p}" fill="${DIM}"/>`;
}
export function dimV(x, y1, y2, label, font, xa, xb) {
    return `<line x1="${xa}" y1="${y1}" x2="${x + 5}" y2="${y1}" stroke="${DIM}" stroke-width=".6"/><line x1="${xb}" y1="${y2}" x2="${x + 5}" y2="${y2}" stroke="${DIM}" stroke-width=".6"/><line x1="${x}" y1="${y1}" x2="${x}" y2="${y2}" stroke="${DIM}" stroke-width=".9"/>`
        + arrow(x, y1, 0) + arrow(x, y2, 1) + `<text x="${x + 7}" y="${(y1 + y2) / 2 + 4}" fill="${DIM}" ${font}>${label}</text>`;
}
export function dimH(y, x1, x2, label, font, above) {
    return `<line x1="${x1}" y1="${y}" x2="${x2}" y2="${y}" stroke="${DIM}" stroke-width=".9"/>`
        + arrow(x1, y, 2) + arrow(x2, y, 3) + `<text x="${(x1 + x2) / 2}" y="${above ? y - 4 : y + 14}" text-anchor="middle" fill="${DIM}" ${font}>${label}</text>`;
}

export function partDims(s, g, X, Y, font) {
    let o = '';
    // cotes de la pièce : longueur de chaque côté le long de la face extérieure, angle des plis non actifs
    for (let i = 0; i < g.n; i++) {
        const off = s.t + 9, a = g.V[i], c = g.V[i + 1], nn = g.rn[i], d = g.dir[i];
        const p0 = [a[0] + nn[0] * off, a[1] + nn[1] * off], p1 = [c[0] + nn[0] * off, c[1] + nn[1] * off];
        const mx = (X(p0[0]) + X(p1[0])) / 2, my = (Y(p0[1]) + Y(p1[1])) / 2;
        let an = -Math.atan2(d[1], d[0]) * 180 / Math.PI;
        if (an > 90 || an < -90) an += 180;
        o += `<line x1="${X(p0[0])}" y1="${Y(p0[1])}" x2="${X(p1[0])}" y2="${Y(p1[1])}" stroke="${PART}" stroke-width=".9"/><text transform="translate(${mx} ${my}) rotate(${an})" dy="-4" text-anchor="middle" fill="${PART}" ${font}>${s.part.legs[i]}</text>`;
        if (i < g.n - 1 && i !== g.k && !g.flat[i]) {
            const bd = s.part.bends[i], sg = g.bs[i], v = g.V[i + 1];
            o += `<text x="${X(v[0]) + (sg > 0 ? -8 : 8)}" y="${Y(v[1]) + (sg > 0 ? -8 : 14)}" text-anchor="${sg > 0 ? 'end' : 'start'}" fill="${PART}" ${font}>${bd.a}°</text>`;
        }
    }
    o += `<text x="${X(g.outerEnd[0]) + 6}" y="${Y(g.outerEnd[1]) + 4}" fill="${PART}" ${font}>ép. ${s.t}</text>`;
    return o;
}

export function handle(key, cx, cy, cursor) {
    return `<circle class="pd-hd" data-k="${key}" cx="${cx}" cy="${cy}" r="6" fill="#fff" stroke="${DIM}" stroke-width="1.6" style="cursor:${cursor}"/>`;
}

/** Renvoie { svg, tf } — tf = transformation écran ↔ mm utilisée par le glisser-déposer. */
export function drawing(s, g) {
    const all = [...g.pts, ...g.part, ...(g.die ? g.die.poly : [])];
    const minx = Math.min(...all.map((p) => p[0])) - 22, maxx = Math.max(...all.map((p) => p[0])) + 40;
    const miny = Math.min(...all.map((p) => p[1])) - (g.die ? 34 : 16), maxy = Math.max(...all.map((p) => p[1])) + 22;
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
    if (g.die) {
        const dd = g.die, yTop = Y(dd.dy), yBot = Y(dd.dy - dd.h);
        o += `<path d="${path(dd.poly)}" fill="url(#pd-h)" stroke="${g.dieCollide ? '#C0392B' : INK}" stroke-width="1.6" stroke-linejoin="round"/>`;
        // V coté entre les arêtes théoriques des épaules, lignes d'attache sous la matrice
        o += `<line x1="${X(-dd.hv)}" y1="${yBot + 2}" x2="${X(-dd.hv)}" y2="${yBot + 14}" stroke="${DIM}" stroke-width=".6"/>`
            + `<line x1="${X(dd.hv)}" y1="${yBot + 2}" x2="${X(dd.hv)}" y2="${yBot + 14}" stroke="${DIM}" stroke-width=".6"/>`
            + dimH(yBot + 10, X(-dd.hv), X(dd.hv), 'V' + fmt(dd.v), font)
            + `<text x="${X(0)}" y="${yBot + 40}" text-anchor="middle" fill="${INK}" ${font}>${esc(dd.label)} · ${fmt(dd.a)}° · R${fmt(dd.r)} · H${fmt(dd.h)}</text>`;
    }
    o += `<line x1="${X(0)}" y1="${Y(-8)}" x2="${X(0)}" y2="${Y(H + 6)}" stroke="${INK}" stroke-width=".6" stroke-dasharray="10 3 2 3"/>`;

    const xr = X(g.XL + g.T) + 34;
    // Deux angles cotés chacun à son intersection de flancs (angle vif virtuel), pas au point bas
    // du rayon : celui de l'outil (bleu) et celui de la pièce (orange), indépendants.
    const vertex = (I, th, r, col, label, dash) => {
        const vx = X(I[0]), vy = Y(I[1]), b1 = -Math.PI / 2 - th, b2 = -Math.PI / 2 + th;
        let out = `<path d="M ${vx + r * Math.cos(b1)} ${vy + r * Math.sin(b1)} A ${r} ${r} 0 0 1 ${vx + r * Math.cos(b2)} ${vy + r * Math.sin(b2)}" fill="none" stroke="${col}"/>`
            + `<text x="${vx}" y="${vy - r - 5}" text-anchor="middle" fill="${col}" ${font}>${label}</text>`;
        if (g.Rn > 0) { // flancs prolongés jusqu'à l'intersection, au-delà du point de tangence avec le rayon
            const tl = g.Rn / Math.tan(th) + g.Rn * 0.8;
            out += [-1, 1].map((sg) => `<line x1="${vx}" y1="${vy}" x2="${X(I[0] + sg * Math.sin(th) * tl)}" y2="${Y(I[1] + Math.cos(th) * tl)}" stroke="${col}" stroke-width=".6" stroke-dasharray="${dash}"/>`).join('')
                + `<circle cx="${vx}" cy="${vy}" r="2" fill="${col}"/>`;
        }
        return { svg: out, hx: vx + r * Math.cos(b1), hy: vy + r * Math.sin(b1), hx2: vx + r * Math.cos(b2), hy2: vy + r * Math.sin(b2) };
    };
    const toolV = g.It ? vertex(g.It, g.theta, 36, DIM, `${g.toolA}°`, '3 2') : null;
    const partV = vertex(g.Ip, g.thetaP, 58, PART, `${bendA(s)}°`, '1 2');
    let radiusTag = '';
    if (g.Rn > 0) {
        // centre du rayon (croix) + rayon coté depuis le centre
        const cx = X(g.C[0]), cy = Y(g.C[1]);
        const cs = Math.max(4, Math.min(8, g.Rn * sc * 0.4));
        const rx = cx + g.Rn * sc * Math.cos(Math.PI / 4), ry = cy + g.Rn * sc * Math.sin(Math.PI / 4);
        radiusTag = `<line x1="${cx - cs}" y1="${cy}" x2="${cx + cs}" y2="${cy}" stroke="${DIM}" stroke-width=".8"/><line x1="${cx}" y1="${cy - cs}" x2="${cx}" y2="${cy + cs}" stroke="${DIM}" stroke-width=".8"/>`
            + `<line x1="${cx}" y1="${cy}" x2="${rx + 30}" y2="${ry + 26}" stroke="${DIM}"/><circle cx="${rx}" cy="${ry}" r="1.6" fill="${DIM}"/>`
            + `<text x="${rx + 33}" y="${ry + 30}" fill="${DIM}" ${font}>R${g.R}</text>`;
    }
    const angleArc = (toolV ? toolV.svg : '') + partV.svg;
    const tHandle = ['t', (g.V[g.k][0] + g.V[g.k + 1][0]) / 2 + g.rn[g.k][0] * s.t, (g.V[g.k][1] + g.V[g.k + 1][1]) / 2 + g.rn[g.k][1] * s.t, 'move'];
    const legHandles = [];
    for (let i = 0; i < g.n; i++) { const v = i <= g.k ? g.V[i] : g.V[i + 1]; legHandles.push(['leg' + i, v[0], v[1], 'move']); }
    // angle de l'outil : réglable en paramétrique seulement (fixé par la référence en bibliothèque)
    const angleHandle = (toolV && !g.lib ? handle('A', toolV.hx, toolV.hy, 'ew-resize') : '')
        + handle('Ap', partV.hx2, partV.hy2, 'ew-resize');

    let hs, caption;
    if (g.lib) {
        o += dimV(xr, Y(g.Hb), Y(0), 'H ' + fmt(g.Hb), font, X(g.XL + g.T) + 4, X(0) + 4);
        o += dimH(Y(g.Hb) - 14, X(g.XL), X(g.XL + g.T), fmt(g.T), font, true);
        hs = [tHandle, ...legHandles];
        caption = `${esc(g.lib.brand)} ${esc(g.lib.name)}${g.toolA ? ' · ' + g.toolA + '°' : ''}${g.R != null ? ' · R' + g.R : ''} · pli ${g.k + 1} à ${bendA(s)}°${g.seq.length > 1 ? ` · étape ${g.step + 1}/${g.seq.length}` : ""} · L ${s.L} mm`;
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
        caption = `${g.f.label} · H${s.H} · ${s.A}° · R${g.R} · pli ${g.k + 1} à ${bendA(s)}°${g.seq.length > 1 ? ` · étape ${g.step + 1}/${g.seq.length}` : ""} · L ${s.L} mm`;
    }
    o += angleArc + radiusTag + partDims(s, g, X, Y, font);
    o += hs.map((h) => handle(h[0], X(h[1]), Y(h[2]), h[3])).join('') + angleHandle;
    o += `<text x="${m}" y="${SVG_H - 12}" fill="${INK}" ${font}>${caption}</text>`;
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${SVG_W}" height="${SVG_H}" viewBox="0 0 ${SVG_W} ${SVG_H}"><rect width="100%" height="100%" fill="${PAPER}"/>${o}</svg>`;
    return { svg, tf: { ox, oy, sc } };
}
