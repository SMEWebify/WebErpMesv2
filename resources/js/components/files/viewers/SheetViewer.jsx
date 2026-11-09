import React, { useState } from 'react';
import usePreview from './usePreview.js';
import PreviewStatus from './PreviewStatus.jsx';
import { columnCount, columnLetter, fillPlaceholders } from '../../../lib/files/filePreview.js';

/**
 * Tableur (xlsx, xls, ods, csv) lu par le serveur et affiché en tableau, une
 * feuille par onglet. Lecture seule : la modification passe par le
 * téléchargement puis « Remplacer par une nouvelle version ».
 */
export default function SheetViewer({ file, t }) {
    const { data, loading, reason } = usePreview(file.preview_url);
    const [active, setActive] = useState(0);

    if (loading || !data) {
        return <PreviewStatus file={file} t={t} loading={loading} reason={reason} />;
    }

    const sheets = data.sheets ?? [];
    const sheet = sheets[Math.min(active, sheets.length - 1)] ?? null;
    const rows = sheet?.rows ?? [];
    const columns = columnCount(rows);

    return (
        <div className="wem-viewer">
            <div className="btn-toolbar mb-2" role="toolbar">
                <div className="btn-group btn-group-sm">
                    <a className="btn btn-outline-secondary" href={file.download_url}>
                        <i className="fas fa-download me-1" />
                        {t('download')}
                    </a>
                </div>
            </div>

            {sheets.length > 1 && (
                <ul className="nav nav-tabs nav-sm mb-0 flex-nowrap overflow-auto">
                    {sheets.map((item, index) => (
                        <li className="nav-item" key={`${index}-${item.name}`}>
                            <button
                                type="button"
                                className={`nav-link py-1 px-2 small text-nowrap${index === active ? ' active' : ''}`}
                                onClick={() => setActive(index)}
                                title={item.hidden ? t('sheet_hidden') : item.name}
                            >
                                {item.hidden && <i className="fas fa-eye-slash me-1 text-muted" />}
                                {item.name}
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {rows.length === 0 ? (
                <div className="wem-viewer-placeholder text-muted">{t('sheet_empty')}</div>
            ) : (
                <div className="wem-sheet-pane">
                    <table className="table table-sm table-bordered mb-0 small wem-sheet">
                        <thead>
                            <tr>
                                <th className="wem-sheet-corner" />
                                {Array.from({ length: columns }, (_, c) => (
                                    <th key={c} className="text-center fw-normal text-muted">{columnLetter(c)}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, r) => (
                                <tr key={r}>
                                    <th className="text-end fw-normal text-muted">{r + 1}</th>
                                    {Array.from({ length: columns }, (_, c) => (
                                        <td key={c}>{row[c] ?? ''}</td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {sheet?.truncated && (
                <div className="alert alert-info py-1 px-2 small mt-2 mb-0">
                    <i className="fas fa-circle-info me-1" />
                    {fillPlaceholders(t('sheet_truncated'), {
                        rows: rows.length,
                        columns,
                        total_rows: sheet.total_rows,
                        total_columns: sheet.total_columns,
                    })}
                </div>
            )}

            <div className="small text-muted mt-2">
                <i className="fas fa-pen-to-square me-1" />
                {t('sheet_edit_hint')}
            </div>
        </div>
    );
}
