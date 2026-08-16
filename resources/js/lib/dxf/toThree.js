import * as THREE from 'three';

/**
 * Converts the tree produced by dxf-parser into three.js line objects.
 *
 * dxf-parser is used for the parsing only — it carries no three.js dependency,
 * so the drawing ends up in the very same scene as the STL and STEP viewers
 * instead of pulling a second copy of three into the bundle.
 */

const ARC_SEGMENTS = 64;
const SPLINE_SEGMENTS = 128;

/** AutoCAD Color Index, restricted to the standard first sixteen slots. */
const ACI = {
    0: 0xffffff, 1: 0xff0000, 2: 0xffff00, 3: 0x00ff00, 4: 0x00ffff,
    5: 0x0000ff, 6: 0xff00ff, 7: 0xffffff, 8: 0x808080, 9: 0xc0c0c0,
};

function colorOf(entity, layers) {
    if (typeof entity.color === 'number') return entity.color;

    const layer = layers?.[entity.layer];

    if (layer) {
        if (typeof layer.color === 'number') return layer.color;
        if (typeof layer.colorIndex === 'number') return ACI[layer.colorIndex] ?? 0xffffff;
    }

    return ACI[entity.colorIndex] ?? 0xffffff;
}

/**
 * AutoCAD draws colour 7 as white on a black background and as black on a white
 * one. The viewer now renders on a light surface, so the same flip is applied
 * here — otherwise every default-coloured entity would be invisible.
 */
function inkFor(color) {
    return color === 0xffffff ? 0x1f2937 : color;
}

const v3 = (point) => new THREE.Vector3(point?.x ?? 0, point?.y ?? 0, point?.z ?? 0);

function arcPoints(rawCenter, radius, startAngle, endAngle, segments = ARC_SEGMENTS) {
    const center = v3(rawCenter);

    let sweep = endAngle - startAngle;
    while (sweep <= 0) sweep += Math.PI * 2;

    const points = [];

    for (let i = 0; i <= segments; i += 1) {
        const angle = startAngle + (sweep * i) / segments;
        points.push(new THREE.Vector3(
            center.x + radius * Math.cos(angle),
            center.y + radius * Math.sin(angle),
            center.z,
        ));
    }

    return points;
}

/**
 * Expand the arc a bulge encodes between two polyline vertices.
 * bulge = tan(includedAngle / 4), signed by the sweep direction.
 */
function bulgePoints(from, to, bulge) {
    const angle = Math.atan(bulge) * 4;

    if (!Number.isFinite(angle) || Math.abs(angle) < 1e-9) return [v3(to)];

    const chord = Math.hypot(to.x - from.x, to.y - from.y);
    const radius = chord / (2 * Math.sin(angle / 2));

    const midX = (from.x + to.x) / 2;
    const midY = (from.y + to.y) / 2;
    const distance = radius * Math.cos(angle / 2);
    const normalX = -(to.y - from.y) / chord;
    const normalY = (to.x - from.x) / chord;

    const center = { x: midX + normalX * distance, y: midY + normalY * distance, z: from.z ?? 0 };

    const startAngle = Math.atan2(from.y - center.y, from.x - center.x);
    const endAngle = startAngle + angle;

    // The sign of the radius has already been consumed when placing the centre,
    // so the sweep itself runs on the absolute radius.
    const sweepRadius = Math.abs(radius);
    const segments = Math.max(8, Math.ceil((Math.abs(angle) / (Math.PI * 2)) * ARC_SEGMENTS));
    const points = [];

    for (let i = 1; i <= segments; i += 1) {
        const a = startAngle + (angle * i) / segments;
        points.push(new THREE.Vector3(
            center.x + sweepRadius * Math.cos(a),
            center.y + sweepRadius * Math.sin(a),
            center.z,
        ));
    }

    return points;
}

function polylinePoints(entity) {
    const vertices = entity.vertices ?? [];
    if (!vertices.length) return [];

    const points = [v3(vertices[0])];

    for (let i = 1; i < vertices.length; i += 1) {
        const previous = vertices[i - 1];
        points.push(...(previous.bulge ? bulgePoints(previous, vertices[i], previous.bulge) : [v3(vertices[i])]));
    }

    if ((entity.shape || entity.closed) && vertices.length > 2) {
        const last = vertices[vertices.length - 1];
        points.push(...(last.bulge ? bulgePoints(last, vertices[0], last.bulge) : [v3(vertices[0])]));
    }

    return points;
}

function ellipsePoints(entity) {
    const center = v3(entity.center);
    const major = v3(entity.majorAxisEndPoint);
    const majorLength = major.length();
    const minorLength = majorLength * (entity.axisRatio ?? 1);
    const rotation = Math.atan2(major.y, major.x);

    const start = entity.startAngle ?? 0;
    const end = entity.endAngle ?? Math.PI * 2;
    let sweep = end - start;
    while (sweep <= 0) sweep += Math.PI * 2;

    const points = [];

    for (let i = 0; i <= ARC_SEGMENTS; i += 1) {
        const parameter = start + (sweep * i) / ARC_SEGMENTS;
        const x = majorLength * Math.cos(parameter);
        const y = minorLength * Math.sin(parameter);

        points.push(new THREE.Vector3(
            center.x + x * Math.cos(rotation) - y * Math.sin(rotation),
            center.y + x * Math.sin(rotation) + y * Math.cos(rotation),
            center.z,
        ));
    }

    return points;
}

async function splinePoints(entity) {
    const control = (entity.controlPoints ?? []).map(v3);

    if (control.length < 2) {
        return (entity.fitPoints ?? []).map(v3);
    }

    const degree = entity.degreeOfSplineCurve ?? 3;
    const knots = entity.knotValues ?? [];

    if (knots.length === control.length + degree + 1) {
        try {
            const { NURBSCurve } = await import('three/examples/jsm/curves/NURBSCurve.js');
            const weighted = control.map((point) => new THREE.Vector4(point.x, point.y, point.z, 1));
            return new NURBSCurve(degree, knots, weighted).getPoints(SPLINE_SEGMENTS);
        } catch {
            // Fall through to the Catmull-Rom approximation below.
        }
    }

    return new THREE.CatmullRomCurve3(control, entity.closed ?? false).getPoints(SPLINE_SEGMENTS);
}

/**
 * Points describing a single entity, or null when the type is not drawable.
 */
async function pointsFor(entity) {
    switch (entity.type) {
        case 'LINE':
            return (entity.vertices ?? []).map(v3);
        case 'LWPOLYLINE':
        case 'POLYLINE':
            return polylinePoints(entity);
        case 'CIRCLE':
            return arcPoints(entity.center, entity.radius, 0, Math.PI * 2);
        case 'ARC':
            return arcPoints(entity.center, entity.radius, entity.startAngle ?? 0, entity.endAngle ?? Math.PI * 2);
        case 'ELLIPSE':
            return ellipsePoints(entity);
        case 'SPLINE':
            return splinePoints(entity);
        case 'SOLID':
        case '3DFACE': {
            const points = (entity.points ?? entity.vertices ?? []).map(v3);
            return points.length ? [...points, points[0]] : [];
        }
        default:
            return null;
    }
}

/**
 * Build the three.js group for a parsed DXF document.
 *
 * @returns {Promise<{object: THREE.Group, stats: {entities: number, layers: number, skipped: string[]}}>}
 */
export async function dxfToThree(dxf) {
    const group = new THREE.Group();
    const layers = dxf?.tables?.layer?.layers ?? {};
    const blocks = dxf?.blocks ?? {};

    // One material per colour, shared by every entity using it.
    const materials = new Map();
    const materialFor = (color) => {
        const ink = inkFor(color);
        if (!materials.has(ink)) {
            materials.set(ink, new THREE.LineBasicMaterial({ color: ink }));
        }
        return materials.get(ink);
    };

    const skipped = new Set();
    let drawn = 0;

    const draw = async (entities, parent) => {
        for (const entity of entities ?? []) {
            if (entity.type === 'INSERT') {
                const block = blocks[entity.name];

                if (!block?.entities) {
                    skipped.add(`INSERT:${entity.name}`);
                    continue;
                }

                const container = new THREE.Group();
                const position = v3(entity.position);
                const base = v3(block.position);

                container.position.set(position.x - base.x, position.y - base.y, position.z - base.z);
                container.scale.set(entity.xScale ?? 1, entity.yScale ?? 1, entity.zScale ?? 1);
                container.rotation.z = ((entity.rotation ?? 0) * Math.PI) / 180;

                parent.add(container);
                await draw(block.entities, container);
                continue;
            }

            const points = await pointsFor(entity);

            if (points === null) {
                skipped.add(entity.type);
                continue;
            }

            if (points.length < 2) continue;

            const geometry = new THREE.BufferGeometry().setFromPoints(points);
            parent.add(new THREE.Line(geometry, materialFor(colorOf(entity, layers))));
            drawn += 1;
        }
    };

    await draw(dxf?.entities, group);

    return {
        object: group,
        stats: {
            entities: drawn,
            layers: Object.keys(layers).length,
            skipped: Array.from(skipped),
        },
    };
}
