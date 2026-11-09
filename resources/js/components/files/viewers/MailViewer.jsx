import React from 'react';
import usePreview from './usePreview.js';
import PreviewStatus from './PreviewStatus.jsx';
import { formatSize, mailDocument } from '../../../lib/files/filePreview.js';

function formatDate(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString(document.documentElement.lang || undefined, {
        dateStyle: 'full',
        timeStyle: 'short',
    });
}

function HeaderRow({ label, value }) {
    if (!value || (Array.isArray(value) && value.length === 0)) return null;

    return (
        <div className="d-flex gap-2">
            <span className="text-muted flex-shrink-0" style={{ minWidth: '3.5rem' }}>{label}</span>
            <span className="text-break">{Array.isArray(value) ? value.join(', ') : value}</span>
        </div>
    );
}

/**
 * E-mail (.eml, .msg) lu par le serveur. Le corps HTML, déjà nettoyé, est
 * affiché dans une iframe sans script (sandbox sans allow-scripts, CSP) ; les
 * pièces jointes se téléchargent une à une.
 */
export default function MailViewer({ file, t, height = 620 }) {
    const { data, loading, reason } = usePreview(file.preview_url);

    if (loading || !data) {
        return <PreviewStatus file={file} t={t} loading={loading} reason={reason} />;
    }

    const attachments = data.attachments ?? [];

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

            <div className="border rounded p-2 mb-2 bg-body-tertiary small">
                <div className="fw-semibold fs-6 mb-1 text-break">{data.subject || t('mail_no_subject')}</div>
                <HeaderRow label={t('mail_from')} value={data.from} />
                <HeaderRow label={t('mail_to')} value={data.to} />
                <HeaderRow label={t('mail_cc')} value={data.cc} />
                <HeaderRow label={t('mail_date')} value={formatDate(data.date)} />
            </div>

            {attachments.length > 0 && (
                <div className="mb-2">
                    <div className="small text-muted mb-1">
                        <i className="fas fa-paperclip me-1" />
                        {t('mail_attachments')} ({attachments.length})
                    </div>
                    <div className="d-flex flex-wrap gap-1">
                        {attachments.map((attachment) => (attachment.download_url ? (
                            <a
                                key={attachment.index}
                                className="btn btn-sm btn-outline-secondary text-truncate"
                                style={{ maxWidth: '100%' }}
                                href={attachment.download_url}
                                title={attachment.name}
                            >
                                <i className="fas fa-download me-1" />
                                {attachment.name}
                                <span className="text-muted ms-1">{formatSize(attachment.size)}</span>
                            </a>
                        ) : (
                            <span
                                key={attachment.index}
                                className="btn btn-sm btn-outline-secondary disabled text-truncate"
                                style={{ maxWidth: '100%' }}
                                title={t('mail_attachment_unavailable')}
                            >
                                <i className="fas fa-envelope me-1" />
                                {attachment.name}
                            </span>
                        )))}
                    </div>
                </div>
            )}

            {data.html ? (
                <iframe
                    title={data.subject || file.name}
                    className="w-100 border rounded"
                    style={{ height }}
                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                    referrerPolicy="no-referrer"
                    srcDoc={mailDocument(data.html)}
                />
            ) : data.text ? (
                <pre className="border rounded p-2 mb-0 small wem-mail-text" style={{ maxHeight: height }}>{data.text}</pre>
            ) : (
                <div className="wem-viewer-placeholder text-muted">{t('mail_no_body')}</div>
            )}
        </div>
    );
}
