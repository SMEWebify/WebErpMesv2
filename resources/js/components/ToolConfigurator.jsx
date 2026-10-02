import React, { useMemo, useRef, useState } from 'react';

// Configurateur d'outil de tournage : assemble le code normalisé en cliquant sur les planches,
// puis crée l'outil dans methods_tools (code = désignation ISO, commentaire = décodage complet).
//   - Porte-outil extérieur  ISO 5608 : [fixation][forme][attaque][dépouille][sens][h][b][longueur][arête]
//   - Barre d'alésage        ISO 6261 : [type][Ø][longueur]-[fixation][forme][attaque][dépouille][sens][arête]
//   - Plaquette              ISO 1832 : [forme][dépouille][tolérance][type] [taille] [épaisseur] [rayon]-[brise-copeaux]
// Porté depuis UsiQuoteIA (IsoToolBuilder / IsoInsertBuilder / isoCheck).

// ─── Couleurs des planches ──────────────────────────────────────────────────
const INS = '#fbbf24', INS_ED = '#b45309';   // plaquette
const BODY = '#9ca3af', BODY_ED = '#4b5563'; // corps
const EDGE = '#ef4444';                       // arête / angle
const DIM = '#64748b';                        // cotes
const HW = '#1f2937', HOLE = '#6b7280';       // visserie, trou

const rad = (d) => d * Math.PI / 180;
const pad2 = (n) => (n === '' || n == null ? '' : String(n).padStart(2, '0'));

// ─── Géométrie des pictogrammes ─────────────────────────────────────────────
function regPoly(cx, cy, r, n, rotDeg = 0) {
    const out = [];
    for (let i = 0; i < n; i++) {
        const a = rad(rotDeg + i * 360 / n - 90);
        out.push(`${(cx + r * Math.cos(a)).toFixed(1)},${(cy + r * Math.sin(a)).toFixed(1)}`);
    }
    return out.join(' ');
}
function rhombus(cx, cy, a, ang) { // rhombe d'angle aigu `ang`
    const b = a * Math.tan(rad(ang / 2));
    return `${cx - a},${cy} ${cx},${cy - b} ${cx + a},${cy} ${cx},${cy + b}`;
}
function para(cx, cy, w, h, ang) { // parallélogramme d'angle aigu `ang`
    const slant = h / Math.tan(rad(ang)), hw = w / 2, hh = h / 2, sx = slant / 2;
    return [`${cx - hw + sx},${cy + hh}`, `${cx + hw + sx},${cy + hh}`, `${cx + hw - sx},${cy - hh}`, `${cx - hw - sx},${cy - hh}`].join(' ');
}
function trigon(cx, cy, r) { // triangle aux côtés bombés
    const p = [];
    for (let i = 0; i < 3; i++) p.push([cx + r * Math.cos(rad(i * 120 - 90)), cy + r * Math.sin(rad(i * 120 - 90))]);
    let d = `M${p[0][0].toFixed(1)},${p[0][1].toFixed(1)}`;
    for (let i = 0; i < 3; i++) {
        const a = p[i], b = p[(i + 1) % 3], mx = (a[0] + b[0]) / 2, my = (a[1] + b[1]) / 2;
        const nx = mx - cx, ny = my - cy, nl = Math.hypot(nx, ny) || 1;
        d += ` Q${(mx + 5 * nx / nl).toFixed(1)},${(my + 5 * ny / nl).toFixed(1)} ${b[0].toFixed(1)},${b[1].toFixed(1)}`;
    }
    return d + 'Z';
}

// ─── Référentiels ───────────────────────────────────────────────────────────
const CLAMPS = [
    { code: 'C', label: 'Bride',               desc: 'Bride par le haut (plaquette sans trou)' },
    { code: 'S', label: 'Vis',                 desc: 'Vis à tête noyée dans la plaquette' },
    { code: 'P', label: 'Levier',              desc: 'Levier engagé dans le trou de la plaquette' },
    { code: 'M', label: 'Bride et levier',     desc: 'Bride haute + levier dans le trou' },
    { code: 'D', label: 'Bride trou central',  desc: 'Bride traversant le trou central' },
];
const SHAPES = [
    { code: 'H', label: 'Hexagonale 120°',  svg: (c) => <polygon points={regPoly(c, c, 19, 6, 30)} /> },
    { code: 'O', label: 'Octogonale 135°',  svg: (c) => <polygon points={regPoly(c, c, 19, 8, 22.5)} /> },
    { code: 'P', label: 'Pentagonale 108°', svg: (c) => <polygon points={regPoly(c, c, 19, 5, 0)} /> },
    { code: 'S', label: 'Carrée 90°',       svg: (c) => <polygon points={regPoly(c, c, 18, 4, 45)} /> },
    { code: 'T', label: 'Triangulaire 60°', svg: (c) => <polygon points={regPoly(c, c, 20, 3, 0)} /> },
    { code: 'C', label: 'Rhombique 80°',    svg: (c) => <polygon points={rhombus(c, c, 21, 80)} /> },
    { code: 'D', label: 'Rhombique 55°',    svg: (c) => <polygon points={rhombus(c, c, 22, 55)} /> },
    { code: 'E', label: 'Rhombique 75°',    svg: (c) => <polygon points={rhombus(c, c, 21, 75)} /> },
    { code: 'V', label: 'Rhombique 35°',    svg: (c) => <polygon points={rhombus(c, c, 23, 35)} /> },
    { code: 'W', label: 'Trigone 80°',      svg: (c) => <path d={trigon(c, c, 20)} /> },
    { code: 'M', label: 'Parallélo. 86°',   svg: (c) => <polygon points={para(c, c, 30, 22, 86)} /> },
    { code: 'L', label: 'Rectangulaire 90°', svg: (c) => <rect x={c - 19} y={c - 11} width="38" height="22" /> },
    { code: 'A', label: 'Parallélo. 85°',   svg: (c) => <polygon points={para(c, c, 30, 22, 85)} /> },
    { code: 'B', label: 'Parallélo. 82°',   svg: (c) => <polygon points={para(c, c, 30, 22, 82)} /> },
    { code: 'K', label: 'Parallélo. 55°',   svg: (c) => <polygon points={para(c, c, 28, 22, 55)} /> },
    { code: 'R', label: 'Ronde',            svg: (c) => <circle cx={c} cy={c} r="19" /> },
];
const STYLES = [
    { code: 'A', ang: 90 }, { code: 'B', ang: 75 }, { code: 'C', ang: 90 }, { code: 'D', ang: 45 },
    { code: 'E', ang: 60 }, { code: 'F', ang: 90 }, { code: 'G', ang: 90 }, { code: 'J', ang: 93 },
    { code: 'K', ang: 75 }, { code: 'L', ang: 95 }, { code: 'M', ang: 50 }, { code: 'N', ang: 63 },
    { code: 'R', ang: 75 }, { code: 'S', ang: 45 }, { code: 'T', ang: 60 }, { code: 'U', ang: 93 },
    { code: 'V', ang: 72.5 }, { code: 'W', ang: 60 }, { code: 'Y', ang: 85 },
];
const CLEAR = [
    { code: 'A', ang: 3 }, { code: 'B', ang: 5 }, { code: 'C', ang: 7 }, { code: 'D', ang: 15 },
    { code: 'E', ang: 20 }, { code: 'F', ang: 25 }, { code: 'G', ang: 30 }, { code: 'N', ang: 0 }, { code: 'P', ang: 11 },
];
const HANDS = [{ code: 'R', label: 'Droite' }, { code: 'L', label: 'Gauche' }, { code: 'N', label: 'Neutre' }];
const LENGTHS = [
    ['A', 32], ['B', 40], ['C', 50], ['D', 60], ['E', 70], ['G', 80], ['H', 100], ['J', 110],
    ['K', 125], ['L', 140], ['M', 150], ['N', 150], ['P', 170], ['Q', 180], ['R', 200],
    ['S', 250], ['T', 300], ['U', 350], ['V', 400], ['W', 400], ['X', 'Spécial'],
];
const BAR_TYPES = [
    ['S', 'Monobloc acier'], ['A', 'Acier + lubrification'], ['B', 'Acier anti-vibrations'], ['D', 'Acier anti-vib. + lub.'],
    ['C', 'Carbure, tête fixe acier'], ['E', 'Carbure, tête acier + lub.'], ['F', 'Carbure, tête + anti-vib.'],
    ['G', 'Carbure, anti-vib. + lub.'], ['H', 'Métal lourd'], ['J', 'Métal lourd + lub.'],
];
const BAR_LENGTHS = [
    ['F', 80], ['H', 100], ['K', 125], ['M', 150], ['P', 170], ['Q', 180], ['R', 200],
    ['S', 250], ['T', 300], ['U', 350], ['V', 400], ['W', 450], ['Y', 500], ['X', 'Spécial'],
];
const TOLERANCES = ['A', 'C', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'U'];
const INSERT_TYPES = [
    { code: 'N', label: 'Sans trou, sans brise-cop.', hole: 'none', chip: 0 },
    { code: 'A', label: 'Trou cylindrique',           hole: 'cyl',  chip: 0 },
    { code: 'R', label: 'Sans trou + brise-cop.',     hole: 'none', chip: 1 },
    { code: 'M', label: 'Trou + brise-cop. 1 face',   hole: 'cyl',  chip: 1 },
    { code: 'G', label: 'Trou + brise-cop. 2 faces',  hole: 'cyl',  chip: 2 },
    { code: 'P', label: 'Trou + brise-cop. partiel',  hole: 'cyl',  chip: 2 },
    { code: 'Q', label: 'Trou fraisé 1 face',         hole: 'csk1', chip: 0 },
    { code: 'T', label: 'Trou fraisé + brise-cop.',   hole: 'csk1', chip: 1 },
    { code: 'W', label: 'Trou 2× fraisé + brise-cop.', hole: 'csk2', chip: 1 },
    { code: 'X', label: 'Modèle spécial',             special: true },
];
const byCode = (list, code) => list.find((x) => x.code === code);
const mmOf = (list, code) => { const e = list.find(([l]) => l === code); return e ? e[1] : null; };

// ─── Pictogrammes ───────────────────────────────────────────────────────────
function ShapeSvg({ shape }) {
    return (
        <svg viewBox="0 0 48 48" width="100%" height="100%">
            <g fill={INS} stroke={INS_ED} strokeWidth="1.4" strokeLinejoin="round">{shape.svg(24)}</g>
        </svg>
    );
}
function StyleSvg({ ang }) { // vue de dessus : arête de coupe à l'angle d'attaque
    const P = [16, 36], L = 26, ex = P[0] + L * Math.sin(rad(ang)), ey = P[1] - L * Math.cos(rad(ang));
    return (
        <svg viewBox="0 0 64 50" width="100%" height="100%">
            <polygon points="16,38 60,38 60,12 30,12 16,26" fill={BODY} stroke={BODY_ED} strokeWidth="1.2" strokeLinejoin="round" />
            <line x1={P[0]} y1={P[1]} x2={P[0] + 30} y2={P[1]} stroke={DIM} strokeWidth="1" strokeDasharray="2 2" />
            <line x1={P[0]} y1={P[1]} x2={ex.toFixed(1)} y2={ey.toFixed(1)} stroke={EDGE} strokeWidth="2.6" strokeLinecap="round" />
            <circle cx={P[0]} cy={P[1]} r="2.2" fill={EDGE} />
        </svg>
    );
}
function ClearSvg({ ang }) { // vue de profil : flanc de dépouille
    const topY = 12, botY = 40, blx = 18 + (botY - topY) * Math.tan(rad(ang));
    return (
        <svg viewBox="0 0 56 50" width="100%" height="100%">
            <rect x="2" y={topY - 2} width="6" height={botY - topY + 6} fill={BODY} stroke={BODY_ED} strokeWidth="0.8" />
            <polygon points={`18,${topY} 42,${topY} 42,${botY} ${blx.toFixed(1)},${botY}`} fill={INS} stroke={INS_ED} strokeWidth="1.2" strokeLinejoin="round" />
            <line x1="18" y1={topY} x2="18" y2={botY + 3} stroke={DIM} strokeWidth="1" strokeDasharray="2 2" />
            <line x1="18" y1={topY} x2={blx.toFixed(1)} y2={botY} stroke={EDGE} strokeWidth="2.4" strokeLinecap="round" />
        </svg>
    );
}
function ToolHeadR() {
    return (
        <g>
            <polygon points="22,12 41,16 37,29 18,27" fill={INS} stroke={INS_ED} strokeWidth="1.2" strokeLinejoin="round" />
            <circle cx="18" cy="27" r="2.2" fill={EDGE} />
            <line x1="48" y1="8" x2="32" y2="8" stroke={DIM} strokeWidth="1.3" />
            <polygon points="32,8 36,6 36,10" fill={DIM} />
        </g>
    );
}
function HandSvg({ code }) { // pièce hachurée + sens d'avance
    const hid = `tc-hatch-${code}`;
    return (
        <svg viewBox="0 0 62 46" width="100%" height="100%">
            <defs>
                <pattern id={hid} width="5" height="5" patternTransform="rotate(45)" patternUnits="userSpaceOnUse">
                    <line x1="0" y1="0" x2="0" y2="5" stroke={DIM} strokeWidth="0.9" />
                </pattern>
            </defs>
            <rect x="4" y="30" width="54" height="13" fill={`url(#${hid})`} stroke={BODY_ED} strokeWidth="0.8" />
            {code === 'R' && <ToolHeadR />}
            {code === 'L' && <g transform="translate(62,0) scale(-1,1)"><ToolHeadR /></g>}
            {code === 'N' && (
                <g>
                    <polygon points="22,12 40,12 31,29" fill={INS} stroke={INS_ED} strokeWidth="1.2" strokeLinejoin="round" />
                    <circle cx="31" cy="29" r="2.2" fill={EDGE} />
                    <line x1="31" y1="9" x2="31" y2="3" stroke={DIM} strokeWidth="1.3" />
                    <polygon points="31,3 29,7 33,7" fill={DIM} />
                </g>
            )}
        </svg>
    );
}
function ClampBase() {
    return (
        <>
            <polygon points="6,52 6,36 94,36 94,52" fill={BODY} stroke={BODY_ED} strokeWidth="1.2" />
            <polygon points="12,36 44,36 40,24 8,24" fill={INS} stroke={INS_ED} strokeWidth="1.2" strokeLinejoin="round" />
        </>
    );
}
function ClampSvg({ code }) { // coupe latérale : corps, plaquette, organe de bridage
    return (
        <svg viewBox="0 0 100 56" width="100%" height="100%">
            <ClampBase />
            {code === 'C' && (<>
                <rect x="54" y="16" width="11" height="20" rx="2" fill={HW} />
                <polygon points="60,12 67,13 67,21 24,28 15,28 15,21 56,15" fill={HW} stroke={HW} strokeLinejoin="round" />
                <circle cx="19" cy="24" r="2" fill={INS_ED} />
            </>)}
            {code === 'S' && (<>
                <rect x="21" y="22" width="6" height="22" rx="1" fill={HW} />
                <polygon points="15,20 33,20 28,27 20,27" fill={HW} stroke={HW} strokeLinejoin="round" />
                <line x1="18" y1="22.5" x2="30" y2="22.5" stroke={INS} strokeWidth="1.6" />
            </>)}
            {code === 'P' && (<>
                <ellipse cx="24" cy="30" rx="3.4" ry="4.6" fill={HOLE} stroke={INS_ED} strokeWidth="0.8" />
                <path d="M74,50 Q44,50 33,40 Q27,35 24,30" fill="none" stroke={HW} strokeWidth="4.5" strokeLinecap="round" />
                <circle cx="24" cy="30" r="3" fill={HW} />
            </>)}
            {code === 'M' && (<>
                <ellipse cx="24" cy="30" rx="3.2" ry="4.4" fill={HOLE} stroke={INS_ED} strokeWidth="0.8" />
                <path d="M76,50 Q48,50 34,40 Q28,35 24,30" fill="none" stroke={HW} strokeWidth="4" strokeLinecap="round" />
                <rect x="56" y="16" width="10" height="20" rx="2" fill={HW} />
                <polygon points="61,12 68,13 68,20 24,27 16,27 16,20 57,14" fill={HW} stroke={HW} strokeLinejoin="round" />
                <circle cx="20" cy="23" r="1.8" fill={INS_ED} />
            </>)}
            {code === 'D' && (<>
                <ellipse cx="24" cy="30" rx="3" ry="4" fill={HOLE} stroke={INS_ED} strokeWidth="0.8" />
                <rect x="21.5" y="10" width="5" height="34" rx="1" fill={HW} />
                <rect x="9" y="7" width="30" height="7" rx="2" fill={HW} />
            </>)}
        </svg>
    );
}
function InsertTypeSvg({ t }) { // section de plaquette : trou + brise-copeaux
    if (t.special) {
        return (
            <svg viewBox="0 0 60 34" width="100%" height="100%">
                <rect x="10" y="9" width="40" height="16" rx="2" fill="none" stroke={INS_ED} strokeWidth="1.4" strokeDasharray="3 2" />
                <text x="30" y="22" fill={INS_ED} fontSize="13" fontWeight="700" textAnchor="middle">?</text>
            </svg>
        );
    }
    const x0 = 9, x1 = 51, yT = 9, yB = 25, cx = 30, cy = (yT + yB) / 2, dip = 4.5;
    const topMid = t.chip >= 1 ? yT + dip : yT, botMid = t.chip >= 2 ? yB - dip : yB;
    return (
        <svg viewBox="0 0 60 34" width="100%" height="100%">
            <polygon points={`${x0},${yT} ${cx},${topMid} ${x1},${yT} ${x1},${yB} ${cx},${botMid} ${x0},${yB}`} fill={INS} stroke={INS_ED} strokeWidth="1.3" strokeLinejoin="round" />
            {t.hole !== 'none' && <ellipse cx={cx} cy={cy} rx="4" ry="6.5" fill={BODY} stroke={BODY_ED} strokeWidth="0.8" />}
            {(t.hole === 'csk1' || t.hole === 'csk2') && <polygon points={`${cx - 7},${yT + 1} ${cx + 7},${yT + 1} ${cx + 4},${yT + 6} ${cx - 4},${yT + 6}`} fill={BODY} stroke={BODY_ED} strokeWidth="0.6" />}
            {t.hole === 'csk2' && <polygon points={`${cx - 7},${yB - 1} ${cx + 7},${yB - 1} ${cx + 4},${yB - 6} ${cx - 4},${yB - 6}`} fill={BODY} stroke={BODY_ED} strokeWidth="0.6" />}
        </svg>
    );
}
const DimArrowH = ({ x1, x2, y, label, color = DIM }) => (
    <>
        <line x1={x1} y1={y} x2={x2} y2={y} stroke={color} strokeWidth="1" />
        <polygon points={`${x1},${y} ${x1 + 4},${y - 2} ${x1 + 4},${y + 2}`} fill={color} />
        <polygon points={`${x2},${y} ${x2 - 4},${y - 2} ${x2 - 4},${y + 2}`} fill={color} />
        {label}
    </>
);
const DIM_SVGS = {
    h: (
        <svg viewBox="0 0 72 46" width="100%" height="100%">
            <polygon points="10,36 60,36 60,16 10,16" fill={BODY} stroke={BODY_ED} strokeWidth="1" />
            <polygon points="11,16 28,16 25,9 8,9" fill={INS} stroke={INS_ED} strokeWidth="1" />
            <line x1="65" y1="16" x2="65" y2="36" stroke={DIM} strokeWidth="1" />
            <polygon points="65,16 63,20 67,20" fill={DIM} /><polygon points="65,36 63,32 67,32" fill={DIM} />
            <text x="69" y="29" fill={DIM} fontSize="10" fontStyle="italic">h</text>
        </svg>
    ),
    b: (
        <svg viewBox="0 0 64 46" width="100%" height="100%">
            <polygon points="12,12 52,12 52,32 12,32" fill={BODY} stroke={BODY_ED} strokeWidth="1" />
            <polygon points="13,12 29,12 27,20 11,20" fill={INS} stroke={INS_ED} strokeWidth="1" />
            <DimArrowH x1={12} x2={52} y={38} label={<text x="32" y="46" fill={DIM} fontSize="10" fontStyle="italic" textAnchor="middle">b</text>} />
        </svg>
    ),
    l: (
        <svg viewBox="0 0 80 46" width="100%" height="100%">
            <polygon points="8,22 70,22 70,34 8,34" fill={BODY} stroke={BODY_ED} strokeWidth="1" />
            <polygon points="8,22 24,22 22,15 6,15" fill={INS} stroke={INS_ED} strokeWidth="1" />
            <DimArrowH x1={8} x2={70} y={40} label={<text x="39" y="11" fill={DIM} fontSize="10" fontStyle="italic" textAnchor="middle">l₁</text>} />
        </svg>
    ),
    edge: (
        <svg viewBox="0 0 56 46" width="100%" height="100%">
            <polygon points="14,12 42,12 42,34 14,34" fill={INS} stroke={INS_ED} strokeWidth="1.3" />
            <DimArrowH x1={14} x2={42} y={39} color={EDGE} label={<text x="28" y="46" fill={EDGE} fontSize="10" fontStyle="italic" textAnchor="middle">l</text>} />
        </svg>
    ),
    barD: (
        <svg viewBox="0 0 56 44" width="100%" height="100%">
            <defs>
                <pattern id="tc-barhatch" width="4" height="4" patternTransform="rotate(45)" patternUnits="userSpaceOnUse">
                    <line x1="0" y1="0" x2="0" y2="4" stroke={DIM} strokeWidth="0.7" />
                </pattern>
            </defs>
            <circle cx="26" cy="22" r="15" fill="url(#tc-barhatch)" stroke={BODY_ED} strokeWidth="1.2" />
            <DimArrowH x1={11} x2={41} y={22} label={<text x="26" y="19" fill={DIM} fontSize="8" fontStyle="italic" textAnchor="middle">dₘ</text>} />
        </svg>
    ),
    barL: (
        <svg viewBox="0 0 80 44" width="100%" height="100%">
            <rect x="6" y="18" width="58" height="12" rx="2" fill={BODY} stroke={BODY_ED} strokeWidth="1" />
            <circle cx="64" cy="24" r="7" fill={BODY} stroke={BODY_ED} strokeWidth="1" />
            <polygon points="58,20 70,20 67,26 61,26" fill={INS} stroke={INS_ED} strokeWidth="0.8" />
            <DimArrowH x1={6} x2={70} y={36} label={<text x="38" y="13" fill={DIM} fontSize="10" fontStyle="italic" textAnchor="middle">l</text>} />
        </svg>
    ),
    iC: (
        <svg viewBox="0 0 60 44" width="100%" height="100%">
            <rect x="14" y="6" width="32" height="32" fill={INS} stroke={INS_ED} strokeWidth="1.3" />
            <circle cx="30" cy="22" r="16" fill="none" stroke={DIM} strokeWidth="1" strokeDasharray="3 2" />
            <DimArrowH x1={14} x2={46} y={22} color={EDGE} label={<text x="30" y="19" fill={EDGE} fontSize="9" fontStyle="italic" textAnchor="middle">iC</text>} />
        </svg>
    ),
    s: (
        <svg viewBox="0 0 64 44" width="100%" height="100%">
            <polygon points="6,34 24,34 15,12" fill={INS} stroke={INS_ED} strokeWidth="1.2" strokeLinejoin="round" />
            <rect x="34" y="14" width="12" height="20" fill={INS} stroke={INS_ED} strokeWidth="1.2" />
            <line x1="51" y1="14" x2="51" y2="34" stroke={DIM} strokeWidth="1" />
            <polygon points="51,14 49,18 53,18" fill={DIM} /><polygon points="51,34 49,30 53,30" fill={DIM} />
            <text x="55" y="27" fill={DIM} fontSize="10" fontStyle="italic">s</text>
        </svg>
    ),
    r: (
        <svg viewBox="0 0 56 44" width="100%" height="100%">
            <path d="M10,8 H34 Q44,8 44,18 V40 H10 Z" fill={INS} stroke={INS_ED} strokeWidth="1.3" strokeLinejoin="round" />
            <path d="M34,8 Q44,8 44,18" fill="none" stroke={EDGE} strokeWidth="2.4" />
            <text x="40" y="32" fill={EDGE} fontSize="10" fontStyle="italic" textAnchor="middle">rε</text>
        </svg>
    ),
};

// ─── Code : assemblage, décodage, cohérence ─────────────────────────────────
const KINDS = [
    ['ext', 'Porte-outil extérieur', 'ISO 5608'],
    ['int', "Barre d'alésage", 'ISO 6261'],
    ['insert', 'Plaquette', 'ISO 1832'],
];
const EMPTY = {
    barType: '', barDiam: '', barLen: '',
    p1: '', p2: '', p3: '', p4: '', p5: '', h: '', w: '', len: '', size: '',
    forme: '', dep: '', tol: '', type: '', iSize: '', thick: '', rad: '', grade: '',
};

/** Segments affichés dans le bandeau « code assemblé » ('-' = séparateur, ' ' = espace). */
function segments(kind, s) {
    if (kind === 'int') return [s.barType, pad2(s.barDiam), s.barLen, '-', s.p1, s.p2, s.p3, s.p4, s.p5, s.size];
    if (kind === 'insert') return [s.forme, s.dep, s.tol, s.type, ' ', pad2(s.iSize), ' ', pad2(s.thick), ' ', pad2(s.rad)];
    return [s.p1, s.p2, s.p3, s.p4, s.p5, pad2(s.h), pad2(s.w), s.len, s.size];
}
function buildCode(kind, s) {
    if (kind === 'int') return `${s.barType}${pad2(s.barDiam)}${s.barLen}-${s.p1}${s.p2}${s.p3}${s.p4}${s.p5}${s.size}`;
    if (kind === 'insert') return `${s.forme}${s.dep}${s.tol}${s.type} ${pad2(s.iSize)} ${pad2(s.thick)} ${pad2(s.rad)}`.trim() + (s.grade ? `-${s.grade}` : '');
    return `${s.p1}${s.p2}${s.p3}${s.p4}${s.p5}${pad2(s.h)}${pad2(s.w)}${s.len}${s.size}`;
}
function isComplete(kind, s) {
    const holder = s.p1 && s.p2 && s.p3 && s.p4 && s.p5 && s.size;
    if (kind === 'int') return !!(s.barType && s.barDiam && s.barLen && holder);
    if (kind === 'insert') return !!(s.forme && s.dep && s.tol && s.type && s.iSize && s.thick && s.rad);
    return !!(holder && s.h && s.w && s.len);
}
const lenLabel = (mm) => (typeof mm === 'number' ? `${mm} mm` : mm);

/** Désignation courte (colonne label) et décodage complet (colonne comment). */
function describe(kind, s) {
    const shape = byCode(SHAPES, kind === 'insert' ? s.forme : s.p2)?.label;
    const clear = byCode(CLEAR, kind === 'insert' ? s.dep : s.p4)?.ang;
    if (kind === 'insert') {
        const t = byCode(INSERT_TYPES, s.type);
        return {
            label: `Plaquette ${shape ?? ''} ${pad2(s.iSize)}·${pad2(s.thick)}·${pad2(s.rad)}${s.grade ? ' ' + s.grade : ''}`.replace(/\s+/g, ' ').trim(),
            lines: [
                `Norme : ISO 1832`,
                `1 Forme : ${s.forme} - ${shape}`,
                `2 Dépouille : ${s.dep} - ${clear}°`,
                `3 Tolérance : ${s.tol}`,
                `4 Type : ${s.type} - ${t?.label}`,
                `5-6 Taille (arête) : ${pad2(s.iSize)}`,
                `7-8 Épaisseur : ${pad2(s.thick)}`,
                `9-10 Rayon de bec : ${pad2(s.rad)} (${(+s.rad / 10).toLocaleString('fr-FR')} mm)`,
                ...(s.grade ? [`Brise-copeaux / constructeur : ${s.grade}`] : []),
            ],
        };
    }
    const clamp = byCode(CLAMPS, s.p1), style = byCode(STYLES, s.p3), hand = byCode(HANDS, s.p5);
    const holder = [
        `1 Fixation : ${s.p1} - ${clamp?.label} (${clamp?.desc})`,
        `2 Forme plaquette : ${s.p2} - ${shape}`,
        `3 Angle d'attaque : ${s.p3} - ${style?.ang}°`,
        `4 Dépouille plaquette : ${s.p4} - ${clear}°`,
        `5 Direction : ${s.p5} - ${hand?.label}`,
    ];
    if (kind === 'int') {
        const bar = BAR_TYPES.find(([c]) => c === s.barType)?.[1];
        return {
            label: `Barre d'alésage ${bar ?? ''} Ø${s.barDiam} ${lenLabel(mmOf(BAR_LENGTHS, s.barLen))} - ${s.p2} ${style?.ang ?? ''}° ${hand?.label ?? ''}`.replace(/\s+/g, ' ').trim(),
            lines: [
                `Norme : ISO 6261 (barre) + ISO 5608 (tête)`,
                `Type de barre : ${s.barType} - ${bar}`,
                `Ø barre : ${s.barDiam} mm`,
                `Longueur : ${s.barLen} - ${lenLabel(mmOf(BAR_LENGTHS, s.barLen))}`,
                ...holder,
                `Longueur d'arête : ${s.size} mm`,
            ],
        };
    }
    return {
        label: `Porte-outil ext. ${s.p2} ${style?.ang ?? ''}° ${hand?.label ?? ''} ${pad2(s.h)}×${pad2(s.w)} ${lenLabel(mmOf(LENGTHS, s.len))}`.replace(/\s+/g, ' ').trim(),
        lines: [
            `Norme : ISO 5608`,
            ...holder,
            `6 Hauteur corps : ${pad2(s.h)} mm`,
            `7 Largeur corps : ${pad2(s.w)} mm`,
            `8 Longueur : ${s.len} - ${lenLabel(mmOf(LENGTHS, s.len))}`,
            `9 Longueur d'arête : ${s.size} mm`,
        ],
    };
}

/** Cohérence porte-outil ↔ plaquette associée (consultatif, jamais bloquant). */
function parseInsertCode(code) {
    const c = String(code || '').toUpperCase().replace(/\s+/g, '').split('-')[0];
    const m = c.match(/^([A-Z])([A-Z])([A-Z])([A-Z])(\d{2})(\d{2})(\d{2})/);
    return m ? { forme: m[1], depouille: m[2], taille: m[5] } : null;
}
function compatChecks(s, insertCode) {
    const i = parseInsertCode(insertCode);
    if (!i || !s.p2 || !s.p4 || !s.size) return [];
    return [
        { label: 'Forme', ok: s.p2 === i.forme, detail: `${s.p2} ↔ ${i.forme}`, fix: 'La forme (pos. 2 du porte-outil) doit être celle de la plaquette.' },
        { label: 'Dépouille', ok: s.p4 === i.depouille, detail: `${s.p4} ↔ ${i.depouille}`, fix: "L'angle de dépouille (pos. 4) doit correspondre à celui de la plaquette." },
        { label: "Taille d'arête", ok: parseInt(s.size, 10) === parseInt(i.taille, 10), detail: `${s.size} ↔ ${i.taille}`, fix: 'La taille du logement (pos. 9) doit correspondre à la longueur d\'arête de la plaquette.' },
    ];
}

// ─── Sous-composants UI ─────────────────────────────────────────────────────
function Section({ n, title, children }) {
    return (
        <div className="mb-3">
            <div className="d-flex align-items-center gap-2 mb-2">
                <span className="badge text-bg-primary">{n}</span>
                <span className="small fw-bold text-uppercase">{title}</span>
            </div>
            <div className="d-flex flex-wrap gap-2">{children}</div>
        </div>
    );
}
function Cell({ on, onClick, code, sub, children, w = 76 }) {
    return (
        <button type="button" onClick={onClick} title={`${code}${sub ? ' - ' + sub : ''}`} aria-pressed={on}
            className={`btn btn-sm p-1 d-flex flex-column align-items-center ${on ? 'btn-primary' : 'btn-light border'}`}
            style={{ width: w }}>
            <div className="rounded bg-white w-100" style={{ height: 40 }}>{children}</div>
            <div className="fw-bold" style={{ letterSpacing: 1 }}>{code}</div>
            {sub && <div className="lh-1" style={{ fontSize: 9, opacity: 0.85 }}>{sub}</div>}
        </button>
    );
}
function DimCard({ illu, label, children }) {
    return (
        <div className="border rounded bg-light p-2 d-flex flex-column align-items-center gap-1" style={{ width: 136 }}>
            {illu && <div className="w-100" style={{ height: 40 }}>{illu}</div>}
            <div className="small text-muted text-center lh-sm">{label}</div>
            {children}
        </div>
    );
}
function TwoDigits({ value, onChange, placeholder = '25' }) {
    return (
        <input className="form-control form-control-sm text-center fw-semibold" value={value} placeholder={placeholder}
            inputMode="numeric" onChange={(e) => onChange(e.target.value.replace(/[^0-9]/g, '').slice(0, 2))} />
    );
}
function LengthSelect({ value, onChange, list }) {
    return (
        <select className="form-select form-select-sm" value={value} onChange={(e) => onChange(e.target.value)}>
            <option value="">-</option>
            {list.map(([l, mm]) => <option key={l} value={l}>{l} - {lenLabel(mm)}</option>)}
        </select>
    );
}

// Planches 1-5 communes au porte-outil extérieur et à la barre d'alésage
function HolderPlates({ s, set }) {
    return (
        <>
            <Section n="1" title="Fixation de la plaquette">
                {CLAMPS.map((c) => (
                    <Cell key={c.code} code={c.code} sub={c.label} on={s.p1 === c.code} onClick={() => set({ p1: c.code })} w={84}>
                        <ClampSvg code={c.code} />
                    </Cell>
                ))}
            </Section>
            <Section n="2" title="Forme de la plaquette">
                {SHAPES.map((sh) => (
                    <Cell key={sh.code} code={sh.code} sub={sh.label} on={s.p2 === sh.code} onClick={() => set({ p2: sh.code })}><ShapeSvg shape={sh} /></Cell>
                ))}
            </Section>
            <Section n="3" title="Angle d'attaque">
                {STYLES.map((st) => (
                    <Cell key={st.code} code={st.code} sub={`${st.ang}°`} on={s.p3 === st.code} onClick={() => set({ p3: st.code })} w={64}><StyleSvg ang={st.ang} /></Cell>
                ))}
            </Section>
            <Section n="4" title="Angle de dépouille de la plaquette">
                {CLEAR.map((cl) => (
                    <Cell key={cl.code} code={cl.code} sub={`${cl.ang}°`} on={s.p4 === cl.code} onClick={() => set({ p4: cl.code })} w={64}><ClearSvg ang={cl.ang} /></Cell>
                ))}
            </Section>
            <Section n="5" title="Direction de coupe">
                {HANDS.map((h) => (
                    <Cell key={h.code} code={h.code} sub={h.label} on={s.p5 === h.code} onClick={() => set({ p5: h.code })}><HandSvg code={h.code} /></Cell>
                ))}
            </Section>
        </>
    );
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// ─── Image de l'outil (aperçu + fichier enregistré dans methods_tools.picture) ─
const PIC_W = 480, PIC_H = 300, PIC_BAND = 58;
const clampN = (v, a, b) => Math.max(a, Math.min(b, v));

/** Plaquette dessinée à partir de la planche de forme (rayon ≈ 20 dans son repère 48×48). */
function InsertShape({ code, cx, cy, size, rot = 0, chip = 0, hole = 'none' }) {
    const shape = byCode(SHAPES, code);
    const k = size / 20;
    if (!shape) {
        return <circle cx={cx} cy={cy} r={size} fill="none" stroke={INS_ED} strokeWidth="1.2" strokeDasharray="4 3" />;
    }
    return (
        <g transform={`translate(${cx} ${cy}) rotate(${rot})`}>
            <g transform={`scale(${k}) translate(-24 -24)`} fill={INS} stroke={INS_ED} strokeWidth={1.4 / k} strokeLinejoin="round">{shape.svg(24)}</g>
            {chip > 0 && (
                <g transform={`scale(${k * 0.72}) translate(-24 -24)`} fill="none" stroke={INS_ED} strokeWidth={1 / (k * 0.72)} strokeDasharray={`${3 / k} ${2 / k}`}>{shape.svg(24)}</g>
            )}
            {hole !== 'none' && <circle r={size * 0.22} fill={BODY} stroke={BODY_ED} strokeWidth="1" />}
            {(hole === 'csk1' || hole === 'csk2') && <circle r={size * 0.32} fill="none" stroke={BODY_ED} strokeWidth="1" />}
        </g>
    );
}

function Hatch({ id }) {
    return (
        <pattern id={id} width="6" height="6" patternTransform="rotate(45)" patternUnits="userSpaceOnUse">
            <line x1="0" y1="0" x2="0" y2="6" stroke={DIM} strokeWidth="1" />
        </pattern>
    );
}

/** Porte-outil extérieur, vue de dessus (même convention que la planche « angle d'attaque »). */
function HolderDrawing({ s }) {
    const k = 3.4, ox = 30, oy = 22;                   // repère de la planche 64×50, agrandi
    const ang = byCode(STYLES, s.p3)?.ang ?? 90;
    const P = [16, 36], L = 22;
    const e = [P[0] + L * Math.sin(rad(ang)), P[1] - L * Math.cos(rad(ang))];
    const mirror = s.p5 === 'L';
    const g = (
        <g transform={`translate(${ox} ${oy}) scale(${k})`}>
            <rect x="-6" y="41" width="80" height="9" fill="url(#tp-hatch)" stroke={BODY_ED} strokeWidth={0.8 / k} />
            <polygon points="16,38 122,38 122,12 30,12 16,26" fill={BODY} stroke={BODY_ED} strokeWidth={1.2 / k} strokeLinejoin="round" />
            <InsertShape code={s.p2} cx={23} cy={30} size={8} rot={0} />
            <line x1={P[0]} y1={P[1]} x2={e[0]} y2={e[1]} stroke={EDGE} strokeWidth={2.4 / k} strokeLinecap="round" />
            <circle cx={P[0]} cy={P[1]} r={2 / k * 1.6} fill={EDGE} />
        </g>
    );
    return mirror ? <g transform={`translate(${PIC_W} 0) scale(-1 1)`}>{g}</g> : g;
}

/** Barre d'alésage, vue de côté : barre cylindrique, plaquette en bout. */
function BarDrawing({ s }) {
    const d = clampN((+s.barDiam || 20) * 2.6, 34, 96), y0 = 112 - d / 2, x0 = 70, x1 = 440;
    const ang = byCode(STYLES, s.p3)?.ang ?? 90;
    const tip = [x0 - 8, y0 + d + 6];
    const e = [tip[0] + 34 * Math.sin(rad(ang)), tip[1] - 34 * Math.cos(rad(ang))];
    const mirror = s.p5 === 'L';
    const g = (
        <g>
            <rect x={x0} y={y0} width={x1 - x0} height={d} rx={d / 2} fill={BODY} stroke={BODY_ED} strokeWidth="1.4" />
            <line x1={x0 + d / 2} y1={y0 + d / 2} x2={x1 + 6} y2={y0 + d / 2} stroke={BODY_ED} strokeWidth="0.8" strokeDasharray="10 3 2 3" />
            <polygon points={`${x0 + 30},${y0} ${x0 - 4},${y0 + d * 0.35} ${tip[0]},${tip[1]} ${x0 + 30},${y0 + d}`} fill={BODY} stroke={BODY_ED} strokeWidth="1.4" strokeLinejoin="round" />
            <InsertShape code={s.p2} cx={tip[0] + 10} cy={tip[1] - 12} size={15} />
            <line x1={tip[0]} y1={tip[1]} x2={e[0]} y2={e[1]} stroke={EDGE} strokeWidth="2.6" strokeLinecap="round" />
            <circle cx={tip[0]} cy={tip[1]} r="3.2" fill={EDGE} />
        </g>
    );
    return mirror ? <g transform={`translate(${PIC_W} 0) scale(-1 1)`}>{g}</g> : g;
}

/** Plaquette vue de face, agrandie. */
function InsertDrawing({ s }) {
    const t = byCode(INSERT_TYPES, s.type);
    return <InsertShape code={s.forme} cx={PIC_W / 2} cy={118} size={88} chip={t?.chip || 0} hole={t?.hole || 'none'} />;
}

function pictureCaption(kind, s) {
    if (kind === 'insert') {
        const parts = [s.iSize && `taille ${pad2(s.iSize)}`, s.thick && `ép. ${pad2(s.thick)}`, s.rad && `rε ${(+s.rad / 10).toLocaleString('fr-FR')} mm`];
        return [byCode(SHAPES, s.forme)?.label, ...parts].filter(Boolean).join(' · ');
    }
    const ang = byCode(STYLES, s.p3)?.ang;
    const common = [ang != null && `κr ${ang}°`, byCode(HANDS, s.p5)?.label, s.size && `arête ${s.size}`];
    if (kind === 'int') {
        const len = mmOf(BAR_LENGTHS, s.barLen);
        return [s.barDiam && `Ø${s.barDiam}`, len && lenLabel(len), ...common].filter(Boolean).join(' · ');
    }
    const len = mmOf(LENGTHS, s.len);
    return [s.h && s.w && `${pad2(s.h)}×${pad2(s.w)}`, len && lenLabel(len), ...common].filter(Boolean).join(' · ');
}

const ToolPicture = React.forwardRef(function ToolPicture({ kind, s, code }, ref) {
    return (
        <svg ref={ref} xmlns="http://www.w3.org/2000/svg" viewBox={`0 0 ${PIC_W} ${PIC_H}`} width={PIC_W} height={PIC_H}
            fontFamily="Arial, Helvetica, sans-serif" style={{ width: '100%', height: 'auto', display: 'block' }}>
            <defs><Hatch id="tp-hatch" /></defs>
            <rect width={PIC_W} height={PIC_H} fill="#ffffff" />
            {kind === 'ext' && <HolderDrawing s={s} />}
            {kind === 'int' && <BarDrawing s={s} />}
            {kind === 'insert' && <InsertDrawing s={s} />}
            <rect x="0" y={PIC_H - PIC_BAND} width={PIC_W} height={PIC_BAND} fill="#f1f5f9" />
            <line x1="0" y1={PIC_H - PIC_BAND} x2={PIC_W} y2={PIC_H - PIC_BAND} stroke="#cbd5e1" />
            <text x={PIC_W / 2} y={PIC_H - 28} textAnchor="middle" fontSize="24" fontWeight="700" letterSpacing="2" fill="#1e293b">{code || '—'}</text>
            <text x={PIC_W / 2} y={PIC_H - 10} textAnchor="middle" fontSize="12" fill="#64748b">{pictureCaption(kind, s)}</text>
        </svg>
    );
});

/** Rasterise le SVG d'aperçu en PNG (×2) pour l'enregistrer comme image de l'outil. */
function svgElementToPngBlob(svgEl, scale = 2) {
    return new Promise((resolve, reject) => {
        const clone = svgEl.cloneNode(true);
        clone.removeAttribute('style'); // le 100 % de l'aperçu n'a pas de sens hors de la page
        const xml = new XMLSerializer().serializeToString(clone);
        const url = URL.createObjectURL(new Blob([xml], { type: 'image/svg+xml;charset=utf-8' }));
        const img = new Image();
        img.onload = () => {
            const c = document.createElement('canvas');
            c.width = PIC_W * scale; c.height = PIC_H * scale;
            const ctx = c.getContext('2d');
            ctx.scale(scale, scale);
            ctx.drawImage(img, 0, 0, PIC_W, PIC_H);
            URL.revokeObjectURL(url);
            c.toBlob((b) => (b ? resolve(b) : reject(new Error('Conversion PNG impossible'))), 'image/png');
        };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error("Rendu de l'image impossible")); };
        img.src = url;
    });
}

// ─── Composant principal ────────────────────────────────────────────────────
/** Bloc « article de stock » : crée en même temps l'article acheté qui portera le stock de l'outil. */
function StockProductFields({ value, onChange, options }) {
    const set = (patch) => onChange({ ...value, ...patch });
    const sel = (key, list, label, required = true) => (
        <div className="col-6">
            <label className="form-label small mb-1">{label}</label>
            <select className={`form-select form-select-sm ${required && !value[key] ? 'is-invalid' : ''}`} value={value[key] ?? ''} onChange={(e) => set({ [key]: e.target.value })}>
                <option value="">{required ? '— choisir —' : '— aucun —'}</option>
                {(list || []).map((o) => <option key={o.id} value={o.id}>{o.label}</option>)}
            </select>
        </div>
    );
    return (
        <div className="border rounded p-2 mb-3">
            <div className="form-check form-switch small">
                <input className="form-check-input" type="checkbox" id="tc-stock" checked={value.enabled} onChange={(e) => set({ enabled: e.target.checked })} />
                <label className="form-check-label" htmlFor="tc-stock">Créer aussi l'article de stock <span className="text-muted">(consommable, outillage acheté)</span></label>
            </div>
            {value.enabled && (
                <div className="row g-2 mt-1">
                    {sel('methods_services_id', options.services, 'Service')}
                    {sel('methods_families_id', options.families, 'Famille')}
                    {sel('methods_units_id', options.units, 'Unité')}
                    {sel('stock_locations_id', options.locations, 'Emplacement', false)}
                    <div className="col-6">
                        <label className="form-label small mb-1">Seuil mini</label>
                        <input type="number" min="0" step="any" className="form-control form-control-sm" value={value.mini_qty} onChange={(e) => set({ mini_qty: e.target.value })} />
                    </div>
                    <div className="col-6">
                        <label className="form-label small mb-1">Qté éco. d'achat</label>
                        <input type="number" min="0" step="any" className="form-control form-control-sm" value={value.qty_eco_min} placeholder="ex. 10" onChange={(e) => set({ qty_eco_min: e.target.value })} />
                    </div>
                    {value.mini_qty !== '' && !value.stock_locations_id && (
                        <div className="col-12 small text-warning-emphasis">Le seuil mini n'est enregistré qu'avec un emplacement.</div>
                    )}
                </div>
            )}
        </div>
    );
}

export default function ToolConfigurator({ storeUrl, indexUrl, currency = '€', stockOptions = {} }) {
    const [kind, setKind] = useState('ext');
    const [s, setS] = useState({ ...EMPTY });
    const set = (patch) => setS((prev) => ({ ...prev, ...patch }));
    const [insertRef, setInsertRef] = useState('');
    const [erp, setErp] = useState({ label: '', qty: 1, cost: '', end_date: '', note: '' });
    const [labelTouched, setLabelTouched] = useState(false);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState([]);
    const [withPicture, setWithPicture] = useState(true);
    const pictureRef = useRef(null);
    const [stockProduct, setStockProduct] = useState({
        enabled: false, methods_services_id: '', methods_families_id: '', methods_units_id: '',
        stock_locations_id: '', mini_qty: '', qty_eco_min: '',
    });
    const stockIncomplete = stockProduct.enabled
        && !(stockProduct.methods_services_id && stockProduct.methods_families_id && stockProduct.methods_units_id);

    const code = buildCode(kind, s);
    const complete = isComplete(kind, s);
    const desc = useMemo(() => describe(kind, s), [kind, s]);
    const label = labelTouched ? erp.label : (complete ? desc.label : '');
    const checks = kind === 'insert' ? [] : compatChecks(s, insertRef);

    const reset = () => { setS({ ...EMPTY }); setInsertRef(''); setLabelTouched(false); setErp((e) => ({ ...e, label: '' })); setErrors([]); };

    async function save() {
        setSaving(true); setErrors([]);
        const comment = [
            ...desc.lines,
            ...(kind !== 'insert' && insertRef ? [`Plaquette associée : ${insertRef.toUpperCase()}`] : []),
            ...(erp.note ? ['', erp.note] : []),
        ].join('\n');
        try {
            const body = new FormData();
            Object.entries({ code, label, qty: erp.qty, cost: erp.cost, end_date: erp.end_date, comment })
                .forEach(([k, v]) => { if (v !== '' && v != null) body.append(k, v); });
            if (stockProduct.enabled) {
                body.append('create_product', '1');
                ['methods_services_id', 'methods_families_id', 'methods_units_id', 'stock_locations_id', 'mini_qty', 'qty_eco_min']
                    .forEach((k) => { if (stockProduct[k] !== '') body.append(`stock[${k}]`, stockProduct[k]); });
                if (erp.cost !== '') body.append('stock[purchased_price]', erp.cost);
            }
            if (withPicture && pictureRef.current) {
                const slug = code.replace(/[^A-Za-z0-9-]+/g, '_');
                body.append('picture', await svgElementToPngBlob(pictureRef.current), `${slug}.png`);
            }
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setErrors(data.errors ? Object.values(data.errors).flat() : [data.message || `Erreur ${res.status}`]);
                setSaving(false);
                return;
            }
            window.location.href = data.redirect || indexUrl;
        } catch (e) {
            setErrors([e.message || String(e)]);
            setSaving(false);
        }
    }

    return (
        <div className="row g-3">
            {/* ── Planches ── */}
            <div className="col-xl-8">
                <div className="card">
                    <div className="card-header py-2">
                        <div className="btn-group btn-group-sm" role="group">
                            {KINDS.map(([k, l, norm]) => (
                                <button key={k} type="button" aria-pressed={kind === k} onClick={() => setKind(k)}
                                    className={`btn ${kind === k ? 'btn-primary' : 'btn-outline-primary'}`}>
                                    {l} <span className="opacity-75 small">· {norm}</span>
                                </button>
                            ))}
                        </div>
                    </div>
                    <div className="card-body">
                        {kind === 'int' && (
                            <>
                                <Section n="A" title="Type de barre">
                                    {BAR_TYPES.map(([cd, lbl]) => (
                                        <button key={cd} type="button" title={lbl} onClick={() => set({ barType: cd })}
                                            className={`btn btn-sm d-flex align-items-center gap-2 ${s.barType === cd ? 'btn-primary' : 'btn-light border'}`}>
                                            <b>{cd}</b><span className="small">{lbl}</span>
                                        </button>
                                    ))}
                                </Section>
                                <Section n="B-C" title="Diamètre et longueur de barre">
                                    <DimCard illu={DIM_SVGS.barD} label="B · Ø barre (mm)"><TwoDigits value={s.barDiam} onChange={(v) => set({ barDiam: v })} placeholder="20" /></DimCard>
                                    <DimCard illu={DIM_SVGS.barL} label="C · Longueur"><LengthSelect value={s.barLen} onChange={(v) => set({ barLen: v })} list={BAR_LENGTHS} /></DimCard>
                                </Section>
                            </>
                        )}

                        {kind !== 'insert' && <HolderPlates s={s} set={set} />}

                        {kind === 'ext' && (
                            <Section n="6-9" title="Dimensions du corps et de la plaquette">
                                <DimCard illu={DIM_SVGS.h} label="6 · Hauteur corps (mm)"><TwoDigits value={s.h} onChange={(v) => set({ h: v })} /></DimCard>
                                <DimCard illu={DIM_SVGS.b} label="7 · Largeur corps (mm)"><TwoDigits value={s.w} onChange={(v) => set({ w: v })} /></DimCard>
                                <DimCard illu={DIM_SVGS.l} label="8 · Longueur porte-outil"><LengthSelect value={s.len} onChange={(v) => set({ len: v })} list={LENGTHS} /></DimCard>
                                <DimCard illu={DIM_SVGS.edge} label="9 · Longueur d'arête (mm)"><TwoDigits value={s.size} onChange={(v) => set({ size: v })} placeholder="12" /></DimCard>
                            </Section>
                        )}
                        {kind === 'int' && (
                            <Section n="9" title="Taille de la plaquette">
                                <DimCard illu={DIM_SVGS.edge} label="Longueur d'arête (mm)"><TwoDigits value={s.size} onChange={(v) => set({ size: v })} placeholder="11" /></DimCard>
                            </Section>
                        )}

                        {kind === 'insert' && (
                            <>
                                <Section n="1" title="Forme de la plaquette">
                                    {SHAPES.map((sh) => (
                                        <Cell key={sh.code} code={sh.code} sub={sh.label} on={s.forme === sh.code} onClick={() => set({ forme: sh.code })}><ShapeSvg shape={sh} /></Cell>
                                    ))}
                                </Section>
                                <Section n="2" title="Angle de dépouille">
                                    {CLEAR.map((cl) => (
                                        <Cell key={cl.code} code={cl.code} sub={`${cl.ang}°`} on={s.dep === cl.code} onClick={() => set({ dep: cl.code })} w={64}><ClearSvg ang={cl.ang} /></Cell>
                                    ))}
                                </Section>
                                <Section n="3" title="Classe de tolérance">
                                    {TOLERANCES.map((t) => (
                                        <button key={t} type="button" onClick={() => set({ tol: t })} style={{ width: 44 }}
                                            className={`btn btn-sm fw-bold ${s.tol === t ? 'btn-primary' : 'btn-light border'}`}>{t}</button>
                                    ))}
                                </Section>
                                <Section n="4" title="Type (trou de fixation et brise-copeaux)">
                                    {INSERT_TYPES.map((t) => (
                                        <Cell key={t.code} code={t.code} sub={t.label} on={s.type === t.code} onClick={() => set({ type: t.code })} w={88}><InsertTypeSvg t={t} /></Cell>
                                    ))}
                                </Section>
                                <Section n="5-10" title="Cotes et brise-copeaux">
                                    <DimCard illu={DIM_SVGS.iC} label="5-6 · Taille"><TwoDigits value={s.iSize} onChange={(v) => set({ iSize: v })} placeholder="12" /></DimCard>
                                    <DimCard illu={DIM_SVGS.s} label="7-8 · Épaisseur"><TwoDigits value={s.thick} onChange={(v) => set({ thick: v })} placeholder="04" /></DimCard>
                                    <DimCard illu={DIM_SVGS.r} label="9-10 · Rayon de bec"><TwoDigits value={s.rad} onChange={(v) => set({ rad: v })} placeholder="08" /></DimCard>
                                    <DimCard label="Brise-copeaux / constructeur">
                                        <input className="form-control form-control-sm text-center fw-semibold" value={s.grade} placeholder="PF, MR…"
                                            onChange={(e) => set({ grade: e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 4) })} />
                                    </DimCard>
                                </Section>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* ── Récapitulatif et création ── */}
            <div className="col-xl-4">
                <div className="card" style={{ position: 'sticky', top: '0.5rem' }}>
                    <div className="card-header py-2 d-flex align-items-center">
                        <h3 className="card-title mb-0">Nouvel outil</h3>
                        <button type="button" className="btn btn-sm btn-link ms-auto" onClick={reset}><i className="fas fa-undo me-1" />Vider</button>
                    </div>
                    <div className="card-body">
                        <div className="small text-muted mb-1">Code assemblé</div>
                        <div className="d-flex flex-wrap align-items-end gap-1 mb-3 font-monospace">
                            {segments(kind, s).map((seg, i) => (seg === '-' || seg === ' ') ? (
                                <span key={i} className="fs-5 fw-bold text-primary" style={{ width: seg === ' ' ? 4 : 'auto' }}>{seg === '-' ? '-' : ''}</span>
                            ) : (
                                <span key={i} className={`fs-5 fw-bold text-center ${seg ? 'text-primary border-primary' : 'text-body-tertiary'}`}
                                    style={{ minWidth: 18, borderBottom: '2px solid', padding: '0 2px' }}>{seg || '·'}</span>
                            ))}
                            {kind === 'insert' && s.grade && <span className="fs-6 text-primary">-{s.grade}</span>}
                        </div>

                        <div className="border rounded overflow-hidden mb-1">
                            <ToolPicture ref={pictureRef} kind={kind} s={s} code={code} />
                        </div>
                        <div className="form-check form-switch small mb-3">
                            <input className="form-check-input" type="checkbox" id="tc-with-picture" checked={withPicture} onChange={(e) => setWithPicture(e.target.checked)} />
                            <label className="form-check-label" htmlFor="tc-with-picture">Enregistrer cette illustration comme image de l'outil</label>
                        </div>

                        {kind !== 'insert' && (
                            <div className="mb-3">
                                <label className="form-label small mb-1">Plaquette associée <span className="text-muted">(contrôle de cohérence)</span></label>
                                <input className="form-control form-control-sm font-monospace" value={insertRef} placeholder="ex. DNMG 15 06 08-PF"
                                    onChange={(e) => setInsertRef(e.target.value)} />
                                {checks.length > 0 && (
                                    <div className="mt-2 d-flex flex-column gap-1">
                                        {checks.map((c) => (
                                            <div key={c.label} className={`small ${c.ok ? 'text-success' : 'text-warning-emphasis'}`} title={c.ok ? '' : c.fix}>
                                                {c.ok ? '✓' : '⚠'} {c.label} <span className="text-muted font-monospace">{c.detail}</span>
                                                {!c.ok && <div className="text-muted ps-3" style={{ fontSize: 11 }}>{c.fix}</div>}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="mb-2">
                            <label className="form-label small mb-1">Désignation</label>
                            <input className="form-control form-control-sm" value={label} placeholder="Générée une fois le code complet"
                                onChange={(e) => { setLabelTouched(true); setErp({ ...erp, label: e.target.value }); }} />
                            {labelTouched && complete && (
                                <button type="button" className="btn btn-link btn-sm p-0" onClick={() => setLabelTouched(false)}>Reprendre la désignation générée</button>
                            )}
                        </div>
                        <div className="row g-2 mb-2">
                            <div className="col-4">
                                <label className="form-label small mb-1">Quantité</label>
                                <input type="number" min="0" className="form-control form-control-sm" value={erp.qty} onChange={(e) => setErp({ ...erp, qty: e.target.value })} />
                            </div>
                            <div className="col-8">
                                <label className="form-label small mb-1">Coût</label>
                                <div className="input-group input-group-sm">
                                    <input type="number" step=".001" min="0" className="form-control" value={erp.cost} onChange={(e) => setErp({ ...erp, cost: e.target.value })} />
                                    <span className="input-group-text">{currency}</span>
                                </div>
                            </div>
                        </div>
                        <div className="mb-2">
                            <label className="form-label small mb-1">Fin de validité</label>
                            <input type="date" className="form-control form-control-sm" value={erp.end_date} onChange={(e) => setErp({ ...erp, end_date: e.target.value })} />
                        </div>
                        <div className="mb-3">
                            <label className="form-label small mb-1">Note</label>
                            <textarea rows="2" className="form-control form-control-sm" value={erp.note} placeholder="Fournisseur, nuance, emplacement…"
                                onChange={(e) => setErp({ ...erp, note: e.target.value })} />
                        </div>

                        {complete && (
                            <details className="small mb-3">
                                <summary className="text-muted">Décodage enregistré en commentaire</summary>
                                <pre className="small bg-light border rounded p-2 mt-1 mb-0" style={{ whiteSpace: 'pre-wrap' }}>{desc.lines.join('\n')}</pre>
                            </details>
                        )}

                        <StockProductFields value={stockProduct} onChange={setStockProduct} options={stockOptions} />

                        {errors.length > 0 && (
                            <div className="alert alert-danger py-2 small">{errors.map((e, i) => <div key={i}>{e}</div>)}</div>
                        )}
                        <button type="button" className="btn btn-primary w-100" disabled={!complete || !label || saving || stockIncomplete} onClick={save}>
                            {saving ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-plus me-1" />}Créer l'outil
                        </button>
                        {!complete && <div className="small text-muted mt-1">Cliquez sur chaque planche pour compléter le code.</div>}
                    </div>
                </div>
            </div>
        </div>
    );
}
