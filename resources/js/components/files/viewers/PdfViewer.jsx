import React from 'react';

/**
 * PDF drawings are handed to the browser's own viewer.
 *
 * Shipping pdf.js would add roughly a megabyte to the bundle for a rendering
 * every supported browser already provides; the file is streamed inline by
 * FileApiController@raw so the plugin picks it up.
 */
export default function PdfViewer({ file, t, height = 900 }) {
    return (
        <div className="wem-viewer">
            <div className="btn-toolbar mb-2" role="toolbar">
                <div className="btn-group btn-group-sm mr-2">
                    <a className="btn btn-outline-secondary" href={file.view_url} target="_blank" rel="noreferrer">
                        <i className="fas fa-external-link-alt mr-1" />
                        {t('open_new_tab')}
                    </a>
                </div>
                <div className="btn-group btn-group-sm">
                    <a className="btn btn-outline-secondary" href={file.download_url}>
                        <i className="fas fa-download mr-1" />
                        {t('download')}
                    </a>
                </div>
            </div>

            <object data={file.view_url} type="application/pdf" width="100%" height={height}>
                <p className="p-3">
                    {t('no_inline_preview')}{' '}
                    <a href={file.download_url}>{file.name}</a>
                </p>
            </object>
        </div>
    );
}
