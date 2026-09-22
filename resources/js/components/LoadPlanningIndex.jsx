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

function addDaysISO(iso, delta) {
    const d = new Date(`${iso}T00:00:00`);
    d.setDate(d.getDate() + delta);
    return toDateKey(d);
}

function diffDays(startIso, endIso) {
    const a = new Date(`${startIso}T00:00:00`);
    const b = new Date(`${endIso}T00:00:00`);
    return Math.round((b - a) / 86400000);
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
            dayNum: parsed.getDate(),
            month: parsed
                .toLocaleDateString(locale, { month: 'short' })
                .replace(/\.$/, ''),
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

/** Format a number of hours as `1h30` / `45m` / `2h` — compact like Nest2Prod. */
function formatHoursCompact(h) {
    if (h === null || h === undefined || h === 0) return '';
    const totalMinutes = Math.round(h * 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    if (hours === 0) return `${minutes}m`;
    if (minutes === 0) return `${hours}h`;
    return `${hours}h${String(minutes).padStart(2, '0')}`;
}

/**
 * Deterministic pastel-ish color per row id, so each service/resource gets its
 * own dot even when the back does not send one.
 */
function colorFor(id) {
    const palette = [
        '#f97316', '#22c55e', '#0ea5e9', '#a855f7', '#ef4444',
        '#eab308', '#14b8a6', '#8b5cf6', '#ec4899', '#64748b',
    ];
    const str = String(id);
    let hash = 0;
    for (let i = 0; i < str.length; i += 1) {
        hash = (hash * 31 + str.charCodeAt(i)) >>> 0;
    }
    return palette[hash % palette.length];
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
const WINDOW_KEY  = 'lp_window_days';

function loadStoredCapacities() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}'); }
    catch { return {}; }
}

function loadStoredWindow(fallback) {
    try {
        const value = parseInt(localStorage.getItem(WINDOW_KEY) ?? '', 10);
        return [7, 14, 30].includes(value) ? value : fallback;
    } catch { return fallback; }
}

// ---------------------------------------------------------------------------
// Calculation panel (job progress modals)
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

function useCalculationStatus(endpoints) {
    const [counts,         setCounts]         = useState({ date: 0, resource: 0 });
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

    useEffect(() => { fetchStatus(); }, [fetchStatus]);
    useInterval(fetchStatus, isPolling ? POLL_MS : null);

    const triggerJob = useCallback(async (endpoint, setStatus, body = null) => {
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
    }, [fetchStatus]);

    return {
        counts,
        dateStatus,
        resourceStatus,
        isPolling,
        triggerDates:     (direction = 'alap', capacityMode = 'infinite') => triggerJob(
            endpoints.calculateDates,
            setDateStatus,
            { direction, capacity_mode: capacityMode },
        ),
        triggerResources: (rebalance = false) => triggerJob(
            endpoints.calculateResources,
            setResourceStatus,
            rebalance ? { rebalance: true } : null,
        ),
    };
}

// ---------------------------------------------------------------------------
// Toolbar (top-right controls)
// ---------------------------------------------------------------------------

function Toolbar({
    viewMode,
    onViewMode,
    windowDays,
    onWindowDays,
    onShift,
    onToday,
    search,
    onSearch,
    trans,
}) {
    const [localSearch, setLocalSearch] = useState(search);
    useEffect(() => { setLocalSearch(search); }, [search]);

    const windowOptions = [
        { value: 7,  label: '1 sem.' },
        { value: 14, label: '2 sem.' },
        { value: 30, label: '1 mois' },
    ];

    return (
        <div className="lp-toolbar">
            {/* View toggle: only heatmap for now, Timeline stays as a hint. */}
            <div className="lp-segbar" role="group" aria-label="Vue">
                <button
                    type="button"
                    className={`lp-segbar__btn ${viewMode === 'heatmap' ? 'is-active' : ''}`}
                    onClick={() => onViewMode('heatmap')}
                    title="Charge par service et par jour"
                >
                    <i className="fas fa-th-large mr-1"></i>Heatmap
                </button>
                <button
                    type="button"
                    className="lp-segbar__btn"
                    disabled
                    title="Bientôt disponible"
                >
                    <i className="fas fa-stream mr-1"></i>Timeline
                </button>
            </div>

            {/* Search: filters row labels client-side. */}
            <div className="lp-search">
                <i className="fas fa-search lp-search__icon"></i>
                <input
                    type="text"
                    className="form-control form-control-sm lp-search__input"
                    placeholder={trans.search_placeholder ?? 'Rechercher un service…'}
                    value={localSearch}
                    onChange={(e) => setLocalSearch(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') onSearch(localSearch);
                        if (e.key === 'Escape') { setLocalSearch(''); onSearch(''); }
                    }}
                    onBlur={() => onSearch(localSearch)}
                />
                {localSearch && (
                    <button
                        type="button"
                        className="lp-search__clear"
                        onClick={() => { setLocalSearch(''); onSearch(''); }}
                        title="Effacer"
                    >
                        <i className="fas fa-times"></i>
                    </button>
                )}
            </div>

            {/* Period picker */}
            <div className="lp-segbar" role="group" aria-label="Fenêtre">
                {windowOptions.map((opt) => (
                    <button
                        key={opt.value}
                        type="button"
                        className={`lp-segbar__btn ${windowDays === opt.value ? 'is-active' : ''}`}
                        onClick={() => onWindowDays(opt.value)}
                    >
                        {opt.label}
                    </button>
                ))}
            </div>

            {/* Navigation */}
            <div className="lp-nav">
                <button
                    type="button"
                    className="lp-nav__btn"
                    onClick={() => onShift(-1)}
                    title="Période précédente"
                >
                    <i className="fas fa-chevron-left"></i>
                </button>
                <button type="button" className="lp-nav__today" onClick={onToday}>
                    {trans.today ?? "Aujourd'hui"}
                </button>
                <button
                    type="button"
                    className="lp-nav__btn"
                    onClick={() => onShift(1)}
                    title="Période suivante"
                >
                    <i className="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Scenario card (Vue / Sens / Capacité / Simuler)
// ---------------------------------------------------------------------------

function ScenarioCard({
    granularity,
    onGranularity,
    direction,
    onDirection,
    capacityMode,
    onCapacityMode,
    displayHoursDiff,
    onDisplayHoursDiff,
    calc,
    trans,
}) {
    const running = calc.isPolling;

    return (
        <div className="lp-scenario">
            <div className="lp-scenario__row">
                <div className="lp-field">
                    <label>Vue</label>
                    <select
                        className="form-control form-control-sm"
                        value={granularity}
                        onChange={(e) => onGranularity(e.target.value)}
                    >
                        <option value="service">Référence (baseline) · Service</option>
                        <option value="resource">Référence (baseline) · Ressource</option>
                    </select>
                </div>

                <div className="lp-scenario__spacer" />

                <div className="lp-field">
                    <label>Sens</label>
                    <select
                        className="form-control form-control-sm"
                        value={direction}
                        onChange={(e) => onDirection(e.target.value)}
                        title="ALAP : rétro-planifié depuis la date d'engagement client. ASAP : planifié au plus tôt depuis la date de démarrage prévue."
                    >
                        <option value="alap">Au plus tard (ALAP)</option>
                        <option value="asap">Au plus tôt (ASAP)</option>
                    </select>
                </div>

                <div className="lp-field">
                    <label>Capacité</label>
                    <select
                        className="form-control form-control-sm"
                        value={capacityMode}
                        onChange={(e) => onCapacityMode(e.target.value)}
                        title="Infinie : les tâches concurrentes ne se voient pas (comportement historique). Finie : chaque tâche vérifie la capacité déjà consommée sur sa ressource et se décale si nécessaire."
                    >
                        <option value="infinite">Infinie</option>
                        <option value="finite">Finie</option>
                    </select>
                </div>

                <label
                    className="lp-check"
                    title="Coché : afficher la marge en heures (ex. +2h) au lieu du taux de charge (%)."
                >
                    <input
                        type="checkbox"
                        checked={displayHoursDiff}
                        onChange={(e) => onDisplayHoursDiff(e.target.checked)}
                    />
                    <span>Afficher la marge en heures</span>
                </label>

                <button
                    type="button"
                    className="lp-btn-primary"
                    disabled={running}
                    onClick={() => calc.triggerDates(direction, capacityMode)}
                    title={
                        (direction === 'asap' ? 'Au plus tôt (ASAP) depuis start_date' : 'Au plus tard (ALAP) depuis internal_delay')
                        + ' · ' + (capacityMode === 'finite' ? 'capacité finie partagée' : 'capacité infinie')
                    }
                >
                    <i className="fas fa-play mr-1"></i>
                    {running ? 'Calcul…' : 'Simuler'}
                </button>
            </div>

            {(calc.counts.date > 0 || calc.counts.resource > 0) && (
                <div className="lp-scenario__alerts">
                    {calc.counts.resource > 0 && (
                        <button
                            type="button"
                            className="lp-chip lp-chip--warn"
                            data-toggle="modal"
                            data-target="#taskCalculationRessource"
                        >
                            <i className="fas fa-user-cog mr-1"></i>
                            {trans.null_resource ?? 'Ressources manquantes'} · {calc.counts.resource}
                        </button>
                    )}
                    {calc.counts.date > 0 && (
                        <button
                            type="button"
                            className="lp-chip lp-chip--warn"
                            data-toggle="modal"
                            data-target="#taskCalculationDate"
                        >
                            <i className="fas fa-calendar-times mr-1"></i>
                            {trans.null_date ?? 'Dates manquantes'} · {calc.counts.date}
                        </button>
                    )}
                    <span className="lp-scenario__hint">
                        Traiter ces éléments avant de figer la charge.
                    </span>
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Custom capacity footer (services without resources)
// ---------------------------------------------------------------------------

function CustomCapacityFooter({ services, customCapacities, onCustomCapacityChange, trans }) {
    const unconfigured = services.filter(s => s.capacity === 0);
    if (unconfigured.length === 0) return null;

    return (
        <div className="lp-custom-capacity">
            <div className="lp-custom-capacity__title">
                <i className="fas fa-sliders-h mr-1"></i>
                {trans.default_capacity ?? 'Capacité journalière par défaut'}
                <small className="text-muted ml-2">
                    ({trans.default_capacity_hint ?? 'services sans ressources configurées'})
                </small>
            </div>
            <div className="lp-custom-capacity__grid">
                {unconfigured.map(service => (
                    <div key={service.id} className="input-group input-group-sm lp-custom-capacity__field">
                        <div className="input-group-prepend">
                            <span className="input-group-text bg-white">{service.label}</span>
                        </div>
                        <input
                            type="number"
                            className="form-control"
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
// Heatmap
// ---------------------------------------------------------------------------

function DayHeader({ day, dayTotal }) {
    const classes = ['lp-day'];
    if (day.isHoliday)      classes.push('is-off');
    else if (day.isWeekend) classes.push('is-weekend');
    if (day.isToday)        classes.push('is-today');
    if (day.isWeekStart)    classes.push('is-week-start');

    return (
        <th
            scope="col"
            className={classes.join(' ')}
            title={day.holidayLabel ? `${day.fullLabel} — ${day.holidayLabel}` : day.fullLabel}
        >
            <div className="lp-day__inner">
                <span className="lp-day__weekday">{day.weekday}</span>
                <span className="lp-day__num">{day.dayNum}</span>
                <span className="lp-day__month">{day.month}</span>
                {dayTotal > 0 && (
                    <span className="lp-day__total">{formatHoursCompact(dayTotal)}</span>
                )}
            </div>
        </th>
    );
}

function RowLabel({ row, stats, byResource, customCapacities, onCustomCapacityChange }) {
    const dotColor = colorFor(row.id);
    const capLabel = byResource
        ? `${Math.round(stats.capacity * 10) / 10}h/j`
        : `${effectiveCapacity(row, customCapacities)}h/j`;

    return (
        <th scope="row" className="lp-row-head">
            <div className="lp-row-head__main">
                <span className="lp-row-head__dot" style={{ backgroundColor: dotColor }} />
                <span className="lp-row-head__label">{row.label}</span>
            </div>
            <div className="lp-row-head__meta">
                <span className="lp-row-head__cap">{capLabel}</span>
                {stats.taskCount > 0 && (
                    <span className="lp-row-head__count">{stats.taskCount} tâches</span>
                )}
                {stats.overloadedDays > 0 && (
                    <span className="lp-row-head__over">
                        <i className="fas fa-exclamation-triangle mr-1"></i>
                        {stats.overloadedDays} j en surcharge
                    </span>
                )}
                {!byResource && row.capacity === 0 && (
                    <input
                        type="number"
                        className="lp-row-head__custom"
                        min="1"
                        max="24"
                        step="0.5"
                        value={customCapacities[row.id] ?? ''}
                        placeholder="8"
                        onChange={(e) => onCustomCapacityChange(row.id, e.target.value)}
                        title="Capacité personnalisée (h/j)"
                    />
                )}
            </div>
        </th>
    );
}

function LoadCell({ day, cell, capacity, taskCount, trans }) {
    const classes = ['lp-cell'];
    if (day.isHoliday)      classes.push('is-off');
    else if (day.isWeekend) classes.push('is-weekend');
    if (day.isToday)        classes.push('is-today');
    if (day.isWeekStart)    classes.push('is-week-start');

    const hasLoad = cell.hours !== null;
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
                <div className={`lp-tile lp-tone-${cell.tone}`}>
                    {offWithLoad && (
                        <i className="fas fa-exclamation-triangle lp-tile__warn" />
                    )}
                    <span className="lp-tile__hours">
                        {formatHoursCompact(cell.hours)}
                    </span>
                    <span className="lp-tile__count">
                        {taskCount > 0
                            ? `${taskCount} tâche${taskCount > 1 ? 's' : ''}`
                            : '—'}
                    </span>
                    <span className="lp-tile__pct">{cell.label}</span>
                </div>
            ) : (
                <span className="lp-cell__empty" aria-hidden="true"></span>
            )}
        </td>
    );
}

function Heatmap({
    data, days, displayHoursDiff, customCapacities, search,
    onCustomCapacityChange, trans,
}) {
    const { rows: rowDefs, hoursPerRowDay, tasksPerRowDay, granularity } = data;
    const byResource = granularity === 'resource';

    // Per-row aggregates, cell tones and per-day column totals (for header sums).
    const { rows, dayTotals } = useMemo(() => {
        const totals = Object.fromEntries(days.map((d) => [d.date, 0]));

        const computedRows = (rowDefs ?? []).map((row) => {
            const rowId = String(row.id);
            const rowCapacity = byResource
                ? (row.capacity ?? 0)
                : effectiveCapacity(row, customCapacities);
            const hoursMap = hoursPerRowDay?.[rowId] ?? {};
            const tasksMap = tasksPerRowDay?.[rowId] ?? {};

            let totalHours = 0;
            let taskCount = 0;
            let overloadedDays = 0;
            let workingDays = 0;
            let capacitySum = 0;
            let peakTone = 'free';

            const cells = days.map((day) => {
                const hours = hoursMap[day.date] ?? null;
                const tasks = tasksMap[day.date] ?? [];
                const dayCapacity = row.capacityPerDay?.[day.date] ?? rowCapacity;
                const cell  = computeCell(hours, dayCapacity, displayHoursDiff);

                if (!day.isOff) {
                    workingDays += 1;
                    capacitySum += dayCapacity;
                }
                if (hours !== null) {
                    totalHours += hours;
                    taskCount  += tasks.length;
                    totals[day.date] += hours;
                    if (!day.isOff) {
                        if (cell.pct >= 100) overloadedDays += 1;
                        if (toneRank(cell.tone) > toneRank(peakTone)) peakTone = cell.tone;
                    }
                }

                return { day, cell, capacity: dayCapacity, taskCount: tasks.length };
            });

            return {
                row,
                capacity: rowCapacity,
                cells,
                totalHours,
                taskCount,
                overloadedDays,
                peakTone,
            };
        });

        return { rows: computedRows, dayTotals: totals };
    }, [rowDefs, days, hoursPerRowDay, tasksPerRowDay, customCapacities, displayHoursDiff, byResource]);

    // Client-side search: filter row labels.
    const needle = search.trim().toLocaleLowerCase();
    const filteredRows = needle
        ? rows.filter((r) => String(r.row.label ?? '').toLocaleLowerCase().includes(needle))
        : rows;

    return (
        <div className="lp-heatmap-card">
            <div className="lp-heatmap-scroll">
                <table className="lp-heatmap">
                    <thead>
                        <tr>
                            <th scope="col" className="lp-row-head lp-row-head--head">
                                {byResource
                                    ? (trans.granularity_resource ?? 'Ressource')
                                    : (trans.granularity_service ?? 'Service')}
                            </th>
                            {days.map((day) => (
                                <DayHeader
                                    key={day.date}
                                    day={day}
                                    dayTotal={dayTotals[day.date] ?? 0}
                                />
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {filteredRows.length === 0 && (
                            <tr>
                                <td colSpan={days.length + 1} className="lp-empty">
                                    <i className="fas fa-inbox mr-2"></i>
                                    Aucun {byResource ? 'ressource' : 'service'} ne correspond à la recherche.
                                </td>
                            </tr>
                        )}
                        {filteredRows.map((r) => (
                            <tr
                                key={r.row.id}
                                className={r.overloadedDays > 0 ? 'lp-row is-overloaded' : 'lp-row'}
                            >
                                <RowLabel
                                    row={r.row}
                                    stats={r}
                                    byResource={byResource}
                                    customCapacities={customCapacities}
                                    onCustomCapacityChange={onCustomCapacityChange}
                                />
                                {r.cells.map(({ day, cell, capacity, taskCount }) => (
                                    <LoadCell
                                        key={day.date}
                                        day={day}
                                        cell={cell}
                                        capacity={capacity}
                                        taskCount={taskCount}
                                        trans={trans}
                                    />
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="lp-heatmap-footer">
                <Legend displayHoursDiff={displayHoursDiff} trans={trans} />
            </div>

            {!byResource && (
                <CustomCapacityFooter
                    services={rowDefs}
                    customCapacities={customCapacities}
                    onCustomCapacityChange={onCustomCapacityChange}
                    trans={trans}
                />
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

const DEFAULT_WINDOW_DAYS = 14;

function formatPeriodLabel(startIso, endIso, locale) {
    if (!startIso || !endIso) return '';
    const start = new Date(`${startIso}T00:00:00`);
    const end   = new Date(`${endIso}T00:00:00`);
    return `${start.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' })} — ${end.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' })}`;
}

export default function LoadPlanningIndex({
    initial,
    startDate: initStart,
    endDate: initEnd,
    displayHoursDiff: initHoursDiff,
    granularity: initGranularity,
    endpoints,
    trans,
}) {
    const locale = currentLocale();

    // Window is derived from persisted preference — the initial dates from the
    // server are honored only when we can not restore anything from storage.
    const initialWindow = useMemo(() => {
        const persisted = loadStoredWindow(null);
        if (persisted !== null) return persisted;
        if (initStart && initEnd) {
            const spread = diffDays(initStart, initEnd) + 1;
            if (spread <= 8)  return 7;
            if (spread <= 16) return 14;
            return 30;
        }
        return DEFAULT_WINDOW_DAYS;
    }, [initStart, initEnd]);

    const [windowDays, setWindowDays] = useState(initialWindow);
    const [startDate,  setStartDate]  = useState(() => {
        if (initStart) return initStart;
        return toDateKey(new Date());
    });
    const endDate = useMemo(
        () => addDaysISO(startDate, windowDays - 1),
        [startDate, windowDays],
    );

    const [displayHoursDiff, setDisplayHoursDiff] = useState(initHoursDiff ?? false);
    const [granularity,      setGranularity]      = useState(initGranularity ?? 'service');
    const [direction,        setDirection]        = useState('alap');
    const [capacityMode,     setCapacityMode]     = useState('infinite');
    const [viewMode,         setViewMode]         = useState('heatmap');
    const [search,           setSearch]           = useState('');

    const [data,             setData]             = useState(initial ?? null);
    const [loading,          setLoading]          = useState(false);
    const [error,            setError]            = useState(null);

    const [customCapacities, setCustomCapacities] = useState(loadStoredCapacities);

    const calc = useCalculationStatus(endpoints);

    const handleCustomCapacityChange = useCallback((serviceId, value) => {
        setCustomCapacities(prev => {
            const next = { ...prev, [serviceId]: value };
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(next)); } catch (_) {}
            return next;
        });
    }, []);

    const handleWindow = useCallback((value) => {
        setWindowDays(value);
        try { localStorage.setItem(WINDOW_KEY, String(value)); } catch (_) {}
    }, []);

    const fetchData = useCallback(async (sd, ed, gr) => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams({ start_date: sd, end_date: ed, granularity: gr });
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

    // Refetch whenever the period, the window or the granularity changes.
    // We skip the first render if the server already sent an `initial` payload
    // that matches the current settings.
    const firstRender = useRef(true);
    useEffect(() => {
        if (firstRender.current && data
            && startDate === (initStart ?? startDate)
            && endDate === (initEnd ?? endDate)
            && granularity === (initGranularity ?? granularity)) {
            firstRender.current = false;
            return;
        }
        firstRender.current = false;
        fetchData(startDate, endDate, granularity);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [startDate, endDate, granularity]);

    const handleShift = useCallback((direction) => {
        setStartDate((prev) => addDaysISO(prev, direction * windowDays));
    }, [windowDays]);

    const handleToday = useCallback(() => {
        setStartDate(toDateKey(new Date()));
    }, []);

    const days = useMemo(
        () => buildDays(data?.possibleDates, data?.bankHolidays ?? {}, locale),
        [data?.possibleDates, data?.bankHolidays, locale],
    );

    const periodLabel = formatPeriodLabel(startDate, endDate, locale);
    const rowLabel = granularity === 'resource' ? 'ressource' : 'service';

    return (
        <div className="lp-app">
            {/* ── Top bar : subtitle + controls ─────────────────────── */}
            <div className="lp-topbar">
                <div className="lp-topbar__title">
                    <p className="lp-topbar__subtitle">
                        Charge par {rowLabel} · {periodLabel}
                    </p>
                </div>
                <Toolbar
                    viewMode={viewMode}
                    onViewMode={setViewMode}
                    windowDays={windowDays}
                    onWindowDays={handleWindow}
                    onShift={handleShift}
                    onToday={handleToday}
                    search={search}
                    onSearch={setSearch}
                    trans={trans}
                />
            </div>

            {/* ── Scenario card ─────────────────────────────────────── */}
            <ScenarioCard
                granularity={granularity}
                onGranularity={setGranularity}
                direction={direction}
                onDirection={setDirection}
                capacityMode={capacityMode}
                onCapacityMode={setCapacityMode}
                displayHoursDiff={displayHoursDiff}
                onDisplayHoursDiff={setDisplayHoursDiff}
                calc={calc}
                trans={trans}
            />

            {error && (
                <div className="alert alert-danger mt-3">
                    <i className="fas fa-exclamation-triangle mr-1"></i>
                    {error}
                </div>
            )}

            {loading && !data && (
                <div className="lp-loading">
                    <div className="spinner-border text-warning" role="status"></div>
                    <span className="ml-2">Chargement du planning…</span>
                </div>
            )}

            {data && (
                <div className={loading ? 'lp-fade' : ''}>
                    <Heatmap
                        data={data}
                        days={days}
                        displayHoursDiff={displayHoursDiff}
                        customCapacities={customCapacities}
                        search={search}
                        onCustomCapacityChange={handleCustomCapacityChange}
                        trans={trans}
                    />
                </div>
            )}

            {/* Calculation modals (kept from the previous version) */}
            <CalcModal
                id="taskCalculationRessource"
                title={trans.calc_resource_title ?? 'Calculer les ressources'}
                status={calc.resourceStatus}
                trans={trans}
                onCalculate={() => calc.triggerResources(false)}
                onRebalance={() => calc.triggerResources(true)}
            />
            <CalcModal
                id="taskCalculationDate"
                title={trans.calc_date_title ?? 'Calculer les dates'}
                status={calc.dateStatus}
                trans={trans}
                onCalculate={() => calc.triggerDates()}
            />
        </div>
    );
}
