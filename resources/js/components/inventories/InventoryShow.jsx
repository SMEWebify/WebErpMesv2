import React, { useEffect, useMemo, useRef, useState } from 'react';

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
    const [details, setDetails] = useState([]);
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);
    const [preview, setPreview] = useState(null);
    const [busy, setBusy] = useState(null);
    const [error, setError] = useState(null);
    const [varianceOnly, setVarianceOnly] = useState(true);
    const fileInputRef = useRef(null);

    async function reload() {
        setLoading(true);
        setError(null);
        const res = await fetch(endpoints.showJson, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        setInventory(json.inventory);
        setDetails(json.details ?? []);
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
    const importDone = inventory.statu === STATUS.EXPORTED || locked;

    return (
        <div>
            <Stepper statu={inventory.statu} trans={trans} />

            <ContextCard inventory={inventory} statusMeta={statusMeta} trans={trans} endpoints={endpoints} />

            {inventory.statu === STATUS.DRAFT && (
                <ExportStep endpoints={endpoints} trans={trans} />
            )}

            {inventory.statu === STATUS.EXPORTED && !locked && (
                <>
                    <ExportStep endpoints={endpoints} trans={trans} compact />
                    <ImportStep
                        fileInputRef={fileInputRef}
                        busy={busy}
                        trans={trans}
                        onFileChosen={onFileChosen}
                    />
                </>
            )}

            {preview?.errors?.length > 0 && (
                <ErrorsCard errors={preview.errors} trans={trans} />
            )}

            {preview && preview.errors?.length === 0 && preview.summary && !importDone && (
                <PreviewOkCard
                    summary={preview.summary}
                    trans={trans}
                    busy={busy}
                    onImport={() => fileInputRef.current?.files?.[0] && handleImport(fileInputRef.current.files[0])}
                />
            )}

            {importDone && summary && (
                <>
                    <BilanCard
                        summary={summary}
                        inventory={inventory}
                        endpoints={endpoints}
                        trans={trans}
                        locked={locked}
                        busy={busy}
                        onValidate={handleValidate}
                        onCancel={handleCancel}
                    />
                    <DetailsTable
                        details={details}
                        trans={trans}
                        varianceOnly={varianceOnly}
                        setVarianceOnly={setVarianceOnly}
                    />
                </>
            )}

            {error && <div className="alert alert-danger mt-3">{error}</div>}
        </div>
    );
}

// ─── Sub-components ────────────────────────────────────────────────────────────

function Stepper({ statu, trans }) {
    const steps = [
        { id: 1, key: 'step_draft',     icon: 'fas fa-file-alt' },
        { id: 2, key: 'step_exported',  icon: 'fas fa-file-download' },
        { id: 3, key: 'step_import',    icon: 'fas fa-file-upload' },
        { id: 4, key: 'step_validated', icon: 'fas fa-check-circle' },
    ];

    // Draft state = at step 1; exported state = between 2 and 3; validated = 4.
    const current = statu === 1 ? 1 : statu === 2 ? 3 : 4;

    return (
        <div className="card mb-3">
            <div className="card-body py-2">
                <div className="d-flex align-items-center justify-content-between">
                    {steps.map((s, i) => (
                        <React.Fragment key={s.id}>
                            <div className="text-center flex-shrink-0" style={{ minWidth: 100 }}>
                                <div
                                    className={`rounded-circle d-inline-flex align-items-center justify-content-center ${s.id <= current ? 'bg-primary text-white' : 'bg-light text-muted'}`}
                                    style={{ width: 40, height: 40 }}
                                >
                                    <i className={s.icon}></i>
                                </div>
                                <div className={`small mt-1 ${s.id <= current ? 'font-weight-bold' : 'text-muted'}`}>
                                    {trans[s.key]}
                                </div>
                            </div>
                            {i < steps.length - 1 && (
                                <div className="flex-grow-1" style={{ height: 2, background: s.id < current ? '#007bff' : '#dee2e6' }}></div>
                            )}
                        </React.Fragment>
                    ))}
                </div>
            </div>
        </div>
    );
}

function ContextCard({ inventory, statusMeta, trans, endpoints }) {
    return (
        <div className="card">
            <div className="card-body d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <span className={`badge ${statusMeta.badge} mr-2`}>{trans[statusMeta.key]}</span>
                    <span className="text-muted">
                        {inventory.creator?.name} · {trans.created_at}: {inventory.start_date}
                        {inventory.validated_at && ` · ${trans.validated_at}: ${new Date(inventory.validated_at).toLocaleDateString(trans.locale)}`}
                    </span>
                </div>
                <a href={endpoints.index} className="btn btn-sm btn-outline-secondary">
                    <i className="fas fa-arrow-left mr-1"></i>{trans.back}
                </a>
            </div>
        </div>
    );
}

function ExportStep({ endpoints, trans, compact }) {
    return (
        <div className="card mt-3">
            <div className="card-header">
                <strong><i className="fas fa-file-download mr-1"></i>{trans.export_step}</strong>
            </div>
            <div className="card-body">
                {!compact && (
                    <>
                        <p className="mb-2">{trans.export_hint}</p>
                        <div className="alert alert-info py-2 small mb-3">
                            <strong>{trans.xlsx_content_title}</strong>
                            <ul className="mb-0 mt-1">
                                <li>{trans.xlsx_content_line_1}</li>
                                <li>{trans.xlsx_content_line_2}</li>
                                <li>{trans.xlsx_content_line_3}</li>
                            </ul>
                        </div>
                    </>
                )}

                <a href={endpoints.export} className="btn btn-primary mr-2">
                    <i className="fas fa-file-download mr-1"></i>{trans.export_normal}
                </a>
                <a href={endpoints.exportBlind} className="btn btn-outline-danger" title={trans.export_blind_tooltip}>
                    <i className="fas fa-eye-slash mr-1"></i>{trans.export_blind}
                </a>

                {!compact && (
                    <div className="text-muted small mt-2">
                        <i className="fas fa-info-circle mr-1"></i>{trans.export_blind_tooltip}
                    </div>
                )}
            </div>
        </div>
    );
}

function ImportStep({ fileInputRef, busy, trans, onFileChosen }) {
    return (
        <div className="card mt-3">
            <div className="card-header">
                <strong><i className="fas fa-file-upload mr-1"></i>{trans.import_step}</strong>
            </div>
            <div className="card-body">
                <p className="mb-2">{trans.import_hint}</p>
                <div className="alert alert-warning py-2 small mb-3">
                    <i className="fas fa-exclamation-triangle mr-1"></i>
                    {trans.import_stock_change_warning}
                </div>

                <input
                    ref={fileInputRef}
                    type="file"
                    accept=".xlsx,.xls"
                    className="form-control-file"
                    onChange={onFileChosen}
                    disabled={busy !== null}
                />
                {busy === 'preview' && (
                    <div className="mt-2 text-muted"><i className="fas fa-spinner fa-spin mr-1"></i>{trans.analysing}</div>
                )}
            </div>
        </div>
    );
}

function ErrorsCard({ errors, trans }) {
    return (
        <div className="card mt-3 border-danger">
            <div className="card-header bg-danger text-white">
                <strong><i className="fas fa-times-circle mr-1"></i>{trans.errors_found} ({errors.length})</strong>
            </div>
            <div className="card-body">
                <p className="text-muted small mb-2">{trans.errors_hint}</p>
                <table className="table table-sm mb-0">
                    <thead>
                        <tr><th style={{ width: 80 }}>{trans.row}</th><th>Message</th></tr>
                    </thead>
                    <tbody>
                        {errors.slice(0, 50).map((err, i) => (
                            <tr key={i}><td>{err.row}</td><td>{err.message}</td></tr>
                        ))}
                    </tbody>
                </table>
                {errors.length > 50 && (
                    <div className="text-muted small mt-2">… {errors.length - 50} {trans.more_errors}</div>
                )}
            </div>
        </div>
    );
}

function PreviewOkCard({ summary, trans, busy, onImport }) {
    return (
        <div className="card mt-3 border-success">
            <div className="card-header bg-success text-white">
                <strong><i className="fas fa-check-circle mr-1"></i>{trans.preview_ok_title}</strong>
            </div>
            <div className="card-body">
                <div className="row mb-2">
                    <SummaryTile label={trans.total_lines}         value={summary.total_lines}           />
                    <SummaryTile label={trans.counted_lines}       value={summary.counted_lines}         />
                    <SummaryTile label={trans.positive_variance}   value={`+${summary.positive_variance_count}`} color="text-success" />
                    <SummaryTile label={trans.negative_variance}   value={`−${summary.negative_variance_count}`} color="text-danger" />
                </div>
                <p className="text-muted small">{trans.preview_ok_hint}</p>
                <button className="btn btn-primary" disabled={busy !== null} onClick={onImport}>
                    <i className="fas fa-upload mr-1"></i>{trans.confirm_import}
                </button>
            </div>
        </div>
    );
}

function BilanCard({ summary, inventory, endpoints, trans, locked, busy, onValidate, onCancel }) {
    return (
        <div className="card mt-3">
            <div className="card-header">
                <strong><i className="fas fa-clipboard-check mr-1"></i>{trans.summary_step}</strong>
            </div>
            <div className="card-body">
                <div className="row mb-3">
                    <SummaryTile label={trans.total_lines}         value={summary.total_lines}           />
                    <SummaryTile label={trans.counted_lines}       value={summary.counted_lines}         />
                    <SummaryTile label={trans.positive_variance}   value={`+${summary.positive_variance_count}`}                                color="text-success" />
                    <SummaryTile label={trans.negative_variance}   value={`−${summary.negative_variance_count}`}                                color="text-danger" />
                    <SummaryTile label={trans.value_impact}        value={`${formatMoney(summary.net_variance_value, trans.locale)}`}         color={summary.net_variance_value >= 0 ? 'text-success' : 'text-danger'} />
                </div>

                {!locked && (
                    <>
                        <div className="alert alert-warning py-2 small">
                            <i className="fas fa-exclamation-triangle mr-1"></i>
                            {trans.validate_incidence_hint}
                        </div>
                        <button className="btn btn-success mr-2" disabled={busy !== null} onClick={onValidate}>
                            <i className="fas fa-check mr-1"></i>{trans.validate}
                        </button>
                        <button className="btn btn-outline-danger" disabled={busy !== null} onClick={onCancel}>
                            <i className="fas fa-times mr-1"></i>{trans.cancel}
                        </button>
                    </>
                )}

                {locked && inventory.statu === 3 && (
                    <div>
                        <div className="alert alert-success py-2">
                            <i className="fas fa-check-circle mr-1"></i>
                            {trans.validated_summary}
                        </div>
                        {endpoints.fileRaw && (
                            <a href={endpoints.fileRaw} className="btn btn-sm btn-outline-secondary" target="_blank" rel="noreferrer">
                                <i className="fas fa-file-download mr-1"></i>{trans.download_counting_file}
                            </a>
                        )}
                    </div>
                )}

                {locked && inventory.statu === 4 && (
                    <div className="alert alert-secondary py-2">
                        <i className="fas fa-info-circle mr-1"></i>
                        {trans.cancelled_summary}
                    </div>
                )}
            </div>
        </div>
    );
}

function DetailsTable({ details, trans, varianceOnly, setVarianceOnly }) {
    const rows = useMemo(() => {
        return details
            .map(d => ({
                ...d,
                _variance: d.counted_qty !== null ? Number(d.counted_qty) - Number(d.theoretical_qty) : null,
            }))
            .filter(d => !varianceOnly || (d._variance !== null && d._variance !== 0));
    }, [details, varianceOnly]);

    return (
        <div className="card mt-3">
            <div className="card-header d-flex align-items-center justify-content-between">
                <strong><i className="fas fa-list mr-1"></i>{trans.details_step} ({rows.length}/{details.length})</strong>
                <label className="mb-0 small">
                    <input
                        type="checkbox"
                        checked={varianceOnly}
                        onChange={e => setVarianceOnly(e.target.checked)}
                        className="mr-1"
                    />
                    {trans.filter_variance_only}
                </label>
            </div>
            <div className="table-responsive" style={{ maxHeight: 500 }}>
                <table className="table table-sm table-hover mb-0">
                    <thead className="thead-light sticky-top">
                        <tr>
                            <th>{trans.code}</th>
                            <th>{trans.location}</th>
                            <th>{trans.batch}</th>
                            <th>X</th>
                            <th>Y</th>
                            <th>Z</th>
                            <th className="text-right">{trans.theoretical_qty}</th>
                            <th className="text-right">{trans.counted_qty}</th>
                            <th className="text-right">{trans.variance_qty}</th>
                            <th className="text-right">{trans.variance_value}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && (
                            <tr><td colSpan={10} className="text-center text-muted p-3">{trans.no_results}</td></tr>
                        )}
                        {rows.map(d => {
                            const varianceValue = d._variance !== null ? d._variance * Number(d.unit_cost) : null;
                            return (
                                <tr key={d.id} className={d._variance > 0 ? 'table-success' : d._variance < 0 ? 'table-danger' : ''}>
                                    <td><code>{d.product?.code}</code></td>
                                    <td>{d.stock_location_product?.stock_location?.code} / {d.stock_location_product?.code}</td>
                                    <td>{d.batch?.number ?? '—'}</td>
                                    <td>{d.x_size ?? ''}</td>
                                    <td>{d.y_size ?? ''}</td>
                                    <td>{d.z_size ?? ''}</td>
                                    <td className="text-right">{Number(d.theoretical_qty).toFixed(2)}</td>
                                    <td className="text-right">{d.counted_qty !== null ? Number(d.counted_qty).toFixed(2) : '—'}</td>
                                    <td className={`text-right ${d._variance > 0 ? 'text-success' : d._variance < 0 ? 'text-danger' : ''}`}>
                                        {d._variance !== null ? (d._variance > 0 ? '+' : '') + d._variance.toFixed(2) : '—'}
                                    </td>
                                    <td className="text-right">{varianceValue !== null ? formatMoney(varianceValue, trans.locale) : '—'}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function SummaryTile({ label, value, color }) {
    return (
        <div className="col-6 col-md">
            <div className="small text-muted">{label}</div>
            <div className={`h4 ${color ?? ''}`}>{value}</div>
        </div>
    );
}

function formatMoney(value, locale) {
    if (value === null || value === undefined) return '—';
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    } catch {
        return Number(value).toFixed(2);
    }
}
