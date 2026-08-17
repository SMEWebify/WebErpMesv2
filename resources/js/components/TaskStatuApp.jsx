import React, { useState, useEffect, Suspense, lazy } from 'react';

// Le viewer embarque three.js / OCCT-WASM : chargé à la demande uniquement,
// pour ne pas alourdir l'écran quand la tâche n'a aucun plan lié.
const FileViewer = lazy(() => import('./files/FileViewer.jsx'));

// ---------------------------------------------------------------------------
// API helpers
// ---------------------------------------------------------------------------
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function apiFetch(url, method = 'GET', body = null) {
    const opts = {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    };
    if (body) opts.body = JSON.stringify(body);
    return fetch(url, opts).then((r) => {
        if (!r.ok) return r.json().then((e) => Promise.reject(e));
        return r.json();
    });
}

// Activity types mirrored from App\Models\TaskActivities
const ACT_START = 1;
const ACT_END = 2;
const ACT_FINISH = 3;

// ---------------------------------------------------------------------------
// Primitives
// ---------------------------------------------------------------------------
function Card({ title, action, children, tight }) {
    return (
        <section className="ts-card">
            {(title || action) && (
                <header className="ts-card__head">
                    <h2 className="ts-card__title">{title}</h2>
                    {action}
                </header>
            )}
            <div className={`ts-card__body${tight ? ' ts-card__body--tight' : ''}`}>{children}</div>
        </section>
    );
}

function Spec({ label, value, unit }) {
    return (
        <div className="ts-specs__item">
            <span className="ts-label">{label}</span>
            <span className="ts-value">
                {value ?? '—'}
                {unit && value !== null && value !== undefined && <span className="ts-metric__unit">{unit}</span>}
            </span>
        </div>
    );
}

function Metric({ label, value, unit, tone }) {
    return (
        <div className={`ts-metric${tone ? ` ts-metric--${tone}` : ''}`}>
            <div className="ts-metric__value">
                {value ?? 0}
                {unit && <span className="ts-metric__unit">{unit}</span>}
            </div>
            <div className="ts-metric__label">{label}</div>
        </div>
    );
}

function Pill({ tone = 'neutral', dot, children }) {
    return (
        <span className={`ts-pill ts-pill--${tone}`}>
            {dot && <span className="ts-pill__dot" />}
            {children}
        </span>
    );
}

// Maps the AdminLTE `bg-*` class the API sends with each timeline entry
// onto the local tone palette, and isolates the FontAwesome part.
function splitIconClass(iconClass) {
    const parts = (iconClass ?? '').split(/\s+/).filter(Boolean);
    const bg = parts.find((c) => c.startsWith('bg-'));
    const icon = parts.filter((c) => !c.startsWith('bg-')).join(' ');
    const tone = {
        'bg-success': 'ok',
        'bg-primary': 'accent',
        'bg-info': 'accent',
        'bg-warning': 'warn',
        'bg-danger': 'danger',
    }[bg];
    return { icon, tone };
}

// ---------------------------------------------------------------------------
// KpiDashboard — landing state (no task selected)
// ---------------------------------------------------------------------------
function KpiDashboard({ kpi, userProductivity, resourceHours, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const avgHours = kpi.averageProcessingTime ? (kpi.averageProcessingTime / 3600).toFixed(2) : '0.00';

    return (
        <>
            <div className="ts-metrics">
                <Metric tone="accent" value={kpi.tasksInProgress} label={t('current_count_task_trans_key')} />
                <Metric tone="ok" value={kpi.totalProducedHours} unit="h" label={t('total_hours_per_month_trans_key')} />
                <Metric tone="warn" value={avgHours} unit="h" label={t('average_time_task_trans_key')} />
                <Metric value={parseFloat(kpi.averageTRS ?? 0).toFixed(2)} unit="%" label={t('trs_per_month_trans_key')} />
            </div>

            <div className="row">
                <div className="col-lg-4">
                    <Card title={t('goal_task_trans_key')}>
                        <div className="ts-specs">
                            <Spec label={t('open_trans_key')} value={kpi.tasksOpen} />
                            <Spec label={t('suspended_trans_key')} value={kpi.tasksPending} />
                            <Spec label={t('supplied_trans_key')} value={kpi.tasksOngoing} />
                            <Spec label={t('finished_trans_key')} value={kpi.tasksCompleted} />
                        </div>
                    </Card>
                </div>

                <div className="col-lg-4">
                    <section className="ts-card">
                        <header className="ts-card__head">
                            <h2 className="ts-card__title">{t('user_productivity_trans_key')}</h2>
                        </header>
                        <table className="ts-table">
                            <thead>
                                <tr>
                                    <th>{t('user_trans_key')}</th>
                                    <th>{t('task_count_trans_key')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {userProductivity.filter((u) => u.tasks_count > 0).map((u, i) => (
                                    <tr key={i}>
                                        <td>{u.name}</td>
                                        <td>{u.tasks_count}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>
                </div>

                <div className="col-lg-4">
                    <section className="ts-card">
                        <header className="ts-card__head">
                            <h2 className="ts-card__title">{t('total_hours_per_resource_trans_key')}</h2>
                        </header>
                        <table className="ts-table">
                            <thead>
                                <tr>
                                    <th>{t('ressource_trans_key')}</th>
                                    <th>{t('total_time_trans_key')} h</th>
                                </tr>
                            </thead>
                            <tbody>
                                {resourceHours.map((r, i) => (
                                    <tr key={i}>
                                        <td>{r.name}</td>
                                        <td>{parseFloat(r.hours ?? 0).toFixed(2)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// TaskHeader — identity, status, navigation, progress
// ---------------------------------------------------------------------------
function TaskHeader({ task, onNavigate, trans, searchSlot }) {
    const t = (key) => trans?.[key] ?? key;
    const pct = Math.min(Math.max(task.progress ?? 0, 0), 100);
    const ol = task.order_lines;

    const runTone = { [ACT_START]: 'ok', [ACT_END]: 'warn', [ACT_FINISH]: 'accent' }[task.last_activity_type] ?? 'neutral';

    return (
        <div className="ts-header">
            <div className="ts-header__top">
                <div>
                    <div className="ts-crumb">
                        {ol?.order && <a href={`/orders/${ol.orders_id}`}>{ol.order.code}</a>}
                        {ol && (
                            <>
                                <span>/</span>
                                <span>
                                    {t('line_trans_key')} {ol.label}
                                </span>
                            </>
                        )}
                        {ol?.product && (
                            <>
                                <span>/</span>
                                <span>{ol.product.label}</span>
                            </>
                        )}
                    </div>

                    <h1 className="ts-title">
                        <span className="ts-title__id">#{task.id}</span>
                        {task.service?.label ?? task.label}
                        {task.status?.title && (
                            <Pill tone={runTone} dot>
                                {task.status.title}
                            </Pill>
                        )}
                        {task.resources?.map((r) => (
                            <Pill key={r.id}>{r.label}</Pill>
                        ))}
                    </h1>
                </div>

                <div className="ts-header__nav">
                    {searchSlot}
                    {task.previous_task && (
                        <button type="button" className="ts-btn" onClick={() => onNavigate(task.previous_task.id)}>
                            <i className="fas fa-arrow-left" />
                            {task.previous_task.ordre} · {task.previous_task.label}
                        </button>
                    )}
                    {task.next_task && (
                        <button type="button" className="ts-btn" onClick={() => onNavigate(task.next_task.id)}>
                            {task.next_task.ordre} · {task.next_task.label}
                            <i className="fas fa-arrow-right" />
                        </button>
                    )}
                </div>
            </div>

            <div className="ts-header__progress">
                <span className="ts-progress__meta">
                    {task.total_net_good_qt ?? 0} / {task.order_qty ?? 0} {t('qty_trans_key')}
                </span>
                <div className="ts-progress">
                    <div className="ts-progress__bar" style={{ width: `${pct}%` }} />
                </div>
                <span className="ts-progress__meta">{pct} %</span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// RunControls — Play / Pause / Fin (or purchase flow for non-internal services)
// ---------------------------------------------------------------------------
function RunControls({ task, apiBase, onReload, trans, purchasesRequestUrl, stack }) {
    const t = (key) => trans?.[key] ?? key;
    const last = task.last_activity_type;
    const [busy, setBusy] = useState(false);
    const layout = `ts-run${stack ? ' ts-run--stack' : ''}`;

    async function action(type) {
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/${type}`, 'POST');
            onReload();
        } finally {
            setBusy(false);
        }
    }

    if (task.service?.type !== 1) {
        return (
            <div className={`${layout} ts-run--duo`}>
                <a className="ts-run__btn ts-run__btn--start" href={purchasesRequestUrl}>
                    <i className="fas fa-cash-register" />
                    {t('new_purchase_document_trans_key')}
                </a>
                <button
                    type="button"
                    className={`ts-run__btn ts-run__btn--stop${busy ? ' is-disabled' : ''}`}
                    disabled={busy}
                    onClick={() => action('finish')}
                >
                    <i className="fas fa-stop" />
                    {t('end_trans_key')}
                </button>
            </div>
        );
    }

    const running = last === ACT_START;
    const canStart = !last || (last !== ACT_START && last !== ACT_FINISH);
    const canPause = !last || (last !== ACT_END && last !== ACT_FINISH);
    const canFinish = last !== ACT_FINISH;

    const btn = (enabled, variant, icon, label, type, extra = '') => (
        <button
            type="button"
            className={`ts-run__btn ts-run__btn--${variant}${extra}${!enabled || busy ? ' is-disabled' : ''}`}
            disabled={!enabled || busy}
            onClick={() => enabled && action(type)}
        >
            <i className={icon} />
            {label}
        </button>
    );

    return (
        <div className={layout}>
            {btn(canStart, 'start', 'fas fa-play', t('play_trans_key'), 'start', running ? ' ts-run__btn--is-active' : '')}
            {btn(canPause, 'pause', 'fas fa-pause', t('pause_trans_key'), 'pause')}
            {btn(canFinish, 'stop', 'fas fa-stop', t('end_trans_key'), 'finish')}
        </div>
    );
}

// ---------------------------------------------------------------------------
// QtyDeclare — one column of the declaration panel (good or rejected)
// ---------------------------------------------------------------------------
function QtyDeclare({ label, endpoint, tone, sign, task, apiBase, onReload }) {
    const [qty, setQty] = useState(0);
    const [busy, setBusy] = useState(false);

    async function declare(amount) {
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/${endpoint}`, 'POST', { qty: amount });
            setQty(0);
            onReload();
        } finally {
            setBusy(false);
        }
    }

    function handleSubmit(e) {
        e.preventDefault();
        if (qty > 0) declare(qty);
    }

    return (
        <div className="ts-declare__col">
            <span className="ts-label">{label}</span>
            <div className="ts-quick">
                {[1, 10, 100].map((n) => (
                    <button
                        key={n}
                        type="button"
                        className={`ts-quick__btn ts-quick__btn--${tone}`}
                        disabled={busy}
                        onClick={() => declare(n)}
                    >
                        {sign}
                        {n}
                    </button>
                ))}
            </div>
            <form onSubmit={handleSubmit}>
                <div className="ts-field">
                    <input
                        type="number"
                        className="ts-input"
                        min="0"
                        value={qty}
                        onChange={(e) => setQty(Number(e.target.value))}
                    />
                    <button type="submit" className="ts-btn ts-btn--primary" disabled={busy || qty <= 0}>
                        Set
                    </button>
                </div>
            </form>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ActivityFeed
// ---------------------------------------------------------------------------
function ActivityFeed({ timeline, trans }) {
    const t = (key) => trans?.[key] ?? key;

    if (!timeline.length) {
        return <div className="ts-empty">{t('logs_activity_trans_key')} — 0</div>;
    }

    let prevDate = null;

    return (
        <div className="ts-feed">
            {timeline.map((item, i) => {
                const showDate = item.date_label !== prevDate;
                prevDate = item.date_label;
                const { icon, tone } = splitIconClass(item.icon_class);

                return (
                    <React.Fragment key={i}>
                        {showDate && <div className="ts-feed__day">{item.date_label}</div>}
                        <div className={`ts-feed__item${tone ? ` ts-feed__item--${tone}` : ''}`}>
                            <div className="ts-feed__text">
                                {icon && <i className={`${icon} ts-muted mr-2`} />}
                                {item.content}
                            </div>
                            <div className="ts-feed__meta">{item.details}</div>
                        </div>
                    </React.Fragment>
                );
            })}
        </div>
    );
}

// ---------------------------------------------------------------------------
// DocumentStage — le plan en grand, au centre de l'écran opérateur
//
// L'API renvoie déjà `documents` trié par pertinence (STEP > PDF > DXF > SVG >
// GEO), donc le premier élément est celui qu'on ouvre par défaut.
// ---------------------------------------------------------------------------
function DocumentStage({ documents, fileTrans, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const ft = (key) => fileTrans?.[key] ?? key;

    const [activeId, setActiveId] = useState(documents[0]?.id ?? null);

    useEffect(() => {
        setActiveId(documents[0]?.id ?? null);
    }, [documents]);

    const active = documents.find((d) => d.id === activeId) ?? null;

    if (!documents.length) {
        return (
            <div className="ts-stage">
                <div className="ts-stage__empty">
                    <i className="fas fa-drafting-compass" />
                    <p>{t('no_data_trans_key')}</p>
                    <span className="ts-muted">{ft('select_a_file')}</span>
                </div>
            </div>
        );
    }

    return (
        <div className="ts-stage">
            {/* Un seul document : rien à choisir, donc pas de barre du tout. */}
            {documents.length > 1 && (
                <div className="ts-stage__bar">
                    <div className="ts-stage__tabs">
                        {documents.map((doc) => (
                            <button
                                key={doc.id}
                                type="button"
                                title={doc.name}
                                className={`ts-doctab${doc.id === activeId ? ' is-active' : ''}`}
                                onClick={() => setActiveId(doc.id)}
                            >
                                <i className={doc.icon} />
                                <span className="ts-doctab__ext">{doc.extension?.toUpperCase()}</span>
                                <span className="ts-doctab__name">{doc.name}</span>
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <div className="ts-stage__canvas">
                <Suspense
                    fallback={
                        <div className="ts-stage__empty">
                            <i className="fas fa-spinner fa-spin" />
                            <p>{ft('loading')}</p>
                        </div>
                    }
                >
                    <FileViewer file={active} t={ft} />
                </Suspense>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// PartCard — part identity, drawing, photo, component
// ---------------------------------------------------------------------------
function PartCard({ task, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const ol = task.order_lines;
    const product = ol?.product;

    const thumb = ol?.picture
        ? `/images/order-lines/${ol.picture}`
        : task.service?.picture
          ? `/storage/images/methods/${task.service.picture}`
          : null;

    return (
        <Card title={t('informations_trans_key')}>
            <div className="ts-part">
                <div className="ts-part__thumb">
                    {thumb ? (
                        <img src={thumb} alt={product?.label ?? t('informations_trans_key')} />
                    ) : (
                        <i className="fas fa-cube" />
                    )}
                </div>
                <div className="ts-part__body">
                    <p className="ts-part__name">{product?.label ?? task.label}</p>
                    <span className="ts-muted" style={{ fontSize: 12 }}>
                        {ol?.order?.code} · {t('line_trans_key')} {ol?.label}
                    </span>

                    <div className="ts-part__actions">
                        {ol && (
                            <a className="ts-btn ts-btn--sm" href={`/orders/${ol.orders_id}`}>
                                <i className="fas fa-file-invoice" />
                                {ol.order?.code}
                            </a>
                        )}
                        {ol?.picture && (
                            <a
                                className="ts-btn ts-btn--sm ts-btn--icon"
                                href={`/images/order-lines/${ol.picture}`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <i className="fa fa-eye" />
                            </a>
                        )}
                        {product?.drawing_file && (
                            <a
                                className="ts-btn ts-btn--sm"
                                href={`/drawing/${product.drawing_file}`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <i className="fas fa-drafting-compass" />
                                {t('drawing_trans_key')}
                            </a>
                        )}
                    </div>
                </div>
            </div>

            {task.component_id && task.component && (
                <div style={{ marginTop: 14, paddingTop: 14, borderTop: '1px solid var(--ts-border)' }}>
                    <span className="ts-label">{t('component_trans_key')}</span>
                    <div className="ts-part__actions" style={{ marginTop: 4 }}>
                        <span className="ts-value">{task.component.code}</span>
                        <a
                            className="ts-btn ts-btn--sm ts-btn--icon"
                            href={`/products/${task.component_id}`}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <i className="fa fa-eye" />
                        </a>
                        {task.component.drawing_file && (
                            <a
                                className="ts-btn ts-btn--sm ts-btn--icon"
                                href={`/drawing/${task.component.drawing_file}`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <i className="fa fa-file" />
                            </a>
                        )}
                    </div>
                </div>
            )}
        </Card>
    );
}

// ---------------------------------------------------------------------------
// StockWithdrawalCard — only when the task consumes a component
// ---------------------------------------------------------------------------
function StockWithdrawalCard({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [qty, setQty] = useState(0);
    const [busy, setBusy] = useState(false);
    const r = task.reservation;

    async function handleSubmit(e) {
        e.preventDefault();
        if (qty <= 0) return;
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/good-qty-stock`, 'POST', {
                qty,
                component_id: task.component_id,
            });
            setQty(0);
            onReload();
        } finally {
            setBusy(false);
        }
    }

    return (
        <Card title={t('remove_from_stock_trans_key')}>
            {r && (
                <div className="ts-badges">
                    <Pill>Demandé {r.requested}</Pill>
                    <Pill tone="accent">Réservé {r.reserved}</Pill>
                    {r.missing > 0 && <Pill tone="danger">Manque {r.missing}</Pill>}
                </div>
            )}
            <form onSubmit={handleSubmit}>
                <div className="ts-field">
                    <input
                        type="number"
                        className="ts-input"
                        min="0"
                        value={qty}
                        onChange={(e) => setQty(Number(e.target.value))}
                    />
                    <button type="submit" className="ts-btn ts-btn--primary" disabled={busy || qty <= 0}>
                        Set
                    </button>
                </div>
            </form>
        </Card>
    );
}

// ---------------------------------------------------------------------------
// PlanningCard — end date + resource, merged into one panel
// ---------------------------------------------------------------------------
function PlanningCard({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [endDate, setEndDate] = useState(task.end_date ?? '');
    const [notRecalculate, setNotRecalculate] = useState(task.not_recalculate ?? false);
    const [dateBusy, setDateBusy] = useState(false);

    const [resourceId, setResourceId] = useState(task.selected_resource_id ?? '');
    const [laborResourceId, setLaborResourceId] = useState(task.selected_labor_resource_id ?? '');
    const [userforcedResource, setUserforcedResource] = useState(task.userforced_resource ?? false);
    const [resBusy, setResBusy] = useState(false);
    const [laborBusy, setLaborBusy] = useState(false);

    async function submitDate(e) {
        e.preventDefault();
        if (!endDate) return;
        setDateBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/date`, 'PUT', {
                end_date: endDate,
                not_recalculate: notRecalculate,
            });
            onReload();
        } finally {
            setDateBusy(false);
        }
    }

    async function submitResource(e) {
        e.preventDefault();
        if (!resourceId) return;
        setResBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/resource`, 'PUT', { resource_id: resourceId });
            setUserforcedResource(true);
            onReload();
        } finally {
            setResBusy(false);
        }
    }

    // Même endpoint : le rôle (machine / main-d'œuvre) est déduit côté serveur
    // de la nature de la ressource, l'affectation machine reste en place.
    async function submitLaborResource(e) {
        e.preventDefault();
        if (!laborResourceId) return;
        setLaborBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/resource`, 'PUT', { resource_id: laborResourceId });
            onReload();
        } finally {
            setLaborBusy(false);
        }
    }

    return (
        <Card title={t('scheduling_trans_key')}>
            <div className="ts-stack">
                <div>
                    <span className="ts-label">{t('end_date_trans_key')}</span>
                    <span className="ts-value">
                        {task.formatted_end_date !== 'NULL' ? task.formatted_end_date : '—'}
                    </span>
                </div>

                <form onSubmit={submitDate}>
                    <div className="ts-field">
                        <input
                            type="datetime-local"
                            className="ts-input"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                        <button type="submit" className="ts-btn" disabled={dateBusy || !endDate}>
                            Set
                        </button>
                    </div>
                    <label className="ts-check" style={{ marginTop: 8 }}>
                        <input
                            type="checkbox"
                            checked={notRecalculate}
                            onChange={(e) => setNotRecalculate(e.target.checked)}
                        />
                        Ne pas recalculer
                    </label>
                </form>

                <form onSubmit={submitResource} style={{ borderTop: '1px solid var(--ts-border)', paddingTop: 12 }}>
                    <span className="ts-label">{t('ressource_trans_key')}</span>
                    <div className="ts-field">
                        <select
                            className="ts-select"
                            value={resourceId}
                            onChange={(e) => setResourceId(e.target.value)}
                        >
                            <option value="">— {t('select_ressource_trans_key')} —</option>
                            {task.service_resources?.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.label}
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="ts-btn" disabled={resBusy || !resourceId}>
                            Set
                        </button>
                    </div>
                    {userforcedResource && (
                        <span className="ts-muted" style={{ fontSize: 12, display: 'block', marginTop: 6 }}>
                            <i className="fas fa-user-lock mr-1" />
                            {t('user_choise_trans_key')}
                        </span>
                    )}
                </form>

                {task.service_labor_resources?.length > 0 && (
                    <form onSubmit={submitLaborResource} style={{ borderTop: '1px solid var(--ts-border)', paddingTop: 12 }}>
                        <span className="ts-label">{t('labor_resource_trans_key')}</span>
                        <div className="ts-field">
                            <select
                                className="ts-select"
                                value={laborResourceId}
                                onChange={(e) => setLaborResourceId(e.target.value)}
                            >
                                <option value="">— {t('select_ressource_trans_key')} —</option>
                                {task.service_labor_resources.map((r) => (
                                    <option key={r.id} value={r.id}>
                                        {r.label}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="ts-btn" disabled={laborBusy || !laborResourceId}>
                                Set
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </Card>
    );
}

// ---------------------------------------------------------------------------
// QualityCard — non conformity + Andon alert
// ---------------------------------------------------------------------------
function QualityCard({ task, apiBase, andonStoreUrl, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [showAndon, setShowAndon] = useState(false);
    const [andonType, setAndonType] = useState('');
    const [andonMessage, setAndonMessage] = useState('');

    async function handleCreateNc() {
        const result = await apiFetch(`${apiBase}/${task.id}/nc`, 'POST');
        if (result.redirect_url) window.location.href = result.redirect_url;
    }

    return (
        <Card title={t('quality_trans_key')}>
            <div className="ts-part__actions" style={{ marginTop: 0 }}>
                <button type="button" className="ts-btn ts-btn--sm" onClick={handleCreateNc}>
                    <i className="fas fa-exclamation-triangle" />
                    {t('new_non_conformitie_trans_key')}
                </button>
                <button type="button" className="ts-btn ts-btn--sm" onClick={() => setShowAndon(!showAndon)}>
                    <i className="fas fa-bell" />
                    Alerte Andon
                </button>
            </div>

            {showAndon && (
                <form action={andonStoreUrl} method="POST" style={{ marginTop: 12 }}>
                    <input type="hidden" name="_token" value={csrfToken()} />
                    <input type="hidden" name="task_id" value={task.id} />
                    <input type="hidden" name="resource_id" value={task.selected_resource_id ?? 1} />
                    <div className="ts-stack">
                        <div>
                            <span className="ts-label">{"Type d'alerte"}</span>
                            <input
                                type="text"
                                className="ts-input"
                                name="type"
                                value={andonType}
                                onChange={(e) => setAndonType(e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <span className="ts-label">Description</span>
                            <textarea
                                className="ts-input"
                                name="message"
                                rows={2}
                                value={andonMessage}
                                onChange={(e) => setAndonMessage(e.target.value)}
                                required
                            />
                        </div>
                        <button type="submit" className="ts-btn ts-btn--primary">
                            {"Ajouter l'alerte"}
                        </button>
                    </div>
                </form>
            )}
        </Card>
    );
}

// ---------------------------------------------------------------------------
// TaskDetail — two-column layout: work area + context rail
// ---------------------------------------------------------------------------
function TaskDetail({
    task,
    apiBase,
    andonStoreUrl,
    purchasesRequestUrl,
    onNavigate,
    trans,
    fileTrans,
    searchSlot,
}) {
    const t = (key) => trans?.[key] ?? key;
    const [tab, setTab] = useState('activity');
    const reload = () => onNavigate(task.id);
    const isInternal = task.service?.type === 1;

    if (!task.order_lines_id) {
        return (
            <>
                <div className="ts-topbar">{searchSlot}</div>
                <div className="ts-alert">{t('quote_task_trans_key')}</div>
            </>
        );
    }

    return (
        <>
            <TaskHeader task={task} onNavigate={onNavigate} trans={trans} searchSlot={searchSlot} />

            <div className="ts-metrics">
                {isInternal && (
                    <Metric tone="accent" value={task.total_log_time} unit="h" label={t('total_time_trans_key')} />
                )}
                <Metric tone="ok" value={task.total_log_good_qt} label={t('finish_part_qty_trans_key')} />
                <Metric tone="danger" value={task.total_log_bad_qt} label={t('bad_part_qty_trans_key')} />
                <Metric value={task.total_net_good_qt} label={t('net_production_qty_trans_key')} />
                {isInternal && <Metric tone="warn" value={task.trs} unit="%" label={t('trs_trans_key')} />}
            </div>

            <div className="ts-shop">
                {/* Rail gauche — actions opérateur, reste visible au scroll */}
                <aside className="ts-shop__rail ts-shop__rail--actions">
                    <Card title={t('task_detail_trans_key')} tight>
                        <RunControls
                            task={task}
                            apiBase={apiBase}
                            onReload={reload}
                            trans={trans}
                            purchasesRequestUrl={purchasesRequestUrl}
                            stack
                        />
                    </Card>

                    <Card title={t('qty_trans_key')} tight>
                        <div className="ts-declare ts-declare--stack">
                            <QtyDeclare
                                label={t('good_rejected_trans_key')}
                                endpoint="good-qty"
                                tone="ok"
                                sign="+"
                                task={task}
                                apiBase={apiBase}
                                onReload={reload}
                            />
                            <QtyDeclare
                                label={t('quantity_rejected_trans_key')}
                                endpoint="bad-qty"
                                tone="danger"
                                sign="−"
                                task={task}
                                apiBase={apiBase}
                                onReload={reload}
                            />
                        </div>
                    </Card>

                    <QualityCard task={task} apiBase={apiBase} andonStoreUrl={andonStoreUrl} trans={trans} />
                </aside>

                {/* Centre — le plan */}
                <div className="ts-shop__stage">
                    <DocumentStage documents={task.documents ?? []} fileTrans={fileTrans} trans={trans} />
                </div>

                {/* Rail droit — contexte */}
                <aside className="ts-shop__rail ts-shop__rail--info">
                    <PartCard task={task} trans={trans} />

                    {task.component_id && (
                        <StockWithdrawalCard task={task} apiBase={apiBase} onReload={reload} trans={trans} />
                    )}

                    <PlanningCard task={task} apiBase={apiBase} onReload={reload} trans={trans} />

                    <section className="ts-card">
                        <header className="ts-card__head">
                            <div className="ts-tabs">
                                <button
                                    type="button"
                                    className={`ts-tab${tab === 'activity' ? ' is-active' : ''}`}
                                    onClick={() => setTab('activity')}
                                >
                                    {t('logs_activity_trans_key')}
                                </button>
                                <button
                                    type="button"
                                    className={`ts-tab${tab === 'specs' ? ' is-active' : ''}`}
                                    onClick={() => setTab('specs')}
                                >
                                    {t('informations_trans_key')}
                                </button>
                            </div>
                        </header>
                        <div className="ts-card__body">
                            {tab === 'activity' ? (
                                <ActivityFeed timeline={task.timeline ?? []} trans={trans} />
                            ) : (
                                <div className="ts-specs">
                                    <Spec label={t('statu_trans_key')} value={task.status?.title} />
                                    <Spec label={t('qty_trans_key')} value={task.order_qty} />
                                    <Spec label={t('cost_trans_key')} value={task.formatted_unit_cost} />
                                    <Spec label={t('price_trans_key')} value={task.formatted_unit_price} />
                                    <Spec label={t('margin_trans_key')} value={task.margin} unit=" %" />
                                    {isInternal && (
                                        <>
                                            <Spec label={t('setting_time_trans_key')} value={task.seting_time} unit=" s" />
                                            <Spec label={t('unit_time_trans_key')} value={task.unit_time} unit=" s" />
                                            <Spec label={t('total_time_trans_key')} value={task.total_time} unit=" h" />
                                            <Spec label={t('trs_trans_key')} value={task.trs} unit=" %" />
                                        </>
                                    )}
                                    <Spec label={t('progress_trans_key')} value={task.progress} unit=" %" />
                                </div>
                            )}
                        </div>
                    </section>
                </aside>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Root component
// ---------------------------------------------------------------------------
export default function TaskStatuApp({
    kpi,
    userProductivity,
    resourceHours,
    initialTaskId,
    pageTitle,
    baseStatuUrl,
    apiBaseUrl,
    andonStoreUrl,
    purchasesRequestUrl,
    trans,
    fileTrans,
}) {
    const [search, setSearch] = useState(initialTaskId ? String(initialTaskId) : '');
    const [task, setTask] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const t = (key) => trans?.[key] ?? key;

    function loadTask(id) {
        if (!id) return;
        setLoading(true);
        setError(null);
        apiFetch(`${apiBaseUrl}/${id}`)
            .then((data) => {
                setTask(data);
                setSearch(String(id));
            })
            .catch(() => setError(`Tâche #${id} introuvable.`))
            .finally(() => setLoading(false));
    }

    useEffect(() => {
        if (initialTaskId) loadTask(initialTaskId);
    }, []);

    function handleSearch(e) {
        e.preventDefault();
        const id = parseInt(search.trim(), 10);
        if (!id) return;
        window.history.pushState({}, '', `${baseStatuUrl}/${id}`);
        loadTask(id);
    }

    function handleNavigate(id) {
        window.history.pushState({}, '', `${baseStatuUrl}/${id}`);
        loadTask(id);
    }

    // Rendu une seule fois puis placé soit dans l'en-tête de tâche (mode compact),
    // soit en pleine largeur sur l'écran d'accueil KPI.
    const searchForm = (inline) => (
        <form className={`ts-search${inline ? ' ts-search--inline' : ''}`} onSubmit={handleSearch}>
            <input
                type="number"
                className="ts-input"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder={t('search_task_trans_key')}
            />
            <button type="submit" className="ts-btn ts-btn--primary" disabled={!search.trim()}>
                <i className="fas fa-search" />
            </button>
        </form>
    );

    const detailReady = !loading && task && !error;

    return (
        <div>
            {!detailReady && (
                <div className="ts-topbar">
                    {pageTitle && <h1 className="ts-title">{pageTitle}</h1>}
                    {searchForm(true)}
                </div>
            )}

            {loading && (
                <div className="ts-spinner">
                    <i className="fas fa-spinner fa-spin fa-2x" />
                </div>
            )}

            {error && <div className="ts-alert ts-alert--warn">{error}</div>}

            {!loading && !task && !error && (
                <KpiDashboard
                    kpi={kpi}
                    userProductivity={userProductivity}
                    resourceHours={resourceHours}
                    trans={trans}
                />
            )}

            {detailReady && (
                <TaskDetail
                    task={task}
                    apiBase={apiBaseUrl}
                    andonStoreUrl={andonStoreUrl}
                    purchasesRequestUrl={purchasesRequestUrl}
                    onNavigate={handleNavigate}
                    trans={trans}
                    fileTrans={fileTrans}
                    searchSlot={searchForm(true)}
                />
            )}
        </div>
    );
}
