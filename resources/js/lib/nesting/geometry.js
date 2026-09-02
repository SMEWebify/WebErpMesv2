/**
 * Extract a normalized 2D geometry (bounding box + drawable primitives) from
 * a DXF or SVG file, so the nesting page can render the real cut contour on
 * top of its shelf-packing rectangle.
 *
 * The packing algorithm still runs on the bounding box only — this helper is
 * purely cosmetic and does not influence how many sheets are needed.
 */

const cache = new Map();

const DEBUG = true;
const tag = file => `[nest#${file.file_id ?? '?'} ${file.file_ext ?? '?'}]`;

/**
 * @returns {Promise<{
 *   bb: { x: number, y: number },
 *   minX: number,
 *   minY: number,
 *   primitives: Array,
 *   source: 'svg' | 'dxf'
 * } | null>}
 */
export async function geometryFor(file) {
    if (!file?.file_url) {
        if (DEBUG) console.debug('[nest] skip — no file_url', file);
        return null;
    }
    if (cache.has(file.file_id)) return cache.get(file.file_id);

    if (DEBUG) console.groupCollapsed(`${tag(file)} resolve geometry`);
    const promise = compute(file)
        .then(geo => {
            if (DEBUG) {
                if (!geo) console.warn(`${tag(file)} geometry = null (fallback bb line)`);
                else console.log(`${tag(file)} OK`, {
                    source: geo.source,
                    bb: geo.bb,
                    minX: geo.minX,
                    minY: geo.minY,
                    primitives: geo.primitives.length,
                });
                console.groupEnd();
            }
            return geo;
        })
        .catch(err => {
            if (DEBUG) {
                console.warn(`${tag(file)} PARSE THREW`, err);
                console.groupEnd();
            }
            return null;
        });

    cache.set(file.file_id, promise);
    return promise;
}

async function compute(file) {
    const kind = file.file_kind || guessKind(file.file_ext);
    if (DEBUG) console.log(`${tag(file)} kind=${kind} url=${file.file_url}`);
    if (kind === 'cad2d') return dxfGeometry(file.file_url, file);
    if (kind === 'vector') return svgGeometry(file.file_url, file);
    if (DEBUG) console.warn(`${tag(file)} unsupported kind`, kind);
    return null;
}

function guessKind(ext) {
    const e = (ext || '').toLowerCase();
    if (e === 'dxf') return 'cad2d';
    if (e === 'svg') return 'vector';
    return null;
}

// ─── DXF ────────────────────────────────────────────────────────────────────
async function dxfGeometry(url, file = {}) {
    const [{ default: DxfParser }, response] = await Promise.all([
        import('dxf-parser'),
        fetch(url, { credentials: 'same-origin' }),
    ]);
    if (DEBUG) console.log(`${tag(file)} fetch DXF status=${response.status} type=${response.headers.get('content-type')}`);
    if (!response.ok) return null;

    const raw = await response.text();
    if (DEBUG) console.log(`${tag(file)} DXF bytes=${raw.length} head=${raw.slice(0, 40).replace(/\s+/g, ' ')}`);
    const parsed = new DxfParser().parseSync(raw);
    if (!parsed) {
        if (DEBUG) console.warn(`${tag(file)} DxfParser returned null`);
        return null;
    }

    if (DEBUG) {
        const counts = {};
        for (const e of parsed.entities || []) counts[e.type] = (counts[e.type] || 0) + 1;
        const blocks = Object.keys(parsed.blocks || {});
        console.log(`${tag(file)} entities`, counts, `blocks=${blocks.length}`, blocks);
    }

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    const primitives = [];
    const skipped = {};

    const grow = (x, y) => {
        if (typeof x !== 'number' || typeof y !== 'number') return;
        if (x < minX) minX = x;
        if (y < minY) minY = y;
        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
    };

    extractPrimitives(parsed.entities || [], parsed.blocks || {}, IDENTITY, primitives, grow, skipped);

    if (DEBUG) console.log(`${tag(file)} DXF primitives=${primitives.length} skipped`, skipped, `bb=${isFinite(minX) ? `${(maxX-minX).toFixed(1)}x${(maxY-minY).toFixed(1)}` : 'unset'}`);

    if (!isFinite(minX) || primitives.length === 0) return null;

    // Rebuild closed contours from disconnected LINE segments (CAM exports often
    // ship the outer profile as ~100 individual lines). This is what makes the
    // fill-rule='evenodd' path render "material vs hole" correctly.
    const { loops, openLines } = reconstructLoops(primitives);
    const pathD = shapeToPath(primitives, loops);

    if (DEBUG) console.log(`${tag(file)} loops=${loops.length} openLines=${openLines.length}`);

    return {
        bb: { x: Math.max(1, maxX - minX), y: Math.max(1, maxY - minY) },
        minX, minY,
        primitives,
        loops,
        openLines,
        pathD,
        source: 'dxf',
    };
}

// ─── DXF entity traversal ───────────────────────────────────────────────────
// The section ENTITIES of a DXF exported by a CAM (SolidWorks, Fusion, Vero
// HxGN…) frequently contains a single INSERT pointing to a block that holds
// the actual cut geometry. We recurse into blocks so those exports do not
// silently degrade to a fallback rectangle.

const IDENTITY = { tx: 0, ty: 0, rot: 0, sx: 1, sy: 1 };
const MAX_INSERT_DEPTH = 5;

// Layers used by CAD/sheet-metal software for bend axes and mould markers.
// They are not cutting paths and would break outer-loop reconstruction
// (extra segments creating false junctions). Match case-insensitively.
const NON_CUT_LAYER_PATTERN = /^(bend|mbend|pli|fold|axis|centerline|hem|form|dimension|dim|text|note)/i;

function transformPoint(x, y, t) {
    const cos = Math.cos(t.rot);
    const sin = Math.sin(t.rot);
    const sx = x * t.sx;
    const sy = y * t.sy;
    return { x: t.tx + sx * cos - sy * sin, y: t.ty + sx * sin + sy * cos };
}

// Compose (parent ∘ child): first apply child transform in its local frame,
// then parent transform on top.
function composeTransform(parent, child) {
    const cos = Math.cos(parent.rot);
    const sin = Math.sin(parent.rot);
    const cx = child.tx * parent.sx;
    const cy = child.ty * parent.sy;
    return {
        tx: parent.tx + cx * cos - cy * sin,
        ty: parent.ty + cx * sin + cy * cos,
        rot: parent.rot + child.rot,
        sx: parent.sx * child.sx,
        sy: parent.sy * child.sy,
    };
}

function extractPrimitives(entities, blocks, transform, out, grow, skipped, depth = 0) {
    const isotropic = (Math.abs(transform.sx) + Math.abs(transform.sy)) / 2;

    for (const e of entities) {
        if (e.layer && NON_CUT_LAYER_PATTERN.test(e.layer)) {
            skipped[`layer:${e.layer}`] = (skipped[`layer:${e.layer}`] || 0) + 1;
            continue;
        }
        switch (e.type) {
            case 'LINE': {
                const a = e.vertices?.[0], b = e.vertices?.[1];
                if (!a || !b) break;
                const pa = transformPoint(a.x, a.y, transform);
                const pb = transformPoint(b.x, b.y, transform);
                grow(pa.x, pa.y); grow(pb.x, pb.y);
                out.push({ kind: 'line', x1: pa.x, y1: pa.y, x2: pb.x, y2: pb.y });
                break;
            }
            case 'LWPOLYLINE':
            case 'POLYLINE': {
                const pts = (e.vertices || []).map(v => transformPoint(v.x, v.y, transform));
                pts.forEach(p => grow(p.x, p.y));
                if (pts.length >= 2) out.push({ kind: 'polyline', points: pts, closed: !!e.shape });
                break;
            }
            case 'CIRCLE': {
                const { center: c, radius: r } = e;
                if (!c || !r) break;
                const p = transformPoint(c.x, c.y, transform);
                const rs = r * isotropic;
                grow(p.x - rs, p.y - rs); grow(p.x + rs, p.y + rs);
                out.push({ kind: 'circle', cx: p.x, cy: p.y, r: rs });
                break;
            }
            case 'ARC': {
                const { center: c, radius: r, startAngle, endAngle } = e;
                if (!c || !r) break;
                const p = transformPoint(c.x, c.y, transform);
                const rs = r * isotropic;
                grow(p.x - rs, p.y - rs); grow(p.x + rs, p.y + rs);
                out.push({ kind: 'arc', cx: p.x, cy: p.y, r: rs, a0: startAngle + transform.rot, a1: endAngle + transform.rot });
                break;
            }
            case 'SPLINE': {
                const pts = (e.controlPoints || []).map(v => transformPoint(v.x, v.y, transform));
                pts.forEach(p => grow(p.x, p.y));
                if (pts.length >= 2) out.push({ kind: 'polyline', points: pts });
                break;
            }
            case 'INSERT': {
                if (depth >= MAX_INSERT_DEPTH) { skipped['INSERT (max depth)'] = (skipped['INSERT (max depth)'] || 0) + 1; break; }
                const block = e.name ? blocks?.[e.name] : null;
                if (!block?.entities?.length) { skipped['INSERT (empty block)'] = (skipped['INSERT (empty block)'] || 0) + 1; break; }
                const child = composeTransform(transform, {
                    tx:  e.position?.x || 0,
                    ty:  e.position?.y || 0,
                    rot: ((e.rotation || 0) * Math.PI) / 180,
                    sx:  e.xScale ?? 1,
                    sy:  e.yScale ?? 1,
                });
                extractPrimitives(block.entities, blocks, child, out, grow, skipped, depth + 1);
                break;
            }
            default:
                skipped[e.type] = (skipped[e.type] || 0) + 1;
                break;
        }
    }
}

// ─── Loop reconstruction ────────────────────────────────────────────────────
// LINE-based contours are reconnected by matching endpoints (quantized to
// EPSILON to tolerate DXF float noise). A closed chain becomes a "loop"; an
// unclosed one falls back to open line strokes. Junctions with >2 neighbours
// pick the first available segment — good enough for standard tooling paths,
// suboptimal on true multi-branch drawings.

const EPSILON = 0.01; // mm

function reconstructLoops(primitives) {
    const segments = [];
    for (const p of primitives) {
        if (p.kind === 'line') {
            segments.push({ a: { x: p.x1, y: p.y1 }, b: { x: p.x2, y: p.y2 } });
        }
    }
    if (!segments.length) return { loops: [], openLines: [] };

    const key = (x, y) => `${Math.round(x / EPSILON)}|${Math.round(y / EPSILON)}`;
    const adjacency = new Map();

    segments.forEach((seg, idx) => {
        const ka = key(seg.a.x, seg.a.y);
        const kb = key(seg.b.x, seg.b.y);
        if (!adjacency.has(ka)) adjacency.set(ka, []);
        if (!adjacency.has(kb)) adjacency.set(kb, []);
        adjacency.get(ka).push({ segIdx: idx, endPoint: seg.b, endKey: kb });
        adjacency.get(kb).push({ segIdx: idx, endPoint: seg.a, endKey: ka });
    });

    const used = new Set();
    const loops = [];
    const openLines = [];

    for (let i = 0; i < segments.length; i++) {
        if (used.has(i)) continue;

        const startPoint = segments[i].a;
        const startKey = key(startPoint.x, startPoint.y);
        const chain = [startPoint, segments[i].b];
        let currentKey = key(segments[i].b.x, segments[i].b.y);
        used.add(i);

        while (currentKey !== startKey) {
            const neighbours = adjacency.get(currentKey) || [];
            const next = neighbours.find(n => !used.has(n.segIdx));
            if (!next) break;
            chain.push(next.endPoint);
            used.add(next.segIdx);
            currentKey = next.endKey;
        }

        if (currentKey === startKey && chain.length >= 3) {
            loops.push({ points: chain });
        } else {
            for (let k = 0; k < chain.length - 1; k++) {
                openLines.push({
                    kind: 'line',
                    x1: chain[k].x, y1: chain[k].y,
                    x2: chain[k + 1].x, y2: chain[k + 1].y,
                });
            }
        }
    }

    return { loops, openLines };
}

function shapeToPath(primitives, loops) {
    const parts = [];
    const push = (pts) => {
        if (pts.length < 3) return;
        parts.push(`M${pts[0].x.toFixed(3)} ${pts[0].y.toFixed(3)}`);
        for (let i = 1; i < pts.length; i++) parts.push(`L${pts[i].x.toFixed(3)} ${pts[i].y.toFixed(3)}`);
        parts.push('Z');
    };

    for (const loop of loops) push(loop.points);

    for (const p of primitives) {
        if (p.kind === 'polyline' && p.closed) push(p.points);
        else if (p.kind === 'circle') {
            // Circle as two 180° arcs.
            parts.push(`M${(p.cx - p.r).toFixed(3)} ${p.cy.toFixed(3)}`);
            parts.push(`A${p.r} ${p.r} 0 1 0 ${(p.cx + p.r).toFixed(3)} ${p.cy.toFixed(3)}`);
            parts.push(`A${p.r} ${p.r} 0 1 0 ${(p.cx - p.r).toFixed(3)} ${p.cy.toFixed(3)}`);
            parts.push('Z');
        }
    }

    return parts.join(' ');
}

// ─── SVG ────────────────────────────────────────────────────────────────────
async function svgGeometry(url, file = {}) {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (DEBUG) console.log(`${tag(file)} fetch SVG status=${response.status}`);
    if (!response.ok) return null;

    const text = await response.text();
    if (DEBUG) console.log(`${tag(file)} SVG bytes=${text.length}`);
    const doc = new DOMParser().parseFromString(text, 'image/svg+xml');
    const svg = doc.querySelector('svg');
    if (!svg) {
        if (DEBUG) console.warn(`${tag(file)} no <svg> root`);
        return null;
    }

    const viewBox = svg.getAttribute('viewBox');
    let vbX = 0, vbY = 0, vbW = 0, vbH = 0;

    if (viewBox) {
        [vbX, vbY, vbW, vbH] = viewBox.split(/[\s,]+/).map(Number);
    } else {
        vbW = parseFloat(svg.getAttribute('width')) || 100;
        vbH = parseFloat(svg.getAttribute('height')) || 100;
    }

    if (DEBUG) console.log(`${tag(file)} SVG viewBox=${vbX} ${vbY} ${vbW} ${vbH}`);
    if (!(vbW > 0 && vbH > 0)) {
        if (DEBUG) console.warn(`${tag(file)} SVG has no usable size`);
        return null;
    }

    // Aggregate every closed primitive of the SVG into one `d`. Rendered with
    // fill-rule=evenodd, that path shows material vs holes correctly regardless
    // of what colours the SVG originally shipped with.
    const { pathD, openLines, counts } = svgToPathD(svg);

    if (DEBUG) console.log(`${tag(file)} SVG shapes`, counts, `openLines=${openLines.length}`);

    if (!pathD && openLines.length === 0) {
        if (DEBUG) console.warn(`${tag(file)} SVG has no drawable content`);
        return null;
    }

    return {
        bb: { x: vbW, y: vbH },
        minX: vbX,
        minY: vbY,
        primitives: [],
        loops: [],
        openLines,
        pathD,
        source: 'svg',
    };
}

// Convert a parsed <svg> root into an aggregated fill path + open strokes.
function svgToPathD(svg) {
    const parts = [];
    const openLines = [];
    const counts = {};
    const bump = (k) => { counts[k] = (counts[k] || 0) + 1; };
    const num = (v, d = 0) => { const n = parseFloat(v); return Number.isFinite(n) ? n : d; };

    svg.querySelectorAll('path').forEach(el => {
        const d = el.getAttribute('d');
        if (d) { parts.push(d); bump('path'); }
    });

    svg.querySelectorAll('polygon').forEach(el => {
        const coords = (el.getAttribute('points') || '').trim().split(/[\s,]+/).map(Number);
        if (coords.length < 6) return;
        parts.push(`M${coords[0]} ${coords[1]}`);
        for (let i = 2; i < coords.length; i += 2) parts.push(`L${coords[i]} ${coords[i + 1]}`);
        parts.push('Z');
        bump('polygon');
    });

    svg.querySelectorAll('rect').forEach(el => {
        const x = num(el.getAttribute('x'));
        const y = num(el.getAttribute('y'));
        const w = num(el.getAttribute('width'));
        const h = num(el.getAttribute('height'));
        if (w <= 0 || h <= 0) return;
        parts.push(`M${x} ${y} L${x + w} ${y} L${x + w} ${y + h} L${x} ${y + h} Z`);
        bump('rect');
    });

    svg.querySelectorAll('circle').forEach(el => {
        const cx = num(el.getAttribute('cx'));
        const cy = num(el.getAttribute('cy'));
        const r  = num(el.getAttribute('r'));
        if (r <= 0) return;
        parts.push(`M${cx - r} ${cy} A${r} ${r} 0 1 0 ${cx + r} ${cy} A${r} ${r} 0 1 0 ${cx - r} ${cy} Z`);
        bump('circle');
    });

    svg.querySelectorAll('ellipse').forEach(el => {
        const cx = num(el.getAttribute('cx'));
        const cy = num(el.getAttribute('cy'));
        const rx = num(el.getAttribute('rx'));
        const ry = num(el.getAttribute('ry'));
        if (rx <= 0 || ry <= 0) return;
        parts.push(`M${cx - rx} ${cy} A${rx} ${ry} 0 1 0 ${cx + rx} ${cy} A${rx} ${ry} 0 1 0 ${cx - rx} ${cy} Z`);
        bump('ellipse');
    });

    svg.querySelectorAll('polyline').forEach(el => {
        const coords = (el.getAttribute('points') || '').trim().split(/[\s,]+/).map(Number);
        if (coords.length < 4) return;
        for (let i = 0; i < coords.length - 2; i += 2) {
            openLines.push({ kind: 'line', x1: coords[i], y1: coords[i + 1], x2: coords[i + 2], y2: coords[i + 3] });
        }
        bump('polyline');
    });

    svg.querySelectorAll('line').forEach(el => {
        openLines.push({
            kind: 'line',
            x1: num(el.getAttribute('x1')), y1: num(el.getAttribute('y1')),
            x2: num(el.getAttribute('x2')), y2: num(el.getAttribute('y2')),
        });
        bump('line');
    });

    return { pathD: parts.join(' '), openLines, counts };
}
