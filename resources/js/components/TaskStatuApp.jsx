import React, { useState, useEffect, useRef } from 'react';

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

// ---------------------------------------------------------------------------
// SmallBox
// ---------------------------------------------------------------------------
function SmallBox({ title, text, icon, theme }) {
    return (
        <div className={`small-box bg-${theme}`}>
            <div className="inner">
                <h3>{title}</h3>
                <p>{text}</p>
            </div>
            <div className="icon">
                <i className={icon} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ProgressBar
// ---------------------------------------------------------------------------
function ProgressBar({ value }) {
    const pct = Math.min(Math.max(value ?? 0, 0), 100);
    return (
        <div className="progress progress-sm">
            <div
                className="progress-bar bg-teal progress-bar-striped progress-bar-animated"
                role="progressbar"
                style={{ width: `${pct}%` }}
            >
                <span>{pct}%</span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// KpiDashboard
// ---------------------------------------------------------------------------
function KpiDashboard({ kpi, userProductivity, resourceHours, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const avgHours = kpi.averageProcessingTime
        ? (kpi.averageProcessingTime / 3600).toFixed(2)
        : '0.00';

    return (
        <>
            <div className="row">
                <div className="col-md-4 mb-4">
                    <SmallBox title={kpi.tasksInProgress} text={t('current_count_task_trans_key')} icon="fas fa-tasks" theme="primary" />
                    <SmallBox title={`${kpi.totalProducedHours} h`} text={t('total_hours_per_month_trans_key')} icon="fas fa-clock" theme="success" />
                </div>
                <div className="col-md-4 mb-4">
                    <div className="card card-purple">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-flag-checkered mr-2" />{t('goal_task_trans_key')}</h3>
                        </div>
                        <div className="card-body">
                            <p>{t('open_trans_key')} : <strong>{kpi.tasksOpen}</strong></p>
                            <p>{t('suspended_trans_key')} : <strong>{kpi.tasksPending}</strong></p>
                            <p>{t('supplied_trans_key')} : <strong>{kpi.tasksOngoing}</strong></p>
                            <p>{t('finished_trans_key')} : <strong>{kpi.tasksCompleted}</strong></p>
                        </div>
                    </div>
                </div>
                <div className="col-md-4 mb-4">
                    <SmallBox title={`${avgHours} h`} text={t('average_time_task_trans_key')} icon="fas fa-clock" theme="orange" />
                    <SmallBox title={`${parseFloat(kpi.averageTRS ?? 0).toFixed(2)} %`} text={t('trs_per_month_trans_key')} icon="fas fa-percentage" theme="info" />
                </div>
            </div>
            <div className="row mt-4">
                <div className="col-md-12">
                    <div className="card card-dark">
                        <div className="card-header"><h3 className="card-title"><i className="fas fa-users mr-2" />{t('user_productivity_trans_key')}</h3></div>
                        <div className="card-body p-0">
                            <table className="table table-striped mb-0">
                                <thead><tr><th>{t('user_trans_key')}</th><th>{t('task_count_trans_key')}</th></tr></thead>
                                <tbody>
                                    {userProductivity.filter((u) => u.tasks_count > 0).map((u, i) => (
                                        <tr key={i}><td>{u.name}</td><td>{u.tasks_count}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div className="row mt-4">
                <div className="col-md-12">
                    <div className="card card-secondary">
                        <div className="card-header"><h3 className="card-title"><i className="fas fa-clock mr-2" />{t('total_hours_per_resource_trans_key')}</h3></div>
                        <div className="card-body p-0">
                            <table className="table table-striped mb-0">
                                <thead><tr><th>{t('ressource_trans_key')}</th><th>{t('total_time_trans_key')} h</th></tr></thead>
                                <tbody>
                                    {resourceHours.map((r, i) => (
                                        <tr key={i}><td>{r.name}</td><td>{parseFloat(r.hours ?? 0).toFixed(2)}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// ActivityTimeline
// ---------------------------------------------------------------------------
function ActivityTimeline({ timeline, trans }) {
    const t = (key) => trans?.[key] ?? key;
    let prevDate = null;

    return (
        <div>
            <h4>{t('logs_activity_trans_key')}</h4>
            <div className="timeline timeline-inverse">
                {timeline.map((item, i) => {
                    const showDateLabel = item.date_label !== prevDate;
                    prevDate = item.date_label;
                    return (
                        <React.Fragment key={i}>
                            {showDateLabel && (
                                <div className="time-label">
                                    <span className="bg-info">{item.date_label}</span>
                                </div>
                            )}
                            <div>
                                <i className={item.icon_class} />
                                <div className="timeline-item">
                                    <span className="time"><i className="far fa-clock" /> {item.details}</span>
                                    <h3 className="timeline-header">{item.content}</h3>
                                </div>
                            </div>
                        </React.Fragment>
                    );
                })}
                <div><i className="far fa-clock bg-gray" /></div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TaskInfoPanel (left column)
// ---------------------------------------------------------------------------
function TaskInfoPanel({ task, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const pct = Math.min(task.progress ?? 0, 100);

    return (
        <div>
            <h4>{t('informations_trans_key')}</h4>

            {task.order_lines && (
                <div className="row mb-2">
                    <a className="btn btn-xs btn-default mr-1" href={`/orders/${task.order_lines.orders_id}`}>
                        {task.order_lines.order?.code} #{t('line_trans_key')} {task.order_lines.label}
                    </a>
                    {task.order_lines.picture && (
                        <a className="btn btn-info btn-xs" href={`/images/order-lines/${task.order_lines.picture}`} target="_blank">
                            <i className="fa fa-eye" />
                        </a>
                    )}
                </div>
            )}

            {task.order_lines?.product?.drawing_file && (
                <div className="row mb-2">
                    <a className="btn btn-info btn-sm" href={`/drawing/${task.order_lines.product.drawing_file}`} target="_blank">
                        <i className="fas fa-barcode mr-1" />{task.order_lines.product.label}
                    </a>
                </div>
            )}

            {task.service?.picture && (
                <div className="row mb-2">
                    <img alt="Service" className="profile-user-img img-fluid img-circle" src={`/storage/images/methods/${task.service.picture}`} />
                </div>
            )}

            {task.service?.type === 1 && (
                <div className="info-box mb-1">
                    <span className="info-box-icon bg-warning"><i className="fa fa-stopwatch" /></span>
                    <div className="info-box-content">
                        <span className="info-box-text">{t('total_time_trans_key')}</span>
                        <span className="info-box-number">{task.total_log_time} h</span>
                    </div>
                </div>
            )}

            <div className="info-box mb-1">
                <span className="info-box-icon bg-info"><i className="fa fa-database" /></span>
                <div className="info-box-content">
                    <span className="info-box-text">{t('finish_part_qty_trans_key')}</span>
                    <span className="info-box-number">{task.total_log_good_qt}</span>
                </div>
            </div>
            <div className="info-box mb-1">
                <span className="info-box-icon bg-danger"><i className="fa fa-arrow-down" /></span>
                <div className="info-box-content">
                    <span className="info-box-text">{t('bad_part_qty_trans_key')}</span>
                    <span className="info-box-number">{task.total_log_bad_qt}</span>
                </div>
            </div>
            <div className="info-box mb-2">
                <span className="info-box-icon bg-success"><i className="fa fa-check" /></span>
                <div className="info-box-content">
                    <span className="info-box-text">{t('net_production_qty_trans_key')}</span>
                    <span className="info-box-number">{task.total_net_good_qt}</span>
                </div>
            </div>

            <div className="text-muted">
                <div className="row">
                    <div className="col-6">
                        <p className="small">{t('statu_trans_key')}<b className="d-block">{task.status?.title}</b></p>
                    </div>
                    <div className="col-6">
                        <p className="small">{t('qty_trans_key')}<b className="d-block">{task.order_qty}</b></p>
                    </div>
                </div>
                <div className="row">
                    <div className="col-4">
                        <p className="small">{t('cost_trans_key')}<b className="d-block">{task.formatted_unit_cost}</b></p>
                    </div>
                    <div className="col-4">
                        <p className="small">{t('margin_trans_key')}<b className="d-block">{task.margin ?? '—'} %</b></p>
                    </div>
                    <div className="col-4">
                        <p className="small">{t('price_trans_key')}<b className="d-block">{task.formatted_unit_price}</b></p>
                    </div>
                </div>
                {task.service?.type === 1 && (
                    <>
                        <div className="row">
                            <div className="col-4">
                                <p className="small">{t('setting_time_trans_key')}<b className="d-block">{task.seting_time} s</b></p>
                            </div>
                            <div className="col-4">
                                <p className="small">{t('unit_time_trans_key')}<b className="d-block">{task.unit_time} s</b></p>
                            </div>
                            <div className="col-4">
                                <p className="small">{t('total_time_trans_key')}<b className="d-block">{task.total_time} h</b></p>
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-6">
                                <p className="small">{t('trs_trans_key')}<b className="d-block">{task.total_log_time}</b></p>
                            </div>
                            <div className="col-6">
                                <p className="small">{t('trs_trans_key')}<b className="d-block">{task.trs} %</b></p>
                            </div>
                        </div>
                    </>
                )}
            </div>

            <div>
                <p className="small">{t('progress_trans_key')}<b className="d-block">{task.progress} %</b></p>
                <ProgressBar value={pct} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ProductionControls
// ---------------------------------------------------------------------------
function ProductionControls({ task, apiBase, onReload, trans, purchasesRequestUrl }) {
    const t = (key) => trans?.[key] ?? key;
    const last = task.last_activity_type;

    async function action(type) {
        await apiFetch(`${apiBase}/${task.id}/${type}`, 'POST');
        onReload();
    }

    if (task.service?.type !== 1) {
        return (
            <div className="row">
                <div className="col-md-6">
                    <a className="btn btn-app bg-success" href={purchasesRequestUrl}>
                        <i className="fas fa-cash-register" />{t('new_purchase_document_trans_key')}
                    </a>
                </div>
                <div className="col-md-6">
                    <button className="btn btn-app bg-danger" onClick={() => action('finish')}>
                        <i className="fas fa-stop" /> {t('end_trans_key')}
                    </button>
                </div>
            </div>
        );
    }

    const canStart  = !last || (last !== 1 && last !== 3);
    const canPause  = !last || (last !== 2 && last !== 3);
    const canFinish = last !== 3;

    return (
        <div className="row">
            <div className="col-md-4">
                <button className={`btn btn-app bg-success${!canStart ? ' disabled' : ''}`}
                    onClick={() => canStart && action('start')}>
                    <i className="fas fa-play" /> {t('play_trans_key')}
                </button>
            </div>
            <div className="col-md-4">
                <button className={`btn btn-app bg-warning${!canPause ? ' disabled' : ''}`}
                    onClick={() => canPause && action('pause')}>
                    <i className="fas fa-pause" /> {t('pause_trans_key')}
                </button>
            </div>
            <div className="col-md-4">
                <button className={`btn btn-app bg-danger${!canFinish ? ' disabled' : ''}`}
                    onClick={() => canFinish && action('finish')}>
                    <i className="fas fa-stop" /> {t('end_trans_key')}
                </button>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// StockWithdrawalForm  (only shown when task.component_id is set)
// ---------------------------------------------------------------------------
function StockWithdrawalForm({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [qty, setQty] = useState(0);
    const [busy, setBusy] = useState(false);

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

    const r = task.reservation;

    return (
        <div className="card card-outline card-lime mb-2">
            <div className="card-body py-2">
                {r && (
                    <div className="mb-2 d-flex flex-wrap" style={{ gap: '0.25rem' }}>
                        <span className="badge badge-secondary">Demandé : {r.requested}</span>
                        <span className="badge badge-primary">Réservé : {r.reserved}</span>
                        {r.missing > 0 && (
                            <span className="badge badge-danger">Manque : {r.missing}</span>
                        )}
                    </div>
                )}
                <form onSubmit={handleSubmit}>
                    <label className="mb-1">{t('remove_from_stock_trans_key')} :</label>
                    <div className="input-group input-group-sm">
                        <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-times" /></span></div>
                        <input type="number" className="form-control" min="0" value={qty}
                            onChange={(e) => setQty(Number(e.target.value))} />
                        <span className="input-group-append">
                            <button type="submit" className="btn btn-info btn-flat" disabled={busy}>Set</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// GoodQtyForm
// ---------------------------------------------------------------------------
function GoodQtyForm({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [qty, setQty] = useState(0);
    const [busy, setBusy] = useState(false);

    async function declare(amount) {
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/good-qty`, 'POST', { qty: amount });
            setQty(0);
            onReload();
        } finally {
            setBusy(false);
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (qty <= 0) return;
        await declare(qty);
    }

    return (
        <div className="card card-outline card-warning mb-2">
            <div className="card-body py-2">
                <label className="mb-1">{t('good_rejected_trans_key')} :</label>
                <div className="row mb-2">
                    {[1, 10, 100].map((n) => (
                        <div key={n} className="col-md-4">
                            <button type="button" className="btn btn-app bg-info" disabled={busy}
                                onClick={() => declare(n)}>
                                <i className="fas fa-thumbs-up" /> +{n}
                            </button>
                        </div>
                    ))}
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="input-group input-group-sm">
                        <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-times" /></span></div>
                        <input type="number" className="form-control" min="0" value={qty}
                            onChange={(e) => setQty(Number(e.target.value))} />
                        <span className="input-group-append">
                            <button type="submit" className="btn btn-info btn-flat" disabled={busy}>Set</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// RejectedQtyForm
// ---------------------------------------------------------------------------
function RejectedQtyForm({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [qty, setQty] = useState(0);
    const [busy, setBusy] = useState(false);

    async function declare(amount) {
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/bad-qty`, 'POST', { qty: amount });
            setQty(0);
            onReload();
        } finally {
            setBusy(false);
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (qty <= 0) return;
        await declare(qty);
    }

    return (
        <div className="card card-outline card-lime mb-2">
            <div className="card-body py-2">
                <label className="mb-1">{t('quantity_rejected_trans_key')} :</label>
                <div className="row mb-2">
                    {[1, 10, 100].map((n) => (
                        <div key={n} className="col-md-4">
                            <button type="button" className="btn btn-app bg-orange" disabled={busy}
                                onClick={() => declare(n)}>
                                <i className="fas fa-thumbs-down" /> -{n}
                            </button>
                        </div>
                    ))}
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="input-group input-group-sm">
                        <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-times" /></span></div>
                        <input type="number" className="form-control" min="0" value={qty}
                            onChange={(e) => setQty(Number(e.target.value))} />
                        <span className="input-group-append">
                            <button type="submit" className="btn btn-info btn-flat" disabled={busy}>Set</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// DateUpdateForm
// ---------------------------------------------------------------------------
function DateUpdateForm({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [endDate, setEndDate] = useState(task.end_date ?? '');
    const [notRecalculate, setNotRecalculate] = useState(task.not_recalculate ?? false);
    const [busy, setBusy] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        if (!endDate) return;
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/date`, 'PUT', { end_date: endDate, not_recalculate: notRecalculate });
            onReload();
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="card card-outline card-info mb-2">
            <div className="card-body py-2">
                <div className="row align-items-center">
                    <div className="col-md-3 text-muted">
                        <p className="small mb-0">{t('end_date_trans_key')}<b className="d-block">{task.formatted_end_date !== 'NULL' ? task.formatted_end_date : '—'}</b></p>
                    </div>
                    <div className="col-md-3">
                        <div className="form-group mb-0">
                            <label className="small">Not Recalculate</label>
                            <input type="checkbox" className="ml-2" checked={notRecalculate}
                                onChange={(e) => setNotRecalculate(e.target.checked)} />
                        </div>
                    </div>
                    <div className="col-md-6">
                        <form onSubmit={handleSubmit}>
                            <div className="input-group input-group-sm">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-calendar" /></span></div>
                                <input type="datetime-local" className="form-control" value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)} />
                                <span className="input-group-append">
                                    <button type="submit" className="btn btn-info btn-flat" disabled={busy}>Set</button>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ResourceSelectForm
// ---------------------------------------------------------------------------
function ResourceSelectForm({ task, apiBase, onReload, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [resourceId, setResourceId] = useState(task.selected_resource_id ?? '');
    const [userforcedResource, setUserforcedResource] = useState(task.userforced_resource ?? false);
    const [busy, setBusy] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        if (!resourceId) return;
        setBusy(true);
        try {
            await apiFetch(`${apiBase}/${task.id}/resource`, 'PUT', { resource_id: resourceId });
            setUserforcedResource(true);
            onReload();
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="card card-outline card-info mb-2">
            <div className="card-body py-2">
                <div className="row align-items-center">
                    <div className="col-md-4">
                        <div className="form-group mb-0">
                            <label className="small">{t('user_choise_trans_key')}</label>
                            <input type="checkbox" className="ml-2" checked={userforcedResource} readOnly />
                        </div>
                    </div>
                    <div className="col-md-8">
                        <form onSubmit={handleSubmit}>
                            <div className="input-group input-group-sm">
                                <select className="form-control" value={resourceId}
                                    onChange={(e) => setResourceId(e.target.value)}>
                                    <option value="">— {t('select_ressource_trans_key')} —</option>
                                    {task.service_resources?.map((r) => (
                                        <option key={r.id} value={r.id}>{r.label}</option>
                                    ))}
                                </select>
                                <span className="input-group-append">
                                    <button type="submit" className="btn btn-info btn-flat" disabled={busy}>Set</button>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// NcAndonPanel
// ---------------------------------------------------------------------------
function NcAndonPanel({ task, apiBase, andonStoreUrl, trans }) {
    const t = (key) => trans?.[key] ?? key;
    const [showAndon, setShowAndon] = useState(false);
    const [andonType, setAndonType] = useState('');
    const [andonMessage, setAndonMessage] = useState('');

    async function handleCreateNc() {
        const result = await apiFetch(`${apiBase}/${task.id}/nc`, 'POST');
        if (result.redirect_url) window.location.href = result.redirect_url;
    }

    return (
        <div className="card card-outline card-danger mb-2">
            <div className="card-body py-2">
                <div className="mb-2">
                    <button type="button" className="btn btn-link text-warning p-0" onClick={handleCreateNc}>
                        <i className="fa fa-exclamation mr-1" />{t('new_non_conformitie_trans_key')}
                    </button>
                </div>
                <div>
                    <button type="button" className="btn btn-link text-danger p-0"
                        onClick={() => setShowAndon(!showAndon)}>
                        <i className="fa fa-exclamation mr-1" /> Ajouter une alerte Andon
                    </button>
                </div>

                {showAndon && (
                    <div className="mt-2 border border-danger rounded p-2">
                        <form action={andonStoreUrl} method="POST">
                            <input type="hidden" name="_token" value={csrfToken()} />
                            <input type="hidden" name="task_id" value={task.id} />
                            <input type="hidden" name="resource_id" value={task.selected_resource_id ?? 1} />
                            <div className="form-group mb-1">
                                <label className="small">{"Type d'alerte"}</label>
                                <input type="text" className="form-control form-control-sm" name="type"
                                    value={andonType} onChange={(e) => setAndonType(e.target.value)} required />
                            </div>
                            <div className="form-group mb-1">
                                <label className="small">Description</label>
                                <textarea className="form-control form-control-sm" name="message" rows={2}
                                    value={andonMessage} onChange={(e) => setAndonMessage(e.target.value)} required />
                            </div>
                            <button type="submit" className="btn btn-danger btn-sm">{"Ajouter l'alerte"}</button>
                        </form>
                    </div>
                )}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TaskDetail  (3-column layout)
// ---------------------------------------------------------------------------
function TaskDetail({ task, apiBase, andonStoreUrl, purchasesRequestUrl, onNavigate, trans }) {
    const t = (key) => trans?.[key] ?? key;

    if (!task.order_lines_id) {
        return (
            <div className="alert alert-info mx-3">
                {t('quote_task_trans_key')}
            </div>
        );
    }

    return (
        <>
            {/* Navigation prev / next */}
            <div className="row mx-1 mb-2">
                <div className="col-6">
                    {task.previous_task && (
                        <button className="btn btn-primary btn-lg btn-block"
                            onClick={() => onNavigate(task.previous_task.id)}>
                            <i className="fas fa-arrow-left mr-1" />
                            {task.previous_task.ordre} - {task.previous_task.label}
                        </button>
                    )}
                </div>
                <div className="col-6">
                    {task.next_task && (
                        <button className="btn btn-primary btn-lg btn-block"
                            onClick={() => onNavigate(task.next_task.id)}>
                            {task.next_task.ordre} - {task.next_task.label}
                            <i className="fas fa-arrow-right ml-1" />
                        </button>
                    )}
                </div>
            </div>

            {/* Main card */}
            <div className="card card-teal mx-1">
                <div className="card-header">
                    <h3 className="card-title">#{task.id} {t('task_detail_trans_key')} {task.label}</h3>
                </div>
                <div className="card-body">
                    <div className="row">
                        {/* Left — Info */}
                        <div className="col-md-2">
                            <TaskInfoPanel task={task} trans={trans} />
                        </div>

                        {/* Center — Timeline */}
                        <div className="col-md-6">
                            <ActivityTimeline timeline={task.timeline ?? []} trans={trans} />
                        </div>

                        {/* Right — Actions */}
                        <div className="col-md-4">
                            <h3 className="text-primary">
                                {t('task_trans_key')} #{task.id} {task.service?.label}
                            </h3>

                            {task.component_id && task.component?.drawing_file && (
                                <h5 className="text-secondary">
                                    {t('component_trans_key')} : {task.component.code}
                                    <a className="btn btn-xs btn-info ml-1" href={`/products/${task.component_id}`} target="_blank">
                                        <i className="fa fa-eye" />
                                    </a>
                                    <a className="btn btn-xs btn-info ml-1" href={`/drawing/${task.component.drawing_file}`} target="_blank">
                                        <i className="fa fa-file" />
                                    </a>
                                </h5>
                            )}

                            <div className="card mb-2">
                                <div className="card-body py-2">
                                    <ProductionControls
                                        task={task}
                                        apiBase={apiBase}
                                        onReload={() => onNavigate(task.id)}
                                        trans={trans}
                                        purchasesRequestUrl={purchasesRequestUrl}
                                    />
                                </div>
                            </div>

                            {task.component_id && (
                                <StockWithdrawalForm task={task} apiBase={apiBase}
                                    onReload={() => onNavigate(task.id)} trans={trans} />
                            )}

                            <GoodQtyForm task={task} apiBase={apiBase}
                                onReload={() => onNavigate(task.id)} trans={trans} />

                            <RejectedQtyForm task={task} apiBase={apiBase}
                                onReload={() => onNavigate(task.id)} trans={trans} />

                            <DateUpdateForm task={task} apiBase={apiBase}
                                onReload={() => onNavigate(task.id)} trans={trans} />

                            <ResourceSelectForm task={task} apiBase={apiBase}
                                onReload={() => onNavigate(task.id)} trans={trans} />

                            <NcAndonPanel task={task} apiBase={apiBase}
                                andonStoreUrl={andonStoreUrl} trans={trans} />
                        </div>
                    </div>
                </div>
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
    baseStatuUrl,
    apiBaseUrl,
    andonStoreUrl,
    purchasesRequestUrl,
    trans,
}) {
    const [search, setSearch]   = useState(initialTaskId ? String(initialTaskId) : '');
    const [task, setTask]       = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError]     = useState(null);

    const t = (key) => trans?.[key] ?? key;

    function loadTask(id) {
        if (!id) return;
        setLoading(true);
        setError(null);
        apiFetch(`${apiBaseUrl}/${id}`)
            .then((data) => { setTask(data); setSearch(String(id)); })
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
        // Update URL without reload for clean navigation
        window.history.pushState({}, '', `${baseStatuUrl}/${id}`);
        loadTask(id);
    }

    function handleNavigate(id) {
        window.history.pushState({}, '', `${baseStatuUrl}/${id}`);
        loadTask(id);
    }

    return (
        <div>
            {/* Search bar */}
            <div className="card-body">
                <form onSubmit={handleSearch}>
                    <div className="input-group mb-3">
                        <div className="input-group-prepend">
                            <span className="input-group-text"><i className="fas fa-search fa-fw" /></span>
                        </div>
                        <input
                            type="number"
                            className="form-control"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={t('search_task_trans_key')}
                        />
                        {search.trim() && (
                            <div className="input-group-append">
                                <button type="submit" className="btn btn-primary">
                                    <i className="fas fa-arrow-right" />
                                </button>
                            </div>
                        )}
                    </div>
                </form>
            </div>

            {loading && (
                <div className="text-center py-4">
                    <i className="fas fa-spinner fa-spin fa-2x text-primary" />
                </div>
            )}

            {error && (
                <div className="alert alert-warning mx-3">{error}</div>
            )}

            {!loading && !task && !error && (
                <KpiDashboard
                    kpi={kpi}
                    userProductivity={userProductivity}
                    resourceHours={resourceHours}
                    trans={trans}
                />
            )}

            {!loading && task && (
                <TaskDetail
                    task={task}
                    apiBase={apiBaseUrl}
                    andonStoreUrl={andonStoreUrl}
                    purchasesRequestUrl={purchasesRequestUrl}
                    onNavigate={handleNavigate}
                    trans={trans}
                />
            )}
        </div>
    );
}
