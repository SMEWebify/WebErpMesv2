/**
 * Compute a bounding box (in millimetres) from a DXF or SVG file.
 *
 * The nesting page uses this only as a fallback when the order line does not
 * carry x_size / y_size — the returned box is the axis-aligned envelope of the
 * shape, not its true contour, which is exactly what a bounding-box shelf
 * packer consumes anyway.
 */

const cache = new Map();

export async function boundingBoxFor(file) {
    if (!file?.file_url) return null;
    if (cache.has(file.file_id)) return cache.get(file.file_id);

    const promise = compute(file).catch(err => {
        console.warn('BB parse failed', file, err);
        return null;
    });

    cache.set(file.file_id, promise);
    return promise;
}

async function compute(file) {
    const kind = file.file_kind || guessKind(file.file_ext);

    if (kind === 'cad2d') return dxfBBox(file.file_url);
    if (kind === 'vector') return svgBBox(file.file_url);
    return null;
}

function guessKind(ext) {
    const e = (ext || '').toLowerCase();
    if (e === 'dxf') return 'cad2d';
    if (e === 'svg') return 'vector';
    return null;
}

async function dxfBBox(url) {
    const [{ default: DxfParser }, response] = await Promise.all([
        import('dxf-parser'),
        fetch(url, { credentials: 'same-origin' }),
    ]);
    if (!response.ok) return null;

    const text = await response.text();
    const parsed = new DxfParser().parseSync(text);
    if (!parsed) return null;

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

    const grow = (x, y) => {
        if (typeof x !== 'number' || typeof y !== 'number') return;
        if (x < minX) minX = x;
        if (y < minY) minY = y;
        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
    };

    for (const e of parsed.entities || []) {
        switch (e.type) {
            case 'LINE':
                grow(e.vertices?.[0]?.x, e.vertices?.[0]?.y);
                grow(e.vertices?.[1]?.x, e.vertices?.[1]?.y);
                break;
            case 'LWPOLYLINE':
            case 'POLYLINE':
                for (const v of e.vertices || []) grow(v.x, v.y);
                break;
            case 'CIRCLE':
                grow(e.center?.x - e.radius, e.center?.y - e.radius);
                grow(e.center?.x + e.radius, e.center?.y + e.radius);
                break;
            case 'ARC': {
                const cx = e.center?.x ?? 0, cy = e.center?.y ?? 0, r = e.radius ?? 0;
                grow(cx - r, cy - r);
                grow(cx + r, cy + r);
                break;
            }
            case 'ELLIPSE':
                grow(e.center?.x - (e.majorAxisEndPoint?.x ?? 0), e.center?.y - (e.majorAxisEndPoint?.y ?? 0));
                grow(e.center?.x + (e.majorAxisEndPoint?.x ?? 0), e.center?.y + (e.majorAxisEndPoint?.y ?? 0));
                break;
            case 'POINT':
                grow(e.position?.x, e.position?.y);
                break;
            case 'SPLINE':
                for (const p of e.controlPoints || []) grow(p.x, p.y);
                break;
            default:
                // TEXT, INSERT, HATCH, DIMENSION... skipped: irrelevant for a cutting envelope.
                break;
        }
    }

    if (!isFinite(minX)) return null;

    return {
        x: Math.max(1, maxX - minX),
        y: Math.max(1, maxY - minY),
        source: 'dxf',
    };
}

async function svgBBox(url) {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (!response.ok) return null;

    const text = await response.text();
    const doc = new DOMParser().parseFromString(text, 'image/svg+xml');
    const svg = doc.querySelector('svg');
    if (!svg) return null;

    const viewBox = svg.getAttribute('viewBox');
    if (viewBox) {
        const [, , w, h] = viewBox.split(/[\s,]+/).map(Number);
        if (w > 0 && h > 0) return { x: w, y: h, source: 'svg-viewbox' };
    }

    const w = parseFloat(svg.getAttribute('width'));
    const h = parseFloat(svg.getAttribute('height'));
    if (w > 0 && h > 0) return { x: w, y: h, source: 'svg-attr' };

    // Last resort: mount off-screen and use getBBox()
    const host = document.createElement('div');
    host.style.cssText = 'position:absolute;left:-99999px;top:0;visibility:hidden;';
    host.appendChild(svg);
    document.body.appendChild(host);
    try {
        const box = svg.getBBox();
        if (box.width > 0 && box.height > 0) {
            return { x: box.width, y: box.height, source: 'svg-getbbox' };
        }
    } finally {
        host.remove();
    }

    return null;
}
