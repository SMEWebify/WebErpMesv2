import React, { useEffect, useRef, useState } from 'react';
import { createStage } from './scene.js';

const VIEWS = ['iso', 'front', 'back', 'top', 'bottom', 'left', 'right'];

/**
 * Common shell for every three.js based viewer.
 *
 * The format specific part is injected through `load`, which receives the stage
 * and must return the object to display (plus an optional info string shown in
 * the status bar).
 *
 * @param {(stage: object, url: string, signal: AbortSignal) => Promise<{object: object, info?: string}>} load
 */
export default function Viewer3D({ file, t, load, orthographic = false, height = 620 }) {
    const containerRef = useRef(null);
    const stageRef = useRef(null);
    const [status, setStatus] = useState('loading');
    const [error, setError] = useState(null);
    const [info, setInfo] = useState(null);

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return undefined;

        const controller = new AbortController();
        const stage = createStage(container, { orthographic });
        stageRef.current = stage;

        setStatus('loading');
        setError(null);
        setInfo(null);

        load(stage, file.view_url, controller.signal)
            .then(({ object, info: loadedInfo }) => {
                if (controller.signal.aborted) return;
                stage.scene.add(object);
                stage.frame(object);
                setInfo(loadedInfo ?? null);
                setStatus('ready');
            })
            .catch((err) => {
                if (controller.signal.aborted) return;
                setError(err.message);
                setStatus('error');
            });

        return () => {
            controller.abort();
            stage.dispose();
            stageRef.current = null;
        };
    }, [file.id, file.view_url]);

    return (
        <div className="wem-viewer">
            <div className="btn-toolbar mb-2" role="toolbar">
                <div className="btn-group btn-group-sm mr-2">
                    {VIEWS.map((view) => (
                        <button
                            key={view}
                            type="button"
                            className="btn btn-outline-secondary"
                            onClick={() => stageRef.current?.setView(view)}
                        >
                            {t(`view_${view}`)}
                        </button>
                    ))}
                </div>
                <div className="btn-group btn-group-sm">
                    <a className="btn btn-outline-secondary" href={file.download_url}>
                        <i className="fas fa-download mr-1" />
                        {t('download')}
                    </a>
                </div>
            </div>

            <div className="position-relative" style={{ height }}>
                <div ref={containerRef} style={{ width: '100%', height: '100%' }} />

                {status === 'loading' && (
                    <div className="wem-viewer-overlay">
                        <i className="fas fa-spinner fa-spin fa-2x mb-2" />
                        <div>{t('loading')}</div>
                    </div>
                )}

                {status === 'error' && (
                    <div className="wem-viewer-overlay">
                        <i className="fas fa-exclamation-triangle fa-2x mb-2" />
                        <div>{error}</div>
                    </div>
                )}
            </div>

            {info && <div className="small text-muted mt-1">{info}</div>}
        </div>
    );
}
