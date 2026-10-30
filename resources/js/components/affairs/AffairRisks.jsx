import React, { useCallback, useEffect, useMemo, useState } from 'react';
import DataTable from '../table/DataTable.jsx';
import { request, fmt, formatDate, todayIso } from './affairShared.jsx';
import {
    SCALE, criticalityClass, statusClass, closeNeedsPermission, emptyDraft, draftFrom, riskPayload, isOverdue,
} from '../../lib/affairRisks.js';

/**
 * Onglet « Risques » de la fiche opportunité : registre des risques de l'affaire
 * (catégorie, probabilité × gravité, action de maîtrise, responsable, échéance,
 * statut). Pas de suppression : le registre est journalisé.
 */

const LS_COL_ORDER = 'affair_risks_col_order';
const LS_HIDDEN = 'affair_risks_hidden_cols';

const SORTERS = {
    label: r => (r.label ?? '').toLowerCase(),
    category: r => r.category ?? '',
    criticality: r => r.criticality ?? 0,
    owner_name: r => (r.owner_name ?? '').toLowerCase(),
    due_date: r => r.due_date ?? '9999',
    status: r => r.status ?? '',
};

function RiskForm({ risk, meta, users, trans, onSaved, onCancel, endpoints }) {
    const t = trans.risks ?? {};
    const f = t.fields ?? {};
    const [draft, setDraft] = useState(() => (risk ? draftFrom(risk) : emptyDraft(meta.categories)));
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    const set = (key) => (e) => setDraft(d => ({ ...d, [key]: e.target.value }));
    const value = Number(draft.probability) * Number(draft.severity);
    const restricted = !meta.can_close && closeNeedsPermission(risk, draft, meta.threshold);

    const submit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const url = risk ? endpoints.update.replace('__RISK__', risk.id) : endpoints.store;
            const data = await request(url, { method: risk ? 'PUT' : 'POST', body: riskPayload(draft) });
            onSaved(data);
        } catch (err) {
            setError(err.message || t.error);
        } finally {
            setSaving(false);
        }
    };

    return (
        <form className="card card-outline card-warning mb-3" onSubmit={submit}>
            <div className="card-header">
                <h3 className="card-title m-0 fs-6">{risk ? t.edit : t.new}</h3>
            </div>
            <div className="card-body">
                {error && <div className="alert alert-danger py-2">{error}</div>}
                <div className="row g-2">
                    <div className="col-12 col-md-8">
                        <label className="form-label small mb-0" htmlFor="risk-label">{f.label}</label>
                        <input id="risk-label" className="form-control" required maxLength={255} value={draft.label} onChange={set('label')} />
                    </div>
                    <div className="col-12 col-md-4">
                        <label className="form-label small mb-0" htmlFor="risk-category">{f.category}</label>
                        <select id="risk-category" className="form-select" required value={draft.category} onChange={set('category')}>
                            {meta.categories.map(c => <option key={c} value={c}>{t.categories?.[c] ?? c}</option>)}
                        </select>
                    </div>
                    <div className="col-12">
                        <label className="form-label small mb-0" htmlFor="risk-description">{f.description}</label>
                        <textarea id="risk-description" className="form-control" rows={2} value={draft.description} onChange={set('description')} />
                    </div>
                    <div className="col-6 col-md-3">
                        <label className="form-label small mb-0" htmlFor="risk-probability">{f.probability}</label>
                        <select id="risk-probability" className="form-select" value={draft.probability} onChange={set('probability')}>
                            {SCALE.map(n => <option key={n} value={n}>{t.scale?.[n] ?? n}</option>)}
                        </select>
                    </div>
                    <div className="col-6 col-md-3">
                        <label className="form-label small mb-0" htmlFor="risk-severity">{f.severity}</label>
                        <select id="risk-severity" className="form-select" value={draft.severity} onChange={set('severity')}>
                            {SCALE.map(n => <option key={n} value={n}>{t.scale?.[n] ?? n}</option>)}
                        </select>
                    </div>
                    <div className="col-6 col-md-2 d-flex flex-column">
                        <span className="form-label small mb-0">{f.criticality}</span>
                        <span className={`badge fs-6 ${criticalityClass(value, meta.threshold)}`}>{value}</span>
                    </div>
                    <div className="col-6 col-md-4">
                        <label className="form-label small mb-0" htmlFor="risk-status">{f.status}</label>
                        <select id="risk-status" className="form-select" value={draft.status} onChange={set('status')}>
                            {meta.statuses.map(s => <option key={s} value={s}>{t.statuses?.[s] ?? s}</option>)}
                        </select>
                    </div>
                    <div className="col-12">
                        <label className="form-label small mb-0" htmlFor="risk-mitigation">{f.mitigation}</label>
                        <textarea id="risk-mitigation" className="form-control" rows={2} value={draft.mitigation} onChange={set('mitigation')} />
                    </div>
                    <div className="col-12 col-md-4">
                        <label className="form-label small mb-0" htmlFor="risk-owner">{f.owner}</label>
                        <select id="risk-owner" className="form-select" value={draft.owner_id} onChange={set('owner_id')}>
                            <option value="">{t.none}</option>
                            {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                    <div className="col-6 col-md-4">
                        <label className="form-label small mb-0" htmlFor="risk-due">{f.due_date}</label>
                        <input id="risk-due" type="date" className="form-control" value={draft.due_date} onChange={set('due_date')} />
                    </div>
                    <div className="col-6 col-md-4">
                        <label className="form-label small mb-0" htmlFor="risk-order">{f.order}</label>
                        <select id="risk-order" className="form-select" value={draft.orders_id} onChange={set('orders_id')}>
                            <option value="">{t.none}</option>
                            {meta.orders.map(o => <option key={o.id} value={o.id}>{o.code}</option>)}
                        </select>
                    </div>
                </div>
                {restricted && (
                    <div className="alert alert-warning py-2 mt-3 mb-0">
                        <i className="fas fa-lock me-1" />{t.close_restricted}
                    </div>
                )}
            </div>
            <div className="card-footer d-flex gap-2 justify-content-end">
                <button type="button" className="btn btn-outline-secondary" onClick={onCancel} disabled={saving}>{t.cancel}</button>
                <button type="submit" className="btn btn-primary" disabled={saving || restricted}>
                    {saving && <span className="spinner-border spinner-border-sm me-1" aria-hidden="true" />}
                    <i className="fas fa-save me-1" />{t.save}
                </button>
            </div>
        </form>
    );
}

export default function AffairRisks({ endpoints = {}, trans = {}, users = [], canEdit = false, locale = 'fr' }) {
    const t = trans.risks ?? {};
    const f = t.fields ?? {};
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [editing, setEditing] = useState(null); // null | 'new' | risque
    const [notice, setNotice] = useState(null);
    const [sortField, setSortField] = useState('criticality');
    const [sortAsc, setSortAsc] = useState(false);

    const apply = (data) => {
        setRows(data?.data ?? []);
        setMeta(data?.meta ?? null);
    };

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            apply(await request(endpoints.index));
        } catch (e) {
            setError(e);
        } finally {
            setLoading(false);
        }
    }, [endpoints.index]);

    useEffect(() => { load(); }, [load]);

    const sorted = useMemo(() => {
        const key = SORTERS[sortField] ?? SORTERS.criticality;
        return [...rows].sort((a, b) => {
            const va = key(a), vb = key(b);
            const cmp = va < vb ? -1 : va > vb ? 1 : 0;
            return sortAsc ? cmp : -cmp;
        });
    }, [rows, sortField, sortAsc]);

    const onSort = (field) => {
        if (field === sortField) setSortAsc(a => !a);
        else { setSortField(field); setSortAsc(true); }
    };

    const today = todayIso();
    const threshold = meta?.threshold ?? 9;

    const columns = [
        { key: 'label', label: f.label, sortable: true, bold: true, filter: 'text', mobile: 'title',
            render: r => <span className="text-break">{r.label}</span> },
        { key: 'category', label: f.category, sortable: true, mobile: 'subtitle',
            render: r => t.categories?.[r.category] ?? r.category,
            filter: 'text', filterValue: r => t.categories?.[r.category] ?? r.category },
        { key: 'probability', label: f.probability, align: 'center', mobile: 'hidden' },
        { key: 'severity', label: f.severity, align: 'center', mobile: 'hidden' },
        { key: 'criticality', label: f.criticality, sortable: true, align: 'center', mobile: 'badge',
            render: r => <span className={`badge ${criticalityClass(r.criticality, threshold)}`}>{r.criticality}</span> },
        { key: 'mitigation', label: f.mitigation, mobile: 'hidden',
            render: r => <span className="small text-break">{r.mitigation || '—'}</span> },
        { key: 'owner_name', label: f.owner, sortable: true, filter: 'text', render: r => r.owner_name || '—' },
        { key: 'due_date', label: f.due_date, sortable: true, filter: 'date', filterValue: r => r.due_date,
            render: r => (
                <span className={isOverdue(r, today) ? 'text-danger fw-bold' : ''}>
                    {r.due_date ? formatDate(r.due_date, locale) : '—'}
                    {isOverdue(r, today) && <i className="fas fa-exclamation-triangle ms-1" title={t.overdue} />}
                </span>
            ) },
        { key: 'status', label: f.status, sortable: true, mobile: 'badge',
            render: r => <span className={`badge ${statusClass(r.status)}`}>{t.statuses?.[r.status] ?? r.status}</span> },
        { key: 'order_code', label: f.order, render: r => r.order_code || '—' },
    ];

    const saved = (data) => {
        apply(data);
        setEditing(null);
        setNotice(t.saved);
    };

    return (
        <div className="affair-risks">
            <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                <p className="small text-body-secondary mb-0 me-auto">{fmt(t.intro, { threshold })}</p>
                {canEdit && meta && editing === null && (
                    <button type="button" className="btn btn-sm btn-warning" onClick={() => { setNotice(null); setEditing('new'); }}>
                        <i className="fas fa-plus me-1" />{t.new}
                    </button>
                )}
                <button type="button" className="btn btn-tool" onClick={load} disabled={loading} title={trans.refresh} aria-label={trans.refresh}>
                    <i className={`fas fa-sync ${loading ? 'fa-spin' : ''}`} />
                </button>
            </div>

            {!canEdit && <div className="alert alert-light border py-2 small">{t.read_only}</div>}
            {notice && <div className="alert alert-success py-2">{notice}</div>}
            {error && <div className="alert alert-danger py-2">{t.load_error}</div>}

            {editing !== null && meta && (
                <RiskForm
                    key={editing === 'new' ? 'new' : editing.id}
                    risk={editing === 'new' ? null : editing}
                    meta={meta}
                    users={users}
                    trans={trans}
                    endpoints={endpoints}
                    onSaved={saved}
                    onCancel={() => setEditing(null)}
                />
            )}

            <DataTable
                rows={sorted}
                columns={columns}
                trans={trans}
                loading={loading}
                sortField={sortField}
                sortAsc={sortAsc}
                onSort={onSort}
                storage={{ order: LS_COL_ORDER, hidden: LS_HIDDEN }}
                rowClickable={false}
                onRowClick={canEdit ? (r => { setNotice(null); setEditing(r); }) : undefined}
                emptyText={t.empty}
                actionsColumn={canEdit}
                rowActions={r => (
                    <button type="button" className="btn btn-sm btn-outline-secondary" title={t.edit} aria-label={t.edit}
                        onClick={() => { setNotice(null); setEditing(r); }}>
                        <i className="fas fa-pen" />
                    </button>
                )}
                mobileActions={canEdit ? (r => (
                    <button type="button" className="btn btn-outline-secondary" style={{ minHeight: 44 }}
                        onClick={() => { setNotice(null); setEditing(r); }}>
                        <i className="fas fa-pen me-1" />{t.edit}
                    </button>
                )) : undefined}
                actionsWidth={44}
            />
        </div>
    );
}
