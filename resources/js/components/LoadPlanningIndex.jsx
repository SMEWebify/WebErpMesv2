import React, { useState, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const CAPACITY_HOURS = 16; // hypothetical daily capacity per service

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

/**
 * bankHolidays keys are either 'mm-dd' (fixed) or 'Y-m-d' (non-fixed).
 * Check both forms for a given date string 'Y-m-d'.
 */
function isBankHoliday(dateStr, bankHolidays) {
    const mmdd = dateStr.slice(5); // '01-01'
    return Object.prototype.hasOwnProperty.call(bankHolidays, mmdd) ||
           Object.prototype.hasOwnProperty.call(bankHolidays, dateStr);
}

function getCellInfo(loadPct, displayHoursDiff, holiday, weekend) {
    if (holiday) {
        return { bgClass: 'bg-dark text-white', label: 'OFF' };
    }
    if (weekend) {
        return { bgClass: 'bg-info-subtle text-dark', label: 'Weekend' };
    }
    if (loadPct === null || loadPct === undefined) {
        return { bgClass: 'bg-light', label: 'N/A' };
    }

    const hoursDiff = Math.round((CAPACITY_HOURS - (loadPct / 100) * CAPACITY_HOURS) * 100) / 100;

    if (displayHoursDiff) {
        if (hoursDiff <= 0)  return { bgClass: 'bg-success text-white',      label: `+ ${Math.abs(hoursDiff)} h` };
        if (hoursDiff <= 4)  return { bgClass: 'bg-warning text-dark',        label: `- ${hoursDiff} h` };
        if (hoursDiff <= 8)  return { bgClass: 'bg-orange text-white',        label: `- ${hoursDiff} h` };
        if (hoursDiff <= 12) return { bgClass: 'bg-danger-light text-white',  label: `- ${hoursDiff} h` };
        return                      { bgClass: 'bg-dark text-white',          label: `- ${hoursDiff} h` };
    }

    const pct = Math.round(loadPct * 100) / 100;
    if (loadPct >= 100) return { bgClass: 'bg-danger text-white',      label: `${pct}%` };
    if (loadPct >= 80)  return { bgClass: 'bg-danger-light',           label: `${pct}%` };
    if (loadPct >= 50)  return { bgClass: 'bg-orange text-white',      label: `${pct}%` };
    if (loadPct >= 20)  return { bgClass: 'bg-warning text-dark',      label: `${pct}%` };
    return                    { bgClass: 'bg-success text-white',      label: `${pct}%` };
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

function FilterForm({ startDate, endDate, displayHoursDiff, loading, trans, onStartDate, onEndDate, onDisplayHoursDiff, onSubmit }) {
    return (
        <div className="card card-outline card-lime">
            <div className="card-body">
                <form onSubmit={onSubmit}>
                    <div className="row">
                        <div className="form-group col-2">
                            <label htmlFor="lp-start-date">{trans.start_date}</label>
                            <input
                                type="date"
                                id="lp-start-date"
                                className="form-control"
                                required
                                value={startDate}
                                onChange={e => onStartDate(e.target.value)}
                            />
                        </div>
                        <div className="form-group col-2">
                            <label htmlFor="lp-end-date">{trans.end_date}</label>
                            <input
                                type="date"
                                id="lp-end-date"
                                className="form-control"
                                required
                                value={endDate}
                                onChange={e => onEndDate(e.target.value)}
                            />
                        </div>
                        <div className="form-group col-2">
                            <label htmlFor="lp-hours-diff">{trans.display_hours_diff}</label>
                            <div className="custom-control custom-switch mt-2">
                                <input
                                    type="checkbox"
                                    className="custom-control-input"
                                    id="lp-hours-diff"
                                    checked={displayHoursDiff}
                                    onChange={e => onDisplayHoursDiff(e.target.checked)}
                                />
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

function LoadTable({ data, displayHoursDiff, trans }) {
    const { services, possibleDates, structureRateLoad, tasksPerServiceDay, bankHolidays } = data;

    return (
        <div className="card card-outline card-lime">
            <div className="card-body">
                <div className="table-responsive">
                    <table className="table table-hover table-bordered align-middle shadow-sm rounded w-100">
                        <thead className="bg-primary text-white text-center">
                            <tr>
                                <th></th>
                                <th>{trans.service}</th>
                                {possibleDates.map(date => {
                                    const weekend = isWeekend(date);
                                    const holiday = isBankHoliday(date, bankHolidays);
                                    const thClass = holiday
                                        ? 'bg-dark text-white'
                                        : weekend
                                            ? 'bg-info-subtle text-dark'
                                            : 'bg-light text-dark';
                                    return (
                                        <th key={date} className={`fw-normal ${thClass}`}>{date}</th>
                                    );
                                })}
                            </tr>
                        </thead>
                        <tbody>
                            {services.map(service => (
                                <tr key={service.id} className="align-middle">
                                    <td className="text-center">
                                        {service.picture && (
                                            <img
                                                alt={service.label}
                                                className="rounded-circle border shadow-sm"
                                                src={`/images/methods/${service.picture}`}
                                                width="40"
                                                height="40"
                                            />
                                        )}
                                    </td>
                                    <td className="fw-semibold">{service.label}</td>
                                    {possibleDates.map(date => {
                                        const svcId    = String(service.id);
                                        const tasks    = tasksPerServiceDay[svcId]?.[date] ?? [];
                                        const loadPct  = structureRateLoad[date]?.[svcId] ?? null;
                                        const holiday  = isBankHoliday(date, bankHolidays);
                                        const weekend  = isWeekend(date);
                                        const { bgClass, label } = getCellInfo(loadPct, displayHoursDiff, holiday, weekend);
                                        const tooltip = tasks.map(id => `#${id}`).join(', ');

                                        return (
                                            <td
                                                key={date}
                                                className={`text-center fw-bold ${bgClass} p-2`}
                                                title={tooltip || undefined}
                                            >
                                                {label}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            <div className="card-footer">
                <button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => history.back()}
                >
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
    const [startDate,       setStartDate]       = useState(initStart    ?? '');
    const [endDate,         setEndDate]         = useState(initEnd      ?? '');
    const [displayHoursDiff, setDisplayHoursDiff] = useState(initHoursDiff ?? false);
    const [data,            setData]            = useState(initial      ?? null);
    const [loading,         setLoading]         = useState(false);
    const [error,           setError]           = useState(null);

    const fetchData = useCallback(async (sd, ed) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ start_date: sd, end_date: ed });
            const res = await fetch(`${endpoints.data}?${params}`, {
                headers: {
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
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

    return (
        <div>
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

            {error && (
                <div className="alert alert-danger">{error}</div>
            )}

            {data && (
                <LoadTable
                    data={data}
                    displayHoursDiff={displayHoursDiff}
                    trans={trans}
                />
            )}
        </div>
    );
}
