// Miniatures des fichiers de la GED, dessinées dans le navigateur.
//
// Le serveur n'a aucun moteur de rendu : la première page qui affiche un plan sans
// miniature la dessine ici, puis la dépose (files.thumbnail_path) pour les
// affichages suivants — écrans, et vignette du PDF « long » qui la relit sur disque.
//
// Chaque moteur (pdf.js, dxf-parser, OpenCascade) n'est chargé qu'au premier
// fichier de son type.

import { renderFileThumbnail, canvasToThumbnail, uploadThumbnail, THUMBNAIL_SIDE } from '../pdf/pdfRender';

/** Trait des dessins au trait, en px de la miniature (≈ 0,6 px une fois réduite à 42 px). */
const INK_WIDTH = 6;
const INK = '#1f2937';

/**
 * Un STEP se lit en entier, dans le fil principal : au-delà, on attend le dépôt
 * (qui a déjà les octets) plutôt que de figer l'écran à l'ouverture d'une liste.
 */
const BREP_AUTO_MAX = 15 * 1024 * 1024;

const DRAWN_KINDS = ['image', 'doc', 'vector', 'cad2d', 'brep'];

/** Deux rendus à la fois au plus : une page de 25 lignes ne lance pas 25 moteurs. */
const queue = [];
let running = 0;
const MAX_RUNNING = 2;

function enqueue(job) {
    return new Promise((resolve, reject) => {
        queue.push({ job, resolve, reject });
        pump();
    });
}

function pump() {
    while (running < MAX_RUNNING && queue.length) {
        const { job, resolve, reject } = queue.shift();
        running++;
        job().then(resolve, reject).finally(() => { running--; pump(); });
    }
}

/** Rendu en cours ou fait, par fichier : un même plan n'est dessiné qu'une fois par page. */
const renders = new Map();

/**
 * Le navigateur sait-il dessiner ce fichier ? `withSource` : les octets sont déjà
 * là (dépôt), aucun plafond de taille.
 */
export function canRenderThumbnail(file, withSource = false) {
    if (!file || !DRAWN_KINDS.includes(file.kind)) return false;
    if (renders.has(file.id)) return true; // déjà lancé (dépôt) : on attend ce rendu
    if (file.kind === 'brep') return withSource || (file.size ?? Infinity) <= BREP_AUTO_MAX;
    return true;
}

/**
 * Miniature PNG (Blob) d'un fichier, déposée sur le serveur au passage.
 * `source` : le fichier local (File / Blob) quand on l'a, pour ne pas le retélécharger.
 */
export function fileThumbnail(file, source = null) {
    if (!renders.has(file.id)) {
        const render = enqueue(() => draw(file, source)).then((blob) => {
            if (blob && file.thumbnail_store_url) {
                uploadThumbnail(file.thumbnail_store_url, blob).catch(() => {});
            }
            return blob;
        });
        renders.set(file.id, render);
    }
    return renders.get(file.id);
}

async function draw(file, source) {
    switch (file.kind) {
        case 'image':
        case 'doc':
            return renderFileThumbnail(file);
        case 'vector':
            return svgThumbnail(await readText(file, source));
        case 'cad2d':
            return dxfThumbnail(await readText(file, source));
        case 'brep':
            return brepThumbnail(file, await readBytes(file, source));
        default:
            return null;
    }
}

async function fetchFile(file) {
    const res = await fetch(file.view_url, { headers: { Accept: '*/*' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res;
}

const readText  = async (file, source) => (source ? source.text() : (await fetchFile(file)).text());
const readBytes = async (file, source) => (source ? source.arrayBuffer() : (await fetchFile(file)).arrayBuffer());

/** Canevas aux proportions d'une boîte de `width` × `height`, plus grand côté = THUMBNAIL_SIDE. */
function canvasFor(width, height) {
    const ratio = width > 0 && height > 0 ? width / height : 1;
    const canvas = document.createElement('canvas');
    canvas.width  = Math.max(1, Math.round(ratio >= 1 ? THUMBNAIL_SIDE : THUMBNAIL_SIDE * ratio));
    canvas.height = Math.max(1, Math.round(ratio >= 1 ? THUMBNAIL_SIDE / ratio : THUMBNAIL_SIDE));
    return canvas;
}

// ---------------------------------------------------------------------------
// SVG (fichier déposé, ou contour généré depuis un .geo)
// ---------------------------------------------------------------------------

/**
 * Le SVG est dessiné comme une image (aucun script n'y est exécuté). Taille fixée
 * d'après le viewBox, et trait épaissi : celui d'un contour est calculé sur la
 * pièce et disparaîtrait une fois la miniature réduite.
 */
export function prepareSvg(text) {
    const doc = new DOMParser().parseFromString(text, 'image/svg+xml');
    const svg = doc.documentElement;
    if (!svg || svg.nodeName !== 'svg' || doc.getElementsByTagName('parsererror').length) {
        throw new Error('SVG illisible');
    }

    const box = (svg.getAttribute('viewBox') ?? '').trim().split(/[\s,]+/).map(Number);
    let width, height;
    if (box.length === 4 && box[2] > 0 && box[3] > 0) {
        [width, height] = [box[2], box[3]];
    } else {
        width  = parseFloat(svg.getAttribute('width'))  || THUMBNAIL_SIDE;
        height = parseFloat(svg.getAttribute('height')) || THUMBNAIL_SIDE;
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    }

    const canvas = canvasFor(width, height);
    svg.setAttribute('width', canvas.width);
    svg.setAttribute('height', canvas.height);

    const style = doc.createElementNS('http://www.w3.org/2000/svg', 'style');
    style.textContent = `* { vector-effect: non-scaling-stroke; stroke-width: ${INK_WIDTH}px !important; }`;
    svg.insertBefore(style, svg.firstChild);

    return { markup: new XMLSerializer().serializeToString(doc), width: canvas.width, height: canvas.height };
}

async function svgThumbnail(text) {
    const { markup, width, height } = prepareSvg(text);
    const url = URL.createObjectURL(new Blob([markup], { type: 'image/svg+xml' }));
    try {
        const img = await new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error('SVG illisible'));
            image.src = url;
        });
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
        return await canvasToThumbnail(canvas);
    } finally {
        URL.revokeObjectURL(url);
    }
}

// ---------------------------------------------------------------------------
// DXF
// ---------------------------------------------------------------------------

/**
 * Polylignes 2D (repère monde) d'un groupe three.js de lignes, tel que le rend
 * dxfToThree : les blocs insérés portent leur propre transformation.
 */
export function flattenLines(object) {
    object.updateMatrixWorld(true);
    const paths = [];
    object.traverse((child) => {
        if (!child.isLine) return;
        const position = child.geometry.getAttribute('position');
        const e = child.matrixWorld.elements;
        const points = [];
        for (let i = 0; i < position.count; i++) {
            const x = position.getX(i), y = position.getY(i), z = position.getZ(i);
            points.push([e[0] * x + e[4] * y + e[8] * z + e[12], e[1] * x + e[5] * y + e[9] * z + e[13]]);
        }
        if (points.length > 1) paths.push(points);
    });
    return paths;
}

/**
 * Trace des polylignes centrées dans un canevas, axe Y vers le haut comme en CAO.
 * Une seule encre : les couleurs de calque (jaune, cyan…) ne se lisent pas sur fond blanc.
 */
export function drawPaths(paths) {
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    for (const path of paths) {
        for (const [x, y] of path) {
            if (x < minX) minX = x;
            if (x > maxX) maxX = x;
            if (y < minY) minY = y;
            if (y > maxY) maxY = y;
        }
    }
    if (!Number.isFinite(minX)) return null;

    const width = maxX - minX, height = maxY - minY;
    const canvas = canvasFor(width || height || 1, height || width || 1);
    const margin = INK_WIDTH * 2;
    const scale = Math.min(
        (canvas.width - 2 * margin) / (width || 1),
        (canvas.height - 2 * margin) / (height || 1),
    );
    const offsetX = (canvas.width - width * scale) / 2;
    const offsetY = (canvas.height - height * scale) / 2;

    const ctx = canvas.getContext('2d');
    ctx.strokeStyle = INK;
    ctx.lineWidth = INK_WIDTH;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    for (const path of paths) {
        path.forEach(([x, y], i) => {
            const px = offsetX + (x - minX) * scale;
            const py = canvas.height - (offsetY + (y - minY) * scale);
            if (i === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
        });
    }
    ctx.stroke();

    return canvas;
}

async function dxfThumbnail(text) {
    const [{ default: DxfParser }, { dxfToThree }] = await Promise.all([
        import('dxf-parser'),
        import('../dxf/toThree.js'),
    ]);
    const { object } = await dxfToThree(new DxfParser().parseSync(text));
    const canvas = drawPaths(flattenLines(object));
    if (!canvas) throw new Error('DXF sans entité dessinable');
    return canvasToThumbnail(canvas);
}

// ---------------------------------------------------------------------------
// STEP / IGES / BREP
// ---------------------------------------------------------------------------

async function brepThumbnail(file, bytes) {
    const [{ loadOcct, readerFor, toMesh }, { THREE }] = await Promise.all([
        import('../brep/occt.js'),
        import('../../components/files/viewers/scene.js'),
    ]);

    const occt = await loadOcct();
    const result = readerFor(occt, (file.extension ?? '').toLowerCase())(new Uint8Array(bytes));
    if (!result?.success || !result.meshes?.length) throw new Error('Fichier B-Rep illisible');

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xffffff);
    scene.add(new THREE.AmbientLight(0xffffff, 0.85));
    const key = new THREE.DirectionalLight(0xffffff, 1.5);
    key.position.set(1, -1, 2);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.55);
    fill.position.set(-1, 0.5, -1);
    scene.add(fill);

    // Arêtes vives soulignées : à 42 px, une pièce grise sans contour se perd.
    const group = new THREE.Group();
    const edgeMaterial = new THREE.LineBasicMaterial({ color: 0x374151 });
    result.meshes.forEach((descriptor) => {
        const mesh = toMesh(descriptor);
        group.add(mesh);
        group.add(new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry, 30), edgeMaterial));
    });
    scene.add(group);

    const box = new THREE.Box3().setFromObject(group);
    const center = box.getCenter(new THREE.Vector3());
    group.position.sub(center);
    const radius = box.getSize(new THREE.Vector3()).length() / 2 || 1;

    // Vue isométrique, Z vers le haut comme dans les logiciels de CAO.
    const camera = new THREE.PerspectiveCamera(30, 1, radius / 100, radius * 100);
    camera.up.set(0, 0, 1);
    camera.position.copy(new THREE.Vector3(1, -1, 0.8).normalize().multiplyScalar(radius / Math.sin((15 * Math.PI) / 180)));
    camera.lookAt(0, 0, 0);

    const canvas = document.createElement('canvas');
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, preserveDrawingBuffer: true });
    try {
        renderer.setSize(THUMBNAIL_SIDE, THUMBNAIL_SIDE, false);
        renderer.render(scene, camera);
        return await canvasToThumbnail(canvas);
    } finally {
        scene.traverse((child) => {
            child.geometry?.dispose?.();
            child.material?.dispose?.();
        });
        renderer.dispose();
        renderer.forceContextLoss();
    }
}
