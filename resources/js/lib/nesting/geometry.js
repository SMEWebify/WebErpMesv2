/**
 * Extract a normalized 2D geometry (bounding box + drawable primitives) from
 * a DXF or SVG file, so the nesting page can render the real cut contour on
 * top of its shelf-packing rectangle.
 *
 * The packing algorithm still runs on the bounding box only — this helper is
 * purely cosmetic and does not influence how many sheets are needed.
 */

const cache = new Map();

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
    if (!file?.file_url) return null;
    if (cache.has(file.file_id)) return cache.get(file.file_id);

    const promise = compute(file).catch(err => {
        console.warn('Geometry parse failed', file, err);
        return null;
    });

    cache.set(file.file_id, promise);
    return promise;
}

async function compute(file) {
    const kind = file.file_kind || guessKind(file.file_ext);
    if (kind === 'cad2d') return dxfGeometry(file.file_url);
    if (kind === 'vector') return svgGeometry(file.file_url);
    return null;
}

function guessKind(ext) {
    const e = (ext || '').toLowerCase();
    if (e === 'dxf') return 'cad2d';
    if (e === 'svg') return 'vector';
    return null;
}

// ─── DXF ────────────────────────────────────────────────────────────────────
async function dxfGeometry(url) {
    const [{ default: DxfParser }, response] = await Promise.all([
        import('dxf-parser'),
        fetch(url, { credentials: 'same-origin' }),
    ]);
    if (!response.ok) return null;

    const parsed = new DxfParser().parseSync(await response.text());
    if (!parsed) return null;

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    const primitives = [];

    const grow = (x, y) => {
        if (typeof x !== 'number' || typeof y !== 'number') return;
        if (x < minX) minX = x;
        if (y < minY) minY = y;
        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
    };

    for (const e of parsed.entities || []) {
        switch (e.type) {
            case 'LINE': {
                const a = e.vertices?.[0], b = e.vertices?.[1];
                if (!a || !b) break;
                grow(a.x, a.y); grow(b.x, b.y);
                primitives.push({ kind: 'line', x1: a.x, y1: a.y, x2: b.x, y2: b.y });
                break;
            }
            case 'LWPOLYLINE':
            case 'POLYLINE': {
                const pts = (e.vertices || []).map(v => ({ x: v.x, y: v.y }));
                pts.forEach(p => grow(p.x, p.y));
                if (pts.length >= 2) primitives.push({ kind: 'polyline', points: pts, closed: !!e.shape });
                break;
            }
            case 'CIRCLE': {
                const { center: c, radius: r } = e;
                if (!c || !r) break;
                grow(c.x - r, c.y - r); grow(c.x + r, c.y + r);
                primitives.push({ kind: 'circle', cx: c.x, cy: c.y, r });
                break;
            }
            case 'ARC': {
                const { center: c, radius: r, startAngle, endAngle } = e;
                if (!c || !r) break;
                grow(c.x - r, c.y - r); grow(c.x + r, c.y + r);
                primitives.push({ kind: 'arc', cx: c.x, cy: c.y, r, a0: startAngle, a1: endAngle });
                break;
            }
            case 'SPLINE': {
                const pts = (e.controlPoints || []).map(v => ({ x: v.x, y: v.y }));
                pts.forEach(p => grow(p.x, p.y));
                if (pts.length >= 2) primitives.push({ kind: 'polyline', points: pts });
                break;
            }
            default:
                // TEXT / INSERT / HATCH... ignored — irrelevant for a cutting outline.
                break;
        }
    }

    if (!isFinite(minX) || primitives.length === 0) return null;

    return {
        bb: { x: Math.max(1, maxX - minX), y: Math.max(1, maxY - minY) },
        minX, minY,
        primitives,
        source: 'dxf',
    };
}

// ─── SVG ────────────────────────────────────────────────────────────────────
async function svgGeometry(url) {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (!response.ok) return null;

    const text = await response.text();
    const doc = new DOMParser().parseFromString(text, 'image/svg+xml');
    const svg = doc.querySelector('svg');
    if (!svg) return null;

    const viewBox = svg.getAttribute('viewBox');
    let vbX = 0, vbY = 0, vbW = 0, vbH = 0;

    if (viewBox) {
        [vbX, vbY, vbW, vbH] = viewBox.split(/[\s,]+/).map(Number);
    } else {
        vbW = parseFloat(svg.getAttribute('width')) || 100;
        vbH = parseFloat(svg.getAttribute('height')) || 100;
    }

    if (!(vbW > 0 && vbH > 0)) return null;

    // Serialize inner content so we can re-inject it inside another <svg>
    const inner = Array.from(svg.childNodes)
        .map(node => node.nodeType === 1 ? node.outerHTML : '')
        .join('');

    return {
        bb: { x: vbW, y: vbH },
        minX: vbX,
        minY: vbY,
        primitives: [{ kind: 'svg-inner', html: inner }],
        source: 'svg',
    };
}
