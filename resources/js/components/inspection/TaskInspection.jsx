import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { apiFetch } from '../../lib/http';
import { openPlan } from '../../lib/pdf/pdfRender';
import { describe, formatLimits } from './characteristics';
import { ACCENT, Overlay, pointToView } from './planOverlay';

// Couleurs des bulles selon la mesure : lisibles sur le blanc du plan.
const COLOR = { todo: ACCENT, ok: '#198754', nok: '#dc3545', judged: '#0d6efd' };
const DEVICE_KEY = 'inspection_device';

function readDevice() {
    try { return localStorage.getItem(DEVICE_KEY) || ''; } catch { return ''; }
}
function writeDevice(id) {
    try { localStorage.setItem(DEVICE_KEY, id); } catch { /* navigation privée */ }
}

/** État d'une cote pour la couleur de sa bulle : nok dès qu'une pièce l'est, ok quand tout est mesuré. */
function statusOf(point) {
    const measures = Object.values(point.measures ?? {});
    if (measures.some(m => m.result === 'nok')) return 'nok';
    const done = point.parts.every(part => point.measures?.[part]);
    if (!done) return 'todo';
    return point.has_tolerance ? 'ok' : 'judged';
}

// ---------------------------------------------------------------------------
// Plan bullé (lecture seule)
// ---------------------------------------------------------------------------

/**
 * Le plan tient entier dans sa zone (largeur et hauteur), sans défilement :
 * `height` est la hauteur disponible (CSS), la zone suit les redimensionnements.
 */
function PlanView({ plan, points, selectedId, onSelect, onlyThisOperation, height }) {
    const [doc, setDoc] = useState(null);
    const [pageNumber, setPageNumber] = useState(1);
    const [size, setSize] = useState({ width: 0, height: 0 });
    const [area, setArea] = useState({ width: 0, height: 0 });
    const [failed, setFailed] = useState(false);
    const boxRef = useRef(null);
    const canvasRef = useRef(null);
    const rotation = plan.rotation ?? 0;

    useEffect(() => {
        if (!boxRef.current) return;
        const observer = new ResizeObserver(([entry]) => {
            const { width, height: h } = entry.contentRect;
            setArea(a => (Math.abs(a.width - width) < 2 && Math.abs(a.height - h) < 2 ? a : { width, height: h }));
        });
        observer.observe(boxRef.current);
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!plan.file) return;
        let opened = null;
        let cancelled = false;
        openPlan(plan.file).then(d => {
            if (cancelled) { d.destroy(); return; }
            opened = d;
            setDoc(d);
        }).catch(() => setFailed(true));
        return () => { cancelled = true; opened?.destroy(); };
    }, [plan.file?.id]);

    // La cote choisie peut être sur une autre page.
    useEffect(() => {
        const p = points.find(x => x.id === selectedId);
        if (p && (p.plan_page ?? 1) !== pageNumber) setPageNumber(p.plan_page ?? 1);
    }, [selectedId]);

    useEffect(() => {
        if (!doc || !canvasRef.current || area.width < 10 || area.height < 10) return;
        let cancelled = false;
        doc.getPage(pageNumber).then(page => {
            if (cancelled) return;
            const s = page.size(rotation);
            // Ajusté à la plus contraignante des deux dimensions : tout le plan est visible.
            const scale = Math.min((area.width - 4) / s.width, (area.height - 4) / s.height);
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            return page.render(canvasRef.current, scale * dpr, rotation).then(() => {
                if (cancelled) return;
                canvasRef.current.style.width = `${Math.round(s.width * scale)}px`;
                canvasRef.current.style.height = `${Math.round(s.height * scale)}px`;
                setSize({ width: Math.round(s.width * scale), height: Math.round(s.height * scale) });
            });
        }).catch(() => setFailed(true));
        return () => { cancelled = true; };
    }, [doc, pageNumber, rotation, area]);

    const onPage = points.filter(p => (p.plan_page ?? 1) === pageNumber).map(p => pointToView(p, rotation));

    if (!plan.file || failed) return null;

    return (
        <div className="d-flex flex-column" style={{ height }}>
            {doc && doc.pageCount > 1 && (
                <div className="btn-group btn-group-sm mb-1 align-self-start">
                    <button type="button" className="btn btn-outline-secondary" disabled={pageNumber <= 1} onClick={() => setPageNumber(n => n - 1)}><i className="fas fa-chevron-left" /></button>
                    <span className="btn btn-outline-secondary disabled">{pageNumber} / {doc.pageCount}</span>
                    <button type="button" className="btn btn-outline-secondary" disabled={pageNumber >= doc.pageCount} onClick={() => setPageNumber(n => n + 1)}><i className="fas fa-chevron-right" /></button>
                </div>
            )}
            <div ref={boxRef} className="border rounded bg-body-tertiary flex-grow-1 d-flex align-items-center justify-content-center"
                 style={{ minHeight: 0, overflow: 'hidden' }}>
                <div style={{ position: 'relative', width: size.width || undefined, height: size.height || undefined }}>
                    <canvas ref={canvasRef} style={{ display: 'block' }} />
                    {size.width > 0 && (
                        <Overlay points={onPage} selectedId={selectedId} width={size.width} height={size.height}
                                 onSelect={id => id && onSelect(id)}
                                 colorOf={p => COLOR[statusOf(p)]}
                                 isDimmed={p => onlyThisOperation && !p.this_operation}
                                 ringOf={p => (p.is_critical ? COLOR.nok : null)} />
                    )}
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Case de saisie d'une pièce
// ---------------------------------------------------------------------------

/**
 * Mesure hors tolérance : bouton « NC » qui déclare une non-conformité du module
 * qualité pré-remplie (cote, valeur, pièce, appareil, opérateur), puis son code.
 */
function NcButton({ measure, onDeclare, trans }) {
    const [busy, setBusy] = useState(false);
    if (measure?.result !== 'nok') return null;

    if (measure.nc) {
        return (
            <a className="badge text-bg-danger text-decoration-none" href={measure.nc_url ?? '#'} target="_blank" rel="noreferrer"
               title={trans.nc_declared}>
                {typeof measure.nc === 'string' ? measure.nc : 'NC'}
            </a>
        );
    }

    return (
        <button type="button" className="btn btn-sm btn-outline-danger px-1 py-0" disabled={busy} title={trans.declare_nc}
                onClick={async () => { setBusy(true); try { await onDeclare(measure); } finally { setBusy(false); } }}>
            NC
        </button>
    );
}

function MeasureCell({ point, part, measure, disabled, onSave, onDeclareNc, inputRef, onEnter, trans }) {
    const [value, setValue] = useState(measure?.value ?? '');
    const [busy, setBusy] = useState(false);
    const isNote = point.characteristic_type === 'note';

    useEffect(() => { setValue(measure?.value ?? ''); }, [measure?.value]);

    const save = async (payload) => {
        setBusy(true);
        try { await onSave(point, part, payload); } finally { setBusy(false); }
    };

    const commit = () => {
        const current = measure?.value ?? '';
        if (String(value) === String(current) || value === '') return;
        save({ value: Number(String(value).replace(',', '.')) });
    };

    const tone = measure
        ? (measure.result === 'nok' ? 'bg-danger-subtle' : point.has_tolerance ? 'bg-success-subtle' : 'bg-info-subtle')
        : '';

    if (isNote) {
        return (
            <td className={`p-1 ${tone}`}>
                <div className="btn-group btn-group-sm w-100">
                    <button type="button" disabled={disabled || busy} title={trans.mark_ok}
                            className={`btn ${measure?.result === 'ok' ? 'btn-success' : 'btn-outline-success'}`}
                            onClick={() => save({ result: 'ok' })}><i className="fas fa-check" /></button>
                    <button type="button" disabled={disabled || busy} title={trans.mark_nok}
                            className={`btn ${measure?.result === 'nok' ? 'btn-danger' : 'btn-outline-danger'}`}
                            onClick={() => save({ result: 'nok' })}><i className="fas fa-xmark" /></button>
                </div>
                <div className="text-center mt-1"><NcButton measure={measure} onDeclare={onDeclareNc} trans={trans} /></div>
            </td>
        );
    }

    return (
        <td className={`p-1 ${tone}`} style={{ minWidth: 92 }}>
            <div className="d-flex align-items-center gap-1">
                <input ref={inputRef} type="text" inputMode="decimal" className="form-control form-control-sm text-end"
                       disabled={disabled || busy} value={value}
                       title={measure ? `${measure.by ?? ''} ${measure.at ? new Date(measure.at).toLocaleString() : ''}` : ''}
                       onChange={e => setValue(e.target.value)}
                       onBlur={commit}
                       onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); commit(); onEnter(); } }} />
                {/* Sans tolérance, c'est l'opérateur qui juge : bascule conforme / non conforme. */}
                {measure && !point.has_tolerance && (
                    <button type="button" disabled={disabled || busy}
                            className={`btn btn-sm px-1 ${measure.result === 'nok' ? 'btn-danger' : 'btn-outline-secondary'}`}
                            title={measure.result === 'nok' ? trans.mark_ok : trans.mark_nok}
                            onClick={() => save({ value: measure.value, result: measure.result === 'nok' ? 'ok' : 'nok' })}>
                        <i className="fas fa-xmark" />
                    </button>
                )}
                <NcButton measure={measure} onDeclare={onDeclareNc} trans={trans} />
            </div>
        </td>
    );
}

// ---------------------------------------------------------------------------
// Onglet Contrôle
// ---------------------------------------------------------------------------

/**
 * Saisie des mesures d'un dossier de contrôle. `baseUrl` : la fiche du dossier,
 * depuis l'écran tâche (…/Task/Statu/Api/{id}/inspection) ou un contrôle libre
 * (/inspection-dossiers/{id}) ; mesures, clôture et NC en sont des sous-adresses.
 */
export default function TaskInspection({ baseUrl, onSummary }) {
    const [sheet, setSheet] = useState(null);
    const [error, setError] = useState(null);
    const [selectedId, setSelectedId] = useState(null);
    const [onlyThisOperation, setOnlyThisOperation] = useState(true);
    const [deviceId, setDeviceId] = useState(readDevice());
    const [full, setFull] = useState(false);
    const inputs = useRef({});

    // Échap quitte le plein écran ; la page derrière ne défile plus pendant ce temps.
    useEffect(() => {
        if (!full) return undefined;
        const onKey = e => { if (e.key === 'Escape') setFull(false); };
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKey);
        return () => { window.removeEventListener('keydown', onKey); document.body.style.overflow = previous; };
    }, [full]);

    const base = baseUrl;

    const load = useCallback(() => apiFetch(base)
        .then(data => { setSheet(data); setError(null); })
        .catch(err => setError(err?.message ?? 'Erreur')), [base]);

    useEffect(() => { load(); }, [load]);

    const t = sheet?.trans ?? {};
    const points = sheet?.points ?? [];
    const visible = useMemo(
        () => points.filter(p => !onlyThisOperation || p.this_operation),
        [points, onlyThisOperation],
    );
    const maxPart = Math.max(1, ...points.flatMap(p => p.parts));
    const columns = Array.from({ length: maxPart }, (_, i) => i + 1);
    const closed = sheet?.dossier?.closed;
    const device = sheet?.devices?.find(d => String(d.id) === String(deviceId));

    const save = async (point, part, payload) => {
        try {
            const data = await apiFetch(`${base}/measure`, {
                method: 'POST',
                body: JSON.stringify({ point_id: point.id, part, device_id: deviceId || null, ...payload }),
            });
            setSheet(s => ({
                ...s,
                dossier: { ...s.dossier, done: data.summary?.done ?? s.dossier.done, nok: data.summary?.nok ?? s.dossier.nok,
                           required: data.summary?.required ?? s.dossier.required, fai_pending: data.summary?.fai_pending ?? s.dossier.fai_pending },
                points: s.points.map(p => (p.id === point.id
                    ? { ...p, measures: { ...(Array.isArray(p.measures) ? {} : p.measures), [part]: data.measure } }
                    : p)),
            }));
            onSummary?.(data.summary);
        } catch (err) {
            setError(err?.message ?? 'Erreur');
        }
    };

    // Entrée : cote suivante (visible et à mesurer) de la même pièce.
    const focusNext = (point, part) => {
        const index = visible.findIndex(p => p.id === point.id);
        const next = visible.slice(index + 1).find(p => p.parts.includes(part) && p.characteristic_type !== 'note');
        if (next) {
            setSelectedId(next.id);
            inputs.current[`${next.id}-${part}`]?.focus();
        }
    };

    const closeControl = () => {
        const incomplete = sheet.dossier.done < sheet.dossier.required;
        if (incomplete && !window.confirm(t.confirm_close_incomplete)) return;
        apiFetch(`${base}/close`, { method: 'POST' })
            .then(data => { onSummary?.(data.summary); load(); })
            .catch(err => setError(err?.message ?? 'Erreur'));
    };

    const declareNc = async (measure) => {
        try {
            const data = await apiFetch(`${base}/measures/${measure.id}/nc`, { method: 'POST' });
            setSheet(s => ({
                ...s,
                points: s.points.map(p => {
                    const measures = Array.isArray(p.measures) ? {} : { ...p.measures };
                    Object.keys(measures).forEach(part => {
                        if (measures[part]?.id === measure.id) measures[part] = { ...measures[part], nc: data.nc.code, nc_url: data.url };
                    });
                    return { ...p, measures };
                }),
            }));
            window.open(data.url, '_blank', 'noopener');
        } catch (err) {
            setError(err?.message ?? 'Erreur');
        }
    };

    if (error && !sheet) return <div className="alert alert-warning m-2">{error}</div>;
    if (!sheet) return <div className="text-center p-4"><i className="fas fa-spinner fa-spin fa-2x text-secondary" /></div>;

    const { dossier } = sheet;

    // Plein écran par une couche fixe plutôt que l'API Fullscreen (absente des
    // iPad pour un élément quelconque) : le plan reste à côté de la grille.
    const fullStyle = { position: 'fixed', inset: 0, zIndex: 1060, overflow: 'hidden' };

    return (
        <div className={full ? 'p-2 bg-body d-flex flex-column' : 'p-2'} style={full ? fullStyle : undefined}>
            <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span className={`badge ${dossier.is_fai ? 'text-bg-warning' : 'text-bg-secondary'}`}>
                    {dossier.is_fai ? t.control_fai : t.control_routine}
                </span>
                <span className="small text-secondary">{dossier.code}</span>
                <span className="badge text-bg-light">{dossier.done} / {dossier.required} {t.measured}</span>
                {dossier.nok > 0 && <span className="badge text-bg-danger">{dossier.nok} {t.nok_count}</span>}
                {closed && <span className="badge text-bg-success"><i className="fas fa-lock me-1" />{t.closed_badge}</span>}
                <div className="flex-grow-1" />
                <div className="btn-group btn-group-sm">
                    <button type="button" className={`btn ${onlyThisOperation ? 'btn-secondary' : 'btn-outline-secondary'}`} onClick={() => setOnlyThisOperation(true)}>{t.this_operation_only}</button>
                    <button type="button" className={`btn ${!onlyThisOperation ? 'btn-secondary' : 'btn-outline-secondary'}`} onClick={() => setOnlyThisOperation(false)}>{t.all_characteristics}</button>
                </div>
                <select className="form-select form-select-sm" style={{ maxWidth: 220 }} title={t.device} value={deviceId}
                        onChange={e => { setDeviceId(e.target.value); writeDevice(e.target.value); }}>
                    <option value="">{t.no_device}</option>
                    {sheet.devices.map(d => <option key={d.id} value={d.id}>{d.label}{d.overdue ? ' ⚠' : ''}</option>)}
                </select>
                {dossier.report_url && (
                    <a className="btn btn-sm btn-outline-primary" href={dossier.report_url} target="_blank" rel="noreferrer">
                        <i className="fas fa-file-signature me-1" />{t.report}
                    </a>
                )}
                {!closed && (
                    <button type="button" className="btn btn-sm btn-success" onClick={closeControl}>
                        <i className="fas fa-lock me-1" />{t.close_control}
                    </button>
                )}
                <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setFull(f => !f)}
                        title={full ? t.exit_fullscreen : t.fullscreen}>
                    <i className={`fas ${full ? 'fa-compress' : 'fa-expand'}`} />
                </button>
            </div>
            {device?.overdue && <div className="alert alert-warning py-1 small mb-2"><i className="fas fa-triangle-exclamation me-1" />{t.device_overdue} : {device.label}</div>}
            {error && <div className="alert alert-danger py-1 small mb-2">{error}</div>}

            {/* Plein écran : plan à gauche, grille à droite ; sinon plan au-dessus de la grille. */}
            <div className={full ? 'd-flex gap-2 flex-grow-1' : ''} style={full ? { minHeight: 0 } : undefined}>
            <div className={full ? 'flex-grow-1' : ''} style={full ? { minWidth: 0 } : undefined}>
                <PlanView plan={sheet.plan} points={points} selectedId={selectedId} onlyThisOperation={onlyThisOperation}
                          height={full ? '100%' : '60vh'}
                          onSelect={id => {
                              setSelectedId(id);
                              const p = points.find(x => x.id === id);
                              if (p) inputs.current[`${p.id}-${p.parts.find(n => !p.measures?.[n]) ?? p.parts[0]}`]?.focus();
                          }} />
            </div>

            <div className={full ? 'table-responsive' : 'table-responsive mt-2'}
                 style={full ? { width: '40%', minWidth: 360, overflow: 'auto' } : undefined}>
                <table className="table table-sm table-bordered align-middle mb-1">
                    <thead className="table-light">
                        <tr>
                            <th style={{ width: 44 }} />
                            <th>{t.characteristic}</th>
                            {columns.map(n => <th key={n} className="text-center">{t.part} {n}</th>)}
                        </tr>
                    </thead>
                    <tbody>
                        {visible.map(p => {
                            const limits = formatLimits(p);
                            return (
                                <tr key={p.id} className={p.id === selectedId ? 'table-active' : ''} onClick={() => setSelectedId(p.id)}>
                                    <td className="text-center">
                                        <span className="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold small"
                                              style={{ width: 26, height: 26, border: `2px solid ${COLOR[statusOf(p)]}`, color: COLOR[statusOf(p)],
                                                       boxShadow: p.is_critical ? `0 0 0 3px ${COLOR.nok}55` : undefined }}>
                                            {p.number}
                                        </span>
                                    </td>
                                    <td>
                                        <div className="fw-semibold" style={{ fontFamily: p.characteristic_type === 'gdt' ? 'monospace' : undefined }}>
                                            {describe(p) || p.label}
                                            {p.count > 1 && <span className="badge text-bg-light ms-1">×{p.count}</span>}
                                        </div>
                                        <div className="small text-secondary">
                                            {limits || (!p.has_tolerance && p.characteristic_type !== 'note' ? t.no_tolerance_hint : '')}
                                            {p.is_critical && <span className="badge text-bg-danger ms-1">{t.critical_short}</span>}
                                        </div>
                                    </td>
                                    {columns.map(n => (p.parts.includes(n) ? (
                                        <MeasureCell key={n} point={p} part={n} measure={p.measures?.[n]} disabled={closed} trans={t}
                                                     inputRef={el => { inputs.current[`${p.id}-${n}`] = el; }}
                                                     onSave={save} onDeclareNc={declareNc} onEnter={() => focusNext(p, n)} />
                                    ) : <td key={n} className="bg-body-tertiary" />))}
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
                {dossier.capped && <div className="small text-secondary">{t.part} 1 – {dossier.parts}</div>}
                <div className="small text-secondary"><i className="fas fa-keyboard me-1" />{t.enter_hint}</div>
            </div>
            </div>
        </div>
    );
}
