import React, { useCallback, useEffect, useRef, useState } from 'react';

const MIN_ZOOM = 0.1;
const MAX_ZOOM = 20;

/**
 * Pan and zoom viewer used for raster images and for SVG drawings.
 *
 * The previous product page rendered the SVG in a fixed 800x800 <img>, which
 * made a full flat pattern unreadable; here the wheel zooms around the cursor
 * and dragging pans.
 */
export default function ImageViewer({ file, t }) {
    const wrapperRef = useRef(null);
    const [transform, setTransform] = useState({ scale: 1, x: 0, y: 0 });
    const [dragging, setDragging] = useState(false);
    const dragOrigin = useRef(null);

    const reset = useCallback(() => setTransform({ scale: 1, x: 0, y: 0 }), []);

    useEffect(() => { reset(); }, [file.id, reset]);

    // Registered manually because React attaches wheel listeners passively,
    // which would let the page scroll while zooming.
    useEffect(() => {
        const wrapper = wrapperRef.current;
        if (!wrapper) return undefined;

        const onWheel = (event) => {
            event.preventDefault();

            const rect = wrapper.getBoundingClientRect();
            const pointerX = event.clientX - rect.left;
            const pointerY = event.clientY - rect.top;

            setTransform((current) => {
                const factor = event.deltaY < 0 ? 1.15 : 1 / 1.15;
                const scale = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, current.scale * factor));
                const ratio = scale / current.scale;

                return {
                    scale,
                    x: pointerX - (pointerX - current.x) * ratio,
                    y: pointerY - (pointerY - current.y) * ratio,
                };
            });
        };

        wrapper.addEventListener('wheel', onWheel, { passive: false });

        return () => wrapper.removeEventListener('wheel', onWheel);
    }, []);

    const onPointerDown = (event) => {
        setDragging(true);
        dragOrigin.current = { x: event.clientX - transform.x, y: event.clientY - transform.y };
        event.currentTarget.setPointerCapture(event.pointerId);
    };

    const onPointerMove = (event) => {
        if (!dragging || !dragOrigin.current) return;
        setTransform((current) => ({
            ...current,
            x: event.clientX - dragOrigin.current.x,
            y: event.clientY - dragOrigin.current.y,
        }));
    };

    const onPointerUp = (event) => {
        setDragging(false);
        dragOrigin.current = null;
        event.currentTarget.releasePointerCapture?.(event.pointerId);
    };

    const zoomBy = (factor) => setTransform((current) => ({
        ...current,
        scale: Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, current.scale * factor)),
    }));

    return (
        <div className="wem-viewer">
            <div className="btn-toolbar mb-2" role="toolbar">
                <div className="btn-group btn-group-sm mr-2">
                    <button type="button" className="btn btn-outline-secondary" onClick={() => zoomBy(1.25)}>
                        <i className="fas fa-search-plus" />
                    </button>
                    <button type="button" className="btn btn-outline-secondary" onClick={() => zoomBy(1 / 1.25)}>
                        <i className="fas fa-search-minus" />
                    </button>
                    <button type="button" className="btn btn-outline-secondary" onClick={reset}>
                        {t('reset_view')}
                    </button>
                </div>
                <div className="btn-group btn-group-sm">
                    <a className="btn btn-outline-secondary" href={file.download_url}>
                        <i className="fas fa-download mr-1" />
                        {t('download')}
                    </a>
                </div>
                <span className="ml-2 align-self-center small text-muted">
                    {Math.round(transform.scale * 100)}%
                </span>
            </div>

            <div
                ref={wrapperRef}
                className="wem-viewer-pane"
                style={{ cursor: dragging ? 'grabbing' : 'grab' }}
                onPointerDown={onPointerDown}
                onPointerMove={onPointerMove}
                onPointerUp={onPointerUp}
                onPointerLeave={onPointerUp}
            >
                <img
                    src={file.view_url}
                    alt={file.name}
                    draggable={false}
                    style={{
                        transform: `translate(${transform.x}px, ${transform.y}px) scale(${transform.scale})`,
                        transformOrigin: '0 0',
                        maxWidth: 'none',
                        userSelect: 'none',
                    }}
                />
            </div>
        </div>
    );
}
