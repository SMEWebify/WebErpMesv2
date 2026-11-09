/**
 * Lecture d'un programme ISO (G-code Fanuc et compatibles) en segments de
 * parcours d'outil, sans dépendance : sert le lecteur de la GED.
 *
 * Seul le mouvement est interprété (G0/G1/G2/G3, cycles de perçage G73/G81-G89,
 * plans G17/G18/G19, G90/G91, G20/G21) : c'est un aperçu du parcours, pas une
 * simulation de la machine. Compensation de rayon (G41/G42), correcteur de
 * longueur (G43), rotation (G68) et macros (#, [ ]) ne déplacent rien ; les
 * codes ignorés qui changeraient le dessin sont signalés dans `warnings`.
 *
 * Les segments sont rangés à plat : `positions` porte 6 nombres par segment
 * (x1 y1 z1 x2 y2 z2), `rapid` 1 pour un déplacement rapide, `lines` le numéro
 * (base 0) de la ligne du fichier qui l'a produit.
 */

const AXES = ['x', 'y', 'z'];

// Axes du plan (a, b) et normale, pour G17 / G18 / G19 ; lettres du centre
// (incrémental depuis le point de départ) dans le même ordre.
const PLANES = {
    17: { a: 0, b: 1, n: 2, ca: 'I', cb: 'J' },
    18: { a: 2, b: 0, n: 1, ca: 'K', cb: 'I' },
    19: { a: 1, b: 2, n: 0, ca: 'J', cb: 'K' },
};

// Codes G qui portent des mots d'axe sans être un déplacement à dessiner :
// temporisation (G4 X = durée), origine machine, décalages.
const NON_MOTION = new Set([4, 10, 28, 30, 52, 53, 92]);

const DRILL_CYCLES = new Set([73, 74, 76, 81, 82, 83, 84, 85, 86, 87, 88, 89]);

// Codes ignorés qui modifient pourtant la trajectoire réelle.
const WARN_CODES = {
    41: 'G41/G42', 42: 'G41/G42', 51: 'G51', 52: 'G52', 68: 'G68', 92: 'G92',
};

const ARC_STEPS_PER_TURN = 72;
const EPSILON = 1e-7;

const TOKEN = /([A-Za-z])\s*([+-]?(?:\d+\.?\d*|\.\d+))/g;

function stripComments(text, comments) {
    let out = '';
    let depth = 0;
    let current = '';

    for (let i = 0; i < text.length; i++) {
        const ch = text[i];

        if (depth === 0 && ch === ';') {
            comments.push(text.slice(i + 1).trim());
            break;
        }
        if (ch === '(') {
            if (depth > 0) current += ch;
            depth++;
            continue;
        }
        if (ch === ')' && depth > 0) {
            depth--;
            if (depth === 0) {
                comments.push(current.trim());
                current = '';
            } else {
                current += ch;
            }
            continue;
        }
        if (depth > 0) current += ch;
        else out += ch;
    }

    if (depth > 0 && current.trim()) comments.push(current.trim());

    return out;
}

/**
 * Mots d'un bloc : `g` liste les codes G (G91.1 compris), `words` la dernière
 * valeur de chaque autre lettre.
 */
export function parseBlock(text) {
    const g = [];
    const m = [];
    const words = {};
    TOKEN.lastIndex = 0;

    let match;
    while ((match = TOKEN.exec(text)) !== null) {
        const letter = match[1].toUpperCase();
        const value = Number(match[2]);

        if (letter === 'G') g.push(value);
        else if (letter === 'M') m.push(value);
        else words[letter] = value;
    }

    return { g, m, words };
}

function length(p, q) {
    return Math.hypot(q[0] - p[0], q[1] - p[1], q[2] - p[2]);
}

/**
 * @param {string} text contenu du fichier
 * @returns {{
 *   lines: string[], positions: number[], rapid: number[], segmentLines: number[],
 *   lineEnd: (number[]|null)[], tools: {number: number, comment: string|null}[],
 *   units: 'mm'|'inch', stats: object, warnings: string[]
 * }}
 */
export function parseGcode(text) {
    const lines = String(text ?? '').split(/\r\n|\r|\n/);

    const positions = [];
    const rapid = [];
    const segmentLines = [];
    const lineEnd = new Array(lines.length).fill(null);
    const warnings = new Set();
    const toolComments = new Map();
    const toolOrder = [];

    const state = {
        pos: [null, null, null],
        motion: 0,
        plane: 17,
        absolute: true,
        feedMode: 94,
        feed: 0,
        units: null,
        cycle: null,       // { code, r, z, retract: 98|99, initialZ }
        retract: 98,
    };

    const stats = {
        blocks: 0,
        rapidLength: 0,
        feedLength: 0,
        feedMinutes: 0,
        feedTimeKnown: true,
        min: [Infinity, Infinity, Infinity],
        max: [-Infinity, -Infinity, -Infinity],
    };

    const known = () => state.pos.every((v) => v !== null);

    const push = (from, to, isRapid, lineIndex) => {
        if (length(from, to) < EPSILON) return;

        positions.push(from[0], from[1], from[2], to[0], to[1], to[2]);
        rapid.push(isRapid ? 1 : 0);
        segmentLines.push(lineIndex);

        const len = length(from, to);
        if (isRapid) {
            stats.rapidLength += len;
        } else {
            stats.feedLength += len;
            if (state.feedMode === 94 && state.feed > 0) stats.feedMinutes += len / state.feed;
            else stats.feedTimeKnown = false;

            for (const p of [from, to]) {
                for (let k = 0; k < 3; k++) {
                    if (p[k] < stats.min[k]) stats.min[k] = p[k];
                    if (p[k] > stats.max[k]) stats.max[k] = p[k];
                }
            }
        }
    };

    // Position d'arrivée d'un mot d'axe, absolue ou incrémentale.
    const target = (words) => AXES.map((axis, k) => {
        const value = words[axis.toUpperCase()];
        if (value === undefined) return state.pos[k];
        if (state.absolute) return value;
        return (state.pos[k] ?? 0) + value;
    });

    const moveTo = (to, isRapid, lineIndex) => {
        if (known()) push(state.pos.slice(), to, isRapid, lineIndex);
        state.pos = to;
    };

    const arcTo = (to, clockwise, words, lineIndex) => {
        const plane = PLANES[state.plane];

        if (!known() || to.some((v) => v === null)) {
            state.pos = to;
            return;
        }

        const from = state.pos.slice();
        const sa = from[plane.a];
        const sb = from[plane.b];
        const ea = to[plane.a];
        const eb = to[plane.b];
        let ca;
        let cb;

        if (words.R !== undefined) {
            const r = words.R;
            const d = Math.hypot(ea - sa, eb - sb);
            if (d < EPSILON || Math.abs(r) < EPSILON) {
                moveTo(to, false, lineIndex);
                return;
            }
            const h = Math.sqrt(Math.max(r * r - (d / 2) ** 2, 0));
            const ua = (ea - sa) / d;
            const ub = (eb - sb) / d;
            const sign = (clockwise ? -1 : 1) * (r > 0 ? 1 : -1);
            ca = (sa + ea) / 2 - sign * h * ub;
            cb = (sb + eb) / 2 + sign * h * ua;
        } else {
            ca = sa + (words[plane.ca] ?? 0);
            cb = sb + (words[plane.cb] ?? 0);
        }

        const r0 = Math.hypot(sa - ca, sb - cb);
        const r1 = Math.hypot(ea - ca, eb - cb);
        const a0 = Math.atan2(sb - cb, sa - ca);
        const a1 = Math.atan2(eb - cb, ea - ca);
        const closed = Math.hypot(ea - sa, eb - sb) < EPSILON;

        let sweep = a1 - a0;
        if (clockwise) {
            if (closed) sweep = -2 * Math.PI;
            else if (sweep > 0) sweep -= 2 * Math.PI;
        } else if (closed) {
            sweep = 2 * Math.PI;
        } else if (sweep < 0) {
            sweep += 2 * Math.PI;
        }

        const steps = Math.max(2, Math.ceil((Math.abs(sweep) / (2 * Math.PI)) * ARC_STEPS_PER_TURN));
        let previous = from;

        for (let i = 1; i <= steps; i++) {
            const f = i / steps;
            const angle = a0 + sweep * f;
            const radius = r0 + (r1 - r0) * f;
            const point = [0, 0, 0];

            point[plane.a] = ca + radius * Math.cos(angle);
            point[plane.b] = cb + radius * Math.sin(angle);
            point[plane.n] = from[plane.n] + (to[plane.n] - from[plane.n]) * f;

            if (i === steps) {
                point[plane.a] = ea;
                point[plane.b] = eb;
            }

            push(previous, point, false, lineIndex);
            previous = point;
        }

        state.pos = to;
    };

    // Cycle de perçage : approche en rapide, plongée à Z en avance, retour au
    // plan R (G99) ou au plan initial (G98) en rapide.
    const drill = (words, lineIndex) => {
        const cycle = state.cycle;
        if (words.R !== undefined) cycle.r = words.R;
        if (words.Z !== undefined) cycle.z = words.Z;

        const x = words.X !== undefined ? (state.absolute ? words.X : (state.pos[0] ?? 0) + words.X) : state.pos[0];
        const y = words.Y !== undefined ? (state.absolute ? words.Y : (state.pos[1] ?? 0) + words.Y) : state.pos[1];
        const r = cycle.r ?? cycle.initialZ;
        const z = cycle.z;

        if (z === null || z === undefined || r === null || x === null || y === null) {
            state.pos = [x, y, state.pos[2]];
            return;
        }

        moveTo([x, y, state.pos[2] ?? cycle.initialZ ?? r], true, lineIndex);
        moveTo([x, y, r], true, lineIndex);
        moveTo([x, y, z], false, lineIndex);
        moveTo([x, y, state.retract === 98 && cycle.initialZ !== null ? cycle.initialZ : r], true, lineIndex);
    };

    lines.forEach((raw, lineIndex) => {
        const comments = [];
        let code = stripComments(raw, comments).trim();

        for (const comment of comments) {
            const tool = /^T(\d+)\b[\s:=-]*(.*)$/i.exec(comment);
            if (tool && !toolComments.has(Number(tool[1]))) {
                toolComments.set(Number(tool[1]), tool[2].trim() || null);
            }
        }

        if (code.startsWith('/')) code = code.slice(1);
        if (code === '' || code === '%') {
            lineEnd[lineIndex] = known() ? state.pos.slice() : null;
            return;
        }

        if (/[#[\]]/.test(code)) {
            warnings.add('macro');
            lineEnd[lineIndex] = known() ? state.pos.slice() : null;
            return;
        }

        stats.blocks++;

        const { g, words } = parseBlock(code);
        let nonMotion = false;
        let motionInBlock = null;

        for (const value of g) {
            if (WARN_CODES[value]) warnings.add(WARN_CODES[value]);

            if (value === 0 || value === 1 || value === 2 || value === 3) {
                state.motion = value;
                motionInBlock = value;
                state.cycle = null;
            } else if (value === 17 || value === 18 || value === 19) {
                state.plane = value;
            } else if (value === 20) {
                state.units = state.units ?? 'inch';
            } else if (value === 21) {
                state.units = state.units ?? 'mm';
            } else if (value === 90) {
                state.absolute = true;
            } else if (value === 91) {
                state.absolute = false;
            } else if (value === 93 || value === 94 || value === 95) {
                state.feedMode = value;
            } else if (value === 98 || value === 99) {
                state.retract = value;
            } else if (value === 80) {
                state.cycle = null;
                if (state.motion === 'cycle') state.motion = 0;
            } else if (DRILL_CYCLES.has(value)) {
                state.cycle = { code: value, r: null, z: null, initialZ: state.pos[2] };
                state.motion = 'cycle';
                motionInBlock = 'cycle';
            } else if (NON_MOTION.has(value)) {
                nonMotion = true;
            }
        }

        if (words.F !== undefined) state.feed = words.F;
        if (words.T !== undefined) {
            const number = Math.round(words.T);
            if (!toolOrder.includes(number)) toolOrder.push(number);
        }

        const hasAxis = AXES.some((axis) => words[axis.toUpperCase()] !== undefined);

        if (!nonMotion && (hasAxis || motionInBlock === 'cycle')) {
            if (state.motion === 'cycle' && state.cycle) {
                if (hasAxis || words.R !== undefined) drill(words, lineIndex);
            } else if (state.motion === 2 || state.motion === 3) {
                arcTo(target(words), state.motion === 2, words, lineIndex);
            } else {
                moveTo(target(words), state.motion === 0, lineIndex);
            }
        }

        lineEnd[lineIndex] = known() ? state.pos.slice() : null;
    });

    const tools = toolOrder.map((number) => ({ number, comment: toolComments.get(number) ?? null }));
    for (const [number, comment] of toolComments) {
        if (!toolOrder.includes(number)) tools.push({ number, comment });
    }

    return {
        lines,
        positions,
        rapid,
        segmentLines,
        lineEnd,
        tools,
        units: state.units ?? 'mm',
        stats,
        warnings: [...warnings],
    };
}

/**
 * Durée lisible d'un temps en minutes : « 12 min », « 1 h 05 », « < 1 min ».
 */
export function formatMinutes(minutes) {
    if (!Number.isFinite(minutes) || minutes <= 0) return null;
    if (minutes < 1) return '< 1 min';
    const total = Math.round(minutes);
    if (total < 60) return `${total} min`;
    const h = Math.floor(total / 60);
    return `${h} h ${String(total % 60).padStart(2, '0')}`;
}
