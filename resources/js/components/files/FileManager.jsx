import React, { useCallback, useEffect, useMemo, useState } from 'react';
import FileDropzone from './FileDropzone.jsx';
import FileViewer from './FileViewer.jsx';
import FileReplaceForm from './FileReplaceForm.jsx';
import FileVersionHistory from './FileVersionHistory.jsx';
import { deleteFile, listFiles, openInOffice, replaceFile, updateFile, uploadFiles } from './api.js';
import { canDeleteFile, canReplaceFile, officeEditApp, versionCaption } from '../../lib/files/fileVersions.js';

/**
 * Unified document tab: one drop area for every format, one list grouped by
 * business role, and one viewer that adapts to the selected file.
 *
 * Mounted from Blade through include/file-manager-mount.blade.php, so the same
 * component serves products, quotes, orders, non-conformities and so on.
 */
export default function FileManager({ endpoints, target, roles, accept, trans, canEdit = true, defaultRevision = '' }) {
    const [files, setFiles] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState(null);
    const [roleFilter, setRoleFilter] = useState('');
    const [replacingId, setReplacingId] = useState(null);
    const [historyFile, setHistoryFile] = useState(null);
    // Fichier ouvert dans Excel / Word : ses enregistrements arrivent en nouvelles versions.
    const [officeEditing, setOfficeEditing] = useState(null);

    // Older mounts may not expose the versioning endpoints.
    const versioning = !!(endpoints?.replace && endpoints?.versions);

    const t = useCallback((key) => trans?.[key] ?? key, [trans]);

    const refresh = useCallback(async () => {
        setLoading(true);
        try {
            const { files: fetched } = await listFiles(endpoints, target);
            setFiles(fetched);
            // Autres composants de la page qui dépendent des fichiers (bouton NEST2BEND : STEP présent ?).
            window.dispatchEvent(new CustomEvent('wem:files-changed', { detail: { target, files: fetched } }));
            setSelectedId((current) => {
                if (current && fetched.some((file) => file.id === current)) return current;
                return fetched.find((file) => file.is_viewable)?.id ?? fetched[0]?.id ?? null;
            });
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, [endpoints, target]);

    useEffect(() => { refresh(); }, [refresh]);

    // Retour sur la page après un passage dans Excel / Word : la liste montre
    // les versions enregistrées entre-temps.
    useEffect(() => {
        if (!officeEditing) return undefined;
        const onFocus = () => { refresh(); };
        window.addEventListener('focus', onFocus);
        return () => window.removeEventListener('focus', onFocus);
    }, [officeEditing, refresh]);

    const handleOfficeEdit = async (file) => {
        setError(null);

        try {
            const { url } = await openInOffice(endpoints, target, file.id);
            setOfficeEditing({ name: file.name, app: officeEditApp(file) });
            window.location.href = url;
        } catch (err) {
            setError(err.message);
        }
    };

    const handleUpload = async (list, meta) => {
        setUploading(true);
        setProgress(0);
        setError(null);

        try {
            const { files: created } = await uploadFiles(endpoints, target, list, meta, setProgress);
            await refresh();
            if (created?.length) setSelectedId(created[0].id);
        } catch (err) {
            setError(err.message);
        } finally {
            setUploading(false);
            setProgress(0);
        }
    };

    const handleReplace = async (file, upload, meta) => {
        setUploading(true);
        setProgress(0);
        setError(null);

        try {
            const { file: created } = await replaceFile(endpoints, target, file.id, upload, meta, setProgress);
            setReplacingId(null);
            await refresh();
            if (created?.id) setSelectedId(created.id);
        } catch (err) {
            setError(err.message);
        } finally {
            setUploading(false);
            setProgress(0);
        }
    };

    const handlePrimary = async (file) => {
        try {
            await updateFile(endpoints, target, file.id, { role: file.role, is_primary: true });
            await refresh();
        } catch (err) {
            setError(err.message);
        }
    };

    const handleRoleChange = async (file, role) => {
        try {
            await updateFile(endpoints, target, file.id, { role, is_primary: file.is_primary });
            await refresh();
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDelete = async (file) => {
        if (!window.confirm(t('confirm_delete').replace(':name', file.name))) return;

        try {
            await deleteFile(endpoints, target, file.id);
            setSelectedId((current) => (current === file.id ? null : current));
            await refresh();
        } catch (err) {
            setError(err.message);
        }
    };

    const visibleFiles = useMemo(
        () => (roleFilter ? files.filter((file) => file.role === roleFilter) : files),
        [files, roleFilter],
    );

    const grouped = useMemo(() => {
        const groups = new Map();

        visibleFiles.forEach((file) => {
            const key = file.role ?? 'autre';
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(file);
        });

        return Array.from(groups.entries());
    }, [visibleFiles]);

    const selected = files.find((file) => file.id === selectedId) ?? null;

    return (
        <div className="wem-file-manager">
            {error && (
                <div className="alert alert-danger alert-dismissible">
                    <button type="button" onClick={() => setError(null)} aria-label="Close" className="btn-close float-end"></button>
                    {error}
                </div>
            )}

            {officeEditing && (
                <div className="alert alert-info alert-dismissible small">
                    <button type="button" onClick={() => setOfficeEditing(null)} aria-label="Close" className="btn-close float-end"></button>
                    <i className="fas fa-file-pen me-1" />
                    <strong>{officeEditing.name}</strong>{' — '}
                    {t('office_edit_hint').replace(':app', officeEditing.app === 'word' ? 'Word' : 'Excel')}
                </div>
            )}

            <div className="row">
                <div className="col-12 col-xl-5 mb-3 mb-xl-0">
                    {canEdit && (
                        <FileDropzone
                            t={t}
                            roles={roles}
                            accept={accept}
                            onUpload={handleUpload}
                            defaultRevision={defaultRevision}
                            uploading={uploading}
                            progress={progress}
                        />
                    )}

                    <div className="wem-file-manager__list-header d-flex flex-wrap align-items-center justify-content-between mt-3 mb-2">
                        <h6 className="mb-0 text-muted me-2">
                            {t('attached_files')} <span className="badge text-bg-secondary">{files.length}</span>
                        </h6>
                        <select
                            className="form-select form-select-sm w-auto"
                            value={roleFilter}
                            onChange={(event) => setRoleFilter(event.target.value)}
                        >
                            <option value="">{t('all_roles')}</option>
                            {Object.entries(roles).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>

                    {loading && <div className="text-muted small"><i className="fas fa-spinner fa-spin me-1" />{t('loading')}</div>}

                    {!loading && !visibleFiles.length && (
                        <div className="text-muted small">{t('no_data')}</div>
                    )}

                    {grouped.map(([role, roleFiles]) => (
                        <div key={role} className="mb-3">
                            <div className="text-uppercase small text-muted mb-1">{roles[role] ?? role}</div>
                            <ul className="list-group list-group-flush">
                                {roleFiles.map((file) => (
                                    <li
                                        key={file.id}
                                        className={`list-group-item px-2 py-2 ${file.id === selectedId ? 'active-file' : ''}`}
                                        onClick={() => setSelectedId(file.id)}
                                        role="button"
                                    >
                                        <div className="d-flex align-items-start">
                                            <i className={`${file.icon} mt-1 me-2`} />
                                            <div className="flex-grow-1 min-width-0">
                                                <div className="text-truncate" title={file.name}>
                                                    {file.name}
                                                    {versionCaption(file, t('version_label')) && (
                                                        <span className="badge text-bg-secondary ms-1">{versionCaption(file, t('version_label'))}</span>
                                                    )}
                                                    {file.is_primary && (
                                                        <span className="badge text-bg-success ms-1">{t('primary')}</span>
                                                    )}
                                                </div>
                                                <div className="small text-muted">
                                                    {file.formatted_size}
                                                    {file.uploaded_by ? ` — ${file.uploaded_by}` : ''}
                                                </div>
                                                {!!file.hashtags?.length && (
                                                    <div className="mt-1">
                                                        {file.hashtags.map((tag) => (
                                                            <span key={tag} className="badge text-bg-info me-1">#{tag}</span>
                                                        ))}
                                                    </div>
                                                )}
                                                {file.comment && (
                                                    <div className="small text-muted fst-italic">{file.comment}</div>
                                                )}
                                            </div>
                                        </div>

                                        {!canEdit && versioning && file.has_history && (
                                            <div className="d-flex align-items-center mt-2">
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm"
                                                    title={t('version_history')}
                                                    onClick={(event) => { event.stopPropagation(); setHistoryFile(file); }}
                                                >
                                                    <i className="fas fa-clock-rotate-left" />
                                                </button>
                                            </div>
                                        )}

                                        {canEdit && (
                                            <div className="d-flex align-items-center mt-2">
                                                <select
                                                    className="form-select form-select-sm me-1"
                                                    value={file.role ?? ''}
                                                    onClick={(event) => event.stopPropagation()}
                                                    onChange={(event) => handleRoleChange(file, event.target.value)}
                                                >
                                                    {Object.entries(roles).map(([value, label]) => (
                                                        <option key={value} value={value}>{label}</option>
                                                    ))}
                                                </select>

                                                {!file.is_primary && (
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-success btn-sm me-1"
                                                        title={t('set_as_primary')}
                                                        onClick={(event) => { event.stopPropagation(); handlePrimary(file); }}
                                                    >
                                                        <i className="fas fa-star" />
                                                    </button>
                                                )}

                                                <a
                                                    className="btn btn-outline-secondary btn-sm me-1"
                                                    href={file.download_url}
                                                    title={t('download')}
                                                    onClick={(event) => event.stopPropagation()}
                                                >
                                                    <i className="fas fa-download" />
                                                </a>

                                                {endpoints?.office_edit && officeEditApp(file) && (
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-success btn-sm me-1"
                                                        title={t(`office_edit_${officeEditApp(file)}`)}
                                                        onClick={(event) => { event.stopPropagation(); handleOfficeEdit(file); }}
                                                    >
                                                        <i className={`fas ${officeEditApp(file) === 'word' ? 'fa-file-word' : 'fa-file-excel'}`} />
                                                    </button>
                                                )}

                                                {versioning && canReplaceFile(file) && (
                                                    <button
                                                        type="button"
                                                        className={`btn btn-sm me-1 ${replacingId === file.id ? 'btn-secondary' : 'btn-outline-secondary'}`}
                                                        title={t('version_replace')}
                                                        onClick={(event) => {
                                                            event.stopPropagation();
                                                            setReplacingId((current) => (current === file.id ? null : file.id));
                                                        }}
                                                    >
                                                        <i className="fas fa-code-branch" />
                                                    </button>
                                                )}

                                                {versioning && file.has_history && (
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-secondary btn-sm me-1"
                                                        title={t('version_history')}
                                                        onClick={(event) => { event.stopPropagation(); setHistoryFile(file); }}
                                                    >
                                                        <i className="fas fa-clock-rotate-left" />
                                                    </button>
                                                )}

                                                {canDeleteFile(file) && (
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-danger btn-sm"
                                                        title={t('delete')}
                                                        onClick={(event) => { event.stopPropagation(); handleDelete(file); }}
                                                    >
                                                        <i className="fas fa-trash" />
                                                    </button>
                                                )}
                                            </div>
                                        )}

                                        {canEdit && replacingId === file.id && (
                                            <FileReplaceForm
                                                file={file}
                                                accept={accept}
                                                defaultRevision={defaultRevision}
                                                t={t}
                                                busy={uploading}
                                                progress={progress}
                                                onSubmit={handleReplace}
                                                onCancel={() => setReplacingId(null)}
                                            />
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="col-12 col-xl-7">
                    <FileViewer file={selected} t={t} />
                </div>
            </div>

            {historyFile && (
                <FileVersionHistory
                    endpoints={endpoints}
                    target={target}
                    file={historyFile}
                    t={t}
                    onClose={() => setHistoryFile(null)}
                />
            )}
        </div>
    );
}
