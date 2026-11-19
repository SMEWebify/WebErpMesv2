import React, { useCallback, useEffect, useRef, useState } from 'react';
import { fmt } from '../lib/i18n.js';

// Textes de l'écran (resources/lang/*/workshop_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

/**
 * WorkshopReportsApp — écran de rapports orienté atelier.
 *
 * Pensé pour un poste debout / écran mural : gros chiffres, peu de clics,
 * rafraîchissement automatique. Les données viennent de WorkshopReportService
 * (réalisé pointé, rebuts, charge machine, andon).
 */

const REFRESH_MS = 60_000;

function fmtHours(value) {
    const hours = Number(value) || 0;
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    return `${h}h${String(m).padStart(2, '0')}`;
}

function fmtNum(value) {
    return new Intl.NumberFormat('fr-FR').format(Number(value) || 0);
}

function fmtDuration(minutes) {
    const total = Math.max(0, Math.round(Number(minutes) || 0));
    const h = Math.floor(total / 60);
    return h > 0 ? `${h}h${String(total % 60).padStart(2, '0')}` : `${total} min`;
}

// ─── Tuile KPI ────────────────────────────────────────────────────────────────

function Tile({ icon, color, label, value, hint }) {
    return (
        <div className="col-6 col-md-3 mb-3">
            <div style={{
                display: 'flex', alignItems: 'stretch', height: '100%',
                background: 'var(--bs-body-bg)', borderRadius: 6, overflow: 'hidden',
                boxShadow: '0 1px 4px rgba(0,0,0,0.16)',
            }}>
                <div style={{
                    width: 64, flexShrink: 0, background: color,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                    <i className={icon} style={{ fontSize: 24, color: '#fff' }} />
                </div>
                <div style={{ padding: '0.5rem 0.8rem', minWidth: 0 }}>
                    <div style={{ fontSize: 11, textTransform: 'uppercase', letterSpacing: 0.4, color: 'var(--bs-secondary-color)' }}>
                        {label}
                    </div>
                    <div style={{ fontSize: 28, fontWeight: 700, lineHeight: 1.1, color: 'var(--bs-emphasis-color)' }}>
                        {value}
                    </div>
                    {hint && <div style={{ fontSize: 12, color: 'var(--bs-secondary-color)' }}>{hint}</div>}
                </div>
            </div>
        </div>
    );
}

// ─── Histogramme SVG empilé (aucune dépendance graphique dans le projet) ──────

function BarChart({ labels, series, height = 200, formatValue = fmtNum }) {
    const totals = labels.map((_, i) => series.reduce((sum, s) => sum + (Number(s.values[i]) || 0), 0));
    const max = Math.max(1, ...totals);
    const barW = 100 / Math.max(labels.length, 1);

    return (
        <div>
            <div style={{ display: 'flex', gap: '1rem', marginBottom: 6, fontSize: 12 }}>
                {series.map(s => (
                    <span key={s.label}>
                        <span style={{
                            display: 'inline-block', width: 10, height: 10,
                            background: s.color, borderRadius: 2, marginRight: 4,
                        }} />
                        {s.label}
                    </span>
                ))}
            </div>
            <div style={{ display: 'flex', alignItems: 'flex-end', height, gap: 2 }}>
                {labels.map((label, i) => (
                    <div key={`${label}-${i}`}
                         title={`${label} — ${series.map(s => `${s.label}: ${formatValue(s.values[i] ?? 0)}`).join(' / ')}`}
                         style={{ flex: `0 0 ${barW}%`, display: 'flex', flexDirection: 'column',
                                  justifyContent: 'flex-end', height: '100%', minWidth: 0 }}>
                        <div style={{ fontSize: 10, textAlign: 'center', color: 'var(--bs-secondary-color)', whiteSpace: 'nowrap' }}>
                            {totals[i] > 0 ? formatValue(totals[i]) : ''}
                        </div>
                        {series.map(s => {
                            const value = Number(s.values[i]) || 0;
                            if (value <= 0) return null;
                            return (
                                <div key={s.label}
                                     style={{ height: `${(value / max) * (height - 26)}px`, background: s.color }} />
                            );
                        })}
                        <div style={{ borderTop: '1px solid var(--bs-border-color)', fontSize: 10, textAlign: 'center',
                                      color: 'var(--bs-secondary-color)', paddingTop: 2, whiteSpace: 'nowrap', overflow: 'hidden' }}>
                            {label}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ─── Tableau de répartition ───────────────────────────────────────────────────

function BreakdownCard({ title, icon, rows, showSessions = true, byUser = false }) {
    return (
        <div className="card mb-3">
            <div className="card-header">
                <h3 className="card-title"><i className={`${icon} me-2`} />{title}</h3>
            </div>
            <div className="card-body p-0" style={{ maxHeight: 320, overflowY: 'auto' }}>
                <table className="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{byUser ? (L.operator ?? 'Opérateur') : (L.label ?? 'Libellé')}</th>
                            <th className="text-end">{L.hours ?? 'Heures'}</th>
                            <th className="text-end">{L.good ?? 'Bonnes'}</th>
                            <th className="text-end">{L.scrap ?? 'Rebuts'}</th>
                            <th className="text-end">{L.scrap_pct ?? '% rebut'}</th>
                            {showSessions && <th className="text-end">{L.sessions ?? 'Sessions'}</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && (
                            <tr><td colSpan={showSessions ? 6 : 5} className="text-center text-muted p-3">{L.no_declaration ?? 'Aucune déclaration'}</td></tr>
                        )}
                        {rows.map((row, i) => (
                            <tr key={`${row.label}-${i}`}>
                                <td>
                                    {row.color && (
                                        <span style={{ display: 'inline-block', width: 10, height: 10, borderRadius: 2,
                                                       background: row.color, marginRight: 6 }} />
                                    )}
                                    {row.label}
                                </td>
                                <td className="text-end"><strong>{fmtHours(row.hours)}</strong></td>
                                <td className="text-end text-success">{fmtNum(row.good)}</td>
                                <td className="text-end text-danger">{row.bad > 0 ? fmtNum(row.bad) : '—'}</td>
                                <td className="text-end">
                                    {row.good + row.bad > 0
                                        ? <span className={`badge ${row.scrap_rate > 5 ? 'text-bg-danger' : 'text-bg-success'}`}>
                                              {row.scrap_rate}&nbsp;%
                                          </span>
                                        : '—'}
                                </td>
                                {showSessions && <td className="text-end">{row.sessions ?? '—'}</td>}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Écran principal ──────────────────────────────────────────────────────────

export default function WorkshopReportsApp({ initial, endpoints, trans = {} }) {
    L = trans?.ui ?? {};
    const [report, setReport]   = useState(initial);
    const [period, setPeriod]   = useState(initial?.period?.key ?? 'today');
    const [loading, setLoading] = useState(false);
    const [error, setError]     = useState(null);
    const firstRender           = useRef(true);

    const load = useCallback(async (key) => {
        setLoading(true);
        setError(null);
        try {
            const url = new URL(endpoints.report, window.location.origin);
            url.searchParams.set('period', key);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error((L.load_failed ?? 'Chargement impossible'));
            setReport(await res.json());
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, [endpoints.report]);

    // La période initiale est déjà rendue côté serveur : pas de double appel au montage.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        load(period);
    }, [period, load]);

    useEffect(() => {
        const timer = setInterval(() => load(period), REFRESH_MS);
        return () => clearInterval(timer);
    }, [period, load]);

    if (!report) return <div className="p-4 text-muted">{L.no_data ?? 'Aucune donnée.'}</div>;

    const { kpi, per_day: perDay, per_resource: perResource, per_user: perUser,
            per_service: perService, andon, in_progress: inProgress } = report;

    const taskUrl = (id) => (endpoints.task ? endpoints.task.replace('__ID__', id) : null);

    return (
        <div>
            {/* Barre de période */}
            <div className="d-flex flex-wrap align-items-center mb-3">
                <div className="btn-group btn-group-lg me-3 mb-2">
                    {(report.periods ?? []).map(p => (
                        <button key={p.key}
                                type="button"
                                className={`btn ${p.key === period ? 'btn-warning' : 'btn-outline-secondary'}`}
                                onClick={() => setPeriod(p.key)}>
                            {p.label}
                        </button>
                    ))}
                </div>
                <button type="button" className="btn btn-outline-secondary btn-lg mb-2 me-3" onClick={() => load(period)}>
                    <i className={`fas fa-sync-alt ${loading ? 'fa-spin' : ''}`} />
                </button>
                <div className="mb-2 text-muted">
                    <div>{report.period.from} → {report.period.to}</div>
                    <small>{L.updated_at ?? 'Mis à jour à'} {report.generated_at} {L.auto_refresh ?? '— rafraîchissement auto toutes les 60 s'}</small>
                </div>
            </div>

            {error && <div className="alert alert-warning">{error}</div>}

            {/* KPI */}
            <div className="row">
                <Tile icon="fas fa-stopwatch"       color="#17a2b8" label={L.clocked_hours ?? 'Heures pointées'} value={fmtHours(kpi.declared_hours)} hint={fmt(L.sessions_count ?? ':count session(s)', { count: fmtNum(kpi.sessions) })} />
                <Tile icon="fas fa-check-circle"    color="#28a745" label={L.good_parts ?? 'Pièces bonnes'}   value={fmtNum(kpi.good_qty)} />
                <Tile icon="fas fa-times-circle"    color="#dc3545" label={L.scrap ?? 'Rebuts'}          value={fmtNum(kpi.bad_qty)} hint={fmt(L.scrap_rate ?? 'Taux :rate %', { rate: kpi.scrap_rate })} />
                <Tile icon="fas fa-flag-checkered"  color="#6f42c1" label={L.finished_tasks ?? 'Tâches terminées'} value={fmtNum(kpi.finished_tasks)} />
                <Tile icon="fas fa-users"           color="#20c997" label={L.active_operators ?? 'Opérateurs actifs'} value={fmtNum(kpi.active_users)} />
                <Tile icon="fas fa-play-circle"     color="#fd7e14" label={L.in_progress ?? 'En cours'}        value={fmtNum(inProgress.length)} hint={L.open_sessions ?? 'sessions ouvertes'} />
                <Tile icon="fas fa-hourglass-end"   color="#e83e8c" label={L.late_tasks ?? 'Tâches en retard'} value={fmtNum(kpi.late_tasks)} hint={L.past_due ?? 'échéance dépassée'} />
                <Tile icon="fas fa-bell"            color="#ffc107" label={L.open_andon ?? 'Andon ouverts'}   value={fmtNum(andon.open)} hint={andon.avg_minutes !== null ? fmt(L.avg_resolution_n ?? 'Résolution moy. :duration', { duration: fmtDuration(andon.avg_minutes) }) : (L.no_resolution ?? 'aucune résolution')} />
            </div>

            {/* En cours maintenant */}
            <div className="card card-warning card-outline mb-3">
                <div className="card-header">
                    <h3 className="card-title"><i className="fas fa-play me-2" />{L.in_progress_now ?? 'En cours maintenant'}</h3>
                    <span className="badge text-bg-warning right">{inProgress.length}</span>
                </div>
                <div className="card-body p-0" style={{ maxHeight: 300, overflowY: 'auto' }}>
                    <table className="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{L.task ?? 'Tâche'}</th>
                                <th>{L.service ?? 'Service'}</th>
                                <th>{L.resource ?? 'Ressource'}</th>
                                <th>{L.operator ?? 'Opérateur'}</th>
                                <th>{L.since ?? 'Depuis'}</th>
                                <th className="text-end">{L.duration ?? 'Durée'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {inProgress.length === 0 && (
                                <tr><td colSpan={6} className="text-center text-muted p-3">{L.no_task_in_progress ?? 'Aucune tâche en cours'}</td></tr>
                            )}
                            {inProgress.map(row => (
                                <tr key={row.task_id}>
                                    <td>
                                        {taskUrl(row.task_id)
                                            ? <a href={taskUrl(row.task_id)}><strong>{row.label}</strong></a>
                                            : <strong>{row.label}</strong>}
                                        <small className="text-muted"> #{row.task_id}</small>
                                    </td>
                                    <td>
                                        {row.service
                                            ? <span className="badge" style={{ background: row.color, color: '#fff' }}>{row.service}</span>
                                            : '—'}
                                    </td>
                                    <td>{row.resource ?? '—'}</td>
                                    <td>{row.user ?? '—'}</td>
                                    <td>{row.since}</td>
                                    <td className="text-end">
                                        <span className={`badge ${row.minutes > 480 ? 'text-bg-danger' : 'text-bg-secondary'}`}>
                                            {fmtDuration(row.minutes)}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Courbes journalières */}
            <div className="row">
                <div className="col-md-6">
                    <div className="card mb-3">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-cubes me-2" />{L.parts_per_day ?? 'Pièces par jour'}</h3>
                        </div>
                        <div className="card-body">
                            <BarChart
                                labels={perDay.map(d => d.label)}
                                series={[
                                    { label: (L.good ?? 'Bonnes'), color: '#28a745', values: perDay.map(d => d.good) },
                                    { label: (L.scrap ?? 'Rebuts'), color: '#dc3545', values: perDay.map(d => d.bad) },
                                ]}
                            />
                        </div>
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="card mb-3">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-clock me-2" />{L.hours_per_day ?? 'Heures pointées par jour'}</h3>
                        </div>
                        <div className="card-body">
                            <BarChart
                                labels={perDay.map(d => d.label)}
                                series={[{ label: (L.hours ?? 'Heures'), color: '#17a2b8', values: perDay.map(d => d.hours) }]}
                                formatValue={fmtHours}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Répartitions */}
            <div className="row">
                <div className="col-md-6">
                    <BreakdownCard title={L.by_resource ?? 'Par ressource'} icon="fas fa-cogs" rows={perResource} />
                </div>
                <div className="col-md-6">
                    <BreakdownCard title={L.by_operator ?? 'Par opérateur'} icon="fas fa-user-clock" rows={perUser} byUser />
                </div>
                <div className="col-md-6">
                    <BreakdownCard title={L.by_service ?? 'Par service'} icon="fas fa-sitemap" rows={perService} showSessions={false} />
                </div>
                <div className="col-md-6">
                    <div className="card mb-3">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-bell me-2" />{L.andon_alerts ?? 'Alertes andon'}</h3>
                            <span className="badge text-bg-secondary right">{andon.total} {L.over_period ?? 'sur la période'}</span>
                        </div>
                        <div className="card-body p-0" style={{ maxHeight: 320, overflowY: 'auto' }}>
                            <table className="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{L.type ?? 'Type'}</th>
                                        <th className="text-end">{L.total ?? 'Total'}</th>
                                        <th className="text-end">{L.unresolved ?? 'Non résolues'}</th>
                                        <th className="text-end">{L.avg_resolution ?? 'Résolution moy.'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {andon.by_type.length === 0 && (
                                        <tr><td colSpan={4} className="text-center text-muted p-3">{L.no_alert ?? 'Aucune alerte'}</td></tr>
                                    )}
                                    {andon.by_type.map(row => (
                                        <tr key={row.label}>
                                            <td>{row.label}</td>
                                            <td className="text-end">{fmtNum(row.count)}</td>
                                            <td className="text-end">
                                                {row.open > 0
                                                    ? <span className="badge text-bg-danger">{row.open}</span>
                                                    : '—'}
                                            </td>
                                            <td className="text-end">
                                                {row.avg_minutes !== null ? fmtDuration(row.avg_minutes) : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {andon.by_resource.length > 0 && (
                                <div className="p-2 border-top">
                                    <small className="text-muted d-block mb-1">{L.most_alerted ?? 'Ressources les plus alertées'}</small>
                                    {andon.by_resource.map(r => (
                                        <span key={r.label} className="badge text-bg-light me-1" style={{ fontSize: 12 }}>
                                            {r.label} <strong>{r.count}</strong>
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
