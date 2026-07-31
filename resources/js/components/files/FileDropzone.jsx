import React, { useRef, useState } from 'react';

/**
 * Single drop area accepting every supported format at once.
 *
 * Replaces the four dedicated upload forms of the product page (image, drawing,
 * STL, SVG): the role is chosen once for the batch and the server derives the
 * kind from the extension.
 */
export default function FileDropzone({ t, roles, accept, onUpload, uploading, progress }) {
    const inputRef = useRef(null);
    const [over, setOver] = useState(false);
    const [role, setRole] = useState('');
    const [isPrimary, setIsPrimary] = useState(true);
    const [comment, setComment] = useState('');
    const [hashtags, setHashtags] = useState('');

    const submit = (files) => {
        const list = Array.from(files ?? []);
        if (!list.length || uploading) return;

        onUpload(list, {
            role: role || undefined,
            isPrimary,
            comment: comment.trim() || undefined,
            hashtags: hashtags.trim() || undefined,
        });

        setComment('');
        setHashtags('');
        if (inputRef.current) inputRef.current.value = '';
    };

    return (
        <div className="wem-dropzone-wrapper">
            <div
                className={`wem-dropzone ${over ? 'is-over' : ''} ${uploading ? 'is-busy' : ''}`}
                onDragOver={(event) => { event.preventDefault(); setOver(true); }}
                onDragLeave={() => setOver(false)}
                onDrop={(event) => {
                    event.preventDefault();
                    setOver(false);
                    submit(event.dataTransfer.files);
                }}
                onClick={() => !uploading && inputRef.current?.click()}
                role="button"
                tabIndex={0}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') inputRef.current?.click();
                }}
            >
                <input
                    ref={inputRef}
                    type="file"
                    multiple
                    accept={accept}
                    className="d-none"
                    onChange={(event) => submit(event.target.files)}
                />

                {uploading ? (
                    <>
                        <i className="fas fa-spinner fa-spin fa-2x mb-2" />
                        <div className="progress w-75 mt-2" style={{ height: 6 }}>
                            <div className="progress-bar" style={{ width: `${progress}%` }} />
                        </div>
                        <div className="small mt-1">{progress}%</div>
                    </>
                ) : (
                    <>
                        <i className="fas fa-cloud-upload-alt fa-2x mb-2" />
                        <div>{t('dropzone_hint')}</div>
                        <div className="small text-muted mt-1">{t('dropzone_formats')}</div>
                    </>
                )}
            </div>

            <div className="form-row mt-2">
                <div className="col-md-4 form-group mb-2">
                    <label className="small mb-1">{t('role')}</label>
                    <select
                        className="form-control form-control-sm"
                        value={role}
                        onChange={(event) => setRole(event.target.value)}
                    >
                        <option value="">{t('role_auto')}</option>
                        {Object.entries(roles).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>
                </div>

                <div className="col-md-4 form-group mb-2">
                    <label className="small mb-1">{t('hashtags')}</label>
                    <input
                        type="text"
                        className="form-control form-control-sm"
                        placeholder="#indiceB #2026"
                        value={hashtags}
                        onChange={(event) => setHashtags(event.target.value)}
                    />
                </div>

                <div className="col-md-4 form-group mb-2">
                    <label className="small mb-1">{t('comment')}</label>
                    <input
                        type="text"
                        className="form-control form-control-sm"
                        value={comment}
                        onChange={(event) => setComment(event.target.value)}
                    />
                </div>
            </div>

            <div className="custom-control custom-checkbox">
                <input
                    type="checkbox"
                    className="custom-control-input"
                    id="wem-dropzone-primary"
                    checked={isPrimary}
                    onChange={(event) => setIsPrimary(event.target.checked)}
                />
                <label className="custom-control-label small" htmlFor="wem-dropzone-primary">
                    {t('set_as_primary')}
                </label>
            </div>
        </div>
    );
}
