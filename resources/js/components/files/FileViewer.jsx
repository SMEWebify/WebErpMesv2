import React, { Suspense, lazy } from 'react';

/**
 * Dispatches a file to the viewer matching its kind.
 *
 * Every engine is code-split: opening a PDF never downloads three.js, and the
 * OpenCascade WASM used for STEP is only fetched when a STEP file is opened.
 */
const MeshViewer = lazy(() => import('./viewers/MeshViewer.jsx'));
const BrepViewer = lazy(() => import('./viewers/BrepViewer.jsx'));
const DxfViewer = lazy(() => import('./viewers/DxfViewer.jsx'));
const ImageViewer = lazy(() => import('./viewers/ImageViewer.jsx'));
const PdfViewer = lazy(() => import('./viewers/PdfViewer.jsx'));

const VIEWERS = {
    mesh: MeshViewer,
    brep: BrepViewer,
    cad2d: DxfViewer,
    vector: ImageViewer,
    image: ImageViewer,
    doc: PdfViewer,
};

function Fallback({ t }) {
    return (
        <div className="wem-viewer-placeholder text-center text-muted">
            <i className="fas fa-spinner fa-spin fa-2x mb-2" />
            <div>{t('loading')}</div>
        </div>
    );
}

export default function FileViewer({ file, t }) {
    if (!file) {
        return (
            <div className="wem-viewer-placeholder text-center text-muted">
                <i className="far fa-hand-point-left fa-2x mb-2" />
                <div>{t('select_a_file')}</div>
            </div>
        );
    }

    const Viewer = VIEWERS[file.kind];

    if (!Viewer) {
        return (
            <div className="wem-viewer-placeholder text-center text-muted">
                <i className={`${file.icon} fa-3x mb-3`} />
                <div className="mb-3">{t('no_inline_preview')}</div>
                <a className="btn btn-primary" href={file.download_url}>
                    <i className="fas fa-download mr-1" />
                    {t('download')}
                </a>
            </div>
        );
    }

    return (
        <Suspense fallback={<Fallback t={t} />}>
            <Viewer key={file.id} file={file} t={t} />
        </Suspense>
    );
}
