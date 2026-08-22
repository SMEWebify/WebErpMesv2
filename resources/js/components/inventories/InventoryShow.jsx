import React, { useEffect, useRef, useState } from 'react';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

const STATUS = {
    DRAFT:     1,
    EXPORTED:  2,
    VALIDATED: 3,
    CANCELLED: 4,
};

const STATUS_META = {
    1: { key: 'status_draft',     badge: 'badge-secondary' },
    2: { key: 'status_exported',  badge: 'badge-info' },
    3: { key: 'status_validated', badge: 'badge-success' },
    4: { key: 'status_cancelled', badge: 'badge-danger' },
};

export default function InventoryShow({ endpoints, trans }) {
    const [inventory, setInventory] = useState(null);
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);
    const [preview, setPreview] = useState(null); // { errors: [], rows: [], summary: {} }
    const [busy, setBusy] = useState(null); // 'preview' | 'import' | 'validate' | 'cancel'
    const [error, setError] = useState(null);
    const fileInputRef = useRef(null);

    async function reload() {
        setLoading(true);
        setError(null);
        const res = await fetch(endpoints.showJson, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        setInventory(json.inventory);
        setSummary(json.summary);
        setLoading(false);
    }

    useEffect(() => { reload(); }, []);

    async function handlePreview(file) {
        setBusy('preview');
        setError(null);
        const form = new FormData();
        form.append('file', file);
        try {
            const res = await fetch(endpoints.importPreview, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: form,
            });
            const json = await res.json();
            setPreview(json);
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(null);
        }
    }

    async function handleImport(file) {
        setBusy('import');
        setError(null);
        const form = new FormData();
        form.append('file', file);
        try {
            const res = await fetch(endpoints.import, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: form,
            });
            const json = await res.json();
            if (!res.ok) {
                setPreview(json);
                setError(json.message ?? trans.errors_found);
                return;
            }
            setPreview(json);
            await reload();
        } catch (e) {
            setError(e.message);
        } finally {
            setBusy(null);
        }
    }

    async function handleValidate() {
        if (!window.confirm(trans.confirm_validate)) return;
        setBusy('validate');
        setError(null);
        try {
            const res = await fetch(endpoints.validate, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            });
            const json = await res.json();
            if (!res.ok) {
                setError(json.message ?? 'Erreur');
                return;
            }
            await reload();
        } finally {
            setBusy(null);
        }
    }

    async function handleCancel() {
        if (!window.confirm(trans.confirm_cancel)) return;
        setBusy('cancel');
        setError(null);
        try {
            const res = await fetch(endpoints.cancel, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            });
            const json = await res.json();
            if (!res.ok) {
                setError(json.message ?? 'Erreur');
                return;
            }
            await reload();
        } finally {
            setBusy(null);
        }
    }

    function onFileChosen(e) {
        const file = e.target.files?.[0];
        if (file) handlePreview(file);
    }

    if (loading || !inventory) return <div className="text-muted p-3">…</div>;

    const statusMeta = STATUS_META[inventory.statu] ?? STATUS_META[1];
    const locked = inventory.statu === STATUS.VALIDATED || inventory.statu === STATUS.CANCELLED;

    return (
        <div>
            {/* Header */}
            <div className="card">
                <div className="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span className={`badge ${statusMeta.badge} mr-2`}>{trans[statusMeta.key]}</span>
                        <span className="text-muted">
                            {inventory.creator?.name} · {inventory.start_date}
                            {inventory.validated_at && ` · ${trans.status_validated}: ${new Date(inventory.validated_at).toLocaleDateString(trans.locale)}`}
                        </span>
                    </div>
                    <a href={endpoints.index} className="btn btn-sm btn-outline-secondary">
                        <i className="fas fa-arrow-left mr-1"></i>{trans.back}
                    </a>
                </div>
            </div>

            {/* Export step */}
            {!locked && (
                <div className="card mt-3">
                    <div className="card-header">
                        <strong>1. {trans.export_step}</strong>
                    </div>
                    <div className="card-body">
                        <p className="text-muted">{trans.export_hint}</p>
                        <a href={endpoints.export} className="btn btn-primary mr-2">
                            <i className="fas fa-file-download mr-1"></i>{trans.export_normal}
                        </a>
                        <a href={endpoints.exportBlind} className="btn btn-outline-danger">
                            <i className="fas fa-eye-slash mr-1"></i>{trans.export_blind}
                        </a>
                    </div>
                </div>
            )}

            {/* Import step */}
            {!locked && (
                <div className="card mt-3">
                    <div className="card-header"><strong>2. {trans.import_step}</strong></div>
                    <div className="card-body">
                        <p className="text-muted">{trans.import_hint}</p>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".xlsx,.xls"
                            className="form-control-file"
                            onChange={onFileChosen}
                            disabled={busy !== null}
                        />
                        {busy === 'preview' && <div className="mt-2 text-muted">…</div>}
                    </div>
                </div>
            )}

            {/* Errors */}
            {preview && preview.errors && preview.errors.length > 0 && (
                <div className="card mt-3 border-danger">
                    <div className="card-header bg-danger text-white">
                        <strong>{trans.errors_found} ({preview.errors.length})</strong>
                    </div>
                    <div className="card-body p-0">
                        <table className="table table-sm mb-0">
                            <thead>
                                <tr><th>{trans.row}</th><th>Message</th></tr>
                            </thead>
                            <tbody>
                                {preview.errors.slice(0, 50).map((err, i) => (
                                    <tr key={i}><td>{err.row}</td><td>{err.message}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Preview OK — confirm import */}
            {preview && preview.errors && preview.errors.length === 0 && !locked && inventory.statu !== STATUS.EXPORTED && (
                <div className="card mt-3 border-success">
                    <div className="card-body d-flex justify-content-between align-items-center">
                        <span>
                            <i className="fas fa-check-circle text-success mr-1"></i>
                            {preview.summary.total_lines} lignes, {preview.summary.counted_lines} comptées, {preview.summary.positive_variance_count}+ / {preview.summary.negative_variance_count}−
                        </span>
                        <button
                            className="btn btn-primary"
                            disabled={busy !== null}
                            onClick={() => fileInputRef.current?.files?.[0] && handleImport(fileInputRef.current.files[0])}
                        >
                            <i className="fas fa-upload mr-1"></i>{trans.import}
                        </button>
                    </div>
                </div>
            )}

            {/* Summary + Validate/Cancel */}
            {(inventory.statu === STATUS.EXPORTED || locked) && summary && (
                <div className="card mt-3">
                    <div className="card-header"><strong>3. {trans.summary_step}</strong></div>
                    <div className="card-body">
                        <div className="row">
                            <SummaryTile label={trans.total_lines}       value={summary.total_lines}   />
                            <SummaryTile label={trans.counted_lines}     value={summary.counted_lines} />
                            <SummaryTile label={trans.positive_variance} value={`+${summary.positive_variance_count} / ${summary.positive_variance_value}`} color="text-success" />
                            <SummaryTile label={trans.negative_variance} value={`${summary.negative_variance_count} / ${summary.negative_variance_value}`}   color="text-danger" />
                            <SummaryTile label={trans.net_variance}      value={summary.net_variance_value} />
                        </div>

                        {!locked && (
                            <div className="mt-3">
                                <button className="btn btn-success mr-2" disabled={busy !== null} onClick={handleValidate}>
                                    <i className="fas fa-check mr-1"></i>{trans.validate}
                                </button>
                                <button className="btn btn-outline-danger" disabled={busy !== null} onClick={handleCancel}>
                                    <i className="fas fa-times mr-1"></i>{trans.cancel}
                                </button>
                            </div>
                        )}

                        {locked && (
                            <div className="mt-3 text-muted">
                                {endpoints.fileRaw && (
                                    <a href={endpoints.fileRaw} className="btn btn-sm btn-outline-secondary">
                                        <i className="fas fa-file-download mr-1"></i>{trans.download_counting_file}
                                    </a>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {error && <div className="alert alert-danger mt-3">{error}</div>}
        </div>
    );
}

function SummaryTile({ label, value, color }) {
    return (
        <div className="col-sm-6 col-md">
            <div className="small text-muted">{label}</div>
            <div className={`h4 ${color ?? ''}`}>{value}</div>
        </div>
    );
}
