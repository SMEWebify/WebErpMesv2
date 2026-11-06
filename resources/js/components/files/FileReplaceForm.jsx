import React, { useState } from 'react';
import { proposedRevision } from '../../lib/files/fileVersions.js';

/**
 * Inline form, under a file of the list, uploading its next version.
 */
export default function FileReplaceForm({ file, accept, defaultRevision, t, busy, progress, onSubmit, onCancel }) {
    const [upload, setUpload] = useState(null);
    const [revisionLabel, setRevisionLabel] = useState(() => proposedRevision(defaultRevision));
    const [comment, setComment] = useState('');

    const submit = (event) => {
        event.preventDefault();
        if (!upload || busy) return;

        onSubmit(file, upload, {
            revisionLabel: revisionLabel.trim() || undefined,
            comment: comment.trim() || undefined,
        });
    };

    return (
        <form className="border rounded p-2 mt-2 bg-body-tertiary" onSubmit={submit} onClick={(event) => event.stopPropagation()}>
            <div className="small fw-semibold mb-2">
                <i className="fas fa-code-branch me-1" />
                {t('version_replace')}
            </div>

            <div className="mb-2">
                <label className="small text-muted mb-1 d-block" htmlFor={`wem-replace-file-${file.id}`}>{t('version_new_file')}</label>
                <input
                    id={`wem-replace-file-${file.id}`}
                    type="file"
                    className="form-control form-control-sm"
                    accept={accept}
                    onChange={(event) => setUpload(event.target.files?.[0] ?? null)}
                />
            </div>

            <div className="row g-2 mb-2">
                <div className="col-4">
                    <label className="small text-muted mb-1 d-block" htmlFor={`wem-replace-rev-${file.id}`}>{t('revision_label')}</label>
                    <input
                        id={`wem-replace-rev-${file.id}`}
                        type="text"
                        maxLength={20}
                        className="form-control form-control-sm"
                        value={revisionLabel}
                        onChange={(event) => setRevisionLabel(event.target.value)}
                    />
                </div>
                <div className="col-8">
                    <label className="small text-muted mb-1 d-block" htmlFor={`wem-replace-comment-${file.id}`}>{t('comment')}</label>
                    <input
                        id={`wem-replace-comment-${file.id}`}
                        type="text"
                        className="form-control form-control-sm"
                        value={comment}
                        onChange={(event) => setComment(event.target.value)}
                    />
                </div>
            </div>

            <div className="small text-muted mb-2">{t('version_hint')}</div>

            {busy && (
                <div className="progress mb-2" style={{ height: 6 }}>
                    <div className="progress-bar" style={{ width: `${progress}%` }} />
                </div>
            )}

            <div className="d-flex justify-content-end">
                <button type="button" className="btn btn-outline-secondary btn-sm me-1" onClick={onCancel} disabled={busy}>
                    {t('version_cancel')}
                </button>
                <button type="submit" className="btn btn-primary btn-sm" disabled={!upload || busy}>
                    {busy ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-upload me-1" />}
                    {t('version_submit')}
                </button>
            </div>
        </form>
    );
}
