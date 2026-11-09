import * as THREE from 'three';

/**
 * Parcours d'outil lu par `parseGcode` → objets three.js du lecteur de la GED.
 *
 * Deux LineSegments (rapides en orange, usinage en bleu) dont la plage de
 * dessin suit le curseur de simulation, un tracé de surbrillance pour la ligne
 * choisie, l'outil, le repère de l'origine programme et une grille sous la pièce.
 */

export const FEED_COLOR = 0x1f5fd6;
export const RAPID_COLOR = 0xf28c28;
export const HIGHLIGHT_COLOR = 0xe0245e;

/**
 * Index de lecture par ligne du fichier : nombre de segments rapides /
 * d'usinage produits jusqu'à la ligne i incluse, et premier segment de chaque
 * ligne (les segments sont rangés dans l'ordre du fichier).
 */
export function buildLineIndex(parsed) {
    const count = parsed.lines.length;
    const rapidUpTo = new Int32Array(count);
    const feedUpTo = new Int32Array(count);
    const first = new Int32Array(count + 1).fill(-1);

    let rapid = 0;
    let feed = 0;
    let s = 0;
    const total = parsed.segmentLines.length;

    for (let line = 0; line < count; line++) {
        first[line] = s;
        while (s < total && parsed.segmentLines[s] === line) {
            if (parsed.rapid[s]) rapid++;
            else feed++;
            s++;
        }
        rapidUpTo[line] = rapid;
        feedUpTo[line] = feed;
    }
    first[count] = total;

    return {
        rapidUpTo,
        feedUpTo,
        /** Segments [début, fin) produits par une ligne. */
        segmentsOf(line) {
            if (line < 0 || line >= count) return [0, 0];
            return [first[line], first[line + 1]];
        },
    };
}

function lineSegments(positions, color, opacity = 1) {
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));

    return new THREE.LineSegments(
        geometry,
        new THREE.LineBasicMaterial({ color, transparent: opacity < 1, opacity }),
    );
}

export function gcodeToThree(parsed) {
    const index = buildLineIndex(parsed);
    const rapidPositions = [];
    const feedPositions = [];
    const rapidLines = [];
    const feedLines = [];

    for (let s = 0; s < parsed.rapid.length; s++) {
        const target = parsed.rapid[s] ? rapidPositions : feedPositions;
        for (let k = 0; k < 6; k++) target.push(parsed.positions[s * 6 + k]);
        (parsed.rapid[s] ? rapidLines : feedLines).push(parsed.segmentLines[s]);
    }

    const group = new THREE.Group();
    const feed = lineSegments(feedPositions, FEED_COLOR);
    const rapid = lineSegments(rapidPositions, RAPID_COLOR, 0.8);
    group.add(feed, rapid);

    const box = new THREE.Box3().setFromObject(group);
    const size = box.isEmpty() ? new THREE.Vector3(1, 1, 1) : box.getSize(new THREE.Vector3());
    const diagonal = size.length() || 1;

    // Origine programme (G54…) : souvent ce que l'opérateur cherche en premier.
    const axes = new THREE.AxesHelper(diagonal * 0.15);
    group.add(axes);

    if (!box.isEmpty()) {
        const span = Math.max(size.x, size.y) * 1.3 || 1;
        const grid = new THREE.GridHelper(span, 20, 0x8a94a3, 0xc3c9d2);
        grid.rotation.x = Math.PI / 2;
        grid.position.set((box.min.x + box.max.x) / 2, (box.min.y + box.max.y) / 2, box.min.z);
        grid.material.transparent = true;
        grid.material.opacity = 0.35;
        group.add(grid);
    }

    const highlight = lineSegments([], HIGHLIGHT_COLOR);
    highlight.renderOrder = 2;
    highlight.material.depthTest = false;
    group.add(highlight);

    // Outil : cylindre dont la pointe est au point programmé.
    const toolRadius = diagonal * 0.012;
    const toolHeight = diagonal * 0.12;
    const toolGeometry = new THREE.CylinderGeometry(toolRadius, toolRadius, toolHeight, 24);
    toolGeometry.rotateX(Math.PI / 2);
    toolGeometry.translate(0, 0, toolHeight / 2);
    const tool = new THREE.Mesh(toolGeometry, new THREE.MeshPhongMaterial({ color: 0x4a4f57 }));
    tool.visible = false;
    group.add(tool);

    const api = {
        group,
        diagonal,

        /** Ne dessine que le parcours jusqu'à la ligne donnée (null = tout). */
        showUpTo(line) {
            if (line === null || line === undefined) {
                feed.geometry.setDrawRange(0, Infinity);
                rapid.geometry.setDrawRange(0, Infinity);
                return;
            }
            feed.geometry.setDrawRange(0, line < 0 ? 0 : index.feedUpTo[line] * 2);
            rapid.geometry.setDrawRange(0, line < 0 ? 0 : index.rapidUpTo[line] * 2);
        },

        /** Surligne les segments d'une ligne et y pose l'outil. */
        select(line) {
            const [from, to] = line === null ? [0, 0] : index.segmentsOf(line);
            const positions = parsed.positions.slice(from * 6, to * 6);
            highlight.geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
            highlight.geometry.computeBoundingSphere();

            const end = line === null ? null : parsed.lineEnd[line];
            tool.visible = !!end;
            if (end) tool.position.set(end[0], end[1], end[2]);
        },

        /** Ligne du fichier sous un point du canevas, ou null. */
        pick(raycaster) {
            raycaster.params.Line = { threshold: diagonal * 0.006 };
            const hits = raycaster.intersectObjects([feed, rapid], false);
            if (!hits.length) return null;
            const hit = hits[0];
            const segment = Math.floor(hit.index / 2);
            return (hit.object === feed ? feedLines : rapidLines)[segment] ?? null;
        },
    };

    return api;
}
