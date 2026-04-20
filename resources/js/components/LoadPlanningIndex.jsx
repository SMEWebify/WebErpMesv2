import React, { useState, useCallback, useEffect, useRef } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function isWeekend(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    const day = d.getDay();
    return day === 0 || day === 6;
}

function isBankHoliday(dateStr, bankHolidays) {
    const mmdd = dateStr.slice(5);
    return Object.prototype.hasOwnProperty.call(bankHolidays, mmdd) ||
           Object.prototype.hasOwnProperty.call(bankHolidays, dateStr);
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
 * Compute load rate and cell styling.
 * Uses raw hours worked + effective capacity — no hardcoded value.
 */
function getCellInfo(hoursWorked, displayHoursDiff, holiday, weekend, capacity) {
    if (holiday) return { bgClass: 'bg-dark text-white', label: 'OFF' };
    if (weekend)  return { bgClass: 'bg-info-subtle text-dark', label: 'Weekend' };

    if (hoursWorked === null || hoursWorked === undefined) {
        return { bgClass: 'bg-light text-muted', label: '—' };
    }

    const loadPct   = (hoursWorked / capacity) * 100;
    const hoursDiff = Math.round((capacity - hoursWorked) * 100) / 100;

    if (displayHoursDiff) {
        if (hoursDiff <= 0)  return { bgClass: 'bg-danger text-white',      label: `+${Math.abs(hoursDiff)} h` };
        if (hoursDiff <= 2)  return { bgClass: 'bg-warning text-dark',      label: `−${hoursDiff} h` };
        if (hoursDiff <= 4)  return { bgClass: 'bg-orange text-white',      label: `−${hoursDiff} h` };
        return                      { bgClass: 'bg-success text-white',     label: `−${hoursDiff} h` };
    }

    const pct = Math.round(loadPct);
    if (loadPct >= 100) return { bgClass: 'bg-danger text-white',   label: `${pct}%` };
    if (loadPct >= 80)  return { bgClass: 'bg-orange text-white',   label: `${pct}%` };
    if (loadPct >= 50)  return { bgClass: 'bg-warning text-dark',   label: `${pct}%` };
    if (loadPct >= 20)  return { bgClass: 'bg-success text-white',  label: `${pct}%` };
    return                    { bgClass: 'bg-light text-muted',     label: `${pct}%` };
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

// localStorage key for persisted custom capacities
const STORAGE_KEY = 'lp_custom_capacities';

function loadStoredCapacities() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}'); }
    catch { return {}; }
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

function CalcModal({ id, title, status, trans, onCalculate }) {
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
                            <button className="btn btn-success btn-block" onClick={onCalculate}>
                                <i className="fas fa-play mr-1"></i>
                                {trans.calculate ?? 'Calculer'}
                            </button>
                        )}
                        {(isRunning || isDone) && (
                            <>
                                <ProgressBar value={progress} />
                                <p className="text-muted mb-3">
                                    {count} tâche(s) traitée(s)
                                    {isRunning && <span className="ml-2 badge badge-warning"><i className="fas fa-spinner fa-spin mr-1"></i>En cours…</span>}
                                    {isDone    && <span className="ml-2 badge badge-success"><i className="fas fa-check mr-1"></i>Terminé</span>}
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

    const triggerJob = async (endpoint, setStatus) => {
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
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

function CapacityBadge({ service, customCapacities }) {
    const cap = effectiveCapacity(service, customCapacities);

    if (service.capacity > 0) {
        // Real capacity from configured resources
        return (
            <span className="badge badge-primary" title="Capacité issue des ressources configurées">
                {cap}h/j
            </span>
        );
    }

    if (customCapacities[service.id] > 0) {
        // User-defined custom capacity
        return (
            <span className="badge badge-warning" title="Capacité définie manuellement (aucune ressource configurée)">
                {cap}h/j
            </span>
        );
    }

    // No capacity at all — fallback used, shown as warning
    return (
        <span className="badge badge-secondary" title="Aucune ressource configurée — fallback 8h/j">
            8h/j*
        </span>
    );
}

// ---------------------------------------------------------------------------
// Custom capacity footer (services without resources)
// ---------------------------------------------------------------------------

function CustomCapacityFooter({ services, customCapacities, onCustomCapacityChange }) {
    const unconfigured = services.filter(s => s.capacity === 0);

    if (unconfigured.length === 0) return null;

    return (
        <div className="card-footer bg-light">
            <p className="mb-2 font-weight-bold text-secondary">
                <i className="fas fa-sliders-h mr-1"></i>
                Capacité journalière par défaut
                <small className="ml-2 text-muted font-weight-normal">
                    (services sans ressources configurées — valeurs utilisées pour le calcul du taux de charge)
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
// Load table
// ---------------------------------------------------------------------------

function LoadTable({ data, displayHoursDiff, customCapacities, onCustomCapacityChange, trans }) {
    const { services, possibleDates, hoursPerServiceDay, tasksPerServiceDay, bankHolidays } = data;

    return (
        <div className="card card-outline card-lime">
            <div className="card-body p-0">
                <div className="table-responsive">
                    <table className="table table-hover table-bordered align-middle shadow-sm rounded w-100 mb-0">
                        <thead className="text-center">
                            <tr>
                                <th className="bg-primary text-white" style={{ minWidth: 44 }}></th>
                                <th className="bg-primary text-white text-left">{trans.service}</th>
                                <th className="bg-primary text-white" style={{ minWidth: 70 }}>Cap.</th>
                                {possibleDates.map(date => {
                                    const weekend = isWeekend(date);
                                    const holiday = isBankHoliday(date, bankHolidays);
                                    const thClass = holiday ? 'bg-dark text-white'
                                        : weekend ? 'bg-secondary text-white'
                                        : 'bg-light text-dark';
                                    return (
                                        <th key={date} className={`fw-normal ${thClass}`} style={{ minWidth: 80 }}>
                                            {date.slice(5)} {/* show MM-DD only */}
                                        </th>
                                    );
                                })}
                            </tr>
                        </thead>
                        <tbody>
                            {services.map(service => {
                                const cap   = effectiveCapacity(service, customCapacities);
                                const svcId = String(service.id);

                                return (
                                    <tr key={service.id} className="align-middle">
                                        <td className="text-center p-1">
                                            {service.picture && (
                                                <img
                                                    alt={service.label}
                                                    className="rounded-circle border shadow-sm"
                                                    src={`/storage/images/methods/${service.picture}`}
                                                    width="36" height="36"
                                                />
                                            )}
                                        </td>
                                        <td className="fw-semibold">{service.label}</td>
                                        <td className="text-center">
                                            <CapacityBadge service={service} customCapacities={customCapacities} />
                                        </td>
                                        {possibleDates.map(date => {
                                            const hours   = hoursPerServiceDay[svcId]?.[date] ?? null;
                                            const tasks   = tasksPerServiceDay[svcId]?.[date] ?? [];
                                            const holiday = isBankHoliday(date, bankHolidays);
                                            const weekend = isWeekend(date);
                                            const { bgClass, label } = getCellInfo(hours, displayHoursDiff, holiday, weekend, cap);
                                            const tooltip = tasks.length
                                                ? `${tasks.length} tâche(s) — ${hours?.toFixed(1) ?? 0}h`
                                                : undefined;

                                            return (
                                                <td
                                                    key={date}
                                                    className={`text-center fw-bold ${bgClass} p-1`}
                                                    title={tooltip}
                                                    style={{ fontSize: '0.8rem' }}
                                                >
                                                    {label}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>

            <CustomCapacityFooter
                services={services}
                customCapacities={customCapacities}
                onCustomCapacityChange={onCustomCapacityChange}
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

    // Custom capacities persisted in localStorage (keyed by service id)
    const [customCapacities, setCustomCapacities] = useState(loadStoredCapacities);

    const handleCustomCapacityChange = useCallback((serviceId, value) => {
        setCustomCapacities(prev => {
            const next = { ...prev, [serviceId]: value };
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(next)); } catch (_) {}
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
                <LoadTable
                    data={data}
                    displayHoursDiff={displayHoursDiff}
                    customCapacities={customCapacities}
                    onCustomCapacityChange={handleCustomCapacityChange}
                    trans={trans}
                />
            )}
        </div>
    );
}
