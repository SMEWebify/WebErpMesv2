import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_CONFIG = {
    1: { badge: 'badge-info',    label: 'in_progress' },
    2: { badge: 'badge-warning', label: 'waiting_customer_data' },
    3: { badge: 'badge-success', label: 'solved' },
    4: { badge: 'badge-danger',  label: 'canceled' },
};

const TYPE_CONFIG = {
    1: { badge: 'badge-warning', label: 'internal' },
    2: { badge: 'badge-danger',  label: 'external' },
};

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
}

function Avatar({ name }) {
    const colors = ['#007bff','#28a745','#dc3545','#fd7e14','#6610f2','#20c997','#e83e8c','#17a2b8'];
    const idx = name ? name.charCodeAt(0) % colors.length : 0;
    return (
        <span style={{
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            width: 28, height: 28, borderRadius: '50%', fontSize: 11, fontWeight: 700,
            color: '#fff', background: colors[idx], flexShrink: 0,
        }}>
            {initials(name)}
        </span>
    );
}

function StatusBadge({ statu, trans }) {
    const cfg = STATUS_CONFIG[statu] ?? { badge: 'badge-secondary', label: String(statu) };
    return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
}

function TypeBadge({ type, trans }) {
    const cfg = TYPE_CONFIG[type] ?? { badge: 'badge-secondary', label: String(type) };
    return <span className={`badge ${cfg.badge}`}>{trans[cfg.label] ?? cfg.label}</span>;
}

// ---------------------------------------------------------------------------
// Modal
// ---------------------------------------------------------------------------

function Modal({ id, title, show, onClose, size, children }) {
    useEffect(() => {
        if (!show) return;
        document.body.style.overflow = 'hidden';
        return () => { document.body.style.overflow = ''; };
    }, [show]);

    if (!show) return null;

    return (
        <div className="modal fade show" tabIndex="-1" style={{ display: 'block', background: 'rgba(0,0,0,0.5)' }} onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
            <div className={`modal-dialog modal-dialog-centered ${size === 'lg' ? 'modal-lg' : ''}`}>
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">{title}</h5>
                        <button type="button" className="close" onClick={onClose}><span>&times;</span></button>
                    </div>
                    <div className="modal-body">{children}</div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// SelectField helper
// ---------------------------------------------------------------------------

function SelectField({ label, name, value, onChange, options, nullable, disabled }) {
    return (
        <div className="form-group">
            {label && <label>{label}</label>}
            <select className="form-control form-control-sm" name={name} value={value ?? ''} onChange={e => onChange(e.target.value || null)} disabled={disabled}>
                {nullable !== false && <option value="">N/A</option>}
                {options.map(o => <option key={o.id} value={o.id}>{o.label ?? o.name ?? o.code}</option>)}
            </select>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ViewModal — read-only details
// ---------------------------------------------------------------------------

function ViewModal({ nc, trans, onClose }) {
    if (!nc) return null;
    const t = k => trans[k] ?? k;

    return (
        <Modal show={!!nc} onClose={onClose} title={`${t('info')} — ${nc.label}`} size="lg">
            <div className="row">
                <div className="col-md-4">
                    <p><strong>{t('failure')} :</strong> {nc.failure_label ?? 'N/A'}</p>
                    <p><strong>{t('failure_comment')} :</strong> {nc.failure_comment || '—'}</p>
                </div>
                <div className="col-md-4">
                    <p><strong>{t('cause')} :</strong> {nc.causes_label ?? 'N/A'}</p>
                    <p><strong>{t('cause_comment')} :</strong> {nc.causes_comment || '—'}</p>
                </div>
                <div className="col-md-4">
                    <p><strong>{t('correction')} :</strong> {nc.correction_label ?? 'N/A'}</p>
                    <p><strong>{t('correction_comment')} :</strong> {nc.correction_comment || '—'}</p>
                </div>
            </div>
            <hr />
            <p><strong>{t('service')} :</strong> {nc.service_label ?? 'N/A'}</p>
            <p><strong>{t('qty')} :</strong> {nc.qty ?? '—'}</p>
            <p><strong>{t('resolution_date')} :</strong> {nc.resolution_date ?? '—'}</p>
            <div className="text-right">
                <button className="btn btn-secondary btn-sm" onClick={onClose}>{t('close')}</button>
            </div>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// EditModal — full edit form
// ---------------------------------------------------------------------------

function EditModal({ nc, trans, users, services, companies, failures, causes, corrections, endpoints, onSaved, onClose }) {
    const [form, setForm]     = useState({});
    const [saving, setSaving] = useState(false);
    const [error, setError]   = useState(null);
    const t = k => trans[k] ?? k;

    useEffect(() => {
        if (!nc) return;
        setForm({
            label:              nc.label ?? '',
            statu:              nc.statu ?? 1,
            type:               nc.type ?? 2,
            user_id:            nc.user_id ?? '',
            service_id:         nc.service_id ?? '',
            companie_id:        nc.companie_id ?? '',
            qty:                nc.qty ?? '',
            failure_id:         nc.failure_id ?? '',
            failure_comment:    nc.failure_comment ?? '',
            causes_id:          nc.causes_id ?? '',
            causes_comment:     nc.causes_comment ?? '',
            correction_id:      nc.correction_id ?? '',
            correction_comment: nc.correction_comment ?? '',
        });
        setError(null);
    }, [nc]);

    if (!nc) return null;

    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    async function handleSave(e) {
        e.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const res = await fetch(`${endpoints.apiBase}/${nc.id}/update`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    label:              form.label,
                    statu:              Number(form.statu),
                    type:               Number(form.type),
                    user_id:            form.user_id || null,
                    service_id:         form.service_id || null,
                    companie_id:        (nc.order_lines_id || nc.task_id) ? nc.companie_id : (form.companie_id || null),
                    qty:                form.qty || null,
                    failure_id:         form.failure_id || null,
                    failure_comment:    form.failure_comment || null,
                    causes_id:          form.causes_id || null,
                    causes_comment:     form.causes_comment || null,
                    correction_id:      form.correction_id || null,
                    correction_comment: form.correction_comment || null,
                }),
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message ?? t('error'));
            onSaved(json.data);
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    }

    async function handleClose() {
        setSaving(true);
        setError(null);
        try {
            const res = await fetch(`${endpoints.apiBase}/${nc.id}/close`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message ?? t('error'));
            onSaved({ ...nc, statu: 3, resolution_date: new Date().toISOString().slice(0, 10) });
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    }

    async function handleReopen() {
        setSaving(true);
        setError(null);
        try {
            const res = await fetch(`${endpoints.apiBase}/${nc.id}/reopen`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message ?? t('error'));
            onSaved({ ...nc, statu: 1, resolution_date: null });
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    }

    const locked = !!(nc.order_lines_id || nc.task_id);

    return (
        <Modal show={!!nc} onClose={onClose} title={`${t('update')} — ${nc.label}`} size="lg">
            <form onSubmit={handleSave}>
                {error && <div className="alert alert-danger py-1">{error}</div>}

                <div className="form-row">
                    <div className="form-group col-md-8">
                        <label>{t('label')}</label>
                        <input className="form-control form-control-sm" value={form.label} onChange={e => set('label', e.target.value)} required />
                    </div>
                    <div className="form-group col-md-4">
                        <label>{t('qty')}</label>
                        <input type="number" className="form-control form-control-sm" value={form.qty} onChange={e => set('qty', e.target.value)} min="1" />
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group col-md-4">
                        <label>{t('status')}</label>
                        <select className="form-control form-control-sm" value={form.statu} onChange={e => set('statu', e.target.value)}>
                            <option value="1">{t('in_progress')}</option>
                            <option value="2">{t('waiting_customer_data')}</option>
                            <option value="3">{t('solved')}</option>
                            <option value="4">{t('canceled')}</option>
                        </select>
                    </div>
                    <div className="form-group col-md-4">
                        <label>{t('type')}</label>
                        <select className="form-control form-control-sm" value={form.type} onChange={e => set('type', e.target.value)}>
                            <option value="1">{t('internal')}</option>
                            <option value="2">{t('external')}</option>
                        </select>
                    </div>
                    <div className="form-group col-md-4">
                        <label>{t('user')}</label>
                        <select className="form-control form-control-sm" value={form.user_id} onChange={e => set('user_id', e.target.value)}>
                            <option value="">—</option>
                            {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group col-md-6">
                        <label>{t('service')}</label>
                        <select className="form-control form-control-sm" value={form.service_id} onChange={e => set('service_id', e.target.value)}>
                            <option value="">N/A</option>
                            {services.map(s => <option key={s.id} value={s.id}>{s.label}</option>)}
                        </select>
                    </div>
                    <div className="form-group col-md-6">
                        <label>{t('company')}</label>
                        <select className="form-control form-control-sm" value={form.companie_id} onChange={e => set('companie_id', e.target.value)} disabled={locked}>
                            <option value="">N/A</option>
                            {companies.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                        </select>
                        {locked && <small className="text-muted">{t('locked_by_order')}</small>}
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group col-md-4">
                        <label>{t('failure_type')}</label>
                        <select className="form-control form-control-sm" value={form.failure_id} onChange={e => set('failure_id', e.target.value)}>
                            <option value="">N/A</option>
                            {failures.map(f => <option key={f.id} value={f.id}>{f.label}</option>)}
                        </select>
                        <textarea className="form-control form-control-sm mt-1" rows="2" placeholder="..." value={form.failure_comment} onChange={e => set('failure_comment', e.target.value)} />
                    </div>
                    <div className="form-group col-md-4">
                        <label>{t('cause_type')}</label>
                        <select className="form-control form-control-sm" value={form.causes_id} onChange={e => set('causes_id', e.target.value)}>
                            <option value="">N/A</option>
                            {causes.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                        </select>
                        <textarea className="form-control form-control-sm mt-1" rows="2" placeholder="..." value={form.causes_comment} onChange={e => set('causes_comment', e.target.value)} />
                    </div>
                    <div className="form-group col-md-4">
                        <label>{t('correction_type')}</label>
                        <select className="form-control form-control-sm" value={form.correction_id} onChange={e => set('correction_id', e.target.value)}>
                            <option value="">N/A</option>
                            {corrections.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                        </select>
                        <textarea className="form-control form-control-sm mt-1" rows="2" placeholder="..." value={form.correction_comment} onChange={e => set('correction_comment', e.target.value)} />
                    </div>
                </div>

                <div className="d-flex justify-content-between align-items-center mt-2">
                    <div>
                        {nc.statu !== 3
                            ? <button type="button" className="btn btn-sm btn-danger mr-2" onClick={handleClose} disabled={saving}>{t('solved')}</button>
                            : <button type="button" className="btn btn-sm btn-success mr-2" onClick={handleReopen} disabled={saving}>{t('reopen')}</button>
                        }
                    </div>
                    <div>
                        <button type="button" className="btn btn-sm btn-secondary mr-2" onClick={onClose}>{t('cancel')}</button>
                        <button type="submit" className="btn btn-sm btn-info" disabled={saving}>
                            {saving ? <><i className="fas fa-spinner fa-spin mr-1" />{t('saving')}</> : <><i className="fas fa-save mr-1" />{t('update')}</>}
                        </button>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// CreateForm
// ---------------------------------------------------------------------------

function CreateForm({ trans, users, services, companies, failures, causes, corrections, endpoints, nextCode, onCreated }) {
    const [open, setOpen]     = useState(false);
    const [form, setForm]     = useState({
        code: nextCode, label: nextCode, statu: '1', type: '1',
        user_id: '', service_id: '', companie_id: '', qty: '',
        failure_id: '', failure_comment: '',
        causes_id: '', causes_comment: '',
        correction_id: '', correction_comment: '',
    });
    const [saving, setSaving] = useState(false);
    const [error, setError]   = useState(null);
    const t = k => trans[k] ?? k;

    const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setError(null);
        try {
            const res = await fetch(endpoints.store, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    code:               form.code,
                    label:              form.label,
                    statu:              Number(form.statu),
                    type:               Number(form.type),
                    user_id:            form.user_id || null,
                    service_id:         form.service_id || null,
                    companie_id:        form.companie_id || null,
                    qty:                form.qty || null,
                    failure_id:         form.failure_id || null,
                    failure_comment:    form.failure_comment || null,
                    causes_id:          form.causes_id || null,
                    causes_comment:     form.causes_comment || null,
                    correction_id:      form.correction_id || null,
                    correction_comment: form.correction_comment || null,
                }),
            });
            const json = await res.json();
            if (!res.ok) {
                const msgs = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? t('error'));
                throw new Error(msgs);
            }
            onCreated(json.data);
            setOpen(false);
            setForm(f => ({ ...f, code: '', label: '', service_id: '', companie_id: '', qty: '', failure_id: '', failure_comment: '', causes_id: '', causes_comment: '', correction_id: '', correction_comment: '' }));
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="card card-outline card-secondary mt-3">
            <div className="card-header" style={{ cursor: 'pointer' }} onClick={() => setOpen(o => !o)}>
                <h3 className="card-title"><i className={`fas fa-chevron-${open ? 'up' : 'down'} mr-2`} />{t('new_non_conformity')}</h3>
            </div>
            {open && (
                <div className="card-body">
                    <form onSubmit={handleSubmit}>
                        {error && <div className="alert alert-danger py-1">{error}</div>}
                        <div className="form-row">
                            <div className="form-group col-md-3">
                                <label>{t('external_id')}</label>
                                <input className="form-control form-control-sm" value={form.code} onChange={e => set('code', e.target.value)} required />
                            </div>
                            <div className="form-group col-md-5">
                                <label>{t('label')}</label>
                                <input className="form-control form-control-sm" value={form.label} onChange={e => set('label', e.target.value)} required />
                            </div>
                            <div className="form-group col-md-2">
                                <label>{t('type')}</label>
                                <select className="form-control form-control-sm" value={form.type} onChange={e => set('type', e.target.value)}>
                                    <option value="1">{t('internal')}</option>
                                    <option value="2">{t('external')}</option>
                                </select>
                            </div>
                            <div className="form-group col-md-2">
                                <label>{t('status')}</label>
                                <select className="form-control form-control-sm" value={form.statu} onChange={e => set('statu', e.target.value)}>
                                    <option value="1">{t('in_progress')}</option>
                                    <option value="2">{t('waiting_customer_data')}</option>
                                    <option value="3">{t('solved')}</option>
                                    <option value="4">{t('canceled')}</option>
                                </select>
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group col-md-4">
                                <label>{t('user')}</label>
                                <select className="form-control form-control-sm" value={form.user_id} onChange={e => set('user_id', e.target.value)} required>
                                    <option value="">—</option>
                                    {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                                </select>
                            </div>
                            <div className="form-group col-md-4">
                                <label>{t('service')}</label>
                                <select className="form-control form-control-sm" value={form.service_id} onChange={e => set('service_id', e.target.value)}>
                                    <option value="">N/A</option>
                                    {services.map(s => <option key={s.id} value={s.id}>{s.label}</option>)}
                                </select>
                            </div>
                            <div className="form-group col-md-4">
                                <label>{t('company')}</label>
                                <select className="form-control form-control-sm" value={form.companie_id} onChange={e => set('companie_id', e.target.value)}>
                                    <option value="">N/A</option>
                                    {companies.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group col-md-4">
                                <label>{t('failure_type')}</label>
                                <select className="form-control form-control-sm" value={form.failure_id} onChange={e => set('failure_id', e.target.value)}>
                                    <option value="">N/A</option>
                                    {failures.map(f => <option key={f.id} value={f.id}>{f.label}</option>)}
                                </select>
                                <textarea className="form-control form-control-sm mt-1" rows="2" placeholder="..." value={form.failure_comment} onChange={e => set('failure_comment', e.target.value)} />
                            </div>
                            <div className="form-group col-md-4">
                                <label>{t('cause_type')}</label>
                                <select className="form-control form-control-sm" value={form.causes_id} onChange={e => set('causes_id', e.target.value)}>
                                    <option value="">N/A</option>
                                    {causes.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                                </select>
                                <textarea className="form-control form-control-sm mt-1" rows="2" placeholder="..." value={form.causes_comment} onChange={e => set('causes_comment', e.target.value)} />
                            </div>
                            <div className="form-group col-md-4">
                                <label>{t('correction_type')}</label>
                                <select className="form-control form-control-sm" value={form.correction_id} onChange={e => set('correction_id', e.target.value)}>
                                    <option value="">N/A</option>
                                    {corrections.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                                </select>
                                <textarea className="form-control form-control-sm mt-1" rows="2" placeholder="..." value={form.correction_comment} onChange={e => set('correction_comment', e.target.value)} />
                            </div>
                        </div>
                        <div className="form-row">
                            <div className="form-group col-md-3">
                                <label>{t('qty')}</label>
                                <input type="number" className="form-control form-control-sm" value={form.qty} onChange={e => set('qty', e.target.value)} min="1" />
                            </div>
                        </div>
                        <button type="submit" className="btn btn-danger btn-sm" disabled={saving}>
                            {saving ? <><i className="fas fa-spinner fa-spin mr-1" />{t('saving')}</> : <><i className="fas fa-save mr-1" />{t('submit')}</>}
                        </button>
                    </form>
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// StatusFilter
// ---------------------------------------------------------------------------

function StatusFilter({ active, onToggle, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {Object.entries(STATUS_CONFIG).map(([id, cfg]) => {
                const sid = Number(id);
                const isActive = active.includes(sid);
                return (
                    <button key={sid} type="button"
                        className={`btn btn-sm ${isActive ? cfg.badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                        onClick={() => onToggle(sid)}
                    >
                        {trans[cfg.label] ?? cfg.label}
                    </button>
                );
            })}
        </div>
    );
}

function TypeFilter({ active, onToggle, trans }) {
    return (
        <div className="d-flex flex-wrap" style={{ gap: '0.25rem' }}>
            {Object.entries(TYPE_CONFIG).map(([id, cfg]) => {
                const tid = Number(id);
                const isActive = active.includes(tid);
                return (
                    <button key={tid} type="button"
                        className={`btn btn-sm ${isActive ? cfg.badge.replace('badge-', 'btn-') : 'btn-outline-secondary'}`}
                        onClick={() => onToggle(tid)}
                    >
                        {trans[cfg.label] ?? cfg.label}
                    </button>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPage }) {
    if (!meta || meta.last_page <= 1) return null;
    const pages = Array.from({ length: meta.last_page }, (_, i) => i + 1);
    return (
        <nav className="mt-2">
            <ul className="pagination pagination-sm m-0 flex-wrap">
                <li className={`page-item ${meta.current_page === 1 ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(meta.current_page - 1)}>&laquo;</button>
                </li>
                {pages.map(p => (
                    <li key={p} className={`page-item ${p === meta.current_page ? 'active' : ''}`}>
                        <button className="page-link" onClick={() => onPage(p)}>{p}</button>
                    </li>
                ))}
                <li className={`page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}`}>
                    <button className="page-link" onClick={() => onPage(meta.current_page + 1)}>&raquo;</button>
                </li>
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function NonConformitiesIndex({
    endpoints, users, services, companies, failures, causes, corrections, nextCode, trans,
}) {
    const t = k => trans[k] ?? k;

    const [items,    setItems]    = useState([]);
    const [meta,     setMeta]     = useState(null);
    const [loading,  setLoading]  = useState(false);
    const [search,   setSearch]   = useState('');
    const [statuses, setStatuses] = useState([]);
    const [types,    setTypes]    = useState([]);
    const [sortField, setSortField] = useState('id');
    const [sortAsc,   setSortAsc]   = useState(false);
    const [page,     setPage]     = useState(1);
    const [viewNc,   setViewNc]   = useState(null);
    const [editNc,   setEditNc]   = useState(null);
    const searchTimer = useRef(null);

    const fetchItems = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams({ search, sort: sortField, asc: sortAsc ? 1 : 0, page });
        statuses.forEach(s => params.append('statuses[]', s));
        types.forEach(t => params.append('types[]', t));
        fetch(`${endpoints.list}?${params}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(json => { setItems(json.data ?? []); setMeta(json.meta ?? null); })
            .finally(() => setLoading(false));
    }, [endpoints.list, search, statuses, types, sortField, sortAsc, page]);

    useEffect(() => { fetchItems(); }, [fetchItems]);

    function handleSearch(val) {
        setSearch(val);
        clearTimeout(searchTimer.current);
        searchTimer.current = setTimeout(() => setPage(1), 300);
    }

    function handleSort(field) {
        if (sortField === field) setSortAsc(a => !a);
        else { setSortField(field); setSortAsc(false); }
        setPage(1);
    }

    function SortIcon({ field }) {
        if (field !== sortField) return <i className="fas fa-sort text-muted ml-1" />;
        return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
    }

    function handleSaved(updated) {
        setItems(prev => prev.map(nc => nc.id === updated.id ? updated : nc));
        setEditNc(null);
    }

    function handleCreated() {
        setPage(1);
        fetchItems();
    }

    return (
        <div>
            {/* Filters */}
            <div className="card card-outline card-primary">
                <div className="card-body">
                    <div className="d-flex flex-wrap align-items-center mb-2" style={{ gap: '0.5rem' }}>
                        {/* Search */}
                        <div className="input-group" style={{ maxWidth: 280 }}>
                            <input
                                className="form-control form-control-sm"
                                placeholder={t('search')}
                                value={search}
                                onChange={e => handleSearch(e.target.value)}
                            />
                            {search && (
                                <div className="input-group-append">
                                    <button className="btn btn-sm btn-outline-secondary" onClick={() => handleSearch('')}>×</button>
                                </div>
                            )}
                        </div>
                        <StatusFilter active={statuses} onToggle={sid => { setStatuses(p => p.includes(sid) ? p.filter(s => s !== sid) : [...p, sid]); setPage(1); }} trans={trans} />
                        <TypeFilter   active={types}    onToggle={tid => { setTypes(p => p.includes(tid) ? p.filter(t => t !== tid) : [...p, tid]); setPage(1); }} trans={trans} />
                        <div className="flex-grow-1" />
                        {loading && <i className="fas fa-spinner fa-spin text-muted" />}
                        {meta && <small className="text-muted">{meta.total} {t('results')}</small>}
                    </div>

                    {/* Table */}
                    <div className="table-responsive">
                        <table className="table table-sm table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style={{ cursor: 'pointer' }} onClick={() => handleSort('code')}>{t('external_id')}<SortIcon field="code" /></th>
                                    <th style={{ cursor: 'pointer' }} onClick={() => handleSort('label')}>{t('label')}<SortIcon field="label" /></th>
                                    <th>{t('user')}</th>
                                    <th style={{ cursor: 'pointer' }} onClick={() => handleSort('type')}>{t('type')}<SortIcon field="type" /></th>
                                    <th style={{ cursor: 'pointer' }} onClick={() => handleSort('statu')}>{t('status')}<SortIcon field="statu" /></th>
                                    <th>{t('company')}</th>
                                    <th>{t('order')}</th>
                                    <th>{t('task')}</th>
                                    <th>{t('delivery_notes')}</th>
                                    <th style={{ cursor: 'pointer' }} onClick={() => handleSort('created_at')}>{t('created_at')}<SortIcon field="created_at" /></th>
                                    <th>{t('actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 && !loading && (
                                    <tr><td colSpan="11" className="text-center text-muted py-3">{t('no_data')}</td></tr>
                                )}
                                {items.map(nc => (
                                    <tr key={nc.id}>
                                        <td><code>{nc.code}</code></td>
                                        <td>{nc.label}</td>
                                        <td><Avatar name={nc.user_name} /></td>
                                        <td><TypeBadge type={nc.type} trans={trans} /></td>
                                        <td><StatusBadge statu={nc.statu} trans={trans} /></td>
                                        <td>
                                            {nc.companie_id
                                                ? <a href={`${endpoints.companieBase}/${nc.companie_id}`} className="btn btn-xs btn-outline-secondary">{nc.companie_label}</a>
                                                : <span className="text-muted">N/A</span>}
                                        </td>
                                        <td>
                                            {nc.order_id
                                                ? <a href={`${endpoints.orderBase}/${nc.order_id}`} className="btn btn-xs btn-outline-dark">{nc.order_code}</a>
                                                : <span className="text-muted">N/A</span>}
                                        </td>
                                        <td>
                                            {nc.task_id
                                                ? <a href={`${endpoints.taskBase}/${nc.task_id}`} className="btn btn-xs btn-success">{t('view')}</a>
                                                : <span className="text-muted">N/A</span>}
                                        </td>
                                        <td>
                                            {nc.deliverys_id
                                                ? <a href={`${endpoints.deliveryBase}/${nc.deliverys_id}`} className="btn btn-xs btn-primary">{nc.delivery_code}</a>
                                                : <span className="text-muted">N/A</span>}
                                        </td>
                                        <td><small>{nc.created_pretty}</small></td>
                                        <td style={{ whiteSpace: 'nowrap' }}>
                                            <button className="btn btn-xs btn-info mr-1" onClick={() => setViewNc(nc)} title={t('view')}>
                                                <i className="fas fa-eye" />
                                            </button>
                                            <button className="btn btn-xs btn-teal mr-1" onClick={() => setEditNc(nc)} title={t('edit')}>
                                                <i className="fas fa-edit" />
                                            </button>
                                            <a href={`${endpoints.pdfBase}/${nc.id}`} className="btn btn-xs btn-danger" title="PDF" target="_blank" rel="noopener noreferrer">
                                                <i className="fas fa-file-pdf" />
                                            </a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <Pagination meta={meta} onPage={setPage} />
                </div>
            </div>

            {/* Create Form */}
            <CreateForm
                trans={trans}
                users={users}
                services={services}
                companies={companies}
                failures={failures}
                causes={causes}
                corrections={corrections}
                endpoints={endpoints}
                nextCode={nextCode}
                onCreated={handleCreated}
            />

            {/* Modals */}
            <ViewModal nc={viewNc} trans={trans} onClose={() => setViewNc(null)} />
            <EditModal
                nc={editNc}
                trans={trans}
                users={users}
                services={services}
                companies={companies}
                failures={failures}
                causes={causes}
                corrections={corrections}
                endpoints={endpoints}
                onSaved={handleSaved}
                onClose={() => setEditNc(null)}
            />
        </div>
    );
}
