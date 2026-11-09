import React, { useEffect, useRef, useState } from 'react';
import { canRenderThumbnail, fileThumbnail } from '../../lib/files/fileThumbnail';

const KIND_ICONS = {
    brep: 'fa-cube',
    mesh: 'fa-cube',
    cad2d: 'fa-compass-drafting',
    vector: 'fa-bezier-curve',
    doc: 'fa-file-pdf',
    image: 'fa-file-image',
    sheet: 'fa-file-excel',
    mail: 'fa-envelope',
    nc: 'fa-route',
};

/**
 * Miniature d'un fichier de la GED (plan, contour, modèle 3D). Sert l'image
 * stockée si elle existe ; sinon, quand elle devient visible, la dessine dans le
 * navigateur (lib/files/fileThumbnail), l'affiche et la dépose sur le serveur.
 */
export default function FileThumbnail({ file, size = 42, style = {} }) {
    const [src, setSrc] = useState(file?.thumbnail_url || null);
    const [failed, setFailed] = useState(false);
    const ref = useRef(null);
    // URL locale du rendu : libérée au démontage seulement, pas quand `src` change
    // (le nettoyage de l'effet de rendu la libérerait avant que l'image la lise).
    const objectUrl = useRef(null);

    useEffect(() => { setSrc(file?.thumbnail_url || null); setFailed(false); }, [file?.id, file?.thumbnail_url]);

    useEffect(() => () => { if (objectUrl.current) URL.revokeObjectURL(objectUrl.current); }, []);

    useEffect(() => {
        if (src || failed || !canRenderThumbnail(file) || !ref.current) return;

        let cancelled = false;
        const observer = new IntersectionObserver((entries) => {
            if (!entries.some((e) => e.isIntersecting)) return;
            observer.disconnect();
            fileThumbnail(file)
                .then((blob) => {
                    if (cancelled) return;
                    if (!blob) { setFailed(true); return; }
                    if (objectUrl.current) URL.revokeObjectURL(objectUrl.current);
                    objectUrl.current = URL.createObjectURL(blob);
                    setSrc(objectUrl.current);
                })
                .catch(() => { if (!cancelled) setFailed(true); });
        }, { rootMargin: '200px' });
        observer.observe(ref.current);

        return () => {
            cancelled = true;
            observer.disconnect();
        };
    }, [file?.id, src, failed]);

    const box = {
        width: size,
        height: size,
        objectFit: 'contain',
        background: 'var(--bs-tertiary-bg)',
        border: '1px solid var(--bs-border-color)',
        borderRadius: 3,
        display: 'block',
        ...style,
    };

    if (src) {
        return <img src={src} alt="" title={file?.name ?? ''} style={box} loading="lazy" onError={() => { setSrc(null); setFailed(true); }} />;
    }

    const pending = !failed && canRenderThumbnail(file);
    const icon = pending ? 'fa-spinner fa-spin' : (KIND_ICONS[file?.kind] ?? 'fa-file');

    return (
        <span ref={ref} className="d-flex align-items-center justify-content-center text-secondary" style={box} title={file?.name ?? ''}>
            <i className={`fas ${icon}`} />
        </span>
    );
}
