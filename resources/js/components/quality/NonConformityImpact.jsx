import React, { useCallback, useEffect, useState } from 'react';
import { impactTotal, proposedDeliveryDate, reworkPayload, usesActualInternal } from '../../lib/ncImpact.js';

// Impact d'une NC (fiche NC) : coûts, délai, ordre de retouche, report de la date
// de livraison. Règles côté serveur : App\Services\Quality\NonConformityImpact.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function fmt(template, params = {}) {
    return String(template ?? '').replace(/:(\w+)/g, (m, key) => (params[key] ?? m));
}

async function request(url, method = 'GET', body = null) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : null,
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
        const firstError = json.errors ? Object.values(json.errors).flat()[0] : null;
        throw new Error(firstError ?? json.message ?? res.statusText);
    }
    return json;
}

const emptyOperation = () => ({ methods_services_id: '', hours: '', label: '' });

function formFrom(impact) {
    return {
        cost_internal: impact?.costs?.internal ?? '',
        cost_external: impact?.costs?.external ?? '',
        cost_scrap: impact?.costs?.scrap ?? '',
        delay_days: impact?.delay_days ?? '',
        impact_comment: impact?.impact_comment ?? '',
    };
}

export default function NonConformityImpact({ ncId, ncCode, apiBase, trans, currency = 'EUR', locale = 'fr' }) {
    const t = trans ?? {};
    const f = t.fields ?? {};
    const r = t.rework ?? {};
    const d = t.delay ?? {};

    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [notice, setNotice] = useState(null);
    const [busy, setBusy] = useState(false);
    const [form, setForm] = useState(formFrom(null));
    const [operations, setOperations] = useState([emptyOperation()]);
    const [newDate, setNewDate] = useState('');

    const money = (value) => new Intl.NumberFormat(locale, { style: 'currency', currency }).format(Number(value ?? 0));
    const dateLabel = (iso) => (iso ? new Date(`${iso}T00:00:00`).toLocaleDateString(locale) : '—');

    const apply = useCallback((json) => {
        setData(json);
        setForm(formFrom(json.impact));
        setNewDate(''); // vide = date proposée, recalculée pendant la saisie du délai
    }, []);

    const load = useCallback(async () => {
        setError(null);
        try {
            apply(await request(`${apiBase}/${ncId}/impact`));
        } catch (e) {
            setError(t.load_error ?? e.message);
        }
    }, [apiBase, ncId, apply]);

    useEffect(() => { load(); }, [load]);

    async function run(action) {
        setBusy(true);
        setError(null);
        setNotice(null);
        try {
            const json = await action();
            apply(json);
            setNotice(json.message ?? null);
            return true;
        } catch (e) {
            setError(e.message);
            return false;
        } finally {
            setBusy(false);
        }
    }

    if (!data && !error) {
        return <div className="text-body-secondary small"><i className="fas fa-spinner fa-spin me-1" />{t.loading ?? '…'}</div>;
    }
    if (!data) {
        return <div className="alert alert-danger py-1 small mb-0">{error}</div>;
    }

    const impact = data.impact;
    const line = impact.order_line;
    const set = (key, value) => setForm((prev) => ({ ...prev, [key]: value }));
    const total = impactTotal({
        internal: form.cost_internal, external: form.cost_external, scrap: form.cost_scrap,
        internalActual: impact.costs.internal_actual,
    });
    // Proposition recalculée pendant la saisie du délai ; le serveur la recalcule à l'enregistrement.
    const proposal = line ? proposedDeliveryDate(line.delivery_date, form.delay_days) : null;
    const delayDays = parseInt(form.delay_days, 10) || 0;
    const payload = reworkPayload(operations);

    const saveImpact = (e) => {
        e.preventDefault();
        run(() => request(`${apiBase}/${ncId}/impact`, 'POST', {
            cost_internal: form.cost_internal === '' ? null : form.cost_internal,
            cost_external: form.cost_external === '' ? null : form.cost_external,
            cost_scrap: form.cost_scrap === '' ? null : form.cost_scrap,
            delay_days: form.delay_days === '' ? null : form.delay_days,
            impact_comment: form.impact_comment,
        }));
    };

    const createRework = async () => {
        const ok = await run(() => request(`${apiBase}/${ncId}/rework`, 'POST', { operations: payload }));
        if (ok) setOperations([emptyOperation()]);
    };

    const applyDelay = () => {
        const date = newDate || proposal;
        if (!date || !window.confirm(fmt(d.confirm, { date: dateLabel(date) }))) return;
        run(() => request(`${apiBase}/${ncId}/delay`, 'POST', { delivery_date: date }));
    };

    const updateOperation = (index, key, value) =>
        setOperations((prev) => prev.map((op, i) => (i === index ? { ...op, [key]: value } : op)));

    const moneyInput = (key) => (
        <div className="col-md-4 mb-2">
            <label className="form-label small mb-1">{f[key] ?? key}</label>
            <input type="number" step="0.01" min="0" className="form-control form-control-sm"
                value={form[key]} onChange={(e) => set(key, e.target.value)} disabled={busy} />
        </div>
    );

    return (
        <div>
            <p className="small text-body-secondary">{t.intro}</p>
            {error && <div className="alert alert-danger py-1 small">{error}</div>}
            {notice && <div className="alert alert-success py-1 small">{notice}</div>}

            {/* Coûts et délai */}
            <form onSubmit={saveImpact}>
                <div className="row gx-2">
                    {moneyInput('cost_internal')}
                    {moneyInput('cost_external')}
                    {moneyInput('cost_scrap')}
                </div>
                <div className="row gx-2 align-items-end">
                    <div className="col-md-4 mb-2">
                        <div className="small text-body-secondary">{f.cost_internal_actual}</div>
                        <div className="fw-semibold">{money(impact.costs.internal_actual)}</div>
                    </div>
                    <div className="col-md-4 mb-2">
                        <div className="small text-body-secondary">{f.total}</div>
                        <div className="fw-bold fs-5 text-danger">{money(total)}</div>
                        {usesActualInternal(form.cost_internal) && Number(impact.costs.internal_actual) > 0 && (
                            <div className="small text-body-secondary">{t.internal_used_actual}</div>
                        )}
                    </div>
                    <div className="col-md-4 mb-2">
                        <label className="form-label small mb-1">{f.delay_days}</label>
                        <input type="number" min="0" step="1" className="form-control form-control-sm"
                            value={form.delay_days} onChange={(e) => set('delay_days', e.target.value)} disabled={busy} />
                    </div>
                </div>
                <div className="mb-2">
                    <label className="form-label small mb-1">{f.impact_comment}</label>
                    <textarea className="form-control form-control-sm" rows="2" value={form.impact_comment}
                        onChange={(e) => set('impact_comment', e.target.value)} disabled={busy} />
                </div>
                <div className="text-end mb-3">
                    <button type="submit" className="btn btn-sm btn-info" disabled={busy}>
                        <i className="fas fa-save me-1" />{t.save}
                    </button>
                </div>
            </form>

            {/* Ordre de retouche */}
            <h6 className="border-top pt-3">
                <i className="fas fa-tools me-1" />{r.title}
                {impact.rework_in_progress && <span className="badge text-bg-warning ms-2">{r.in_progress}</span>}
            </h6>
            <p className="small text-body-secondary mb-2">{r.help}</p>

            {impact.rework.length === 0 ? (
                <div className="small text-body-secondary mb-2">{r.empty}</div>
            ) : (
                <div className="table-responsive mb-2">
                    <table className="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{r.label}</th>
                                <th>{r.service}</th>
                                <th>{r.status}</th>
                                <th className="text-end">{r.planned}</th>
                                <th className="text-end">{r.actual}</th>
                                <th className="text-end">{r.cost}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {impact.rework.map((task) => (
                                <tr key={task.id}>
                                    <td><a href={`${data.task_url}/${task.id}`} title={r.open_task}>{task.label}</a></td>
                                    <td>{task.service}</td>
                                    <td>
                                        <span className={`badge ${task.finished ? 'text-bg-success' : 'text-bg-secondary'}`}>{task.status}</span>
                                    </td>
                                    <td className="text-end">{task.planned_hours} h</td>
                                    <td className="text-end">{task.actual_hours} h</td>
                                    <td className="text-end">{money(task.actual_cost)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {impact.rework_refusal ? (
                <div className="small text-body-secondary mb-3"><i className="fas fa-info-circle me-1" />{impact.rework_refusal}</div>
            ) : !data.can_rework ? (
                <div className="small text-body-secondary mb-3"><i className="fas fa-lock me-1" />{r.no_permission}</div>
            ) : (
                <div className="card card-body bg-body-tertiary p-2 mb-3">
                    {operations.map((op, index) => (
                        <div className="row gx-2 align-items-end" key={index}>
                            <div className="col-md-4 mb-2">
                                <label className="form-label small mb-1">{r.service}</label>
                                <select className="form-select form-select-sm" value={op.methods_services_id}
                                    onChange={(e) => updateOperation(index, 'methods_services_id', e.target.value)} disabled={busy}>
                                    <option value="">—</option>
                                    {data.services.map((s) => <option key={s.id} value={s.id}>{s.code} — {s.label}</option>)}
                                </select>
                            </div>
                            <div className="col-md-2 mb-2">
                                <label className="form-label small mb-1">{r.hours}</label>
                                <input type="number" min="0" step="0.25" className="form-control form-control-sm"
                                    value={op.hours} onChange={(e) => updateOperation(index, 'hours', e.target.value)} disabled={busy} />
                            </div>
                            <div className="col-md-5 mb-2">
                                <label className="form-label small mb-1">{r.label}</label>
                                <input className="form-control form-control-sm" maxLength={255} value={op.label}
                                    placeholder={fmt(t.rework_label, { code: ncCode, service: '…' })}
                                    onChange={(e) => updateOperation(index, 'label', e.target.value)} disabled={busy} />
                            </div>
                            <div className="col-md-1 mb-2 text-end">
                                {operations.length > 1 && (
                                    <button type="button" className="btn btn-sm btn-outline-danger" title={r.remove_operation}
                                        onClick={() => setOperations((prev) => prev.filter((_, i) => i !== index))} disabled={busy}>
                                        <i className="fas fa-times" />
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                    <div className="d-flex justify-content-between">
                        <button type="button" className="btn btn-sm btn-outline-secondary"
                            onClick={() => setOperations((prev) => [...prev, emptyOperation()])} disabled={busy}>
                            <i className="fas fa-plus me-1" />{r.add_operation}
                        </button>
                        <button type="button" className="btn btn-sm btn-warning" onClick={createRework} disabled={busy || payload.length === 0}>
                            <i className="fas fa-tools me-1" />{r.create}
                        </button>
                    </div>
                </div>
            )}

            {/* Report de la date de livraison : proposé, jamais imposé */}
            {line && (
                <>
                    <h6 className="border-top pt-3"><i className="fas fa-calendar-alt me-1" />{d.title}</h6>
                    <p className="small text-body-secondary mb-2">{fmt(d.help, { code: ncCode })}</p>
                    <div className="row gx-2 align-items-end">
                        <div className="col-md-4 mb-2">
                            <div className="small text-body-secondary">{d.current}</div>
                            <div className="fw-semibold">{dateLabel(line.delivery_date)}</div>
                        </div>
                        <div className="col-md-4 mb-2">
                            {proposal ? (
                                <>
                                    <div className="small text-body-secondary">{fmt(d.proposed, { days: delayDays })}</div>
                                    <div className="fw-semibold text-warning-emphasis">{dateLabel(proposal)}</div>
                                </>
                            ) : (
                                <div className="small text-body-secondary">{d.none}</div>
                            )}
                        </div>
                        {data.can_rework && (
                            <div className="col-md-4 mb-2">
                                <label className="form-label small mb-1">{d.date}</label>
                                <div className="input-group input-group-sm">
                                    <input type="date" className="form-control" value={newDate || proposal || ''}
                                        onChange={(e) => setNewDate(e.target.value)} disabled={busy} />
                                    <button type="button" className="btn btn-outline-warning" onClick={applyDelay}
                                        disabled={busy || !(newDate || proposal)} title={d.apply}>
                                        <i className="fas fa-calendar-check" />
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </>
            )}
        </div>
    );
}
