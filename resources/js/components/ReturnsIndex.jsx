import React, { useState, useEffect, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const RETURN_STATUS = {
    1: { badge: 'badge-info',    key: 'status_received' },
    2: { badge: 'badge-primary', key: 'status_diagnosed' },
    3: { badge: 'badge-warning', key: 'status_in_rework' },
    4: { badge: 'badge-success', key: 'status_closed' },
};

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function apiHeaders() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    };
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, { headers: apiHeaders(), ...options });
    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw { status: res.status, errors: body.errors ?? {}, message: body.message ?? `HTTP ${res.status}` };
    }
    return res.json();
}

function endpointFor(template, id) {
    return template.replace('__ID__', id);
}

// ---------------------------------------------------------------------------
// SortIcon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;

    const cur  = meta.current_page;
    const last = meta.last_page;
    const visible = new Set([1, last, cur - 1, cur, cur + 1]
        .filter(p => p >= 1 && p <= last));
    const sorted = [...visible].sort((a, b) => a - b);

    return (
        <nav className="d-flex justify-content-between align-items-center px-3 pb-2">
            <small className="text-muted">{meta.total} résultat{meta.total > 1 ? 's' : ''}</small>
            <ul className="pagination pagination-sm mb-0">
                <li className={`page-item ${cur === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(cur - 1)}>«</button>
                </li>
                {sorted.map((p, i) => (
                    <React.Fragment key={p}>
                        {i > 0 && sorted[i - 1] < p - 1 && (
                            <li className="page-item disabled"><span className="page-link">…</span></li>
                        )}
                        <li className={`page-item ${p === cur ? 'active' : ''}`}>
                            <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                        </li>
                    </React.Fragment>
                ))}
                <li className={`page-item ${cur === last ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(cur + 1)}>»</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Alert flash
// ---------------------------------------------------------------------------

function Flash({ msg, type, onClose }) {
    if (!msg) return null;
    return (
        <div className={`alert alert-${type} alert-dismissible`} role="alert">
            {msg}
            <button type="button" className="close" onClick={onClose}><span>&times;</span></button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Create form
// ---------------------------------------------------------------------------

function CreateForm({ endpoints, props, trans, onCreated, onCancel }) {
    const emptyLine = () => ({ delivery_line_id: '', original_task_id: '', qty: '', issue_description: '', rework_instructions: '' });

    const [code]          = useState(props.nextCode ?? '');
    const [label, setLabel]       = useState(props.nextCode ?? '');
    const [deliverysId, setDeliverysId]       = useState('');
    const [ncId, setNcId]         = useState('');
    const [report, setReport]     = useState('');
    const [lines, setLines]       = useState([emptyLine()]);
    const [deliveryLines, setDeliveryLines] = useState([]);
    const [errors, setErrors]     = useState({});
    const [saving, setSaving]     = useState(false);

    async function handleDeliveryChange(id) {
        setDeliverysId(id);
        if (!id) { setDeliveryLines([]); return; }
        try {
            const res = await apiFetch(`/fr/deliverys/json/${id}/lines`).catch(() => null);
            if (res?.data) {
                setDeliveryLines(res.data);
            }
        } catch { /* ignore — endpoint might not exist yet */ }
    }

    function addLine() {
        setLines(prev => [...prev, emptyLine()]);
    }

    function removeLine(i) {
        setLines(prev => prev.filter((_, idx) => idx !== i));
    }

    function updateLine(i, key, value) {
        setLines(prev => prev.map((l, idx) => idx === i ? { ...l, [key]: value } : l));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({ code, label, deliverys_id: deliverysId || null, quality_non_conformity_id: ncId || null, customer_report: report, lines }),
            });
            onCreated();
        } catch (err) {
            setErrors(err.errors ?? {});
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="card card-outline card-info mb-4">
            <div className="card-header py-2">
                <h3 className="card-title"><i className="fas fa-plus mr-1" />{trans.add_return}</h3>
            </div>
            <div className="card-body">
                <form onSubmit={handleSubmit}>
                    <div className="form-row">
                        <div className="form-group col-md-4">
                            <label>{trans.code}</label>
                            <input type="text" className="form-control" value={code} readOnly />
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.label}</label>
                            <input type="text" className={`form-control ${errors.label ? 'is-invalid' : ''}`} value={label} onChange={e => setLabel(e.target.value)} />
                            {errors.label && <div className="invalid-feedback">{errors.label[0]}</div>}
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.delivery}</label>
                            <select className="form-control" value={deliverysId} onChange={e => handleDeliveryChange(e.target.value)}>
                                <option value="">{trans.choose}</option>
                                {props.deliveries?.map(d => <option key={d.id} value={d.id}>{d.code}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="form-row">
                        <div className="form-group col-md-4">
                            <label>{trans.non_conformity}</label>
                            <select className="form-control" value={ncId} onChange={e => setNcId(e.target.value)}>
                                <option value="">{trans.choose}</option>
                                {props.nonConformities?.map(nc => <option key={nc.id} value={nc.id}>{nc.code}</option>)}
                            </select>
                        </div>
                        <div className="form-group col-md-8">
                            <label>{trans.customer_report}</label>
                            <textarea className="form-control" rows="2" value={report} onChange={e => setReport(e.target.value)} />
                        </div>
                    </div>

                    <h6 className="mt-2">{trans.lines}</h6>
                    {errors.lines && <div className="text-danger small mb-2">{errors.lines[0]}</div>}

                    {lines.map((line, i) => (
                        <div key={i} className="border rounded p-3 mb-2">
                            <div className="form-row">
                                <div className="form-group col-md-4">
                                    <label>{trans.delivery_line}</label>
                                    <select className="form-control form-control-sm" value={line.delivery_line_id} onChange={e => updateLine(i, 'delivery_line_id', e.target.value)}>
                                        <option value="">{trans.choose}</option>
                                        {deliveryLines.map(dl => <option key={dl.id} value={dl.id}>{dl.ordre} - {dl.label}</option>)}
                                    </select>
                                </div>
                                <div className="form-group col-md-3">
                                    <label>{trans.task}</label>
                                    <input type="number" className="form-control form-control-sm" value={line.original_task_id} onChange={e => updateLine(i, 'original_task_id', e.target.value)} />
                                </div>
                                <div className="form-group col-md-2">
                                    <label>{trans.qty}</label>
                                    <input type="number" min="1" className="form-control form-control-sm" value={line.qty} onChange={e => updateLine(i, 'qty', e.target.value)} />
                                </div>
                                <div className="form-group col-md-1 d-flex align-items-end">
                                    <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => removeLine(i)} disabled={lines.length === 1}>
                                        <i className="fas fa-trash" />
                                    </button>
                                </div>
                            </div>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label>{trans.issue}</label>
                                    <textarea className="form-control form-control-sm" rows="2" value={line.issue_description} onChange={e => updateLine(i, 'issue_description', e.target.value)} />
                                </div>
                                <div className="form-group col-md-6">
                                    <label>{trans.action}</label>
                                    <textarea className="form-control form-control-sm" rows="2" value={line.rework_instructions} onChange={e => updateLine(i, 'rework_instructions', e.target.value)} />
                                </div>
                            </div>
                        </div>
                    ))}

                    <button type="button" className="btn btn-sm btn-outline-primary mb-3" onClick={addLine}>
                        <i className="fas fa-plus mr-1" />{trans.add_line}
                    </button>

                    <div className="d-flex justify-content-end" style={{ gap: 8 }}>
                        <button type="button" className="btn btn-secondary" onClick={onCancel}>{trans.choose === 'Choisir' ? 'Annuler' : 'Cancel'}</button>
                        <button type="submit" className="btn btn-success" disabled={saving}>
                            <i className="fas fa-save mr-1" />{trans.save}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Diagnosis inline form
// ---------------------------------------------------------------------------

function DiagnosisForm({ ret, endpoint, trans, onDone, onCancel }) {
    const [notes, setNotes]   = useState(ret.diagnosis ?? '');
    const [report, setReport] = useState(ret.customer_report ?? '');
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            await apiFetch(endpoint, { method: 'POST', body: JSON.stringify({ diagnosis: notes, customer_report: report }) });
            onDone();
        } catch (err) {
            setErrors(err.errors ?? {});
        } finally {
            setSaving(false);
        }
    }

    return (
        <tr>
            <td colSpan={7}>
                <form onSubmit={handleSubmit} className="p-2">
                    <div className="form-row">
                        <div className="form-group col-md-6">
                            <label>{trans.diagnosis}</label>
                            <textarea className={`form-control form-control-sm ${errors.diagnosis ? 'is-invalid' : ''}`} rows="3" value={notes} onChange={e => setNotes(e.target.value)} />
                            {errors.diagnosis && <div className="invalid-feedback">{errors.diagnosis[0]}</div>}
                        </div>
                        <div className="form-group col-md-6">
                            <label>{trans.customer_report}</label>
                            <textarea className="form-control form-control-sm" rows="3" value={report} onChange={e => setReport(e.target.value)} />
                        </div>
                    </div>
                    <div style={{ gap: 6, display: 'flex' }}>
                        <button type="submit" className="btn btn-sm btn-success" disabled={saving}>
                            <i className="fas fa-save mr-1" />{trans.save}
                        </button>
                        <button type="button" className="btn btn-sm btn-secondary" onClick={onCancel}>×</button>
                    </div>
                </form>
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// Closure inline form
// ---------------------------------------------------------------------------

function ClosureForm({ ret, endpoint, trans, onDone, onCancel }) {
    const [notes, setNotes] = useState(ret.resolution_notes ?? '');
    const [saving, setSaving] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        try {
            await apiFetch(endpoint, { method: 'POST', body: JSON.stringify({ resolution_notes: notes }) });
            onDone();
        } finally {
            setSaving(false);
        }
    }

    return (
        <tr>
            <td colSpan={7}>
                <form onSubmit={handleSubmit} className="p-2">
                    <div className="form-group">
                        <label>{trans.closure_comment}</label>
                        <textarea className="form-control form-control-sm" rows="3" value={notes} onChange={e => setNotes(e.target.value)} />
                    </div>
                    <div style={{ gap: 6, display: 'flex' }}>
                        <button type="submit" className="btn btn-sm btn-success" disabled={saving}>
                            <i className="fas fa-check mr-1" />{trans.close_return}
                        </button>
                        <button type="button" className="btn btn-sm btn-secondary" onClick={onCancel}>×</button>
                    </div>
                </form>
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function ReturnsIndex({ endpoints, props, trans }) {
const [rows, setRows]         = useState([]);
    const [meta, setMeta]         = useState(null);
    const [loading, setLoading]   = useState(true);
    const [fetchError, setFetchError] = useState('');
    const [page, setPage]         = useState(1);
    const [search, setSearch]     = useState('');
    const [status, setStatus]     = useState('');
    const [sort, setSort]         = useState({ field: 'created_at', asc: false });

    const [showCreate, setShowCreate]           = useState(false);
    const [diagnosisId, setDiagnosisId]         = useState(null);
    const [closingId, setClosingId]             = useState(null);
    const [flash, setFlash]                     = useState({ msg: '', type: 'success' });

    const showFlash = (msg, type = 'success') => {
        setFlash({ msg, type });
        setTimeout(() => setFlash({ msg: '', type: 'success' }), 4000);
    };

    const fetchData = useCallback(async () => {
        if (!endpoints?.list) {
            console.error('ReturnsIndex: endpoints.list is missing', endpoints);
            setLoading(false);
            setFetchError('Configuration manquante : endpoint list non défini');
            return;
        }
        setLoading(true);
        try {
            setFetchError('');
            const params = new URLSearchParams({ search, status, sort: sort.field, asc: sort.asc ? '1' : '0', page });
            const url = `${endpoints.list}?${params}`;
            const json = await apiFetch(url);
            setRows(json.data ?? []);
            setMeta(json.meta ?? null);
        } catch (err) {
            console.error('ReturnsIndex fetch error:', err);
            setFetchError(err?.message ?? 'Erreur de chargement');
            setRows([]);
        } finally {
            setLoading(false);
        }
    }, [endpoints?.list, search, status, sort, page]);

    useEffect(() => { fetchData(); }, [fetchData]);

    function handleSort(field) {
        setSort(prev => ({ field, asc: prev.field === field ? !prev.asc : false }));
        setPage(1);
    }

    function handleSearch(val) {
        setSearch(val);
        setPage(1);
    }

    function handleStatus(val) {
        setStatus(val);
        setPage(1);
    }

    async function handleReopen(ret) {
        try {
            const res = await apiFetch(endpointFor(endpoints.reopen, ret.id), { method: 'POST' });
            showFlash(res.message);
            fetchData();
        } catch (err) {
            showFlash(err.message, 'danger');
        }
    }

    function afterAction(msg) {
        showFlash(msg);
        setDiagnosisId(null);
        setClosingId(null);
        setShowCreate(false);
        fetchData();
    }

    const statusOptions = [
        { value: '1', label: trans.status_received },
        { value: '2', label: trans.status_diagnosed },
        { value: '3', label: trans.status_in_rework },
        { value: '4', label: trans.status_closed },
    ];

    return (
        <div>
            <Flash msg={flash.msg} type={flash.type} onClose={() => setFlash({ msg: '', type: 'success' })} />
            {fetchError && (
                <div className="alert alert-danger alert-dismissible py-2">
                    <i className="fas fa-exclamation-triangle mr-1" />{fetchError}
                    <button type="button" className="close py-1" onClick={() => setFetchError('')}><span>&times;</span></button>
                </div>
            )}

            {showCreate && (
                <CreateForm
                    endpoints={endpoints}
                    props={props}
                    trans={trans}
                    onCreated={() => { showFlash(trans.save); setShowCreate(false); fetchData(); }}
                    onCancel={() => setShowCreate(false)}
                />
            )}

            <div className="card card-outline card-primary">
                <div className="card-header py-2">
                    <div className="d-flex flex-wrap align-items-center" style={{ gap: 8 }}>
                        {/* Search */}
                        <div className="input-group" style={{ maxWidth: 240 }}>
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-search" /></span>
                            </div>
                            <input
                                type="text"
                                className="form-control form-control-sm"
                                placeholder={trans.search}
                                value={search}
                                onChange={e => handleSearch(e.target.value)}
                            />
                        </div>

                        {/* Status filter */}
                        <select className="form-control form-control-sm" style={{ maxWidth: 160 }} value={status} onChange={e => handleStatus(e.target.value)}>
                            <option value="">{trans.all}</option>
                            {statusOptions.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>

                        <div style={{ flex: 1 }} />

                        <button className="btn btn-sm btn-primary" onClick={() => setShowCreate(v => !v)}>
                            <i className="fas fa-plus mr-1" />{trans.add_return}
                        </button>
                    </div>
                </div>

                <div className="table-responsive">
                    <table className="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('code')}>
                                    {trans.code}<SortIcon field="code" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('label')}>
                                    {trans.label}<SortIcon field="label" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th>{trans.delivery}</th>
                                <th>{trans.non_conformity}</th>
                                <th>{trans.status}</th>
                                <th style={{ cursor: 'pointer' }} onClick={() => handleSort('created_at')}>
                                    {trans.created_at}<SortIcon field="created_at" sortField={sort.field} sortAsc={sort.asc} />
                                </th>
                                <th>{trans.actions}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={7} className="text-center py-4">
                                        <i className="fas fa-spinner fa-spin" /> {trans.loading}
                                    </td>
                                </tr>
                            ) : rows.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="text-center py-4 text-muted">{trans.no_data}</td>
                                </tr>
                            ) : rows.map(row => {
                                const statusCfg = RETURN_STATUS[row.statu] ?? { badge: 'badge-secondary', key: '' };
                                return (
                                    <React.Fragment key={row.id}>
                                        <tr>
                                            <td><code>{row.code}</code></td>
                                            <td>{row.label}</td>
                                            <td>{row.delivery?.code ?? '—'}</td>
                                            <td>{row.non_conformity?.code ?? '—'}</td>
                                            <td>
                                                <span className={`badge ${statusCfg.badge}`}>
                                                    {trans[statusCfg.key] ?? row.status_label}
                                                </span>
                                            </td>
                                            <td>{row.created_at}</td>
                                            <td>
                                                <div className="d-flex flex-wrap" style={{ gap: 4 }}>
                                                    <button
                                                        className="btn btn-xs btn-outline-info"
                                                        title={trans.diagnosis}
                                                        onClick={() => setDiagnosisId(v => v === row.id ? null : row.id)}
                                                    >
                                                        <i className="fas fa-stethoscope" />
                                                    </button>
                                                    <button
                                                        className="btn btn-xs btn-outline-secondary"
                                                        title={trans.reopen_tasks}
                                                        disabled={row.statu >= 4}
                                                        onClick={() => handleReopen(row)}
                                                    >
                                                        <i className="fas fa-undo" />
                                                    </button>
                                                    <button
                                                        className="btn btn-xs btn-outline-success"
                                                        title={trans.close_return}
                                                        disabled={row.statu === 4}
                                                        onClick={() => setClosingId(v => v === row.id ? null : row.id)}
                                                    >
                                                        <i className="fas fa-check" />
                                                    </button>
                                                    <a href={row.url} className="btn btn-xs btn-outline-primary" title={trans.view}>
                                                        <i className="fas fa-eye" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        {diagnosisId === row.id && (
                                            <DiagnosisForm
                                                ret={row}
                                                endpoint={endpointFor(endpoints.diagnose, row.id)}
                                                trans={trans}
                                                onDone={() => afterAction(trans.save)}
                                                onCancel={() => setDiagnosisId(null)}
                                            />
                                        )}

                                        {closingId === row.id && (
                                            <ClosureForm
                                                ret={row}
                                                endpoint={endpointFor(endpoints.close, row.id)}
                                                trans={trans}
                                                onDone={() => afterAction(trans.close_return)}
                                                onCancel={() => setClosingId(null)}
                                            />
                                        )}
                                    </React.Fragment>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={meta} onPage={p => setPage(p)} />
            </div>
        </div>
    );
}
