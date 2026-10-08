import React, { useState, useEffect, useCallback } from 'react';
import { DataTable, Pagination, StatusFilter, MobileFilters } from './table';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const RETURN_STATUS = {
    1: { badge: 'badge-info',    key: 'status_received' },
    2: { badge: 'badge-primary', key: 'status_diagnosed' },
    3: { badge: 'badge-warning', key: 'status_in_rework' },
    4: { badge: 'badge-success', key: 'status_closed' },
};

// Même table au format attendu par StatusFilter ({ badge, label } = clé de traduction).
const STATUS_FILTER_CONFIG = Object.fromEntries(
    Object.entries(RETURN_STATUS).map(([id, cfg]) => [id, { badge: cfg.badge, label: cfg.key }]),
);

const LS_COL_ORDER   = 'returns_table_col_order';
const LS_HIDDEN_COLS = 'returns_table_hidden_cols';

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
    );
}

// ---------------------------------------------------------------------------
// Table — colonnes déclarées, rendu par le DataTable partagé
// ---------------------------------------------------------------------------

function returnColumns(trans) {
    return [
        { key: 'code',           label: trans.code,           sortable: true,
          render: r => <code>{r.code}</code>, filter: 'text', mobile: 'title', mobileRender: r => r.code },
        { key: 'label',          label: trans.label,          sortable: true,
          filter: 'text', mobile: 'subtitle' },
        { key: 'delivery',       label: trans.delivery,
          render: r => r.delivery?.code ?? '—', filterValue: r => r.delivery?.code, filter: 'text',
          mobile: 'subtitle', mobileOrder: 1, mobileRender: r => r.delivery?.code },
        { key: 'non_conformity', label: trans.non_conformity,
          render: r => r.non_conformity?.code ?? '—', filterValue: r => r.non_conformity?.code, filter: 'text' },
        { key: 'statu',          label: trans.status,         sortable: true,
          render: r => {
              const cfg = RETURN_STATUS[r.statu] ?? { badge: 'badge-secondary', key: '' };
              return <span className={`badge ${cfg.badge}`}>{trans[cfg.key] ?? r.status_label}</span>;
          },
          mobile: 'badge' },
        { key: 'created_at',     label: trans.created_at,     sortable: true,
          filter: 'date', mobile: 'subtitle' },
    ];
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
    const [statuses, setStatuses] = useState([]);
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
            const params = new URLSearchParams({ search, sort: sort.field, asc: sort.asc ? '1' : '0', page });
            statuses.forEach(s => params.append('statuses[]', s));
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
    }, [endpoints?.list, search, statuses, sort, page]);

    useEffect(() => { fetchData(); }, [fetchData]);

    function handleSort(field) {
        setSort(prev => ({ field, asc: prev.field === field ? !prev.asc : false }));
        setPage(1);
    }

    function handleSearch(val) {
        setSearch(val);
        setPage(1);
    }

    function handleStatusToggle(id) {
        setStatuses(prev => (prev.includes(id) ? prev.filter(s => s !== id) : [...prev, id]));
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
                        <MobileFilters count={statuses.length} trans={trans}>
                            <StatusFilter config={STATUS_FILTER_CONFIG} selected={statuses} onToggle={handleStatusToggle} trans={trans} buttonType="button" />
                        </MobileFilters>

                        <div style={{ flex: 1 }} />

                        <button className="btn btn-sm btn-primary" onClick={() => setShowCreate(v => !v)}>
                            <i className="fas fa-plus mr-1" />{trans.add_return}
                        </button>
                    </div>
                </div>

                <DataTable
                    rows={rows}
                    columns={returnColumns(trans)}
                    loading={loading}
                    trans={trans}
                    sortField={sort.field}
                    sortAsc={sort.asc}
                    onSort={handleSort}
                    storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN_COLS }}
                    unsortableIcon={false}
                    tableClassName="table table-hover table-sm mb-0"
                    loadingContent={<><i className="fas fa-spinner fa-spin" /> {trans.loading}</>}
                    emptyText={trans.no_data}
                    actionsHeader={trans.actions}
                    actionsWidth={170}
                    actionsCellStyle={{ whiteSpace: 'nowrap' }}
                    rowHref={r => r.url}
                    rowActions={row => (
                        <div className="d-flex" style={{ gap: 4 }}>
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
                            <a href={row.url} className="btn btn-xs btn-info" title={trans.view}>
                                <i className="fas fa-eye" />
                            </a>
                        </div>
                    )}
                    mobileActions={row => (
                        <div className="d-flex flex-wrap" style={{ gap: '0.5rem' }}>
                            <button type="button" className="btn btn-outline-info" style={{ minHeight: 44 }}
                                onClick={() => setDiagnosisId(v => v === row.id ? null : row.id)}>
                                <i className="fas fa-stethoscope mr-1" />{trans.diagnosis}
                            </button>
                            <button type="button" className="btn btn-outline-secondary" style={{ minHeight: 44 }}
                                disabled={row.statu >= 4} onClick={() => handleReopen(row)}>
                                <i className="fas fa-undo mr-1" />{trans.reopen_tasks}
                            </button>
                            <button type="button" className="btn btn-outline-success" style={{ minHeight: 44 }}
                                disabled={row.statu === 4} onClick={() => setClosingId(v => v === row.id ? null : row.id)}>
                                <i className="fas fa-check mr-1" />{trans.close_return}
                            </button>
                        </div>
                    )}
                    renderExpanded={row => (diagnosisId === row.id || closingId === row.id) && (
                        <>
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
                        </>
                    )}
                />

                <Pagination meta={meta} around={1} boundaries showTotal navClassName="d-flex justify-content-between align-items-center px-3 pb-2" ulClassName="pagination pagination-sm mb-0" onPage={p => setPage(p)} />
            </div>
        </div>
    );
}
