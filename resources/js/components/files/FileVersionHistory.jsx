import React, { useEffect, useState } from 'react';
import { listVersions } from './api.js';
import { versionCaption } from '../../lib/files/fileVersions.js';

function formatDate(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString(document.documentElement.lang || undefined, {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

/**
 * Side drawer listing every version of a file's lineage, newest first. Each
 * version, replaced or not, stays downloadable.
 */
export default function FileVersionHistory({ endpoints, target, file, t, onClose }) {
    const [versions, setVersions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        listVersions(endpoints, target, file.id)
            .then(({ versions: fetched }) => { if (!cancelled) { setVersions(fetched ?? []); setError(null); } })
            .catch((err) => { if (!cancelled) setError(err.message); })
            .finally(() => { if (!cancelled) setLoading(false); });

        return () => { cancelled = true; };
    }, [endpoints, target, file.id]);

    useEffect(() => {
        const onKey = (event) => { if (event.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);

    return (
        <>
            <div className="offcanvas-backdrop fade show" onClick={onClose} />
            <div className="offcanvas offcanvas-end show" tabIndex={-1} role="dialog" aria-modal="true" aria-label={t('version_history')}>
                <div className="offcanvas-header border-bottom">
                    <h5 className="offcanvas-title text-truncate">
                        <i className="fas fa-clock-rotate-left me-2" />
                        {t('version_history')}
                    </h5>
                    <button type="button" className="btn-close" aria-label={t('version_close')} onClick={onClose} />
                </div>
                <div className="offcanvas-body">
                    <div className="small text-muted mb-3 text-truncate" title={file.name}>{file.name}</div>

                    {loading && <div className="text-muted small"><i className="fas fa-spinner fa-spin me-1" />{t('loading')}</div>}
                    {error && <div className="alert alert-danger py-2 small">{error}</div>}

                    <ul className="list-group">
                        {versions.map((version) => (
                            <li key={version.id} className="list-group-item">
                                <div className="d-flex align-items-start justify-content-between">
                                    <div className="min-width-0 me-2">
                                        <div className="fw-semibold">
                                            <span className="badge text-bg-secondary me-1">
                                                {versionCaption({ ...version, has_history: true }, t('version_label'))}
                                            </span>
                                            {version.is_current && (
                                                <span className="badge text-bg-success">{t('version_current')}</span>
                                            )}
                                        </div>
                                        <div className="small text-truncate mt-1" title={version.name}>{version.name}</div>
                                        <div className="small text-muted">
                                            {formatDate(version.created_at)}
                                            {version.uploaded_by ? ` — ${version.uploaded_by}` : ''}
                                            {version.formatted_size ? ` — ${version.formatted_size}` : ''}
                                        </div>
                                        {version.superseded_at && (
                                            <div className="small text-muted">
                                                {t('version_superseded').replace(':date', formatDate(version.superseded_at))}
                                            </div>
                                        )}
                                        {version.comment && (
                                            <div className="small text-muted fst-italic mt-1">{version.comment}</div>
                                        )}
                                    </div>
                                    <div className="d-flex flex-shrink-0">
                                        <a className="btn btn-outline-secondary btn-sm me-1" href={version.view_url} target="_blank" rel="noopener noreferrer" title={t('open_new_tab')}>
                                            <i className="fas fa-up-right-from-square" />
                                        </a>
                                        <a className="btn btn-outline-secondary btn-sm" href={version.download_url} title={t('download')}>
                                            <i className="fas fa-download" />
                                        </a>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </>
    );
}
