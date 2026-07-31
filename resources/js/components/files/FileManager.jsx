import React, { useCallback, useEffect, useMemo, useState } from 'react';
import FileDropzone from './FileDropzone.jsx';
import FileViewer from './FileViewer.jsx';
import { deleteFile, listFiles, updateFile, uploadFiles } from './api.js';

/**
 * Unified document tab: one drop area for every format, one list grouped by
 * business role, and one viewer that adapts to the selected file.
 *
 * Mounted from Blade through include/file-manager-mount.blade.php, so the same
 * component serves products, quotes, orders, non-conformities and so on.
 */
export default function FileManager({ endpoints, target, roles, accept, trans, canEdit = true }) {
    const [files, setFiles] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState(null);
    const [roleFilter, setRoleFilter] = useState('');

    const t = useCallback((key) => trans?.[key] ?? key, [trans]);

    const refresh = useCallback(async () => {
        setLoading(true);
        try {
            const { files: fetched } = await listFiles(endpoints, target);
            setFiles(fetched);
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
                    <button type="button" className="close" onClick={() => setError(null)}>&times;</button>
                    {error}
                </div>
            )}

            <div className="row">
                <div className="col-lg-4">
                    {canEdit && (
                        <FileDropzone
                            t={t}
                            roles={roles}
                            accept={accept}
                            onUpload={handleUpload}
                            uploading={uploading}
                            progress={progress}
                        />
                    )}

                    <div className="d-flex align-items-center justify-content-between mt-3 mb-2">
                        <h6 className="mb-0 text-muted">
                            {t('attached_files')} <span className="badge badge-secondary">{files.length}</span>
                        </h6>
                        <select
                            className="form-control form-control-sm w-auto"
                            value={roleFilter}
                            onChange={(event) => setRoleFilter(event.target.value)}
                        >
                            <option value="">{t('all_roles')}</option>
                            {Object.entries(roles).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>

                    {loading && <div className="text-muted small"><i className="fas fa-spinner fa-spin mr-1" />{t('loading')}</div>}

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
                                            <i className={`${file.icon} mt-1 mr-2`} />
                                            <div className="flex-grow-1 min-width-0">
                                                <div className="text-truncate" title={file.name}>
                                                    {file.name}
                                                    {file.is_primary && (
                                                        <span className="badge badge-success ml-1">{t('primary')}</span>
                                                    )}
                                                </div>
                                                <div className="small text-muted">
                                                    {file.formatted_size}
                                                    {file.uploaded_by ? ` — ${file.uploaded_by}` : ''}
                                                </div>
                                                {!!file.hashtags?.length && (
                                                    <div className="mt-1">
                                                        {file.hashtags.map((tag) => (
                                                            <span key={tag} className="badge badge-info mr-1">#{tag}</span>
                                                        ))}
                                                    </div>
                                                )}
                                                {file.comment && (
                                                    <div className="small text-muted font-italic">{file.comment}</div>
                                                )}
                                            </div>
                                        </div>

                                        {canEdit && (
                                            <div className="d-flex align-items-center mt-2">
                                                <select
                                                    className="form-control form-control-sm mr-1"
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
                                                        className="btn btn-outline-success btn-sm mr-1"
                                                        title={t('set_as_primary')}
                                                        onClick={(event) => { event.stopPropagation(); handlePrimary(file); }}
                                                    >
                                                        <i className="fas fa-star" />
                                                    </button>
                                                )}

                                                <a
                                                    className="btn btn-outline-secondary btn-sm mr-1"
                                                    href={file.download_url}
                                                    title={t('download')}
                                                    onClick={(event) => event.stopPropagation()}
                                                >
                                                    <i className="fas fa-download" />
                                                </a>

                                                <button
                                                    type="button"
                                                    className="btn btn-outline-danger btn-sm"
                                                    title={t('delete')}
                                                    onClick={(event) => { event.stopPropagation(); handleDelete(file); }}
                                                >
                                                    <i className="fas fa-trash" />
                                                </button>
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="col-lg-8">
                    <FileViewer file={selected} t={t} />
                </div>
            </div>
        </div>
    );
}
