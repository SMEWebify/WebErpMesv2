import React, { useState, useEffect, useCallback } from 'react';

// ─── UTILS ────────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
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

function formatDate(d) {
    if (!d) return '—';
    const [y, m, day] = String(d).split('T')[0].split('-').map(Number);
    return new Intl.DateTimeFormat('fr-FR').format(new Date(y, m - 1, day));
}

// ─── STATUS CONFIG ────────────────────────────────────────────────────────────

const PLAN_STATUS = {
    draft:  { label: 'Brouillon', badge: 'badge-secondary' },
    active: { label: 'Actif',     badge: 'badge-primary' },
    closed: { label: 'Clôturé',   badge: 'badge-dark' },
};

const SCHEDULE_STATUS = {
    planned:     { label: 'Planifié',  badge: 'badge-info',      icon: 'fa-calendar',      btn: 'btn-info' },
    in_progress: { label: 'En cours',  badge: 'badge-warning',   icon: 'fa-spinner',       btn: 'btn-warning' },
    completed:   { label: 'Réalisé',   badge: 'badge-success',   icon: 'fa-check-circle',  btn: 'btn-success' },
    cancelled:   { label: 'Annulé',    badge: 'badge-secondary', icon: 'fa-times-circle',  btn: 'btn-secondary' },
};

const FINDING_TYPES = {
    major_nc:       { label: 'NC Majeure',    badge: 'badge-danger',  icon: 'fa-exclamation-circle' },
    minor_nc:       { label: 'NC Mineure',    badge: 'badge-warning', icon: 'fa-exclamation-triangle' },
    observation:    { label: 'Observation',   badge: 'badge-info',    icon: 'fa-eye' },
    positive_point: { label: 'Point positif', badge: 'badge-success', icon: 'fa-thumbs-up' },
};

const MONTHS_FR = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
const MONTHS_FULL = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

// ─── SHARED UI ────────────────────────────────────────────────────────────────

function Modal({ show, onClose, title, size = 'lg', children, footer }) {
    if (!show) return null;
    return (
        <div className="modal fade show d-block" style={{ background: 'rgba(0,0,0,.5)' }}
            onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
            <div className={`modal-dialog modal-dialog-centered modal-${size}`}>
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">{title}</h5>
                        <button type="button" className="close" onClick={onClose}><span>&times;</span></button>
                    </div>
                    <div className="modal-body">{children}</div>
                    {footer && <div className="modal-footer">{footer}</div>}
                </div>
            </div>
        </div>
    );
}

function Spinner() {
    return (
        <div className="text-center py-5">
            <i className="fas fa-circle-notch fa-spin fa-2x text-muted"></i>
        </div>
    );
}

function Alert({ type, msg, onClose }) {
    if (!msg) return null;
    return (
        <div className={`alert alert-${type} alert-dismissible`}>
            {msg}
            {onClose && <button type="button" className="close" onClick={onClose}><span>&times;</span></button>}
        </div>
    );
}

// ─── DONUT SVG ────────────────────────────────────────────────────────────────

function DonutChart({ slices }) {
    // slices: [{ value, color, label }]
    const total = slices.reduce((s, x) => s + x.value, 0);
    if (total === 0) return <p className="text-muted text-center mt-3">Aucune donnée</p>;

    const R = 60, CX = 75, CY = 75, r = 35;
    let cumAngle = -Math.PI / 2;

    const paths = slices.map((slice, i) => {
        if (slice.value === 0) return null;
        const angle = (slice.value / total) * 2 * Math.PI;
        const x1 = CX + R * Math.cos(cumAngle);
        const y1 = CY + R * Math.sin(cumAngle);
        cumAngle += angle;
        const x2 = CX + R * Math.cos(cumAngle);
        const y2 = CY + R * Math.sin(cumAngle);
        const large = angle > Math.PI ? 1 : 0;

        // inner arc
        const ix1 = CX + r * Math.cos(cumAngle - angle);
        const iy1 = CY + r * Math.sin(cumAngle - angle);
        const ix2 = CX + r * Math.cos(cumAngle);
        const iy2 = CY + r * Math.sin(cumAngle);

        return (
            <path key={i}
                d={`M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2} L ${ix2} ${iy2} A ${r} ${r} 0 ${large} 0 ${ix1} ${iy1} Z`}
                fill={slice.color} stroke="#fff" strokeWidth="1.5" />
        );
    });

    return (
        <svg viewBox="0 0 150 150" style={{ width: '100%', maxWidth: 150, display: 'block', margin: '0 auto' }}>
            {paths}
            <text x={CX} y={CY - 6} textAnchor="middle" fontSize="14" fontWeight="700" fill="#333">{total}</text>
            <text x={CX} y={CY + 12} textAnchor="middle" fontSize="10" fill="#888">Total</text>
        </svg>
    );
}

// ─── BAR CHART SVG (Monthly) ─────────────────────────────────────────────────

function MonthlyBarChart({ monthly }) {
    const byMonth = Array.from({ length: 12 }, (_, i) => {
        const m = i + 1;
        const rows = (monthly ?? []).filter(r => r.month === m);
        return {
            month: m,
            completed: rows.find(r => r.status === 'completed')?.count ?? 0,
            planned:   rows.find(r => r.status === 'planned')?.count ?? 0,
            cancelled: rows.find(r => r.status === 'cancelled')?.count ?? 0,
        };
    });

    const maxVal = Math.max(...byMonth.map(m => m.completed + m.planned + m.cancelled), 1);
    const W = 560, H = 180, PAD = { top: 10, right: 10, bottom: 28, left: 28 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;
    const barW = (plotW / 12) * 0.65;
    const xPos = i => PAD.left + (i / 12) * plotW + (plotW / 24);

    return (
        <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', display: 'block' }}>
            {[0, 0.25, 0.5, 0.75, 1].map(t => {
                const y = PAD.top + plotH * (1 - t);
                return (
                    <g key={t}>
                        <line x1={PAD.left} y1={y} x2={PAD.left + plotW} y2={y} stroke="#efefef" strokeWidth="1" />
                        <text x={PAD.left - 4} y={y + 4} textAnchor="end" fontSize="9" fill="#aaa">
                            {Math.round(maxVal * t)}
                        </text>
                    </g>
                );
            })}
            {byMonth.map((m, i) => {
                const x = xPos(i) - barW / 2;
                const total = m.completed + m.planned + m.cancelled;
                if (total === 0) return (
                    <text key={i} x={xPos(i)} y={H - 4} textAnchor="middle" fontSize="9" fill="#aaa">
                        {MONTHS_FR[m.month]}
                    </text>
                );
                let yOff = PAD.top + plotH;
                const segments = [
                    { val: m.completed, color: '#28a745' },
                    { val: m.planned,   color: '#17a2b8' },
                    { val: m.cancelled, color: '#adb5bd' },
                ];
                return (
                    <g key={i}>
                        {segments.map((seg, si) => {
                            if (seg.val === 0) return null;
                            const h = (seg.val / maxVal) * plotH;
                            yOff -= h;
                            return <rect key={si} x={x} y={yOff} width={barW} height={h} fill={seg.color} rx="1" />;
                        })}
                        <text x={xPos(i)} y={H - 4} textAnchor="middle" fontSize="9" fill="#666">
                            {MONTHS_FR[m.month]}
                        </text>
                    </g>
                );
            })}
            {/* Legend */}
            {[['#28a745','Réalisé'],['#17a2b8','Planifié'],['#adb5bd','Annulé']].map(([c, l], i) => (
                <g key={l} transform={`translate(${PAD.left + i * 95}, ${H - 2})`}>
                    <rect x="0" y="-8" width="10" height="10" fill={c} rx="1" />
                    <text x="14" y="0" fontSize="9" fill="#666">{l}</text>
                </g>
            ))}
        </svg>
    );
}

// ─── TAB: DASHBOARD ───────────────────────────────────────────────────────────

function DashboardTab({ kpi: initialKpi, endpoints }) {
    const [kpi, setKpi] = useState(initialKpi);
    const [loading, setLoading] = useState(false);

    const refresh = useCallback(async () => {
        setLoading(true);
        try { setKpi(await apiFetch(endpoints.dashboardKpi)); }
        finally { setLoading(false); }
    }, [endpoints]);

    if (loading) return <Spinner />;

    const { year, totalSchedules, completedSchedules, completionRate,
            majorNc, minorNc, observations, findingsByClause, monthly } = kpi;
    const positivePoints = (kpi.totalFindings ?? 0) - majorNc - minorNc - observations;

    const donutSlices = [
        { value: majorNc,       color: '#dc3545', label: 'NC Majeures' },
        { value: minorNc,       color: '#fd7e14', label: 'NC Mineures' },
        { value: observations,  color: '#17a2b8', label: 'Observations' },
        { value: positivePoints > 0 ? positivePoints : 0, color: '#28a745', label: 'Points positifs' },
    ];

    return (
        <div>
            {/* KPI small-boxes */}
            <div className="row">
                <div className="col-lg-3 col-md-6">
                    <div className="small-box bg-info">
                        <div className="inner">
                            <h3>{totalSchedules}</h3>
                            <p>Audits planifiés {year}</p>
                        </div>
                        <div className="icon"><i className="fas fa-calendar-alt" /></div>
                        <a href="#" className="small-box-footer" onClick={e => e.preventDefault()}>
                            Voir le calendrier <i className="fas fa-arrow-circle-right" />
                        </a>
                    </div>
                </div>
                <div className="col-lg-3 col-md-6">
                    <div className="small-box bg-success">
                        <div className="inner">
                            <h3>{completionRate} <sup style={{ fontSize: '0.5em' }}>%</sup></h3>
                            <p>Taux de réalisation</p>
                        </div>
                        <div className="icon"><i className="fas fa-check-circle" /></div>
                        <a href="#" className="small-box-footer" onClick={e => e.preventDefault()}>
                            {completedSchedules} / {totalSchedules} réalisés <i className="fas fa-arrow-circle-right" />
                        </a>
                    </div>
                </div>
                <div className="col-lg-3 col-md-6">
                    <div className="small-box bg-danger">
                        <div className="inner">
                            <h3>{majorNc}</h3>
                            <p>NC Majeures</p>
                        </div>
                        <div className="icon"><i className="fas fa-exclamation-circle" /></div>
                        <a href="#" className="small-box-footer" onClick={e => e.preventDefault()}>
                            {minorNc} NC mineures <i className="fas fa-arrow-circle-right" />
                        </a>
                    </div>
                </div>
                <div className="col-lg-3 col-md-6">
                    <div className="small-box bg-warning">
                        <div className="inner">
                            <h3>{observations}</h3>
                            <p>Observations</p>
                        </div>
                        <div className="icon"><i className="fas fa-eye" /></div>
                        <a href="#" className="small-box-footer" onClick={refresh}>
                            Actualiser <i className="fas fa-sync-alt" />
                        </a>
                    </div>
                </div>
            </div>

            {/* Charts row */}
            <div className="row">
                {/* Donut constats */}
                <div className="col-md-3">
                    <div className="card card-success">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-pie mr-1" />Répartition des constats
                            </h3>
                        </div>
                        <div className="card-body">
                            <DonutChart slices={donutSlices} />
                            <div className="mt-3">
                                {donutSlices.filter(s => s.value > 0).map(s => (
                                    <div key={s.label} className="d-flex justify-content-between align-items-center mb-1">
                                        <div className="d-flex align-items-center">
                                            <span className="mr-2" style={{
                                                display: 'inline-block', width: 12, height: 12,
                                                borderRadius: 2, background: s.color, flexShrink: 0,
                                            }} />
                                            <span style={{ fontSize: '0.82rem' }}>{s.label}</span>
                                        </div>
                                        <strong>{s.value}</strong>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Monthly bar chart */}
                <div className="col-md-6">
                    <div className="card card-purple">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-chart-bar mr-1" />Audits par mois — {year}
                            </h3>
                        </div>
                        <div className="card-body">
                            <MonthlyBarChart monthly={monthly} />
                        </div>
                    </div>
                </div>

                {/* Top findings by clause */}
                <div className="col-md-3">
                    <div className="card card-orange">
                        <div className="card-header">
                            <h3 className="card-title">
                                <i className="fas fa-list-ol mr-1" />Constats par clause
                            </h3>
                        </div>
                        <div className="card-body p-0">
                            {(!findingsByClause || findingsByClause.length === 0) ? (
                                <p className="text-muted text-center py-3 small">Aucun constat</p>
                            ) : (
                                <table className="table table-sm mb-0">
                                    <tbody>
                                        {findingsByClause.map(row => (
                                            <tr key={row.iso_clause}>
                                                <td><span className="badge badge-secondary">§{row.iso_clause}</span></td>
                                                <td>
                                                    <div className="progress" style={{ height: 8 }}>
                                                        <div className="progress-bar bg-danger"
                                                            style={{ width: `${(row.count / (findingsByClause[0]?.count || 1)) * 100}%` }} />
                                                    </div>
                                                </td>
                                                <td className="text-right"><strong>{row.count}</strong></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* ISO 9001 reminder */}
            <div className="callout callout-info">
                <h5><i className="fas fa-award mr-2"></i>ISO 9001:2015 — §9.2 Audits internes</h5>
                <p className="mb-0 small">
                    L'organisme doit réaliser des <strong>audits internes à intervalles planifiés</strong>.
                    Les auditeurs doivent être <strong>indépendants</strong> des activités auditées.
                    Les résultats doivent être communiqués à la <strong>direction concernée</strong>.
                </p>
            </div>
        </div>
    );
}

// ─── TAB: PLANS ───────────────────────────────────────────────────────────────

function PlansTab({ endpoints, canAdmin }) {
    const [plans, setPlans]   = useState([]);
    const [loading, setLoading] = useState(true);
    const [modal, setModal]   = useState(null);
    const [form, setForm]     = useState({ title: '', year: new Date().getFullYear(), scope: '', status: 'draft' });
    const [saving, setSaving] = useState(false);
    const [error, setError]   = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try { setPlans(await apiFetch(endpoints.plansIndex)); }
        finally { setLoading(false); }
    }, [endpoints]);

    useEffect(() => { load(); }, [load]);

    function openCreate() {
        setForm({ title: `Plan d'audit ${new Date().getFullYear()}`, year: new Date().getFullYear(), scope: '', status: 'draft' });
        setModal('create');
        setError(null);
    }
    function openEdit(plan) {
        setForm({ title: plan.title, year: plan.year, scope: plan.scope || '', status: plan.status });
        setModal(plan);
        setError(null);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true); setError(null);
        try {
            if (modal === 'create') {
                const created = await apiFetch(endpoints.plansStore, { method: 'POST', body: JSON.stringify(form) });
                setPlans(p => [created, ...p]);
            } else {
                const updated = await apiFetch(`${endpoints.planBase}/${modal.id}`, { method: 'PUT', body: JSON.stringify(form) });
                setPlans(p => p.map(pl => pl.id === updated.id ? { ...pl, ...updated } : pl));
            }
            setModal(null);
        } catch (err) { setError(err.message); }
        finally { setSaving(false); }
    }

    async function handleDelete(id) {
        if (!confirm('Supprimer ce plan et toutes ses planifications ?')) return;
        await apiFetch(`${endpoints.planBase}/${id}`, { method: 'DELETE' });
        setPlans(p => p.filter(pl => pl.id !== id));
    }

    if (loading) return <Spinner />;

    const planForm = (
        <>
            <Alert type="danger" msg={error} onClose={() => setError(null)} />
            <div className="form-row">
                <div className="col-md-8">
                    <div className="form-group">
                        <label>Titre <span className="text-danger">*</span></label>
                        <input className="form-control" value={form.title}
                            onChange={e => setForm(f => ({ ...f, title: e.target.value }))} />
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label>Année <span className="text-danger">*</span></label>
                        <input type="number" className="form-control" value={form.year} min={2020} max={2099}
                            onChange={e => setForm(f => ({ ...f, year: parseInt(e.target.value) }))} />
                    </div>
                </div>
            </div>
            <div className="form-group">
                <label>Périmètre</label>
                <textarea className="form-control" rows={3} value={form.scope}
                    placeholder="Ex: Tous les processus du SMQ, site de production..."
                    onChange={e => setForm(f => ({ ...f, scope: e.target.value }))} />
            </div>
            <div className="form-group">
                <label>Statut</label>
                <select className="form-control" value={form.status}
                    onChange={e => setForm(f => ({ ...f, status: e.target.value }))}>
                    <option value="draft">Brouillon</option>
                    <option value="active">Actif</option>
                    <option value="closed">Clôturé</option>
                </select>
            </div>
        </>
    );

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-3">
                <h5 className="mb-0">Plans d'audit annuels</h5>
                {canAdmin && (
                    <button className="btn btn-primary btn-sm" onClick={openCreate}>
                        <i className="fas fa-plus mr-1" />Nouveau plan
                    </button>
                )}
            </div>

            {plans.length === 0 ? (
                <div className="callout callout-info">
                    <h5>Aucun plan d'audit</h5>
                    <p className="mb-0">Commencez par créer un plan annuel (ex: <em>Plan d'audit 2026</em>), puis planifiez des audits par processus dans l'onglet <strong>Calendrier</strong>.</p>
                </div>
            ) : (
                <div className="row">
                    {plans.map(plan => {
                        const st   = PLAN_STATUS[plan.status] ?? PLAN_STATUS.draft;
                        const rate = plan.completion_rate ?? 0;
                        const colorClass = rate >= 80 ? 'success' : rate >= 50 ? 'warning' : 'danger';
                        return (
                            <div key={plan.id} className="col-md-6 col-xl-4 mb-3">
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">{plan.title}</h3>
                                        <div className="card-tools">
                                            <span className={`badge ${st.badge}`}>{st.label}</span>
                                        </div>
                                    </div>
                                    <div className="card-body">
                                        <div className="d-flex justify-content-between align-items-center mb-2">
                                            <span className="text-muted small">
                                                <i className="fas fa-calendar mr-1" />Année {plan.year}
                                            </span>
                                            <span className="text-muted small">
                                                {plan.completed_count ?? 0} / {plan.schedules_count ?? 0} audits
                                            </span>
                                        </div>
                                        <div className="progress progress-sm mb-1">
                                            <div className={`progress-bar bg-${colorClass}`} style={{ width: `${rate}%` }} />
                                        </div>
                                        <div className="d-flex justify-content-between">
                                            <small className="text-muted">{rate}% réalisés</small>
                                        </div>
                                        {plan.scope && <p className="text-muted small mt-2 mb-0">{plan.scope}</p>}
                                        {plan.creator && (
                                            <p className="text-muted small mt-1 mb-0">
                                                <i className="fas fa-user mr-1" />{plan.creator.name}
                                            </p>
                                        )}
                                    </div>
                                    {canAdmin && (
                                        <div className="card-footer">
                                            <button className="btn btn-default btn-sm mr-2" onClick={() => openEdit(plan)}>
                                                <i className="fas fa-edit mr-1" />Modifier
                                            </button>
                                            <button className="btn btn-default btn-sm text-danger" onClick={() => handleDelete(plan.id)}>
                                                <i className="fas fa-trash" />
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            <Modal show={!!modal} onClose={() => setModal(null)}
                title={modal === 'create' ? "Nouveau plan d'audit" : 'Modifier le plan'}
                footer={
                    <>
                        <button className="btn btn-default" onClick={() => setModal(null)}>Annuler</button>
                        <button className="btn btn-primary" onClick={handleSubmit} disabled={saving}>
                            {saving && <i className="fas fa-spinner fa-spin mr-1" />}Enregistrer
                        </button>
                    </>
                }>
                {planForm}
            </Modal>
        </div>
    );
}

// ─── TAB: CALENDRIER ─────────────────────────────────────────────────────────

function CalendarTab({ endpoints, users, processes: initialProcesses, checklists, canAdmin }) {
    const [schedules, setSchedules] = useState([]);
    const [plans, setPlans]         = useState([]);
    const [loading, setLoading]     = useState(true);
    const [filterPlan, setFilterPlan]     = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [filterMonth, setFilterMonth]   = useState('');
    const [modal, setModal]       = useState(null);
    const [form, setForm]         = useState({});
    const [saving, setSaving]     = useState(false);
    const [error, setError]       = useState(null);
    const [execModal, setExecModal] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const [sch, pls] = await Promise.all([
                apiFetch(endpoints.schedulesIndex),
                apiFetch(endpoints.plansIndex),
            ]);
            setSchedules(sch);
            setPlans(pls);
        } finally { setLoading(false); }
    }, [endpoints]);

    useEffect(() => { load(); }, [load]);

    const filtered = schedules.filter(s => {
        if (filterPlan && s.audit_plan_id !== parseInt(filterPlan)) return false;
        if (filterStatus && s.status !== filterStatus) return false;
        if (filterMonth) {
            const d = new Date(s.planned_date);
            if (d.getMonth() + 1 !== parseInt(filterMonth)) return false;
        }
        return true;
    });

    // Group by month
    const byMonth = filtered.reduce((acc, s) => {
        const d = new Date(s.planned_date);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        const label = `${MONTHS_FULL[d.getMonth() + 1]} ${d.getFullYear()}`;
        if (!acc[key]) acc[key] = { label, items: [] };
        acc[key].items.push(s);
        return acc;
    }, {});

    function openCreate() {
        setForm({
            audit_plan_id: plans[0]?.id ?? '',
            audit_process_id: '',
            audit_checklist_id: '',
            planned_date: '',
            planned_duration_hours: 2,
            lead_auditor_id: '',
            notes: '',
        });
        setModal('create');
        setError(null);
    }

    function openEdit(s) {
        setForm({
            audit_plan_id: s.audit_plan_id,
            audit_process_id: s.audit_process_id,
            audit_checklist_id: s.audit_checklist_id ?? '',
            planned_date: String(s.planned_date).split('T')[0],
            planned_duration_hours: s.planned_duration_hours,
            lead_auditor_id: s.lead_auditor_id ?? '',
            status: s.status,
            notes: s.notes ?? '',
        });
        setModal(s);
        setError(null);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true); setError(null);
        try {
            const body = { ...form,
                audit_checklist_id: form.audit_checklist_id || null,
                lead_auditor_id:    form.lead_auditor_id    || null,
            };
            if (modal === 'create') {
                const created = await apiFetch(endpoints.schedulesStore, { method: 'POST', body: JSON.stringify(body) });
                setSchedules(s => [...s, created]);
            } else {
                const updated = await apiFetch(`${endpoints.scheduleBase}/${modal.id}`, { method: 'PUT', body: JSON.stringify(body) });
                setSchedules(s => s.map(x => x.id === updated.id ? { ...x, ...updated } : x));
            }
            setModal(null);
        } catch (err) { setError(err.message); }
        finally { setSaving(false); }
    }

    async function handleDelete(id) {
        if (!confirm('Supprimer cette planification ?')) return;
        await apiFetch(`${endpoints.scheduleBase}/${id}`, { method: 'DELETE' });
        setSchedules(s => s.filter(x => x.id !== id));
    }

    if (loading) return <Spinner />;

    const statusCounts = Object.entries(SCHEDULE_STATUS).map(([k, v]) => ({
        key: k, ...v, count: schedules.filter(s => s.status === k).length,
    }));

    return (
        <div>
            {/* Status filter buttons like QuotesIndex */}
            <div className="d-flex flex-wrap align-items-center mb-3" style={{ gap: '0.4rem' }}>
                {statusCounts.map(s => (
                    <button key={s.key}
                        className={`btn btn-sm ${filterStatus === s.key ? s.btn : 'btn-outline-secondary'}`}
                        onClick={() => setFilterStatus(filterStatus === s.key ? '' : s.key)}>
                        <i className={`fas ${s.icon} mr-1`} />{s.label}
                        <span className="badge badge-light ml-1">{s.count}</span>
                    </button>
                ))}
                <div className="ml-auto d-flex" style={{ gap: '0.4rem' }}>
                    <select className="form-control form-control-sm" style={{ width: 160 }}
                        value={filterPlan} onChange={e => setFilterPlan(e.target.value)}>
                        <option value="">Tous les plans</option>
                        {plans.map(p => <option key={p.id} value={p.id}>{p.title} ({p.year})</option>)}
                    </select>
                    <select className="form-control form-control-sm" style={{ width: 130 }}
                        value={filterMonth} onChange={e => setFilterMonth(e.target.value)}>
                        <option value="">Tous les mois</option>
                        {MONTHS_FULL.slice(1).map((m, i) => <option key={i + 1} value={i + 1}>{m}</option>)}
                    </select>
                    {canAdmin && (
                        <button className="btn btn-primary btn-sm" onClick={openCreate} disabled={plans.length === 0}>
                            <i className="fas fa-plus mr-1" />Planifier
                        </button>
                    )}
                </div>
            </div>

            {plans.length === 0 && (
                <div className="callout callout-warning">
                    <h5>Aucun plan actif</h5>
                    <p className="mb-0">Créez d'abord un <strong>plan d'audit annuel</strong> dans l'onglet "Plans annuels".</p>
                </div>
            )}

            {filtered.length === 0 && plans.length > 0 ? (
                <div className="callout callout-info">
                    <p className="mb-0">Aucun audit pour cette sélection.</p>
                </div>
            ) : (
                Object.entries(byMonth).sort(([a], [b]) => a.localeCompare(b)).map(([key, group]) => (
                    <div key={key} className="mb-4">
                        <h6 className="text-muted mb-2">
                            <i className="fas fa-calendar-week mr-1" />{group.label}
                        </h6>
                        {group.items.map(s => {
                            const st = SCHEDULE_STATUS[s.status] ?? SCHEDULE_STATUS.planned;
                            return (
                                <div key={s.id} className="card card-outline mb-2"
                                    style={{ borderLeftWidth: 4, borderLeftColor:
                                        s.status === 'completed' ? '#28a745' :
                                        s.status === 'in_progress' ? '#ffc107' :
                                        s.status === 'cancelled' ? '#adb5bd' : '#17a2b8' }}>
                                    <div className="card-body py-2 px-3">
                                        <div className="d-flex align-items-center">
                                            {/* Date badge */}
                                            <div className="text-center mr-3" style={{ minWidth: 42 }}>
                                                <div className="font-weight-bold" style={{ fontSize: 20, lineHeight: 1 }}>
                                                    {new Date(s.planned_date).getDate()}
                                                </div>
                                                <div className="text-muted" style={{ fontSize: 11 }}>
                                                    {MONTHS_FR[new Date(s.planned_date).getMonth() + 1]}
                                                </div>
                                            </div>
                                            <div className="border-right mr-3" style={{ height: 36 }} />
                                            {/* Content */}
                                            <div className="flex-grow-1">
                                                <div className="d-flex align-items-center flex-wrap" style={{ gap: '0.3rem' }}>
                                                    <strong>{s.process?.name ?? '—'}</strong>
                                                    <span className={`badge ${st.badge}`}>{st.label}</span>
                                                    {s.plan && <span className="badge badge-secondary">{s.plan.year}</span>}
                                                    {s.checklist && (
                                                        <span className="badge badge-light border">
                                                            <i className="fas fa-list-check mr-1" />{s.checklist.title}
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="small text-muted mt-1">
                                                    <i className="fas fa-clock mr-1" />{s.planned_duration_hours}h
                                                    {s.lead_auditor && (
                                                        <span className="ml-2">
                                                            <i className="fas fa-user mr-1" />{s.lead_auditor.name}
                                                        </span>
                                                    )}
                                                    {s.notes && <span className="ml-2 font-italic">{s.notes}</span>}
                                                </div>
                                            </div>
                                            {/* Actions */}
                                            <div className="d-flex align-items-center ml-2" style={{ gap: '0.3rem' }}>
                                                {(s.status === 'planned' || s.status === 'in_progress') && (
                                                    <button className="btn btn-sm btn-success" onClick={() => setExecModal(s)}
                                                        title={s.status === 'planned' ? "Lancer l'audit" : "Continuer l'audit"}>
                                                        <i className={`fas ${s.status === 'planned' ? 'fa-play' : 'fa-clipboard-list'}`} />
                                                    </button>
                                                )}
                                                {canAdmin && (
                                                    <>
                                                        <button className="btn btn-sm btn-default" onClick={() => openEdit(s)}>
                                                            <i className="fas fa-edit" />
                                                        </button>
                                                        <button className="btn btn-sm btn-default text-danger" onClick={() => handleDelete(s.id)}>
                                                            <i className="fas fa-trash" />
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ))
            )}

            {/* Plan/Edit modal */}
            <Modal show={!!modal} onClose={() => setModal(null)}
                title={modal === 'create' ? 'Planifier un audit' : 'Modifier la planification'}
                footer={
                    <>
                        <button className="btn btn-default" onClick={() => setModal(null)}>Annuler</button>
                        <button className="btn btn-primary" onClick={handleSubmit} disabled={saving}>
                            {saving && <i className="fas fa-spinner fa-spin mr-1" />}Enregistrer
                        </button>
                    </>
                }>
                <Alert type="danger" msg={error} onClose={() => setError(null)} />
                <div className="form-row">
                    <div className="col-md-6">
                        <div className="form-group">
                            <label>Plan d'audit <span className="text-danger">*</span></label>
                            <select className="form-control" value={form.audit_plan_id}
                                onChange={e => setForm(f => ({ ...f, audit_plan_id: parseInt(e.target.value) }))}>
                                <option value="">— Sélectionner —</option>
                                {plans.map(p => <option key={p.id} value={p.id}>{p.title} ({p.year})</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div className="form-group">
                            <label>Processus audité <span className="text-danger">*</span></label>
                            <select className="form-control" value={form.audit_process_id}
                                onChange={e => setForm(f => ({ ...f, audit_process_id: parseInt(e.target.value) }))}>
                                <option value="">— Sélectionner —</option>
                                {initialProcesses.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
                <div className="form-row">
                    <div className="col-md-4">
                        <div className="form-group">
                            <label>Date planifiée <span className="text-danger">*</span></label>
                            <input type="date" className="form-control" value={form.planned_date}
                                onChange={e => setForm(f => ({ ...f, planned_date: e.target.value }))} />
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="form-group">
                            <label>Durée (heures)</label>
                            <input type="number" className="form-control" min={1} max={40} value={form.planned_duration_hours}
                                onChange={e => setForm(f => ({ ...f, planned_duration_hours: parseInt(e.target.value) }))} />
                        </div>
                    </div>
                    <div className="col-md-4">
                        <div className="form-group">
                            <label>Auditeur pilote</label>
                            <select className="form-control" value={form.lead_auditor_id}
                                onChange={e => setForm(f => ({ ...f, lead_auditor_id: e.target.value }))}>
                                <option value="">— Aucun —</option>
                                {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
                <div className="form-group">
                    <label>Checklist ISO 9001</label>
                    <select className="form-control" value={form.audit_checklist_id}
                        onChange={e => setForm(f => ({ ...f, audit_checklist_id: e.target.value }))}>
                        <option value="">— Aucune —</option>
                        {checklists.map(c => (
                            <option key={c.id} value={c.id}>
                                {c.iso_clause ? `[§${c.iso_clause}] ` : ''}{c.title}
                            </option>
                        ))}
                    </select>
                </div>
                {modal !== 'create' && (
                    <div className="form-group">
                        <label>Statut</label>
                        <select className="form-control" value={form.status}
                            onChange={e => setForm(f => ({ ...f, status: e.target.value }))}>
                            {Object.entries(SCHEDULE_STATUS).map(([v, c]) =>
                                <option key={v} value={v}>{c.label}</option>
                            )}
                        </select>
                    </div>
                )}
                <div className="form-group">
                    <label>Notes</label>
                    <textarea className="form-control" rows={2} value={form.notes}
                        onChange={e => setForm(f => ({ ...f, notes: e.target.value }))} />
                </div>
            </Modal>

            {execModal && (
                <ExecutionModal
                    schedule={execModal}
                    endpoints={endpoints}
                    onClose={() => setExecModal(null)}
                    onUpdated={(id, status) => {
                        setSchedules(s => s.map(x => x.id === id ? { ...x, status } : x));
                        setExecModal(null);
                    }}
                />
            )}
        </div>
    );
}

// ─── EXECUTION MODAL ─────────────────────────────────────────────────────────

function ExecutionModal({ schedule, endpoints, onClose, onUpdated }) {
    const [execution, setExecution] = useState(null);
    const [loading, setLoading]     = useState(false);
    const [form, setForm] = useState({
        actual_date: new Date().toISOString().split('T')[0],
        actual_duration_hours: schedule.planned_duration_hours,
        summary: '', conclusion: '',
    });
    const [saving, setSaving]         = useState(false);
    const [error, setError]           = useState(null);
    const [newFinding, setNewFinding] = useState({ type: 'observation', description: '', evidence: '', iso_clause: '', audit_checklist_item_id: '' });
    const [addingFinding, setAddingFinding] = useState(false);

    useEffect(() => {
        if (schedule.latest_execution) {
            setLoading(true);
            apiFetch(`${endpoints.executionBase}/${schedule.latest_execution.id}`)
                .then(data => {
                    setExecution(data);
                    setForm({
                        actual_date: String(data.actual_date).split('T')[0],
                        actual_duration_hours: data.actual_duration_hours ?? schedule.planned_duration_hours,
                        summary: data.summary ?? '',
                        conclusion: data.conclusion ?? '',
                    });
                })
                .finally(() => setLoading(false));
        }
    }, []);

    async function startExecution() {
        setSaving(true); setError(null);
        try {
            const created = await apiFetch(endpoints.executionBase, {
                method: 'POST',
                body: JSON.stringify({ audit_schedule_id: schedule.id, ...form }),
            });
            const full = await apiFetch(`${endpoints.executionBase}/${created.id}`);
            setExecution(full);
            onUpdated(schedule.id, 'in_progress');
        } catch (err) { setError(err.message); }
        finally { setSaving(false); }
    }

    async function saveExecution() {
        if (!execution) return;
        setSaving(true);
        try { await apiFetch(`${endpoints.executionBase}/${execution.id}`, { method: 'PUT', body: JSON.stringify(form) }); }
        catch (err) { setError(err.message); }
        finally { setSaving(false); }
    }

    async function closeExecution() {
        if (!confirm('Clôturer cet audit ? Cette action est irréversible.')) return;
        setSaving(true);
        try {
            await apiFetch(`${endpoints.executionBase}/${execution.id}`, { method: 'PUT', body: JSON.stringify(form) });
            await apiFetch(`${endpoints.executionBase}/${execution.id}/close`, { method: 'POST' });
            onUpdated(schedule.id, 'completed');
        } catch (err) { setError(err.message); setSaving(false); }
    }

    async function addFinding(e) {
        e.preventDefault();
        if (!newFinding.description) return;
        setAddingFinding(true); setError(null);
        try {
            const body = { ...newFinding, audit_execution_id: execution.id,
                audit_checklist_item_id: newFinding.audit_checklist_item_id || null };
            const created = await apiFetch(endpoints.findingBase, { method: 'POST', body: JSON.stringify(body) });
            setExecution(ex => ({ ...ex, findings: [...(ex.findings ?? []), created] }));
            setNewFinding({ type: 'observation', description: '', evidence: '', iso_clause: '', audit_checklist_item_id: '' });
        } catch (err) { setError(err.message); }
        finally { setAddingFinding(false); }
    }

    async function deleteFinding(id) {
        await apiFetch(`${endpoints.findingBase}/${id}`, { method: 'DELETE' });
        setExecution(ex => ({ ...ex, findings: ex.findings.filter(f => f.id !== id) }));
    }

    const checklistItems = execution?.schedule?.checklist?.items ?? [];
    const findings       = execution?.findings ?? [];
    const closed         = execution?.status === 'closed';

    return (
        <Modal show onClose={onClose} size="xl"
            title={
                <span>
                    <i className="fas fa-clipboard-check mr-2" />
                    Audit : {schedule.process?.name}
                    <small className="text-muted ml-2">{formatDate(schedule.planned_date)}</small>
                </span>
            }
            footer={
                execution ? (
                    <>
                        <button className="btn btn-default" onClick={onClose}>Fermer</button>
                        {!closed && (
                            <button className="btn btn-info" onClick={saveExecution} disabled={saving}>
                                <i className="fas fa-save mr-1" />Sauvegarder
                            </button>
                        )}
                        {!closed && (
                            <button className="btn btn-success ml-auto" onClick={closeExecution} disabled={saving}>
                                <i className="fas fa-lock mr-1" />Clôturer l'audit
                            </button>
                        )}
                    </>
                ) : (
                    <>
                        <button className="btn btn-default" onClick={onClose}>Annuler</button>
                        <button className="btn btn-success" onClick={startExecution} disabled={saving}>
                            {saving && <i className="fas fa-spinner fa-spin mr-1" />}
                            <i className="fas fa-play mr-1" />Démarrer l'audit
                        </button>
                    </>
                )
            }>

            <Alert type="danger" msg={error} onClose={() => setError(null)} />

            {loading ? <Spinner /> : (
                <>
                    {closed && (
                        <div className="callout callout-success">
                            <i className="fas fa-check-circle mr-2" />Audit clôturé
                        </div>
                    )}

                    {/* Header form */}
                    <div className="form-row">
                        <div className="col-md-3">
                            <div className="form-group">
                                <label>Date réelle</label>
                                <input type="date" className="form-control" value={form.actual_date} disabled={closed}
                                    onChange={e => setForm(f => ({ ...f, actual_date: e.target.value }))} />
                            </div>
                        </div>
                        <div className="col-md-2">
                            <div className="form-group">
                                <label>Durée (h)</label>
                                <input type="number" className="form-control" min={1} max={40} disabled={closed}
                                    value={form.actual_duration_hours}
                                    onChange={e => setForm(f => ({ ...f, actual_duration_hours: parseInt(e.target.value) }))} />
                            </div>
                        </div>
                        <div className="col-md-7">
                            <div className="form-group">
                                <label>Résumé</label>
                                <input className="form-control" value={form.summary} disabled={closed}
                                    onChange={e => setForm(f => ({ ...f, summary: e.target.value }))} />
                            </div>
                        </div>
                    </div>

                    {execution && (
                        <>
                            {/* Checklist */}
                            {checklistItems.length > 0 && (
                                <div className="card">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <i className="fas fa-list-check mr-2" />
                                            Checklist — {execution.schedule?.checklist?.title}
                                        </h3>
                                        <div className="card-tools">
                                            <span className="badge badge-primary">{checklistItems.length} questions</span>
                                        </div>
                                    </div>
                                    <div className="card-body p-0">
                                        <table className="table table-sm mb-0">
                                            <thead className="thead-light">
                                                <tr>
                                                    <th style={{ width: 70 }}>Clause</th>
                                                    <th>Question</th>
                                                    <th style={{ width: 160 }}>Constats</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {checklistItems.map(item => {
                                                    const iFindings = findings.filter(f => f.audit_checklist_item_id === item.id);
                                                    return (
                                                        <tr key={item.id}>
                                                            <td>
                                                                <span className="badge badge-secondary">§{item.iso_clause}</span>
                                                            </td>
                                                            <td className="small">{item.question}</td>
                                                            <td>
                                                                {iFindings.map(f => (
                                                                    <span key={f.id}
                                                                        className={`badge ${FINDING_TYPES[f.type]?.badge} mr-1`}
                                                                        title={f.description}>
                                                                        {FINDING_TYPES[f.type]?.label}
                                                                    </span>
                                                                ))}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}

                            {/* Findings */}
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="card-title">
                                        <i className="fas fa-flag mr-2" />Constats enregistrés
                                    </h3>
                                    <div className="card-tools">
                                        {Object.entries(FINDING_TYPES).map(([k, v]) => {
                                            const count = findings.filter(f => f.type === k).length;
                                            if (!count) return null;
                                            return <span key={k} className={`badge ${v.badge} mr-1`}>{v.label}: {count}</span>;
                                        })}
                                    </div>
                                </div>
                                {findings.length === 0 ? (
                                    <div className="card-body text-muted text-center py-3 small">Aucun constat enregistré</div>
                                ) : (
                                    <div className="card-body p-0">
                                        <table className="table table-sm mb-0">
                                            <thead className="thead-light">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Clause</th>
                                                    <th>Description</th>
                                                    <th>Preuve</th>
                                                    {!closed && <th></th>}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {findings.map(f => {
                                                    const ft = FINDING_TYPES[f.type];
                                                    return (
                                                        <tr key={f.id}>
                                                            <td>
                                                                <span className={`badge ${ft?.badge}`}>
                                                                    <i className={`fas ${ft?.icon} mr-1`} />{ft?.label}
                                                                </span>
                                                            </td>
                                                            <td><span className="badge badge-secondary">§{f.iso_clause || '—'}</span></td>
                                                            <td className="small">{f.description}</td>
                                                            <td className="small text-muted">{f.evidence || '—'}</td>
                                                            {!closed && (
                                                                <td>
                                                                    <button className="btn btn-xs btn-outline-danger"
                                                                        onClick={() => deleteFinding(f.id)}>
                                                                        <i className="fas fa-times" />
                                                                    </button>
                                                                </td>
                                                            )}
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>

                            {/* Add finding */}
                            {!closed && (
                                <div className="card card-primary card-outline">
                                    <div className="card-header">
                                        <h3 className="card-title">
                                            <i className="fas fa-plus mr-2" />Ajouter un constat
                                        </h3>
                                    </div>
                                    <div className="card-body">
                                        <div className="form-row">
                                            <div className="col-md-3">
                                                <div className="form-group mb-2">
                                                    <label className="small">Type <span className="text-danger">*</span></label>
                                                    <select className="form-control form-control-sm" value={newFinding.type}
                                                        onChange={e => setNewFinding(f => ({ ...f, type: e.target.value }))}>
                                                        {Object.entries(FINDING_TYPES).map(([v, c]) =>
                                                            <option key={v} value={v}>{c.label}</option>
                                                        )}
                                                    </select>
                                                </div>
                                            </div>
                                            <div className="col-md-2">
                                                <div className="form-group mb-2">
                                                    <label className="small">Clause ISO</label>
                                                    <input className="form-control form-control-sm" placeholder="ex: 8.5.1"
                                                        value={newFinding.iso_clause}
                                                        onChange={e => setNewFinding(f => ({ ...f, iso_clause: e.target.value }))} />
                                                </div>
                                            </div>
                                            {checklistItems.length > 0 && (
                                                <div className="col-md-4">
                                                    <div className="form-group mb-2">
                                                        <label className="small">Question associée</label>
                                                        <select className="form-control form-control-sm"
                                                            value={newFinding.audit_checklist_item_id}
                                                            onChange={e => setNewFinding(f => ({ ...f, audit_checklist_item_id: e.target.value }))}>
                                                            <option value="">— Aucune —</option>
                                                            {checklistItems.map(item => (
                                                                <option key={item.id} value={item.id}>
                                                                    [{item.iso_clause}] {item.question.slice(0, 55)}…
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        <div className="form-row">
                                            <div className="col-md-6">
                                                <div className="form-group mb-2">
                                                    <label className="small">Description <span className="text-danger">*</span></label>
                                                    <textarea className="form-control form-control-sm" rows={2}
                                                        placeholder="Décrivez le constat..."
                                                        value={newFinding.description}
                                                        onChange={e => setNewFinding(f => ({ ...f, description: e.target.value }))} />
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group mb-2">
                                                    <label className="small">Preuve / référence</label>
                                                    <textarea className="form-control form-control-sm" rows={2}
                                                        placeholder="Documents vus, observations..."
                                                        value={newFinding.evidence}
                                                        onChange={e => setNewFinding(f => ({ ...f, evidence: e.target.value }))} />
                                                </div>
                                            </div>
                                        </div>
                                        <button className="btn btn-sm btn-primary" onClick={addFinding}
                                            disabled={addingFinding || !newFinding.description}>
                                            {addingFinding
                                                ? <i className="fas fa-spinner fa-spin mr-1" />
                                                : <i className="fas fa-plus mr-1" />}
                                            Ajouter le constat
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Conclusion */}
                            <div className="form-group">
                                <label><strong>Conclusion de l'audit</strong></label>
                                <textarea className="form-control" rows={3} disabled={closed}
                                    placeholder="Synthèse générale, points forts, axes d'amélioration..."
                                    value={form.conclusion}
                                    onChange={e => setForm(f => ({ ...f, conclusion: e.target.value }))} />
                            </div>
                        </>
                    )}
                </>
            )}
        </Modal>
    );
}

// ─── TAB: CHECKLISTS ─────────────────────────────────────────────────────────

function ChecklistsTab({ checklists: initial, endpoints }) {
    const [checklists, setChecklists] = useState(initial);
    const [expanded, setExpanded]     = useState(null);
    const [loading, setLoading]       = useState(false);

    async function refresh() {
        setLoading(true);
        try { setChecklists(await apiFetch(endpoints.checklistsIndex)); }
        finally { setLoading(false); }
    }

    const grouped = checklists.reduce((acc, cl) => {
        const prefix = cl.iso_clause?.split('.')[0] ?? 'Autres';
        if (!acc[prefix]) acc[prefix] = [];
        acc[prefix].push(cl);
        return acc;
    }, {});

    const clauseTitles = {
        '4': 'Contexte de l\'organisme', '5': 'Leadership', '6': 'Planification',
        '7': 'Support', '8': 'Réalisation', '9': 'Évaluation', '10': 'Amélioration',
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-3">
                <h5 className="mb-0">Checklists ISO 9001:2015</h5>
                <button className="btn btn-default btn-sm" onClick={refresh} disabled={loading}>
                    <i className={`fas fa-sync-alt mr-1 ${loading ? 'fa-spin' : ''}`} />Actualiser
                </button>
            </div>

            <div className="callout callout-info">
                <p className="mb-0 small">
                    <strong>{checklists.reduce((s, c) => s + (c.items?.length ?? 0), 0)} questions</strong> pré-remplies
                    selon la norme <strong>ISO 9001:2015</strong> (§4 à §10).
                    Assignez une checklist à chaque audit planifié pour guider l'auditeur.
                </p>
            </div>

            {Object.entries(grouped).sort(([a], [b]) => parseInt(a) - parseInt(b)).map(([prefix, cls]) => (
                <div key={prefix} className="card card-outline card-secondary mb-2">
                    <div className="card-header">
                        <h3 className="card-title">
                            <span className="badge badge-dark mr-2">§{prefix}</span>
                            {clauseTitles[prefix] ?? `Clause ${prefix}`}
                        </h3>
                        <div className="card-tools">
                            <span className="badge badge-secondary">
                                {cls.reduce((s, c) => s + (c.items?.length ?? 0), 0)} questions
                            </span>
                        </div>
                    </div>
                    <div className="card-body p-0">
                        {cls.map(cl => (
                            <div key={cl.id} style={{ borderTop: '1px solid #f4f6f9' }}>
                                <div className="d-flex align-items-center justify-content-between px-3 py-2"
                                    style={{ cursor: 'pointer' }}
                                    onClick={() => setExpanded(expanded === cl.id ? null : cl.id)}>
                                    <div>
                                        <strong>{cl.title}</strong>
                                        {cl.iso_clause && <span className="badge badge-secondary ml-2">§{cl.iso_clause}</span>}
                                        {cl.is_default && <span className="badge badge-primary ml-1">ISO 9001</span>}
                                        <span className="text-muted small ml-2">
                                            {cl.items?.length ?? 0} question(s)
                                        </span>
                                    </div>
                                    <i className={`fas fa-chevron-${expanded === cl.id ? 'up' : 'down'} text-muted`} />
                                </div>
                                {expanded === cl.id && cl.items?.length > 0 && (
                                    <table className="table table-sm mb-0">
                                        <thead className="thead-light">
                                            <tr>
                                                <th style={{ width: 70 }}>Clause</th>
                                                <th>#</th>
                                                <th>Question</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {cl.items.map((item, i) => (
                                                <tr key={item.id}>
                                                    <td><span className="badge badge-secondary">§{item.iso_clause}</span></td>
                                                    <td className="text-muted small">{i + 1}</td>
                                                    <td className="small">{item.question}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

// ─── TAB: PROCESSUS ──────────────────────────────────────────────────────────

function ProcessesTab({ processes: initial, endpoints, users, canAdmin }) {
    const [processes, setProcesses] = useState(initial);
    const [modal, setModal]         = useState(null);
    const [form, setForm]           = useState({ name: '', description: '', responsible_user_id: '' });
    const [saving, setSaving]       = useState(false);
    const [error, setError]         = useState(null);

    function openCreate() {
        setForm({ name: '', description: '', responsible_user_id: '' });
        setModal('create'); setError(null);
    }
    function openEdit(p) {
        setForm({ name: p.name, description: p.description ?? '', responsible_user_id: p.responsible_user_id ?? '' });
        setModal(p); setError(null);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true); setError(null);
        try {
            const body = { ...form, responsible_user_id: form.responsible_user_id || null };
            if (modal === 'create') {
                const created = await apiFetch(endpoints.processesStore, { method: 'POST', body: JSON.stringify(body) });
                setProcesses(p => [...p, created]);
            } else {
                const updated = await apiFetch(`${endpoints.processBase}/${modal.id}`, { method: 'PUT', body: JSON.stringify(body) });
                setProcesses(p => p.map(x => x.id === updated.id ? updated : x));
            }
            setModal(null);
        } catch (err) { setError(err.message); }
        finally { setSaving(false); }
    }

    async function handleDelete(id) {
        if (!confirm('Supprimer ce processus ?')) return;
        await apiFetch(`${endpoints.processBase}/${id}`, { method: 'DELETE' });
        setProcesses(p => p.filter(x => x.id !== id));
    }

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-3">
                <h5 className="mb-0">Processus auditables</h5>
                {canAdmin && (
                    <button className="btn btn-primary btn-sm" onClick={openCreate}>
                        <i className="fas fa-plus mr-1" />Nouveau processus
                    </button>
                )}
            </div>

            <div className="row">
                {processes.map(p => (
                    <div key={p.id} className="col-md-4 mb-3">
                        <div className="card card-outline card-primary">
                            <div className="card-header">
                                <h3 className="card-title">{p.name}</h3>
                                {canAdmin && (
                                    <div className="card-tools">
                                        <button className="btn btn-tool" onClick={() => openEdit(p)}>
                                            <i className="fas fa-edit" />
                                        </button>
                                        <button className="btn btn-tool text-danger" onClick={() => handleDelete(p.id)}>
                                            <i className="fas fa-trash" />
                                        </button>
                                    </div>
                                )}
                            </div>
                            <div className="card-body">
                                {p.description && <p className="small text-muted mb-2">{p.description}</p>}
                                {p.responsible_user && (
                                    <p className="small mb-0">
                                        <i className="fas fa-user mr-1 text-muted" />
                                        <strong>Responsable :</strong> {p.responsible_user.name}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <Modal show={!!modal} onClose={() => setModal(null)}
                title={modal === 'create' ? 'Nouveau processus' : 'Modifier le processus'} size="md"
                footer={
                    <>
                        <button className="btn btn-default" onClick={() => setModal(null)}>Annuler</button>
                        <button className="btn btn-primary" onClick={handleSubmit} disabled={saving}>
                            {saving && <i className="fas fa-spinner fa-spin mr-1" />}Enregistrer
                        </button>
                    </>
                }>
                <Alert type="danger" msg={error} onClose={() => setError(null)} />
                <div className="form-group">
                    <label>Nom <span className="text-danger">*</span></label>
                    <input className="form-control" value={form.name}
                        onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
                </div>
                <div className="form-group">
                    <label>Description</label>
                    <textarea className="form-control" rows={3} value={form.description}
                        onChange={e => setForm(f => ({ ...f, description: e.target.value }))} />
                </div>
                <div className="form-group">
                    <label>Responsable</label>
                    <select className="form-control" value={form.responsible_user_id}
                        onChange={e => setForm(f => ({ ...f, responsible_user_id: e.target.value }))}>
                        <option value="">— Aucun —</option>
                        {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                    </select>
                </div>
            </Modal>
        </div>
    );
}

// ─── MAIN APP ─────────────────────────────────────────────────────────────────

export default function AuditPlannerApp({ endpoints, users, processes, checklists, kpi, canAdmin }) {
    const [activeTab, setActiveTab] = useState('dashboard');

    const tabs = [
        { id: 'dashboard',  label: 'Tableau de bord', icon: 'fa-chart-bar' },
        { id: 'plans',      label: 'Plans annuels',   icon: 'fa-folder-open' },
        { id: 'calendar',   label: 'Calendrier',      icon: 'fa-calendar-alt' },
        { id: 'checklists', label: 'Checklists ISO',  icon: 'fa-clipboard-list' },
        { id: 'processes',  label: 'Processus',       icon: 'fa-sitemap' },
    ];

    return (
        <div className="container-fluid">
            <ul className="nav nav-tabs mb-3">
                {tabs.map(tab => (
                    <li key={tab.id} className="nav-item">
                        <a href="#"
                            className={`nav-link ${activeTab === tab.id ? 'active' : ''}`}
                            onClick={e => { e.preventDefault(); setActiveTab(tab.id); }}>
                            <i className={`fas ${tab.icon} mr-1`} />{tab.label}
                        </a>
                    </li>
                ))}
            </ul>

            {activeTab === 'dashboard'  && <DashboardTab kpi={kpi} endpoints={endpoints} />}
            {activeTab === 'plans'      && <PlansTab endpoints={endpoints} canAdmin={canAdmin} />}
            {activeTab === 'calendar'   && <CalendarTab endpoints={endpoints} users={users} processes={processes} checklists={checklists} canAdmin={canAdmin} />}
            {activeTab === 'checklists' && <ChecklistsTab checklists={checklists} endpoints={endpoints} />}
            {activeTab === 'processes'  && <ProcessesTab processes={processes} endpoints={endpoints} users={users} canAdmin={canAdmin} />}
        </div>
    );
}
