import React from 'react';
import { previewErrorKey } from '../../../lib/files/filePreview.js';

/**
 * Chargement ou échec d'un aperçu calculé par le serveur ; en cas d'échec le
 * fichier reste téléchargeable.
 */
export default function PreviewStatus({ file, t, loading, reason }) {
    if (loading) {
        return (
            <div className="wem-viewer-placeholder text-center text-muted">
                <i className="fas fa-spinner fa-spin fa-2x mb-2" />
                <div>{t('loading')}</div>
            </div>
        );
    }

    return (
        <div className="wem-viewer-placeholder text-center text-muted">
            <i className={`${file.icon ?? 'far fa-file'} fa-3x mb-3`} />
            <div className="mb-3">{t(previewErrorKey(reason))}</div>
            <a className="btn btn-primary" href={file.download_url}>
                <i className="fas fa-download me-1" />
                {t('download')}
            </a>
        </div>
    );
}
