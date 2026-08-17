import React, { useState, useCallback, useEffect, useMemo, useRef } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/** Replace :placeholders in a translated string. */
function fmt(template, params = {}) {
    return Object.entries(params).reduce(
        (acc, [key, value]) => acc.split(`:${key}`).join(value),
        String(template ?? '')
    );
}

function currentLocale() {
    if (typeof document !== 'undefined' && document.documentElement.lang) {
        return document.documentElement.lang;
    }
    if (typeof navigator !== 'undefined' && navigator.language) {
        return navigator.language;
    }
    return 'en';
}

/** Local (not UTC) Y-m-d key, so "today" matches the server dates. */
function toDateKey(date) {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

/**
 * Turn the flat Y-m-d list into decorated day columns:
 * weekday label, short date, today / weekend / bank holiday flags.
 */
function buildDays(possibleDates, bankHolidays, locale) {
    const todayKey = toDateKey(new Date());

    return (possibleDates ?? []).map((date) => {
        const parsed = new Date(`${date}T00:00:00`);
        const weekDay = parsed.getDay();
        const holidayLabel = bankHolidays?.[date] ?? bankHolidays?.[date.slice(5)] ?? null;
        const isWeekend = weekDay === 0 || weekDay === 6;

        return {
            date,
            weekday: parsed
                .toLocaleDateString(locale, { weekday: 'short' })
                .replace(/\.$/, '')
                .toLocaleUpperCase(locale),
            label: parsed.toLocaleDateString(locale, { day: '2-digit', month: '2-digit' }),
            fullLabel: parsed.toLocaleDateString(locale, {
                weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric',
            }),
            isToday: date === todayKey,
            isWeekend,
            isHoliday: Boolean(holidayLabel),
            holidayLabel,
            isOff: isWeekend || Boolean(holidayLabel),
            isWeekStart: weekDay === 1,
        };
    });
}

/**
 * Return the effective daily capacity for a service.
 * Priority: configured resources > user-defined custom > fallback 8h.
 */
function effectiveCapacity(service, customCapacities) {
    if (service.capacity > 0) return service.capacity;
    const custom = parseFloat(customCapacities[service.id]);
    return custom > 0 ? custom : 8;
}

/**
 * Compute the load tone + label of a single cell.
 * Uses raw hours worked + effective capacity — no hardcoded value.
 * Thresholds are unchanged from the previous table version.
 */
function computeCell(hoursWorked, capacity, displayHoursDiff) {
    if (hoursWorked === null || hoursWorked === undefined) {
        return { tone: 'none', label: null, pct: null, hours: null };
    }

    const pct = capacity > 0 ? (hoursWorked / capacity) * 100 : 0;
    const diff = Math.round((capacity - hoursWorked) * 100) / 100;

    let tone;
    if (displayHoursDiff) {
        if (diff <= 0)      tone = 'over';
        else if (diff <= 2) tone = 'high';
        else if (diff <= 4) tone = 'medium';
        else                tone = 'low';
    } else {
        if (pct >= 100)     tone = 'over';
        else if (pct >= 80) tone = 'high';
        else if (pct >= 50) tone = 'medium';
        else if (pct >= 20) tone = 'low';
        else                tone = 'free';
    }

    const label = displayHoursDiff
        ? (diff <= 0 ? `+${Math.abs(diff)} h` : `−${diff} h`)
        : `${Math.round(pct)} %`;

    return { tone, label, pct, diff, hours: hoursWorked };
}

const TONE_ORDER = ['free', 'low', 'medium', 'high', 'over'];

function toneRank(tone) {
    const index = TONE_ORDER.indexOf(tone);
    return index === -1 ? -1 : index;
}

// ---------------------------------------------------------------------------
// Polling hook
// ---------------------------------------------------------------------------

function useInterval(callback, delay) {
    const savedCallback = useRef(callback);
    useEffect(() => { savedCallback.current = callback; }, [callback]);
    useEffect(() => {
        if (delay === null) return;
        const id = setInterval(() => savedCallback.current(), delay);
        return () => clearInterval(id);
    }, [delay]);
}

// localStorage keys for persisted preferences
const STORAGE_KEY = 'lp_custom_capacities';
const VIEW_KEY    = 'lp_compact_view';

function loadStoredCapacities() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}'); }
    catch { return {}; }
}

function loadStoredCompact() {
    try { return localStorage.getItem(VIEW_KEY) === '1'; }
    catch { return false; }
}

// ---------------------------------------------------------------------------
// TaskCalculationPanel (replaces Livewire)
// ---------------------------------------------------------------------------

const POLL_MS  = 3000;
const EMPTY_JOB = { jobStatus: null, progress: 0, count: 0, messages: [] };

function ProgressBar({ value }) {
    return (
        <div className="progress mb-2" style={{ height: 22 }}>
            <div
                className="progress-bar progress-bar-striped progress-bar-animated bg-success"
                role="progressbar"
                style={{ width: `${value}%` }}
                aria-valuenow={value}
                aria-valuemin="0"
                aria-valuemax="100"
            >
                {value}%
            </div>
        </div>
    );
}

function CalcModal({ id, title, status, trans, onCalculate, onRebalance }) {
    const { jobStatus, progress, count, messages } = status;
    const isRunning = jobStatus === 'running';
    const isDone    = jobStatus === 'finished';
    const isIdle    = !isRunning && !isDone;

    return (
        <div className="modal fade" id={id} tabIndex="-1" role="dialog" aria-hidden="true">
            <div className="modal-dialog modal-lg" role="document">
                <div className="modal-content">
                    <div className="modal-header bg-teal">
                        <h5 className="modal-title text-white">
                            <i className="fas fa-cogs mr-2"></i>{title}
                        </h5>
                        <button type="button" className="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div className="modal-body">
                        {isIdle && (
                            <>
                                <button className="btn btn-success btn-block" onClick={onCalculate}>
                                    <i className="fas fa-play mr-1"></i>
                                    {trans.calculate ?? 'Calculer'}
                                </button>
                                {onRebalance && (
                                    <>
                                        <button className="btn btn-outline-secondary btn-block" onClick={onRebalance}>
                                            <i className="fas fa-random mr-1"></i>
                                            {trans.rebalance ?? 'Rééquilibrer les affectations automatiques'}
                                        </button>
                                        <small className="text-muted d-block">
                                            {trans.rebalance_hint ?? 'Les ressources choisies manuellement et les tâches déjà démarrées ne sont pas déplacées.'}
                                        </small>
                                    </>
                                )}
                            </>
                        )}
                        {(isRunning || isDone) && (
                            <>
                                <ProgressBar value={progress} />
                                <p className="text-muted mb-3">
                                    {fmt(trans.tasks_processed ?? ':count tâche(s) traitée(s)', { count })}
                                    {isRunning && (
                                        <span className="ml-2 badge badge-warning">
                                            <i className="fas fa-spinner fa-spin mr-1"></i>
                                            {trans.running ?? 'En cours…'}
                                        </span>
                                    )}
                                    {isDone && (
                                        <span className="ml-2 badge badge-success">
                                            <i className="fas fa-check mr-1"></i>
                                            {trans.finished ?? 'Terminé'}
                                        </span>
                                    )}
                                </p>
                                {isDone && (
                                    <button className="btn btn-primary btn-block mb-3" onClick={() => window.location.reload()}>
                                        <i className="fas fa-sync mr-1"></i>
                                        {trans.refresh ?? 'Actualiser'}
                                    </button>
                                )}
                                {messages.length > 0 && (
                                    <div className="bg-light rounded p-2" style={{ maxHeight: 220, overflowY: 'auto', fontSize: '0.82rem' }}>
                                        <ul className="list-unstyled mb-0">
                                            {messages.map((msg, i) => (
                                                <li key={i} className="text-muted"><i className="fas fa-angle-right mr-1"></i>{msg}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function TaskCalculationPanel({ endpoints, initialCounts, trans }) {
    const [counts,         setCounts]         = useState(initialCounts);
    const [dateStatus,     setDateStatus]     = useState(EMPTY_JOB);
    const [resourceStatus, setResourceStatus] = useState(EMPTY_JOB);

    const isPolling = dateStatus.jobStatus === 'running' || resourceStatus.jobStatus === 'running';

    const fetchStatus = useCallback(async () => {
        try {
            const res = await fetch(endpoints.calculationStatus, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (!res.ok) return;
            const data = await res.json();
            setDateStatus(data.dates);
            setResourceStatus(data.resources);
            setCounts({
                date:     data.dates.countTaskNullDate          ?? 0,
                resource: data.resources.countTaskNullRessource ?? 0,
            });
        } catch (_) {}
    }, [endpoints.calculationStatus]);

    useInterval(fetchStatus, isPolling ? POLL_MS : null);

    const triggerJob = async (endpoint, setStatus, body = null) => {
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: body ? JSON.stringify(body) : undefined,
            });
            if (!res.ok) return;
            setStatus({ jobStatus: 'running', progress: 0, count: 0, messages: [] });
            setTimeout(fetchStatus, 800);
        } catch (_) {}
    };

    return (
        <>
            <div className="d-flex justify-content-end mb-3" style={{ gap: '0.5rem' }}>
                <button
                    type="button"
                    className={`btn btn-sm ${counts.resource > 0 ? 'btn-warning' : 'btn-outline-secondary'}`}
                    data-toggle="modal"
                    data-target="#taskCalculationRessource"
                >
                    <i className="fas fa-user-cog mr-1"></i>
                    {trans.null_resource ?? 'Ressources manquantes'} ({counts.resource})
                </button>
                <button
                    type="button"
                    className={`btn btn-sm ${counts.date > 0 ? 'btn-warning' : 'btn-outline-secondary'}`}
                    data-toggle="modal"
                    data-target="#taskCalculationDate"
                >
                    <i className="fas fa-calendar-times mr-1"></i>
                    {trans.null_date ?? 'Dates manquantes'} ({counts.date})
                </button>
            </div>

            <CalcModal
                id="taskCalculationRessource"
                title={trans.calc_resource_title ?? 'Calculer les ressources'}
                status={resourceStatus}
                trans={trans}
                onCalculate={() => triggerJob(endpoints.calculateResources, setResourceStatus)}
                onRebalance={() => triggerJob(endpoints.calculateResources, setResourceStatus, { rebalance: true })}
            />
            <CalcModal
                id="taskCalculationDate"
                title={trans.calc_date_title ?? 'Calculer les dates'}
                status={dateStatus}
                trans={trans}
                onCalculate={() => triggerJob(endpoints.calculateDates, setDateStatus)}
            />
        </>
    );
}

// ---------------------------------------------------------------------------
// Filter form
// ---------------------------------------------------------------------------

function FilterForm({ startDate, endDate, displayHoursDiff, loading, trans, onStartDate, onEndDate, onDisplayHoursDiff, onSubmit }) {
    return (
        <div className="card card-outline card-lime">
            <div className="card-body">
                <form onSubmit={onSubmit}>
                    <div className="row">
                        <div className="form-group col-2">
                            <label htmlFor="lp-start-date">{trans.start_date}</label>
                            <input type="date" id="lp-start-date" className="form-control" required
                                value={startDate} onChange={e => onStartDate(e.target.value)} />
                        </div>
                        <div className="form-group col-2">
                            <label htmlFor="lp-end-date">{trans.end_date}</label>
                            <input type="date" id="lp-end-date" className="form-control" required
                                value={endDate} onChange={e => onEndDate(e.target.value)} />
                        </div>
                        <div className="form-group col-2">
                            <label htmlFor="lp-hours-diff">{trans.display_hours_diff}</label>
                            <div className="custom-control custom-switch mt-2">
                                <input type="checkbox" className="custom-control-input" id="lp-hours-diff"
                                    checked={displayHoursDiff} onChange={e => onDisplayHoursDiff(e.target.checked)} />
                                <label className="custom-control-label" htmlFor="lp-hours-diff">
                                    {displayHoursDiff ? trans.yes : trans.no}
                                </label>
                            </div>
                        </div>
                        <div className="form-group col-2 d-flex align-items-end">
                            <button type="submit" className="btn btn-danger btn-flat" disabled={loading}>
                                <i className="fas fa-save mr-1"></i>
                                {loading ? '...' : trans.submit}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Capacity column badge
// ---------------------------------------------------------------------------

function CapacityBadge({ service, customCapacities, trans }) {
    const cap = effectiveCapacity(service, customCapacities);

    if (service.capacity > 0) {
        // Real capacity from configured resources
        return (
            <span className="badge badge-primary" title={trans.capacity_from_resources ?? 'Capacité issue des ressources configurées'}>
                {cap}h/j
            </span>
        );
    }

    if (customCapacities[service.id] > 0) {
        // User-defined custom capacity
        return (
            <span className="badge badge-warning" title={trans.capacity_custom ?? 'Capacité définie manuellement (aucune ressource configurée)'}>
                {cap}h/j
            </span>
        );
    }

    // No capacity at all — fallback used, shown as warning
    return (
        <span className="badge badge-secondary" title={trans.capacity_fallback ?? 'Aucune ressource configurée — fallback 8h/j'}>
            8h/j*
        </span>
    );
}

// ---------------------------------------------------------------------------
// Custom capacity footer (services without resources)
// ---------------------------------------------------------------------------

function CustomCapacityFooter({ services, customCapacities, onCustomCapacityChange, trans }) {
    const unconfigured = services.filter(s => s.capacity === 0);

    if (unconfigured.length === 0) return null;

    return (
        <div className="card-footer bg-light">
            <p className="mb-2 font-weight-bold text-secondary">
                <i className="fas fa-sliders-h mr-1"></i>
                {trans.default_capacity ?? 'Capacité journalière par défaut'}
                <small className="ml-2 text-muted font-weight-normal">
                    ({trans.default_capacity_hint ?? 'services sans ressources configurées — valeurs utilisées pour le calcul du taux de charge'})
                </small>
            </p>
            <div className="row">
                {unconfigured.map(service => (
                    <div key={service.id} className="col-auto mb-2">
                        <div className="input-group input-group-sm">
                            <div className="input-group-prepend">
                                <span className="input-group-text bg-white">
                                    {service.picture && (
                                        <img
                                            src={`/storage/images/methods/${service.picture}`}
                                            alt={service.label}
                                            width="16" height="16"
                                            className="rounded-circle mr-1"
                                        />
                                    )}
                                    {service.label}
                                </span>
                            </div>
                            <input
                                type="number"
                                className="form-control"
                                style={{ width: 70 }}
                                min="1"
                                max="24"
                                step="0.5"
                                value={customCapacities[service.id] ?? ''}
                                placeholder="8"
                                onChange={e => onCustomCapacityChange(service.id, e.target.value)}
                            />
                            <div className="input-group-append">
                                <span className="input-group-text">h/j</span>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Legend
// ---------------------------------------------------------------------------

function Legend({ displayHoursDiff, trans }) {
    const entries = displayHoursDiff
        ? [
            { tone: 'low',    label: trans.level_low    ?? 'Faible',    hint: '> 4 h' },
            { tone: 'medium', label: trans.level_medium ?? 'Équilibré', hint: '≤ 4 h' },
            { tone: 'high',   label: trans.level_high   ?? 'Tendu',     hint: '≤ 2 h' },
            { tone: 'over',   label: trans.level_over   ?? 'Surcharge', hint: '≤ 0 h' },
        ]
        : [
            { tone: 'free',   label: trans.level_free   ?? 'Libre',     hint: '< 20 %' },
            { tone: 'low',    label: trans.level_low    ?? 'Faible',    hint: '20 – 50 %' },
            { tone: 'medium', label: trans.level_medium ?? 'Équilibré', hint: '50 – 80 %' },
            { tone: 'high',   label: trans.level_high   ?? 'Tendu',     hint: '80 – 100 %' },
            { tone: 'over',   label: trans.level_over   ?? 'Surcharge', hint: '≥ 100 %' },
        ];

    return (
        <div className="lp-legend">
            <span className="lp-legend__title">{trans.legend ?? 'Niveau de charge'}</span>
            {entries.map(entry => (
                <span key={entry.tone} className={`lp-legend__item lp-tone-${entry.tone}`}>
                    <span className="lp-legend__dot"></span>
                    {entry.label}
                    <small className="lp-legend__hint">{entry.hint}</small>
                </span>
            ))}
            <span className="lp-legend__item lp-tone-weekend">
                <span className="lp-legend__dot"></span>
                {trans.weekend ?? 'Week-end'}
            </span>
            <span className="lp-legend__item lp-tone-off">
                <span className="lp-legend__dot"></span>
                {trans.day_off ?? 'Jour férié'}
            </span>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Timeline (services × days)
// ---------------------------------------------------------------------------

function DayHeader({ day, trans }) {
    const classes = ['lp-day'];
    if (day.isHoliday)      classes.push('lp-tone-off');
    else if (day.isWeekend) classes.push('lp-tone-weekend');
    if (day.isToday)        classes.push('lp-col-today');
    if (day.isWeekStart)    classes.push('lp-week-start');

    return (
        <th
            scope="col"
            className={classes.join(' ')}
            title={day.holidayLabel ? `${day.fullLabel} — ${day.holidayLabel}` : day.fullLabel}
        >
            <span className="lp-day__weekday">{day.weekday}</span>
            <span className="lp-day__date">{day.label}</span>
            {day.isToday && (
                <span className="lp-day__today">{trans.today ?? "Aujourd'hui"}</span>
            )}
        </th>
    );
}

function ServiceCell({ service, stats, compact, customCapacities, trans }) {
    if (compact) {
        return (
            <th scope="row" className="lp-col-service">
                <div className="lp-service lp-service--compact">
                    <span className={`lp-service__dot lp-tone-${stats.peakTone}`}></span>
                    <span className="lp-service__name">{service.label}</span>
                    <span className="lp-service__cap text-muted">{stats.capacity}h/j</span>
                </div>
            </th>
        );
    }

    return (
        <th scope="row" className="lp-col-service">
            <div className="lp-service">
                {service.picture ? (
                    <img
                        alt={service.label}
                        className="lp-service__avatar"
                        src={`/storage/images/methods/${service.picture}`}
                        width="38" height="38"
                    />
                ) : (
                    <span className="lp-service__avatar lp-service__avatar--empty">
                        <i className="fas fa-industry"></i>
                    </span>
                )}
                <div className="lp-service__body">
                    <span className="lp-service__name">{service.label}</span>
                    <div className="lp-service__badges">
                        <CapacityBadge service={service} customCapacities={customCapacities} trans={trans} />
                        {stats.totalHours > 0 && (
                            <span className="badge badge-light border">
                                {fmt(trans.period_total ?? 'Total :hours h', {
                                    hours: Math.round(stats.totalHours * 10) / 10,
                                })}
                            </span>
                        )}
                        {stats.taskCount > 0 && (
                            <span className="badge badge-light border">
                                {fmt(trans.tasks_count ?? ':count tâche(s)', { count: stats.taskCount })}
                            </span>
                        )}
                        {stats.overloadedDays > 0 && (
                            <span className="badge badge-danger">
                                {fmt(trans.overloaded_days ?? ':count jour(s) en surcharge', {
                                    count: stats.overloadedDays,
                                })}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </th>
    );
}

function LoadCell({ day, cell, capacity, taskCount, compact, trans }) {
    const classes = ['lp-cell'];
    if (day.isHoliday)      classes.push('lp-tone-off');
    else if (day.isWeekend) classes.push('lp-tone-weekend');
    else                    classes.push(`lp-tone-${cell.tone}`);
    if (day.isToday)        classes.push('lp-col-today');
    if (day.isWeekStart)    classes.push('lp-week-start');

    const hasLoad = cell.hours !== null;

    // A day off with booked hours must still be readable: keep the "off" tone
    // for the column but show the load, flagged with a warning icon.
    const offWithLoad = day.isOff && hasLoad;

    const tooltipParts = [day.fullLabel];
    if (day.holidayLabel) tooltipParts.push(day.holidayLabel);
    if (hasLoad) {
        tooltipParts.push(`${Math.round(cell.hours * 10) / 10} h / ${capacity} h — ${Math.round(cell.pct)} %`);
        if (taskCount > 0) {
            tooltipParts.push(fmt(trans.tasks_count ?? ':count tâche(s)', { count: taskCount }));
        }
    }

    return (
        <td className={classes.join(' ')} title={tooltipParts.join(' — ')}>
            {hasLoad ? (
                <div className="lp-cell__inner">
                    <span className="lp-pill">
                        {offWithLoad && <i className="fas fa-exclamation-triangle lp-pill__warn"></i>}
                        {cell.label}
                    </span>
                    {!compact && (
                        <span className="lp-gauge">
                            <span
                                className="lp-gauge__fill"
                                style={{ width: `${Math.min(Math.max(cell.pct, 0), 100)}%` }}
                            ></span>
                        </span>
                    )}
                </div>
            ) : (
                <span className="lp-cell__empty">
                    {day.isHoliday ? 'OFF' : day.isWeekend ? '·' : '—'}
                </span>
            )}
        </td>
    );
}

function LoadTimeline({
    data, days, displayHoursDiff, compact, customCapacities,
    onToggleCompact, onCustomCapacityChange, trans,
}) {
    const { services, hoursPerServiceDay, tasksPerServiceDay } = data;

    // Per-service aggregates over the displayed period (badges + row highlight).
    const rows = useMemo(() => services.map((service) => {
        const capacity = effectiveCapacity(service, customCapacities);
        const svcId    = String(service.id);
        const hoursMap = hoursPerServiceDay?.[svcId] ?? {};
        const tasksMap = tasksPerServiceDay?.[svcId] ?? {};

        let totalHours = 0;
        let taskCount = 0;
        let overloadedDays = 0;
        let workingDays = 0;
        let peakTone = 'free';

        const cells = days.map((day) => {
            const hours = hoursMap[day.date] ?? null;
            const tasks = tasksMap[day.date] ?? [];
            const cell  = computeCell(hours, capacity, displayHoursDiff);

            if (!day.isOff) workingDays += 1;
            if (hours !== null) {
                totalHours += hours;
                taskCount  += tasks.length;
                if (!day.isOff) {
                    if (cell.pct >= 100) overloadedDays += 1;
                    if (toneRank(cell.tone) > toneRank(peakTone)) peakTone = cell.tone;
                }
            }

            return { day, cell, taskCount: tasks.length };
        });

        return {
            service,
            capacity,
            cells,
            totalHours,
            taskCount,
            overloadedDays,
            peakTone,
            avgPct: workingDays > 0 && capacity > 0
                ? (totalHours / (workingDays * capacity)) * 100
                : 0,
        };
    }), [services, days, hoursPerServiceDay, tasksPerServiceDay, customCapacities, displayHoursDiff]);

    return (
        <div className="card card-outline card-lime">
            <div className="card-header d-flex align-items-center flex-wrap">
                <h3 className="card-title mb-0">
                    <i className="fas fa-chart-bar mr-1"></i>
                    {trans.service}
                </h3>
                <button
                    type="button"
                    className="btn btn-sm btn-outline-secondary ml-auto"
                    onClick={onToggleCompact}
                >
                    <i className={`fas ${compact ? 'fa-expand-alt' : 'fa-compress-alt'} mr-1`}></i>
                    {compact
                        ? (trans.detailed_view ?? 'Vue détaillée')
                        : (trans.compact_view ?? 'Vue compacte')}
                </button>
            </div>

            <div className="card-body p-0">
                <div className={`lp-timeline ${compact ? 'lp-timeline--compact' : ''}`}>
                    <table className="lp-table">
                        <thead>
                            <tr>
                                <th scope="col" className="lp-col-service lp-col-service--head">
                                    {trans.service}
                                </th>
                                {days.map(day => (
                                    <DayHeader key={day.date} day={day} trans={trans} />
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map(row => (
                                <tr
                                    key={row.service.id}
                                    className={row.overloadedDays > 0 ? 'lp-row lp-row--overloaded' : 'lp-row'}
                                >
                                    <ServiceCell
                                        service={row.service}
                                        stats={row}
                                        compact={compact}
                                        customCapacities={customCapacities}
                                        trans={trans}
                                    />
                                    {row.cells.map(({ day, cell, taskCount }) => (
                                        <LoadCell
                                            key={day.date}
                                            day={day}
                                            cell={cell}
                                            capacity={row.capacity}
                                            taskCount={taskCount}
                                            compact={compact}
                                            trans={trans}
                                        />
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card-body py-2">
                <Legend displayHoursDiff={displayHoursDiff} trans={trans} />
            </div>

            <CustomCapacityFooter
                services={services}
                customCapacities={customCapacities}
                onCustomCapacityChange={onCustomCapacityChange}
                trans={trans}
            />

            <div className="card-footer">
                <button type="button" className="btn btn-secondary" onClick={() => history.back()}>
                    <i className="fas fa-arrow-left mr-1"></i>
                    {trans.back}
                </button>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function LoadPlanningIndex({ initial, startDate: initStart, endDate: initEnd, displayHoursDiff: initHoursDiff, endpoints, trans }) {
    const [startDate,        setStartDate]        = useState(initStart     ?? '');
    const [endDate,          setEndDate]          = useState(initEnd       ?? '');
    const [displayHoursDiff, setDisplayHoursDiff] = useState(initHoursDiff ?? false);
    const [data,             setData]             = useState(initial       ?? null);
    const [loading,          setLoading]          = useState(false);
    const [error,            setError]            = useState(null);
    const [compact,          setCompact]          = useState(loadStoredCompact);

    // Custom capacities persisted in localStorage (keyed by service id)
    const [customCapacities, setCustomCapacities] = useState(loadStoredCapacities);

    const handleCustomCapacityChange = useCallback((serviceId, value) => {
        setCustomCapacities(prev => {
            const next = { ...prev, [serviceId]: value };
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(next)); } catch (_) {}
            return next;
        });
    }, []);

    const handleToggleCompact = useCallback(() => {
        setCompact(prev => {
            const next = !prev;
            try { localStorage.setItem(VIEW_KEY, next ? '1' : '0'); } catch (_) {}
            return next;
        });
    }, []);

    const fetchData = useCallback(async (sd, ed) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ start_date: sd, end_date: ed });
            const res = await fetch(`${endpoints.data}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                throw new Error(body.error ?? `HTTP ${res.status}`);
            }
            setData(await res.json());
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    }, [endpoints.data]);

    const handleSubmit = (e) => {
        e.preventDefault();
        fetchData(startDate, endDate);
    };

    const days = useMemo(
        () => buildDays(data?.possibleDates, data?.bankHolidays ?? {}, currentLocale()),
        [data?.possibleDates, data?.bankHolidays]
    );

    const initialCounts = {
        date:     initial?.countTaskNullDate      ?? 0,
        resource: initial?.countTaskNullRessource ?? 0,
    };

    return (
        <div>
            {endpoints.calculationStatus && (
                <TaskCalculationPanel
                    endpoints={endpoints}
                    initialCounts={initialCounts}
                    trans={trans}
                />
            )}

            <FilterForm
                startDate={startDate}
                endDate={endDate}
                displayHoursDiff={displayHoursDiff}
                loading={loading}
                trans={trans}
                onStartDate={setStartDate}
                onEndDate={setEndDate}
                onDisplayHoursDiff={setDisplayHoursDiff}
                onSubmit={handleSubmit}
            />

            {error && <div className="alert alert-danger">{error}</div>}

            {data && (
                <LoadTimeline
                    data={data}
                    days={days}
                    displayHoursDiff={displayHoursDiff}
                    compact={compact}
                    customCapacities={customCapacities}
                    onToggleCompact={handleToggleCompact}
                    onCustomCapacityChange={handleCustomCapacityChange}
                    trans={trans}
                />
            )}
        </div>
    );
}
