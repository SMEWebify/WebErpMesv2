// Rendu de plans (PDF ou image) sur un canevas, pour le bulleur et les miniatures.
//
// pdf.js (≈ 1 Mo + worker) n'est chargé qu'au premier appel : les autres écrans
// n'en paient rien. Le lecteur PDF de la GED (PdfViewer) reste le lecteur du
// navigateur ; pdf.js ne sert qu'aux écrans qui dessinent par-dessus le plan.

import { csrfToken } from '../http';

let pdfjsPromise = null;

export function loadPdfjs() {
    if (!pdfjsPromise) {
        pdfjsPromise = Promise.all([
            import('pdfjs-dist'),
            import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
        ]).then(([pdfjs, worker]) => {
            pdfjs.GlobalWorkerOptions.workerSrc = worker.default;
            return pdfjs;
        });
    }
    return pdfjsPromise;
}

const quarter = rotation => (((rotation % 360) + 360) % 360) / 90;

/** Dimensions d'une page une fois tournée de `rotation` degrés (sens horaire). */
function rotatedSize(width, height, rotation) {
    return quarter(rotation) % 2 ? { width: height, height: width } : { width, height };
}

/**
 * Ouvre un plan. Renvoie un objet commun aux PDF et aux images :
 * { pageCount, getPage(n) → { width, height, size(rotation), render(canvas, scale, rotation) }, destroy() }.
 * width / height : page à l'échelle 1, sans rotation ajoutée ; size(rotation) : une fois tournée.
 */
export async function openPlan(file) {
    if (file.kind === 'image') {
        const img = await loadImage(file.view_url);
        const w = img.naturalWidth, h = img.naturalHeight;
        return {
            pageCount: 1,
            async getPage() {
                return {
                    width: w,
                    height: h,
                    size: rotation => rotatedSize(w, h, rotation),
                    async textIn() { return ''; }, // une image n'a pas de texte : lecture IA
                    async render(canvas, scale, rotation = 0) {
                        const out = rotatedSize(w, h, rotation);
                        canvas.width  = Math.round(out.width * scale);
                        canvas.height = Math.round(out.height * scale);
                        const ctx = canvas.getContext('2d');
                        ctx.save();
                        ctx.translate(canvas.width / 2, canvas.height / 2);
                        ctx.rotate(quarter(rotation) * Math.PI / 2);
                        ctx.drawImage(img, -w * scale / 2, -h * scale / 2, w * scale, h * scale);
                        ctx.restore();
                    },
                };
            },
            destroy() {},
        };
    }

    const pdfjs = await loadPdfjs();
    const doc = await pdfjs.getDocument({ url: file.view_url, withCredentials: true }).promise;

    return {
        pageCount: doc.numPages,
        async getPage(number) {
            const page = await doc.getPage(number);
            const base = page.getViewport({ scale: 1 });
            // La rotation demandée s'ajoute à celle que le PDF déclare (/Rotate).
            const absolute = rotation => (page.rotate + rotation) % 360;
            let task = null;
            return {
                width:  base.width,
                height: base.height,
                size: rotation => rotatedSize(base.width, base.height, rotation),
                /**
                 * Texte du PDF dont l'ancrage tombe dans `box` (repère de la page,
                 * proportions 0–1), lu de gauche à droite. Vide quand le plan est
                 * exporté avec des textes vectorisés (traits) : l'IA prend le relais.
                 */
                async textIn(box, margin = 0.01) {
                    const content = await page.getTextContent();
                    const x0 = box.box_x - margin, y0 = box.box_y - margin;
                    const x1 = box.box_x + box.box_w + margin, y1 = box.box_y + box.box_h + margin;
                    const inside = (px, py) => px >= x0 && px <= x1 && py >= y0 && py <= y1;
                    const hits = [];
                    for (const item of content.items) {
                        if (!item.str || !item.str.trim()) continue;
                        const [, , , , e, f] = item.transform;
                        const [sx, sy] = base.convertToViewportPoint(e, f);
                        const end = base.convertToViewportPoint(e + item.width * Math.cos(Math.atan2(item.transform[1], item.transform[0])),
                                                                 f + item.width * Math.sin(Math.atan2(item.transform[1], item.transform[0])));
                        const ax = sx / base.width, ay = sy / base.height;
                        const bx = end[0] / base.width, by = end[1] / base.height;
                        if (inside(ax, ay) || inside(bx, by) || inside((ax + bx) / 2, (ay + by) / 2)) {
                            hits.push({ str: item.str, x: Math.min(ax, bx), y: Math.min(ay, by) });
                        }
                    }
                    hits.sort((a, b) => (Math.abs(a.y - b.y) < 0.01 ? a.x - b.x : a.y - b.y));
                    return hits.map(h => h.str.trim()).join(' ').replace(/\s+/g, ' ').trim();
                },
                async render(canvas, scale, rotation = 0) {
                    task?.cancel();
                    const viewport = page.getViewport({ scale, rotation: absolute(rotation) });
                    canvas.width  = Math.round(viewport.width);
                    canvas.height = Math.round(viewport.height);
                    task = page.render({ canvas, canvasContext: canvas.getContext('2d'), viewport });
                    try {
                        await task.promise;
                    } catch (e) {
                        if (e?.name !== 'RenderingCancelledException') throw e;
                    }
                },
            };
        },
        destroy() { doc.destroy(); },
    };
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = url;
    });
}

export const THUMBNAIL_SIDE = 400;

/** Réduit un canevas à THUMBNAIL_SIDE px de plus grand côté et l'encode en PNG. */
export function canvasToThumbnail(source) {
    const ratio = Math.min(1, THUMBNAIL_SIDE / Math.max(source.width, source.height));
    const out = document.createElement('canvas');
    out.width  = Math.max(1, Math.round(source.width * ratio));
    out.height = Math.max(1, Math.round(source.height * ratio));
    const ctx = out.getContext('2d');
    ctx.fillStyle = '#ffffff'; // fond du papier, pas une couleur d'interface
    ctx.fillRect(0, 0, out.width, out.height);
    ctx.drawImage(source, 0, 0, out.width, out.height);
    return new Promise(resolve => out.toBlob(resolve, 'image/png'));
}

/** Première page d'un plan → miniature PNG (Blob). */
export async function renderFileThumbnail(file) {
    const plan = await openPlan(file);
    try {
        const page = await plan.getPage(1);
        const canvas = document.createElement('canvas');
        await page.render(canvas, THUMBNAIL_SIDE / Math.max(page.width, page.height));
        return await canvasToThumbnail(canvas);
    } finally {
        plan.destroy();
    }
}

export async function uploadThumbnail(url, blob) {
    const body = new FormData();
    body.append('thumbnail', blob, 'thumbnail.png');
    const res = await fetch(url, {
        method: 'POST',
        body,
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}
