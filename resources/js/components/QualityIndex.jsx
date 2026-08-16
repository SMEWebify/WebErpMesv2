import React, { useState, useEffect, useCallback } from 'react';
import { createPortal } from 'react-dom';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
        ...options,
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(json.message || `HTTP ${res.status}`);
        err.errors = json.errors ?? {};
        throw err;
    }
    return json;
}

async function apiJson(url, body) {
    return apiFetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
}

function formatDate(str) {
    if (!str) return '—';
    try {
        const d = new Date(str);
        return d.toLocaleDateString('fr-FR');
    } catch {
        return str;
    }
}

// ---------------------------------------------------------------------------
// Modal overlay
// ---------------------------------------------------------------------------

function Modal({ title, onClose, children, size = 'md' }) {
    const widths = { sm: 480, md: 640, lg: 900 };
    return createPortal(
        <div
            style={{
                position: 'fixed', top: 0, left: 0,
                width: '100vw', height: '100vh',
                zIndex: 9999,
                backgroundColor: 'rgba(0,0,0,0.55)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                padding: '1rem',
            }}
            onClick={e => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div style={{
                width: widths[size] ?? 640,
                maxWidth: '100%',
                maxHeight: '90vh',
                display: 'flex',
                flexDirection: 'column',
                borderRadius: 6,
                overflow: 'hidden',
                boxShadow: '0 10px 40px rgba(0,0,0,0.4)',
                background: '#fff',
            }}>
                <div style={{
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '0.85rem 1.25rem',
                    backgroundColor: '#17a2b8',
                    flexShrink: 0,
                }}>
                    <h5 style={{ margin: 0, color: '#fff', fontWeight: 600, fontSize: '1rem' }}>
                        {title}
                    </h5>
                    <button onClick={onClose} style={{
                        background: 'none', border: 'none', color: '#fff',
                        fontSize: '1.25rem', cursor: 'pointer', lineHeight: 1,
                    }}>×</button>
                </div>
                <div style={{ overflowY: 'auto', padding: '1rem' }}>
                    {children}
                </div>
            </div>
        </div>,
        document.body
    );
}

// ---------------------------------------------------------------------------
// KPI Card
// ---------------------------------------------------------------------------

const THEME_COLORS = {
    teal: '#20c997', purple: '#6f42c1', info: '#17a2b8',
    secondary: '#6c757d', orange: '#fd7e14', success: '#28a745',
    warning: '#ffc107', danger: '#dc3545',
};

function KpiCard({ value, label, icon, theme }) {
    const color = THEME_COLORS[theme] ?? THEME_COLORS.info;
    return (
        <div className="small-box" style={{ backgroundColor: color, color: '#fff', borderRadius: 4, padding: '1rem', marginBottom: '1rem', position: 'relative', overflow: 'hidden' }}>
            <div className="inner">
                <h3 style={{ fontSize: '2rem', fontWeight: 700, margin: 0 }}>{value}</h3>
                <p style={{ margin: 0, fontSize: '0.85rem' }}>{label}</p>
            </div>
            <div className="icon" style={{ position: 'absolute', right: 10, top: 10, fontSize: '3rem', opacity: 0.3 }}>
                <i className={icon} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Stacked Horizontal Bar (internal vs external %)
// ---------------------------------------------------------------------------

function StackedBar({ label, internal, external, trans }) {
    const internalPct = Math.round(internal);
    const externalPct = 100 - internalPct;
    return (
        <div className="mb-3">
            <div className="d-flex justify-content-between mb-1">
                <small><strong>{label}</strong></small>
            </div>
            <div style={{ display: 'flex', height: 22, borderRadius: 4, overflow: 'hidden' }}>
                {internalPct > 0 && (
                    <div title={`${trans.internal ?? 'Interne'}: ${internalPct}%`}
                        style={{ width: `${internalPct}%`, background: 'rgba(75,192,192,1)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <small style={{ color: '#fff', fontSize: '0.7rem', fontWeight: 600 }}>{internalPct}%</small>
                    </div>
                )}
                {externalPct > 0 && (
                    <div title={`${trans.external ?? 'Externe'}: ${externalPct}%`}
                        style={{ width: `${externalPct}%`, background: 'rgb(255,205,86)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <small style={{ color: '#555', fontSize: '0.7rem', fontWeight: 600 }}>{externalPct}%</small>
                    </div>
                )}
                {internalPct === 0 && externalPct === 0 && (
                    <div style={{ width: '100%', background: '#dee2e6', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <small style={{ color: '#999', fontSize: '0.7rem' }}>—</small>
                    </div>
                )}
            </div>
            <div className="d-flex mt-1" style={{ gap: '1rem' }}>
                <small><span style={{ display: 'inline-block', width: 10, height: 10, background: 'rgba(75,192,192,1)', borderRadius: 2, marginRight: 4 }} />{trans.internal ?? 'Interne'}</small>
                <small><span style={{ display: 'inline-block', width: 10, height: 10, background: 'rgb(255,205,86)', borderRadius: 2, marginRight: 4 }} />{trans.external ?? 'Externe'}</small>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Horizontal Bar Chart (top generators)
// ---------------------------------------------------------------------------

function HorizontalBarChart({ data, trans }) {
    const labels = data?.labels ?? [];
    const values = (data?.datasets?.[0]?.data ?? []).map(Number);
    const colors = data?.datasets?.[0]?.backgroundColor ?? [];
    const maxVal = Math.max(...values, 1);

    if (!labels.length) return <p className="text-muted text-center small py-3">—</p>;

    return (
        <div>
            {labels.map((label, i) => (
                <div key={i} className="mb-2">
                    <div className="d-flex justify-content-between mb-1">
                        <small style={{ fontSize: '0.75rem', maxWidth: '70%', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{label}</small>
                        <small style={{ fontWeight: 600 }}>{values[i]}</small>
                    </div>
                    <div style={{ height: 10, background: '#f0f0f0', borderRadius: 4, overflow: 'hidden' }}>
                        <div style={{
                            height: '100%',
                            width: `${(values[i] / maxVal) * 100}%`,
                            background: colors[i % colors.length] ?? 'rgba(75,192,192,0.8)',
                            transition: 'width 0.3s ease',
                        }} />
                    </div>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Radar / Status distribution chart (simple spider SVG)
// ---------------------------------------------------------------------------

function RadarChart({ derogation, nonConformity, action, trans }) {
    const AXES = [
        trans.in_progress ?? 'En cours',
        trans.waiting_customer ?? 'Attente client',
        trans.validated ?? 'Validé',
        trans.canceled ?? 'Annulé',
    ];
    const N = AXES.length;
    const datasets = [
        { label: trans.derogations ?? 'Dérogations', data: derogation, color: 'rgba(255,99,132,0.6)', border: 'rgb(255,99,132)' },
        { label: trans.non_conformities ?? 'Non-conformités', data: nonConformity, color: 'rgba(54,162,235,0.4)', border: 'rgba(75,192,192,1)' },
        { label: trans.actions ?? 'Actions', data: action, color: 'rgba(255,205,86,0.4)', border: 'rgb(255,205,86)' },
    ];

    const cx = 140, cy = 140, R = 100;
    const maxVal = Math.max(...datasets.flatMap(d => d.data ?? []), 1);

    function angleFor(i) { return (i / N) * 2 * Math.PI - Math.PI / 2; }
    function point(i, val) {
        const r = (val / maxVal) * R;
        return [cx + r * Math.cos(angleFor(i)), cy + r * Math.sin(angleFor(i))];
    }
    function polygon(vals) {
        return (vals ?? []).map((v, i) => point(i, v).join(',')).join(' ');
    }

    // Grid rings
    const rings = [0.25, 0.5, 0.75, 1.0];

    return (
        <div>
            <svg viewBox="0 0 280 280" style={{ width: '100%', maxWidth: 260, display: 'block', margin: '0 auto' }}>
                {/* Grid */}
                {rings.map((r, ri) => (
                    <polygon key={ri}
                        points={Array.from({ length: N }, (_, i) => {
                            const [x, y] = [cx + R * r * Math.cos(angleFor(i)), cy + R * r * Math.sin(angleFor(i))];
                            return `${x},${y}`;
                        }).join(' ')}
                        fill="none" stroke="#ddd" strokeWidth="1" />
                ))}
                {/* Spokes */}
                {Array.from({ length: N }, (_, i) => {
                    const [x, y] = [cx + R * Math.cos(angleFor(i)), cy + R * Math.sin(angleFor(i))];
                    return <line key={i} x1={cx} y1={cy} x2={x} y2={y} stroke="#ddd" strokeWidth="1" />;
                })}
                {/* Datasets */}
                {datasets.map((ds, di) => (
                    <polygon key={di}
                        points={polygon(ds.data)}
                        fill={ds.color} stroke={ds.border} strokeWidth="1.5" />
                ))}
                {/* Axis labels */}
                {AXES.map((label, i) => {
                    const angle = angleFor(i);
                    const lx = cx + (R + 18) * Math.cos(angle);
                    const ly = cy + (R + 18) * Math.sin(angle);
                    return (
                        <text key={i} x={lx} y={ly}
                            textAnchor="middle" dominantBaseline="middle"
                            fontSize="9" fill="#555">
                            {label}
                        </text>
                    );
                })}
            </svg>
            <div className="d-flex flex-wrap justify-content-center mt-2" style={{ gap: '0.75rem' }}>
                {datasets.map((ds, i) => (
                    <div key={i} className="d-flex align-items-center" style={{ gap: 4, fontSize: '0.75rem' }}>
                        <span style={{ width: 10, height: 10, background: ds.border, borderRadius: 2, flexShrink: 0 }} />
                        {ds.label}
                    </div>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Dashboard Tab
// ---------------------------------------------------------------------------

function DashboardTab({ kpi, rates, statusCounts, topGenerators, trans }) {
    return (
        <div>
            {/* KPI cards */}
            <div className="row">
                <div className="col-lg-2 col-md-4 col-sm-6">
                    <KpiCard value={kpi.totalActionsOpen ?? 0} label={`${trans.open ?? 'Ouvert'} ${trans.actions ?? 'Actions'}`} icon="fas fa-chart-bar" theme="teal" />
                </div>
                <div className="col-lg-2 col-md-4 col-sm-6">
                    <KpiCard value={kpi.totalNonConformitiesOpen ?? 0} label={`${trans.open ?? 'Ouvert'} ${trans.non_conformities ?? 'Non-conformités'}`} icon="fas fa-chart-bar" theme="purple" />
                </div>
                <div className="col-lg-2 col-md-4 col-sm-6">
                    <KpiCard value={kpi.totalDerogationsOpen ?? 0} label={`${trans.open ?? 'Ouvert'} ${trans.derogations ?? 'Dérogations'}`} icon="fas fa-chart-bar" theme="info" />
                </div>
                <div className="col-lg-2 col-md-4 col-sm-6">
                    <KpiCard value={`${kpi.litigationRate ?? 0} %`} label={trans.litigation_rate ?? 'Taux litige'} icon="fas fa-chart-bar" theme="secondary" />
                </div>
                <div className="col-lg-2 col-md-4 col-sm-6">
                    <KpiCard value={`${kpi.resolutionTimes ?? 0} ${trans.days ?? 'j'}`} label={trans.resolution_time ?? 'Temps résolution'} icon="fas fa-clock" theme="orange" />
                </div>
            </div>

            <hr />

            <div className="row">
                {/* Stacked bars */}
                <div className="col-md-4">
                    <div className="card card-outline card-teal">
                        <div className="card-header">
                            <h3 className="card-title small">{trans.internal_external_rates ?? 'Taux interne / externe'}</h3>
                        </div>
                        <div className="card-body">
                            <StackedBar
                                label={trans.actions ?? 'Actions'}
                                internal={rates.internalActionRate ?? 0}
                                external={rates.externalActionRate ?? 0}
                                trans={trans}
                            />
                            <StackedBar
                                label={trans.derogations ?? 'Dérogations'}
                                internal={rates.internalDerogationRate ?? 0}
                                external={rates.externalDerogationRate ?? 0}
                                trans={trans}
                            />
                            <StackedBar
                                label={trans.non_conformities ?? 'Non-conformités'}
                                internal={rates.internalNonConformityRate ?? 0}
                                external={rates.externalNonConformityRate ?? 0}
                                trans={trans}
                            />
                        </div>
                    </div>
                </div>

                {/* Top generators */}
                <div className="col-md-4">
                    <div className="card card-outline card-purple">
                        <div className="card-header">
                            <h3 className="card-title small">{trans.top_generators ?? 'Top générateurs NC'}</h3>
                        </div>
                        <div className="card-body">
                            <HorizontalBarChart data={topGenerators} trans={trans} />
                        </div>
                    </div>
                </div>

                {/* Radar */}
                <div className="col-md-4">
                    <div className="card card-outline card-info">
                        <div className="card-header">
                            <h3 className="card-title small">{trans.status_distribution ?? 'Distribution des statuts'}</h3>
                        </div>
                        <div className="card-body">
                            <RadarChart
                                derogation={Object.values(statusCounts.derogationStatusCounts ?? {})}
                                nonConformity={Object.values(statusCounts.nonConformityStatusCounts ?? {})}
                                action={Object.values(statusCounts.actionStatusCounts ?? {})}
                                trans={trans}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Device Edit Modal
// ---------------------------------------------------------------------------

function DeviceEditModal({ device, users, services, endpoints, trans, onClose, onSaved }) {
    const [form, setForm] = useState({
        label: device.label ?? '',
        service_id: device.service_id ?? '',
        user_id: device.user_id ?? '',
        serial_number: device.serial_number ?? '',
        calibrated_at: device.calibrated_at ? device.calibrated_at.substring(0, 10) : '',
        calibration_due_at: device.calibration_due_at ? device.calibration_due_at.substring(0, 10) : '',
        calibration_status: device.calibration_status ?? '',
        calibration_provider: device.calibration_provider ?? '',
        location: device.location ?? '',
        capability_index: device.capability_index ?? '',
    });
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    function set(field, value) {
        setForm(f => ({ ...f, [field]: value }));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const url = endpoints.deviceUpdate.replace('__ID__', device.id);
            const result = await apiJson(url, form);
            onSaved(result.device);
        } catch (err) {
            setErrors(err.errors ?? { _: err.message });
        } finally {
            setSaving(false);
        }
    }

    return (
        <Modal title={`${trans.update ?? 'Modifier'} — ${device.label}`} onClose={onClose} size="lg">
            <form onSubmit={handleSubmit}>
                {errors._ && <div className="alert alert-danger py-2">{errors._}</div>}
                <div className="row">
                    <div className="form-group col-md-6">
                        <label>{trans.label ?? 'Libellé'}</label>
                        <input className="form-control" value={form.label} onChange={e => set('label', e.target.value)} required />
                        {errors.label && <small className="text-danger">{errors.label[0]}</small>}
                    </div>
                    <div className="form-group col-md-6">
                        <label>{trans.service ?? 'Service'}</label>
                        <select className="form-control" value={form.service_id} onChange={e => set('service_id', e.target.value)}>
                            <option value="">—</option>
                            {(services ?? []).map(s => <option key={s.id} value={s.id}>{s.label}</option>)}
                        </select>
                    </div>
                </div>
                <div className="row">
                    <div className="form-group col-md-6">
                        <label>{trans.user ?? 'Responsable'}</label>
                        <select className="form-control" value={form.user_id} onChange={e => set('user_id', e.target.value)}>
                            <option value="">—</option>
                            {(users ?? []).map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                    <div className="form-group col-md-6">
                        <label>{trans.serial_number ?? 'N° série'}</label>
                        <input className="form-control" value={form.serial_number} onChange={e => set('serial_number', e.target.value)} />
                    </div>
                </div>
                <div className="row">
                    <div className="form-group col-md-6">
                        <label>{trans.calibrated_at ?? 'Étalonné le'}</label>
                        <input type="date" className="form-control" value={form.calibrated_at} onChange={e => set('calibrated_at', e.target.value)} />
                    </div>
                    <div className="form-group col-md-6">
                        <label>{trans.calibration_due_at ?? 'Prochain étalonnage'}</label>
                        <input type="date" className="form-control" value={form.calibration_due_at} onChange={e => set('calibration_due_at', e.target.value)} />
                    </div>
                </div>
                <div className="row">
                    <div className="form-group col-md-6">
                        <label>{trans.calibration_status ?? 'Statut étalonnage'}</label>
                        <input className="form-control" value={form.calibration_status} onChange={e => set('calibration_status', e.target.value)} />
                    </div>
                    <div className="form-group col-md-6">
                        <label>{trans.calibration_provider ?? 'Prestataire'}</label>
                        <input className="form-control" value={form.calibration_provider} onChange={e => set('calibration_provider', e.target.value)} />
                    </div>
                </div>
                <div className="row">
                    <div className="form-group col-md-6">
                        <label>{trans.location ?? 'Emplacement'}</label>
                        <input className="form-control" value={form.location} onChange={e => set('location', e.target.value)} />
                    </div>
                    <div className="form-group col-md-6">
                        <label>{trans.capability_index ?? 'Indice de capabilité'}</label>
                        <input type="number" step="0.001" min="0" className="form-control" value={form.capability_index} onChange={e => set('capability_index', e.target.value)} />
                    </div>
                </div>
                <div className="card-footer text-right px-0 pb-0">
                    <button type="button" className="btn btn-secondary btn-sm mr-2" onClick={onClose}>{trans.cancel ?? 'Annuler'}</button>
                    <button type="submit" className="btn btn-info btn-sm" disabled={saving}>
                        <i className="fas fa-save mr-1" />{saving ? (trans.saving ?? 'Enregistrement…') : (trans.save ?? 'Enregistrer')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

// ---------------------------------------------------------------------------
// Calibration badge helper
// ---------------------------------------------------------------------------

function CalibrationBadge({ device }) {
    if (!device.calibration_due_at) return <span className="text-muted">—</span>;
    const badgeClass = device.calibration_alert_class ? `badge badge-${device.calibration_alert_class}` : 'badge badge-success';
    return <span className={badgeClass}>{formatDate(device.calibration_due_at)}</span>;
}

// ---------------------------------------------------------------------------
// Device Create Form
// ---------------------------------------------------------------------------

function DeviceCreateForm({ users, services, endpoints, trans, onCreated }) {
    const empty = {
        code: '', label: '', service_id: '', user_id: '', serial_number: '',
        calibrated_at: '', calibration_due_at: '', calibration_status: '',
        calibration_provider: '', location: '', capability_index: '',
    };
    const [form, setForm] = useState(empty);
    const [picture, setPicture] = useState(null);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const [success, setSuccess] = useState(false);

    function set(field, value) { setForm(f => ({ ...f, [field]: value })); }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        setSuccess(false);
        try {
            const fd = new FormData();
            Object.entries(form).forEach(([k, v]) => { if (v !== '') fd.append(k, v); });
            if (picture) fd.append('picture', picture);
            const res = await fetch(endpoints.deviceCreate, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: fd,
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) throw { errors: json.errors ?? {}, message: json.message ?? `HTTP ${res.status}` };
            setForm(empty);
            setPicture(null);
            setSuccess(true);
            onCreated(json.device);
        } catch (err) {
            setErrors(err.errors ?? { _: err.message ?? 'Erreur' });
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="card card-secondary">
            <div className="card-header">
                <h3 className="card-title">{trans.new_measuring_device ?? 'Nouvel appareil de mesure'}</h3>
            </div>
            <form onSubmit={handleSubmit} encType="multipart/form-data">
                <div className="card-body">
                    {errors._ && <div className="alert alert-danger py-2">{errors._}</div>}
                    {success && <div className="alert alert-success py-2">{trans.created_success ?? 'Appareil créé avec succès.'}</div>}
                    <div className="row">
                        <div className="form-group col-md-4">
                            <label>{trans.code ?? 'Code externe'}</label>
                            <input className="form-control" value={form.code} onChange={e => set('code', e.target.value)} placeholder={trans.code ?? 'Code'} />
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.label ?? 'Libellé'} *</label>
                            <input className="form-control" required value={form.label} onChange={e => set('label', e.target.value)} />
                            {errors.label && <small className="text-danger">{errors.label[0]}</small>}
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.service ?? 'Service'}</label>
                            <select className="form-control" value={form.service_id} onChange={e => set('service_id', e.target.value)}>
                                <option value="">—</option>
                                {(services ?? []).map(s => <option key={s.id} value={s.id}>{s.label}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="row">
                        <div className="form-group col-md-4">
                            <label>{trans.user ?? 'Responsable'}</label>
                            <select className="form-control" value={form.user_id} onChange={e => set('user_id', e.target.value)}>
                                <option value="">—</option>
                                {(users ?? []).map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.serial_number ?? 'N° série'}</label>
                            <input className="form-control" value={form.serial_number} onChange={e => set('serial_number', e.target.value)} />
                        </div>
                    </div>
                    <div className="row">
                        <div className="form-group col-md-4">
                            <label>{trans.calibrated_at ?? 'Étalonné le'}</label>
                            <input type="date" className="form-control" value={form.calibrated_at} onChange={e => set('calibrated_at', e.target.value)} />
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.calibration_due_at ?? 'Prochain étalonnage'}</label>
                            <input type="date" className="form-control" value={form.calibration_due_at} onChange={e => set('calibration_due_at', e.target.value)} />
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.calibration_status ?? 'Statut'}</label>
                            <input className="form-control" value={form.calibration_status} onChange={e => set('calibration_status', e.target.value)} />
                        </div>
                    </div>
                    <div className="row">
                        <div className="form-group col-md-4">
                            <label>{trans.calibration_provider ?? 'Prestataire'}</label>
                            <input className="form-control" value={form.calibration_provider} onChange={e => set('calibration_provider', e.target.value)} />
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.location ?? 'Emplacement'}</label>
                            <input className="form-control" value={form.location} onChange={e => set('location', e.target.value)} />
                        </div>
                        <div className="form-group col-md-4">
                            <label>{trans.capability_index ?? 'Capabilité'}</label>
                            <input type="number" step="0.001" min="0" className="form-control" value={form.capability_index} onChange={e => set('capability_index', e.target.value)} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label>{trans.picture ?? 'Photo'}</label>
                        <div className="input-group">
                            <div className="custom-file">
                                <input type="file" className="custom-file-input" accept="image/*"
                                    onChange={e => setPicture(e.target.files[0] ?? null)} />
                                <label className="custom-file-label">
                                    {picture ? picture.name : (trans.choose_file ?? 'Choisir un fichier')}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card-footer text-right">
                    <button type="submit" className="btn btn-danger btn-sm" disabled={saving}>
                        <i className="fas fa-save mr-1" />{saving ? (trans.saving ?? 'Enregistrement…') : (trans.submit ?? 'Enregistrer')}
                    </button>
                </div>
            </form>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Measuring Devices Tab
// ---------------------------------------------------------------------------

function MeasuringDevicesTab({ calibrationAlerts, endpoints, trans }) {
    const [devices, setDevices] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [editDevice, setEditDevice] = useState(null);
    const [users, setUsers] = useState([]);
    const [services, setServices] = useState([]);
    const [selectLoaded, setSelectLoaded] = useState(false);

    const fetchDevices = useCallback(async (p) => {
        setLoading(true);
        try {
            const data = await apiFetch(`${endpoints.deviceList}?page=${p}`);
            setDevices(data.data ?? []);
            setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total });
        } catch (err) {
            console.error('Devices fetch error', err);
        } finally {
            setLoading(false);
        }
    }, [endpoints.deviceList]);

    useEffect(() => { fetchDevices(page); }, [fetchDevices, page]);

    async function loadSelectData() {
        if (selectLoaded) return;
        try {
            const data = await apiFetch(endpoints.selectData);
            setUsers(data.users ?? []);
            setServices(data.services ?? []);
            setSelectLoaded(true);
        } catch (err) { console.error(err); }
    }

    function handleEdit(device) {
        loadSelectData();
        setEditDevice(device);
    }

    function handleSaved(updated) {
        setDevices(ds => ds.map(d => d.id === updated.id ? updated : d));
        setEditDevice(null);
    }

    function handleCreated(device) {
        if (page === 1) fetchDevices(1);
        else setPage(1);
    }

    const overdue = calibrationAlerts?.overdue ?? [];
    const dueSoon = calibrationAlerts?.dueSoon ?? [];
    const threshold = calibrationAlerts?.thresholdDays ?? 7;

    return (
        <div>
            {overdue.length > 0 && (
                <div className="alert alert-danger">
                    <strong>{trans.calibration_overdue ?? 'Étalonnage en retard'}</strong>
                    <ul className="mb-0 mt-1">
                        {overdue.map((d, i) => (
                            <li key={i}>{d.label} ({d.code}) — {d.calibration_due_at} {d.user && `— ${d.user}`}</li>
                        ))}
                    </ul>
                </div>
            )}
            {dueSoon.length > 0 && (
                <div className="alert alert-warning">
                    <strong>{trans.calibration_due_soon ?? 'Étalonnage à venir'}</strong>
                    <p className="mb-1 small">{(trans.calibration_due_soon_description ?? 'Échéance dans les prochains {days} jours').replace('{days}', threshold)}</p>
                    <ul className="mb-0">
                        {dueSoon.map((d, i) => (
                            <li key={i}>{d.label} ({d.code}) — {d.calibration_due_at} {d.user && `— ${d.user}`}</li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="row">
                <div className="col-md-7">
                    <div className="card card-primary">
                        <div className="card-header">
                            <h3 className="card-title">{trans.measuring_devices ?? 'Appareils de mesure'}</h3>
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive">
                                <table className="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>{trans.code ?? 'Code'}</th>
                                            <th>{trans.label ?? 'Libellé'}</th>
                                            <th>{trans.service ?? 'Service'}</th>
                                            <th>{trans.user ?? 'Responsable'}</th>
                                            <th>{trans.calibrated_at ?? 'Étalonné le'}</th>
                                            <th>{trans.calibration_due_at ?? 'Prochain étalonnage'}</th>
                                            <th>{trans.calibration_status ?? 'Statut'}</th>
                                            <th>{trans.location ?? 'Emplacement'}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {loading ? (
                                            <tr><td colSpan={9} className="text-center py-3"><i className="fas fa-spinner fa-spin" /></td></tr>
                                        ) : devices.length === 0 ? (
                                            <tr><td colSpan={9} className="text-center text-muted py-3">{trans.no_data ?? '—'}</td></tr>
                                        ) : devices.map(d => (
                                            <tr key={d.id}>
                                                <td>{d.code}</td>
                                                <td>{d.label}</td>
                                                <td>{d.service_label ?? '—'}</td>
                                                <td>{d.user_name ?? '—'}</td>
                                                <td>{formatDate(d.calibrated_at)}</td>
                                                <td><CalibrationBadge device={d} /></td>
                                                <td>{d.calibration_status ?? '—'}</td>
                                                <td>{d.location ?? '—'}</td>
                                                <td>
                                                    <button className="btn btn-xs btn-default" onClick={() => handleEdit(d)}>
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {meta.last_page > 1 && (
                            <div className="card-footer d-flex align-items-center" style={{ gap: '0.5rem' }}>
                                <button className="btn btn-sm btn-outline-secondary" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
                                    <i className="fas fa-chevron-left" />
                                </button>
                                <span className="small">{page} / {meta.last_page} ({meta.total})</span>
                                <button className="btn btn-sm btn-outline-secondary" disabled={page >= meta.last_page} onClick={() => setPage(p => p + 1)}>
                                    <i className="fas fa-chevron-right" />
                                </button>
                            </div>
                        )}
                    </div>
                </div>
                <div className="col-md-5">
                    <DeviceCreateForm
                        users={users}
                        services={services}
                        endpoints={endpoints}
                        trans={trans}
                        onCreated={handleCreated}
                    />
                </div>
            </div>

            {editDevice && (
                <DeviceEditModal
                    device={editDevice}
                    users={users}
                    services={services}
                    endpoints={endpoints}
                    trans={trans}
                    onClose={() => setEditDevice(null)}
                    onSaved={handleSaved}
                />
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Reference list (Failure / Cause / Correction)
// ---------------------------------------------------------------------------

function ReferenceList({ items, type, endpoints, trans, onUpdated }) {
    const [editItem, setEditItem] = useState(null);
    const [editLabel, setEditLabel] = useState('');
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    function openEdit(item) {
        setEditItem(item);
        setEditLabel(item.label);
        setErrors({});
    }

    async function handleUpdate(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const urlKey = `${type}Update`;
            const url = endpoints[urlKey].replace('__ID__', editItem.id);
            const result = await apiJson(url, { label: editLabel });
            onUpdated(type, result.item);
            setEditItem(null);
        } catch (err) {
            setErrors(err.errors ?? { _: err.message });
        } finally {
            setSaving(false);
        }
    }

    return (
        <div>
            <div className="table-responsive p-0">
                <table className="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>{trans.code ?? 'Code'}</th>
                            <th>{trans.label ?? 'Libellé'}</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {items.length === 0 ? (
                            <tr><td colSpan={3} className="text-muted text-center">{trans.no_data ?? '—'}</td></tr>
                        ) : items.map(item => (
                            <tr key={item.id}>
                                <td>{item.code ?? '—'}</td>
                                <td>{item.label}</td>
                                <td>
                                    <button className="btn btn-xs btn-default" onClick={() => openEdit(item)}>
                                        <i className="fas fa-edit" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {editItem && (
                <Modal title={`${trans.update ?? 'Modifier'} — ${editItem.label}`} onClose={() => setEditItem(null)} size="sm">
                    <form onSubmit={handleUpdate}>
                        {errors._ && <div className="alert alert-danger py-2">{errors._}</div>}
                        <div className="form-group">
                            <label>{trans.label ?? 'Libellé'}</label>
                            <input className="form-control" value={editLabel} onChange={e => setEditLabel(e.target.value)} required />
                            {errors.label && <small className="text-danger">{errors.label[0]}</small>}
                        </div>
                        <div className="text-right">
                            <button type="button" className="btn btn-sm btn-secondary mr-2" onClick={() => setEditItem(null)}>{trans.cancel ?? 'Annuler'}</button>
                            <button type="submit" className="btn btn-sm btn-danger" disabled={saving}>
                                <i className="fas fa-save mr-1" />{saving ? (trans.saving ?? 'Enregistrement…') : (trans.save ?? 'Enregistrer')}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}

function ReferenceCreate({ type, endpoints, trans, onCreated }) {
    const [code, setCode] = useState('');
    const [label, setLabel] = useState('');
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const [success, setSuccess] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        setSuccess(false);
        try {
            const urlKey = `${type}Create`;
            const result = await apiJson(endpoints[urlKey], { code, label });
            setCode('');
            setLabel('');
            setSuccess(true);
            onCreated(type, result.item);
        } catch (err) {
            setErrors(err.errors ?? { _: err.message });
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="card card-secondary">
            <div className="card-header">
                <h3 className="card-title small">{trans[`new_${type}`] ?? `Nouveau ${type}`}</h3>
            </div>
            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    {errors._ && <div className="alert alert-danger py-2">{errors._}</div>}
                    {success && <div className="alert alert-success py-2">{trans.created_success ?? 'Créé avec succès.'}</div>}
                    <div className="form-group">
                        <label>{trans.code ?? 'Code externe'}</label>
                        <input className="form-control" value={code} onChange={e => setCode(e.target.value)} placeholder={trans.code ?? 'Code'} />
                        {errors.code && <small className="text-danger">{errors.code[0]}</small>}
                    </div>
                    <div className="form-group">
                        <label>{trans.label ?? 'Libellé'} *</label>
                        <input className="form-control" required value={label} onChange={e => setLabel(e.target.value)} placeholder={trans.label ?? 'Libellé'} />
                        {errors.label && <small className="text-danger">{errors.label[0]}</small>}
                    </div>
                </div>
                <div className="card-footer text-right">
                    <button type="submit" className="btn btn-danger btn-sm" disabled={saving}>
                        <i className="fas fa-save mr-1" />{saving ? (trans.saving ?? 'Enregistrement…') : (trans.submit ?? 'Enregistrer')}
                    </button>
                </div>
            </form>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Settings Tab
// ---------------------------------------------------------------------------

const SETTINGS_TYPES = [
    { key: 'failure',    themeClass: 'card-teal',   label: 'Défaillances',    transKey: 'failure_types'    },
    { key: 'cause',      themeClass: 'card-purple',  label: 'Causes',          transKey: 'cause_types'      },
    { key: 'correction', themeClass: 'card-orange',  label: 'Corrections',     transKey: 'correction_types' },
];

function SettingsTab({ initialFailures, initialCauses, initialCorrections, endpoints, trans }) {
    const [lists, setLists] = useState({
        failure: initialFailures ?? [],
        cause: initialCauses ?? [],
        correction: initialCorrections ?? [],
    });

    function handleUpdated(type, updated) {
        setLists(prev => ({
            ...prev,
            [type]: prev[type].map(item => item.id === updated.id ? updated : item),
        }));
    }

    function handleCreated(type, newItem) {
        setLists(prev => ({ ...prev, [type]: [...prev[type], newItem] }));
    }

    return (
        <div>
            {SETTINGS_TYPES.map(({ key, themeClass, label, transKey }) => (
                <div key={key} className={`card card-outline ${themeClass} collapsed-card`}>
                    <div className="card-header">
                        <h3 className="card-title">{trans[transKey] ?? label}</h3>
                        <div className="card-tools">
                            <button type="button" className="btn btn-tool" data-card-widget="collapse">
                                <i className="fas fa-plus" />
                            </button>
                        </div>
                    </div>
                    <div className="card-body" style={{ display: 'none' }}>
                        <div className="row">
                            <div className="col-md-6">
                                <ReferenceList
                                    items={lists[key]}
                                    type={key}
                                    endpoints={endpoints}
                                    trans={trans}
                                    onUpdated={handleUpdated}
                                />
                            </div>
                            <div className="col-md-6">
                                <ReferenceCreate
                                    type={key}
                                    endpoints={endpoints}
                                    trans={trans}
                                    onCreated={handleCreated}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main QualityIndex
// ---------------------------------------------------------------------------

export default function QualityIndex({
    kpi,
    rates,
    statusCounts,
    topGenerators,
    calibrationAlerts,
    initialFailures,
    initialCauses,
    initialCorrections,
    endpoints,
    trans,
}) {
    const [tab, setTab] = useState('dashboard');

    const tabs = [
        { key: 'dashboard',   label: trans.dashboard        ?? 'Tableau de bord' },
        { key: 'devices',     label: trans.measuring_devices ?? 'Appareils de mesure' },
        { key: 'settings',    label: trans.settings          ?? 'Paramètres' },
    ];

    return (
        <div className="card card-outline card-info">
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    {tabs.map(t => (
                        <li key={t.key} className="nav-item">
                            <a
                                className={`nav-link${tab === t.key ? ' active' : ''}`}
                                href="#"
                                onClick={e => { e.preventDefault(); setTab(t.key); }}
                            >
                                {t.label}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
            <div className="card-body">
                {tab === 'dashboard' && (
                    <DashboardTab
                        kpi={kpi}
                        rates={rates}
                        statusCounts={statusCounts}
                        topGenerators={topGenerators}
                        trans={trans}
                    />
                )}
                {tab === 'devices' && (
                    <MeasuringDevicesTab
                        calibrationAlerts={calibrationAlerts}
                        endpoints={endpoints}
                        trans={trans}
                    />
                )}
                {tab === 'settings' && (
                    <SettingsTab
                        initialFailures={initialFailures}
                        initialCauses={initialCauses}
                        initialCorrections={initialCorrections}
                        endpoints={endpoints}
                        trans={trans}
                    />
                )}
            </div>
        </div>
    );
}
