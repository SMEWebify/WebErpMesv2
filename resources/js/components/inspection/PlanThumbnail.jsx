import React, { useEffect, useRef, useState } from 'react';
import { renderFileThumbnail, uploadThumbnail } from '../../lib/pdf/pdfRender';

// Deux rendus à la fois au plus : une page de 25 lignes ne lance pas 25 pdf.js.
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

/**
 * Miniature d'un plan. Sert l'image stockée si elle existe ; sinon, quand la
 * ligne devient visible, dessine la première page dans le navigateur, l'affiche
 * et la dépose sur le serveur pour les affichages suivants.
 */
export default function PlanThumbnail({ url = null, file = null, width = 64, height = 46, title = '' }) {
    const [src, setSrc] = useState(url || file?.thumbnail_url || null);
    const [failed, setFailed] = useState(false);
    const ref = useRef(null);

    useEffect(() => { setSrc(url || file?.thumbnail_url || null); }, [url, file?.thumbnail_url]);

    useEffect(() => {
        if (src || failed || !file || !['doc', 'image'].includes(file.kind) || !ref.current) return;

        let objectUrl = null;
        let cancelled = false;
        const observer = new IntersectionObserver(entries => {
            if (!entries.some(e => e.isIntersecting)) return;
            observer.disconnect();
            enqueue(() => renderFileThumbnail(file))
                .then(blob => {
                    if (cancelled || !blob) return;
                    objectUrl = URL.createObjectURL(blob);
                    setSrc(objectUrl);
                    if (file.thumbnail_store_url) uploadThumbnail(file.thumbnail_store_url, blob).catch(() => {});
                })
                .catch(() => { if (!cancelled) setFailed(true); });
        }, { rootMargin: '200px' });
        observer.observe(ref.current);

        return () => {
            cancelled = true;
            observer.disconnect();
            if (objectUrl) URL.revokeObjectURL(objectUrl);
        };
    }, [file?.id, src, failed]);

    const box = {
        width, height,
        border: '1px solid var(--bs-border-color)',
        borderRadius: 4,
        background: 'var(--bs-tertiary-bg)',
        objectFit: 'contain',
        flexShrink: 0,
    };

    if (src) {
        return <img src={src} alt={title} title={title} style={box} loading="lazy" onError={() => { setSrc(null); setFailed(true); }} />;
    }

    return (
        <span ref={ref} className="d-inline-flex align-items-center justify-content-center text-secondary" style={box} title={title}>
            <i className={`fas ${file ? (failed ? 'fa-file-circle-exclamation' : 'fa-spinner fa-spin') : 'fa-file-circle-xmark'}`} />
        </span>
    );
}
