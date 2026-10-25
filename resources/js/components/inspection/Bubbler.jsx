import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { apiFetch, csrfToken } from '../../lib/http';
import { openPlan, canvasToThumbnail, uploadThumbnail, THUMBNAIL_SIDE } from '../../lib/pdf/pdfRender';
import { describe, frameCells, formatLimits, parseDimensionText, GDT_SYMBOLS, TYPES, FREQUENCIES } from './characteristics';
import { ACCENT, Overlay, boxThrough, clamp01, drawBubblesOnCanvas, pointToView, toPage } from './planOverlay';

const url = (template, id) => template.replace('__ID__', id);
const SAVE_DELAY = 600;

// ---------------------------------------------------------------------------
// Fiche d'une cote
// ---------------------------------------------------------------------------

function FeatureFrame({ point }) {
    const cells = frameCells(point);
    return (
        <span className="d-inline-flex border border-secondary rounded-1 bg-body" style={{ fontFamily: 'monospace' }}>
            {cells.map((c, i) => (
                <span key={i} className={`px-2 py-1 ${i ? 'border-start border-secondary' : ''}`}>{c}</span>
            ))}
        </span>
    );
}

function Field({ label, children, className = 'col-6' }) {
    return (
        <div className={className}>
            <label className="form-label small text-secondary mb-0">{label}</label>
            {children}
        </div>
    );
}

const READING_BADGE = {
    busy:  ['text-bg-secondary', 'fa-spinner fa-spin', 'reading'],
    pdf:   ['text-bg-info', 'fa-file-pdf', 'read_from_pdf'],
    ai:    ['text-bg-primary', 'fa-wand-magic-sparkles', 'read_by_ai'],
    none:  ['text-bg-light', 'fa-circle-question', 'nothing_read'],
    error: ['text-bg-danger', 'fa-triangle-exclamation', 'read_failed'],
};

function PointForm({ point, services, trans, editable, onChange, onDelete, errors, reading, onReread, canReread }) {
    const set = (field) => (e) => {
        const target = e.target;
        const value = target.type === 'checkbox' ? target.checked : target.value;
        onChange(point.id, { [field]: value === '' ? null : value });
    };
    const isGdt = point.characteristic_type === 'gdt';
    const isNote = point.characteristic_type === 'note';
    const limits = formatLimits(point);

    return (
        <div className="card mb-3 border-warning-subtle">
            <div className="card-header d-flex align-items-center gap-2 bg-warning-subtle">
                <span className="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold"
                      style={{ width: 32, height: 32, border: `2.5px solid ${ACCENT}`, color: ACCENT }}>{point.number}</span>
                <span className="flex-grow-1 text-truncate">
                    {isGdt ? <FeatureFrame point={point} /> : <strong>{describe(point) || trans.new_characteristic}</strong>}
                </span>
                {editable && canReread && (
                    <button type="button" className="btn btn-sm btn-outline-primary" title={trans.reread_hint}
                            disabled={reading === 'busy'} onClick={() => onReread(point)}>
                        <i className="fas fa-wand-magic-sparkles" />
                    </button>
                )}
                {editable && (
                    <button type="button" className="btn btn-sm btn-outline-danger" title={trans.delete} onClick={() => onDelete(point)}>
                        <i className="fas fa-trash" />
                    </button>
                )}
            </div>
            {reading && READING_BADGE[reading] && (
                <div className="px-3 pt-2">
                    <span className={`badge ${READING_BADGE[reading][0]}`}>
                        <i className={`fas ${READING_BADGE[reading][1]} me-1`} />{trans[READING_BADGE[reading][2]]}
                    </span>
                    {(reading === 'pdf' || reading === 'ai') && <small className="text-secondary ms-2">{trans.check_reading}</small>}
                </div>
            )}
            <fieldset className="card-body" disabled={!editable}>
                <div className="row g-2">
                    <Field label={trans.type}>
                        <select className="form-select form-select-sm" value={point.characteristic_type} onChange={set('characteristic_type')}>
                            {TYPES.map(t => <option key={t} value={t}>{trans[`type_${t}`]}</option>)}
                        </select>
                    </Field>
                    <Field label={trans.count}>
                        <div className="input-group input-group-sm">
                            <span className="input-group-text">×</span>
                            <input type="number" min="1" className="form-control" value={point.count ?? 1} onChange={set('count')} />
                        </div>
                    </Field>

                    {isGdt && (
                        <>
                            <Field label={trans.gdt_symbol} className="col-12">
                                <select className="form-select form-select-sm" value={point.gdt_symbol ?? ''} onChange={set('gdt_symbol')}>
                                    <option value="">—</option>
                                    {Object.entries(GDT_SYMBOLS).map(([key, glyph]) => (
                                        <option key={key} value={key}>{glyph}  {trans[`gdt_${key}`]}</option>
                                    ))}
                                </select>
                            </Field>
                            <Field label={trans.zone_value}>
                                <div className="input-group input-group-sm">
                                    <button type="button" className={`btn ${point.diameter_zone ? 'btn-secondary' : 'btn-outline-secondary'}`}
                                            title={trans.diameter_zone}
                                            onClick={() => onChange(point.id, { diameter_zone: !point.diameter_zone })}>⌀</button>
                                    <input type="number" step="any" className="form-control" value={point.tol_max ?? ''}
                                           onChange={e => onChange(point.id, { tol_max: e.target.value === '' ? null : e.target.value, tol_min: 0, nominal_value: 0 })} />
                                </div>
                            </Field>
                            <Field label={trans.modifier}>
                                <select className="form-select form-select-sm" value={point.material_modifier ?? ''} onChange={set('material_modifier')}>
                                    <option value="">—</option>
                                    <option value="M">Ⓜ {trans.mmc}</option>
                                    <option value="L">Ⓛ {trans.lmc}</option>
                                </select>
                            </Field>
                            <Field label={trans.datums} className="col-12">
                                <input type="text" className="form-control form-control-sm text-uppercase" placeholder="A,B,C" maxLength={30}
                                       value={point.datums ?? ''} onChange={e => onChange(point.id, { datums: e.target.value.toUpperCase() || null })} />
                            </Field>
                        </>
                    )}

                    {!isGdt && !isNote && (
                        <>
                            <Field label={trans.nominal} className="col-4">
                                <input type="number" step="any" className="form-control form-control-sm" value={point.nominal_value ?? ''} onChange={set('nominal_value')} />
                            </Field>
                            <Field label={trans.tol_minus} className="col-4">
                                <input type="number" step="any" className="form-control form-control-sm" placeholder="-0.1" value={point.tol_min ?? ''} onChange={set('tol_min')} />
                            </Field>
                            <Field label={trans.tol_plus} className="col-4">
                                <input type="number" step="any" className="form-control form-control-sm" placeholder="+0.1" value={point.tol_max ?? ''} onChange={set('tol_max')} />
                            </Field>
                            {limits && <div className="col-12 small text-secondary">{trans.limits} : {limits} {point.unit ?? ''}</div>}
                        </>
                    )}

                    <Field label={isNote ? trans.note : trans.label} className="col-12">
                        <input type="text" className="form-control form-control-sm" value={point.label ?? ''} maxLength={255} onChange={set('label')} />
                    </Field>

                    <Field label={trans.grid_zone} className="col-4">
                        <input type="text" className="form-control form-control-sm text-uppercase" placeholder="G8" maxLength={10}
                               value={point.grid_zone ?? ''} onChange={e => onChange(point.id, { grid_zone: e.target.value.toUpperCase() || null })} />
                    </Field>
                    <Field label={trans.unit} className="col-4">
                        <input type="text" className="form-control form-control-sm" placeholder="mm" maxLength={10} value={point.unit ?? ''} onChange={set('unit')} />
                    </Field>
                    <Field label={trans.instrument} className="col-4">
                        <input type="text" className="form-control form-control-sm" maxLength={255} value={point.instrument_type ?? ''} onChange={set('instrument_type')} />
                    </Field>

                    <Field label={trans.sampling}>
                        <select className="form-select form-select-sm" value={point.frequency_type ?? 'all'} onChange={set('frequency_type')}>
                            {FREQUENCIES.map(f => <option key={f} value={f}>{trans[`frequency_${f}`]}</option>)}
                        </select>
                    </Field>
                    <Field label={trans.sampling_value}>
                        <input type="number" min="1" className="form-control form-control-sm" disabled={!['every_n', 'sample'].includes(point.frequency_type)}
                               value={point.frequency_value ?? ''} onChange={set('frequency_value')} />
                    </Field>

                    <Field label={trans.operation} className="col-12">
                        <select className="form-select form-select-sm" value={point.methods_services_id ?? ''} onChange={set('methods_services_id')}>
                            <option value="">{trans.all_operations}</option>
                            {services.map(s => <option key={s.id} value={s.id}>{s.code} — {s.label}</option>)}
                        </select>
                    </Field>

                    <div className="col-6">
                        <div className="form-check form-switch mt-2">
                            <input className="form-check-input" type="checkbox" id={`crit-${point.id}`} checked={!!point.is_critical} onChange={set('is_critical')} />
                            <label className="form-check-label" htmlFor={`crit-${point.id}`}>{trans.critical}</label>
                        </div>
                    </div>
                    <div className="col-6">
                        <div className="form-check form-switch mt-2">
                            <input className="form-check-input" type="checkbox" id={`fai-${point.id}`} checked={!!point.is_fai} onChange={set('is_fai')} />
                            <label className="form-check-label" htmlFor={`fai-${point.id}`}>{trans.fai}</label>
                        </div>
                    </div>
                </div>
                {errors && <div className="alert alert-danger py-1 px-2 small mt-2 mb-0">{errors}</div>}
            </fieldset>
        </div>
    );
}

function PointList({ points, selectedId, onSelect, trans }) {
    if (points.length === 0) {
        return <p className="text-secondary small px-1">{trans.no_points_hint}</p>;
    }
    return (
        <div className="list-group list-group-flush border rounded mb-3">
            {points.map(p => (
                <button key={p.id} type="button"
                        className={`list-group-item list-group-item-action d-flex align-items-center gap-2 py-1 ${p.id === selectedId ? 'active' : ''}`}
                        onClick={() => onSelect(p.id)}>
                    <span className="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold small flex-shrink-0"
                          style={{ width: 26, height: 26, border: `2px solid ${p.id === selectedId ? 'currentColor' : ACCENT}`, color: p.id === selectedId ? 'inherit' : ACCENT }}>
                        {p.number}
                    </span>
                    <span className="flex-grow-1 text-truncate" style={{ fontFamily: p.characteristic_type === 'gdt' ? 'monospace' : undefined }}>
                        {describe(p) || <em className="opacity-75">{trans.to_fill}</em>}
                    </span>
                    {p.count > 1 && <span className="badge text-bg-light">×{p.count}</span>}
                    {p.is_critical && <span className="badge text-bg-warning">{trans.critical_short}</span>}
                    {(p.plan_page ?? 1) > 1 && <span className="badge text-bg-secondary">p.{p.plan_page}</span>}
                </button>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Bulleur
// ---------------------------------------------------------------------------

export default function Bubbler({ planId, endpoints, trans, canEdit = false, canApprove = false, aiReading = false }) {
    const [plan, setPlan]         = useState(null);
    const [points, setPoints]     = useState([]);
    const [services, setServices] = useState([]);
    const [files, setFiles]       = useState([]);
    const [error, setError]       = useState(null);
    const [pointErrors, setPointErrors] = useState({});
    const [reading, setReading]   = useState({});
    const [selectedId, setSelectedId] = useState(null);
    const [mode, setMode]         = useState('draw');
    const [pageNumber, setPageNumber] = useState(1);
    const [zoom, setZoom]         = useState(1);
    const [rotation, setRotation] = useState(0);
    const [doc, setDoc]           = useState(null);
    const [page, setPage]         = useState(null);
    const [pageSize, setPageSize] = useState({ width: 0, height: 0 });
    const [rendering, setRendering] = useState(false);
    const [saving, setSaving]     = useState(0);
    const [busy, setBusy]         = useState(false);

    const viewportRef = useRef(null);
    const canvasRef = useRef(null);
    const saveTimers = useRef({});
    const pending = useRef({});
    const thumbTimer = useRef(null);

    const editable = canEdit && plan?.status === 'draft';
    const pagePoints = useMemo(
        () => points.filter(p => (p.plan_page ?? 1) === pageNumber).map(p => pointToView(p, rotation)),
        [points, pageNumber, rotation],
    );
    const selected = points.find(p => p.id === selectedId) ?? null;

    // --- chargement ---------------------------------------------------------
    const load = useCallback(() => apiFetch(url(endpoints.show, planId)).then(data => {
        setPlan(data.plan);
        setRotation(data.plan.rotation ?? 0);
        setPoints(data.plan.points ?? []);
        setServices(data.services ?? []);
        setFiles(data.files ?? []);
    }).catch(err => setError(err?.message ?? trans.load_error)), [planId]);

    useEffect(() => { load(); }, [load]);

    useEffect(() => {
        document.body.classList.add('sidebar-collapse');
    }, []);

    // Plan figé ou lecture seule : l'outil de sélection, pas celui de bullage.
    useEffect(() => { if (plan && !editable) setMode('select'); }, [plan?.status, editable]);

    // --- ouverture du plan --------------------------------------------------
    useEffect(() => {
        if (!plan?.file) return;
        let cancelled = false;
        let opened = null;
        setRendering(true);
        openPlan(plan.file).then(d => {
            if (cancelled) { d.destroy(); return; }
            opened = d;
            setDoc(d);
            setPageNumber(n => Math.min(n, d.pageCount));
            if (editable && plan.page_count !== d.pageCount) {
                apiFetch(url(endpoints.update, plan.id), { method: 'PATCH', body: JSON.stringify({ page_count: d.pageCount }) }).catch(() => {});
            }
        }).catch(() => { if (!cancelled) { setError(trans.plan_unreadable); setRendering(false); } });
        return () => { cancelled = true; opened?.destroy(); setDoc(null); };
    }, [plan?.file?.id]);

    useEffect(() => {
        if (!doc) return;
        let cancelled = false;
        doc.getPage(pageNumber).then(p => { if (!cancelled) setPage(p); });
        return () => { cancelled = true; };
    }, [doc, pageNumber]);

    // Échelle « largeur de la zone » × zoom ; rendu à la densité de l'écran.
    useEffect(() => {
        if (!page || !viewportRef.current || !canvasRef.current) return;
        const available = Math.max(300, viewportRef.current.clientWidth - 24);
        const size = page.size(rotation);
        const cssScale = (available / size.width) * zoom;
        const width = Math.round(size.width * cssScale);
        const height = Math.round(size.height * cssScale);
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        setRendering(true);
        page.render(canvasRef.current, cssScale * dpr, rotation).then(() => {
            canvasRef.current.style.width = `${width}px`;
            canvasRef.current.style.height = `${height}px`;
            setPageSize({ width, height });
            setRendering(false);
        }).catch(() => setRendering(false));
    }, [page, zoom, rotation]);

    // --- miniature avec bulles (page 1) --------------------------------------
    const refreshThumbnail = useCallback(async (list, turn = rotation) => {
        if (!doc || !editable) return;
        const first = await doc.getPage(1);
        const canvas = document.createElement('canvas');
        const scale = (THUMBNAIL_SIDE * 2) / Math.max(first.width, first.height);
        await first.render(canvas, scale, turn);
        const onFirstPage = list.filter(p => (p.plan_page ?? 1) === 1).map(p => pointToView(p, turn));
        drawBubblesOnCanvas(canvas.getContext('2d'), onFirstPage, canvas.width, canvas.height, Math.max(7, canvas.width / 60));
        const blob = await canvasToThumbnail(canvas);
        if (blob) await uploadThumbnail(url(endpoints.thumbnail, planId), blob).catch(() => {});
    }, [doc, editable, planId, rotation]);

    const rotate = (delta) => {
        const next = (rotation + delta + 360) % 360;
        setRotation(next);
        if (!editable) return; // plan figé : la rotation reste propre à cet affichage
        apiFetch(url(endpoints.update, planId), { method: 'PATCH', body: JSON.stringify({ rotation: next }) })
            .then(() => {
                clearTimeout(thumbTimer.current);
                thumbTimer.current = setTimeout(() => refreshThumbnail(points, next), 1500);
            })
            .catch(err => setError(err?.message ?? trans.save_error));
    };

    const scheduleThumbnail = (list) => {
        clearTimeout(thumbTimer.current);
        thumbTimer.current = setTimeout(() => refreshThumbnail(list), 3000);
    };

    // --- écriture des cotes --------------------------------------------------
    const flush = useCallback((id) => {
        const payload = pending.current[id];
        if (!payload) return Promise.resolve();
        delete pending.current[id];
        setSaving(s => s + 1);
        return apiFetch(url(endpoints.pointUpdate, id), { method: 'PATCH', body: JSON.stringify(payload) })
            .then(data => {
                setPointErrors(e => ({ ...e, [id]: null }));
                setPoints(list => list.map(p => (p.id === id ? { ...p, ...data.point, ...(pending.current[id] ?? {}) } : p)));
            })
            .catch(err => setPointErrors(e => ({ ...e, [id]: Object.values(err?.errors ?? {}).flat().join(' ') || err?.message })))
            .finally(() => setSaving(s => s - 1));
    }, []);

    const updatePoint = (id, changes) => {
        setPoints(list => {
            const next = list.map(p => (p.id === id ? { ...p, ...changes } : p));
            scheduleThumbnail(next);
            return next;
        });
        pending.current[id] = { ...(pending.current[id] ?? {}), ...changes };
        clearTimeout(saveTimers.current[id]);
        saveTimers.current[id] = setTimeout(() => flush(id), SAVE_DELAY);
    };

    // --- lecture de la zone ---------------------------------------------------

    /** Découpe la zone affichée (avec une marge), agrandie pour rester lisible. */
    const cropZone = (viewBox) => {
        const src = canvasRef.current;
        if (!src || !src.width) return Promise.resolve(null);
        const mx = viewBox.box_w * 0.15, my = viewBox.box_h * 0.15;
        const x = Math.max(0, (viewBox.box_x - mx) * src.width);
        const y = Math.max(0, (viewBox.box_y - my) * src.height);
        const w = Math.min(src.width - x, (viewBox.box_w + 2 * mx) * src.width);
        const h = Math.min(src.height - y, (viewBox.box_h + 2 * my) * src.height);
        const scale = Math.min(4, Math.max(1, 480 / Math.max(w, h)));
        const out = document.createElement('canvas');
        out.width = Math.round(w * scale);
        out.height = Math.round(h * scale);
        const ctx = out.getContext('2d');
        ctx.fillStyle = '#ffffff'; // fond du papier
        ctx.fillRect(0, 0, out.width, out.height);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(src, x, y, w, h, 0, 0, out.width, out.height);
        return new Promise(resolve => out.toBlob(resolve, 'image/png'));
    };

    /**
     * Pré-remplit la fiche depuis la zone : texte du PDF d'abord (instantané,
     * gratuit), l'IA ensuite si le texte manque ou ne ressemble pas à une cote
     * (cadre de tolérance, plan aux textes vectorisés, image). `forceAi` : relecture
     * demandée depuis la fiche. Rien n'est validé : l'utilisateur relit.
     */
    const readZone = async (point, { forceAi = false } = {}) => {
        const id = point.id;
        const mark = state => setReading(r => ({ ...r, [id]: state }));
        mark('busy');
        try {
            if (!forceAi && doc) {
                const pg = await doc.getPage(point.plan_page ?? 1);
                const parsed = parseDimensionText(await pg.textIn(point));
                if (parsed) { updatePoint(id, parsed); mark('pdf'); return; }
            }
            if (!aiReading || (point.plan_page ?? 1) !== pageNumber) { mark('none'); return; }

            const blob = await cropZone(pointToView(point, rotation));
            if (!blob) { mark('none'); return; }
            const body = new FormData();
            body.append('image', blob, 'zone.png');
            const res = await fetch(url(endpoints.readZone, planId), {
                method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || trans.read_failed);
            updatePoint(id, data.characteristic);
            mark('ai');
        } catch (e) {
            mark('error');
            setPointErrors(errs => ({ ...errs, [id]: e?.message ?? trans.read_failed }));
        }
    };

    /** `viewBox` : rectangle tracé à l'écran, converti dans le repère de la page. */
    const createPoint = (viewBox) => {
        const box = boxThrough(toPage, viewBox.box_x, viewBox.box_y, viewBox.box_w, viewBox.box_h, rotation);
        // Bulle en haut à gauche du rectangle tel qu'on le voit.
        const [bx, by] = toPage(clamp01(viewBox.box_x - 0.02), clamp01(viewBox.box_y - 0.025), rotation);
        const bubble = { bubble_x: bx, bubble_y: by };
        setSaving(s => s + 1);
        apiFetch(url(endpoints.pointStore, planId), {
            method: 'POST',
            body: JSON.stringify({ ...box, ...bubble, plan_page: pageNumber, characteristic_type: 'dimension', count: 1, is_fai: true, is_critical: false, diameter_zone: false, frequency_type: 'all' }),
        })
            .then(data => {
                setPoints(list => { const next = [...list, data.point]; scheduleThumbnail(next); return next; });
                setSelectedId(data.point.id);
                readZone(data.point);
            })
            .catch(err => setError(err?.message ?? trans.save_error))
            .finally(() => setSaving(s => s - 1));
    };

    const deletePoint = (point) => {
        if (!window.confirm(trans.confirm_delete_point.replace(':number', point.number))) return;
        clearTimeout(saveTimers.current[point.id]);
        delete pending.current[point.id];
        apiFetch(url(endpoints.pointDestroy, point.id), { method: 'DELETE' })
            .then(() => {
                setPoints(list => { const next = list.filter(p => p.id !== point.id); scheduleThumbnail(next); return next; });
                setSelectedId(null);
            })
            .catch(err => setError(err?.message ?? trans.save_error));
    };

    const flushAll = () => Promise.all(Object.keys(pending.current).map(id => { clearTimeout(saveTimers.current[id]); return flush(Number(id)); }));

    const renumber = () => {
        flushAll().then(() => apiFetch(url(endpoints.renumber, planId), { method: 'POST' }))
            .then(data => { setPoints(data.points); scheduleThumbnail(data.points); })
            .catch(err => setError(err?.message ?? trans.save_error));
    };

    const approve = () => {
        if (!window.confirm(trans.confirm_approve)) return;
        setBusy(true);
        flushAll()
            .then(() => refreshThumbnail(points))
            .then(() => apiFetch(url(endpoints.approve, planId), { method: 'POST' }))
            .then(() => load())
            .catch(err => setError(err?.message ?? trans.save_error))
            .finally(() => setBusy(false));
    };

    const newVersion = () => {
        setBusy(true);
        apiFetch(endpoints.store, {
            method: 'POST',
            body: JSON.stringify({ products_id: plan.product_id, revision: plan.product_revision ?? plan.revision, from_plan_id: plan.id, file_id: plan.file?.id ?? null }),
        })
            .then(data => { window.location.href = url(endpoints.bubbler, data.plan.id); })
            .catch(err => { setError(err?.message ?? trans.save_error); setBusy(false); });
    };

    const destroyPlan = () => {
        if (!window.confirm(trans.confirm_delete_plan)) return;
        apiFetch(url(endpoints.destroy, planId), { method: 'DELETE' })
            .then(() => { window.location.href = endpoints.index; })
            .catch(err => setError(err?.message ?? trans.save_error));
    };

    const changeFile = (fileId) => {
        if (!fileId) return;
        apiFetch(url(endpoints.update, planId), { method: 'PATCH', body: JSON.stringify({ file_id: Number(fileId), page_count: null }) })
            .then(data => { setPlan(p => ({ ...p, ...data.plan })); setPageNumber(1); })
            .catch(err => setError(err?.message ?? trans.save_error));
    };

    // Raccourcis : Suppr efface la cote choisie, Échap la désélectionne, B / V changent d'outil.
    useEffect(() => {
        const onKey = (e) => {
            if (['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;
            if (e.key === 'Escape') setSelectedId(null);
            if ((e.key === 'Delete' || e.key === 'Backspace') && selected && editable) deletePoint(selected);
            if (e.key === 'b' || e.key === 'B') setMode('draw');
            if (e.key === 'v' || e.key === 'V') setMode('select');
            if (e.key === 'r' || e.key === 'R') rotate(e.shiftKey ? -90 : 90);
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    });

    // Enregistre ce qui attend encore avant de quitter la page.
    useEffect(() => {
        const onLeave = (e) => {
            if (Object.keys(pending.current).length === 0) return;
            flushAll();
            e.preventDefault();
        };
        window.addEventListener('beforeunload', onLeave);
        return () => window.removeEventListener('beforeunload', onLeave);
    }, []);

    const selectPoint = (id) => {
        setSelectedId(id);
        let p = points.find(x => x.id === id);
        if (!p) return;
        if ((p.plan_page ?? 1) !== pageNumber) setPageNumber(p.plan_page ?? 1);
        else if (viewportRef.current && pageSize.width && p.box_x != null) {
            p = pointToView(p, rotation);
            const vp = viewportRef.current;
            const top = p.box_y * pageSize.height - vp.clientHeight / 3;
            const left = p.box_x * pageSize.width - vp.clientWidth / 3;
            const outside = top < vp.scrollTop - 40 || top > vp.scrollTop + vp.clientHeight * 0.5
                || left < vp.scrollLeft - 40 || left > vp.scrollLeft + vp.clientWidth * 0.5;
            if (outside) vp.scrollTo({ top, left, behavior: 'smooth' });
        }
    };

    if (error && !plan) return <div className="alert alert-danger">{error}</div>;
    if (!plan) return <div className="text-center py-5"><i className="fas fa-spinner fa-spin fa-2x text-secondary" /></div>;

    const statusBadge = { draft: 'text-bg-warning', approved: 'text-bg-success', obsolete: 'text-bg-secondary' }[plan.status];

    return (
        <div>
            {/* Barre d'outils */}
            <div className="card mb-2">
                <div className="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                    <a href={endpoints.index} className="btn btn-sm btn-outline-secondary" title={trans.back}><i className="fas fa-arrow-left" /></a>
                    <div className="me-2">
                        <div className="fw-semibold lh-sm">
                            <a href={url(endpoints.product, plan.product_id)}>{plan.product_code}</a>
                            <span className="text-secondary ms-2">{trans.revision} {plan.revision ?? '—'}</span>
                            <span className={`badge ms-2 ${statusBadge}`}>{trans[`status_${plan.status}`]}</span>
                        </div>
                        <div className="small text-secondary lh-sm">{plan.product_label}</div>
                    </div>

                    {editable && files.length > 0 && (
                        <select className="form-select form-select-sm" style={{ maxWidth: 220 }} value={plan.file?.id ?? ''}
                                title={trans.drawing_file} onChange={e => changeFile(e.target.value)}>
                            {!plan.file && <option value="">{trans.choose_file}</option>}
                            {files.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
                        </select>
                    )}

                    <div className="btn-group btn-group-sm" role="group">
                        <button type="button" className={`btn ${mode === 'select' ? 'btn-secondary' : 'btn-outline-secondary'}`}
                                title={`${trans.tool_select} (V)`} onClick={() => setMode('select')}><i className="fas fa-arrow-pointer" /></button>
                        <button type="button" className={`btn ${mode === 'draw' ? 'btn-primary' : 'btn-outline-primary'}`} disabled={!editable}
                                title={`${trans.tool_bubble} (B)`} onClick={() => setMode('draw')}><i className="fas fa-vector-square me-1" />{trans.tool_bubble}</button>
                    </div>

                    <div className="btn-group btn-group-sm">
                        <button type="button" className="btn btn-outline-secondary" title={trans.zoom_out} onClick={() => setZoom(z => Math.max(0.5, +(z - 0.25).toFixed(2)))}><i className="fas fa-magnifying-glass-minus" /></button>
                        <button type="button" className="btn btn-outline-secondary" title={trans.zoom_fit} onClick={() => setZoom(1)}>{Math.round(zoom * 100)} %</button>
                        <button type="button" className="btn btn-outline-secondary" title={trans.zoom_in} onClick={() => setZoom(z => Math.min(5, +(z + 0.25).toFixed(2)))}><i className="fas fa-magnifying-glass-plus" /></button>
                    </div>

                    <div className="btn-group btn-group-sm">
                        <button type="button" className="btn btn-outline-secondary" title={`${trans.rotate_left} (Maj + R)`} onClick={() => rotate(-90)}><i className="fas fa-rotate-left" /></button>
                        <button type="button" className="btn btn-outline-secondary" title={`${trans.rotate_right} (R)`} onClick={() => rotate(90)}><i className="fas fa-rotate-right" /></button>
                    </div>

                    {doc && doc.pageCount > 1 && (
                        <div className="btn-group btn-group-sm">
                            <button type="button" className="btn btn-outline-secondary" disabled={pageNumber <= 1} onClick={() => setPageNumber(n => n - 1)}><i className="fas fa-chevron-left" /></button>
                            <span className="btn btn-outline-secondary disabled">{trans.page} {pageNumber} / {doc.pageCount}</span>
                            <button type="button" className="btn btn-outline-secondary" disabled={pageNumber >= doc.pageCount} onClick={() => setPageNumber(n => n + 1)}><i className="fas fa-chevron-right" /></button>
                        </div>
                    )}

                    <div className="flex-grow-1" />
                    <small className="text-secondary">
                        {saving > 0 ? <><i className="fas fa-spinner fa-spin me-1" />{trans.saving}</> : editable ? <><i className="fas fa-check me-1" />{trans.saved}</> : null}
                    </small>
                    {editable && <button type="button" className="btn btn-sm btn-outline-secondary" onClick={renumber} title={trans.renumber_hint}><i className="fas fa-arrow-down-1-9 me-1" />{trans.renumber}</button>}
                    {editable && <button type="button" className="btn btn-sm btn-outline-danger" onClick={destroyPlan} title={trans.delete_plan}><i className="fas fa-trash" /></button>}
                    {plan.status === 'draft' && canApprove && (
                        <button type="button" className="btn btn-sm btn-success" disabled={busy || points.length === 0 || !plan.file} onClick={approve}>
                            <i className="fas fa-circle-check me-1" />{trans.approve}
                        </button>
                    )}
                    {plan.status !== 'draft' && canEdit && (
                        <button type="button" className="btn btn-sm btn-outline-primary" disabled={busy} onClick={newVersion}>
                            <i className="fas fa-code-branch me-1" />{trans.new_version}
                        </button>
                    )}
                </div>
                {plan.status === 'approved' && (
                    <div className="card-footer py-1 small text-secondary">
                        <i className="fas fa-lock me-1" />{trans.approved_locked.replace(':name', plan.approved_by ?? '—')}
                    </div>
                )}
            </div>

            {error && <div className="alert alert-danger py-2 alert-dismissible">{error}<button type="button" className="btn-close" onClick={() => setError(null)} /></div>}

            <div className="row g-2">
                <div className="col-lg-8 col-xl-9">
                    <div ref={viewportRef} className="border rounded bg-body-tertiary"
                         style={{ height: 'calc(100vh - 210px)', minHeight: 420, overflow: 'auto', position: 'relative' }}
                         onWheel={e => { if (e.ctrlKey) { e.preventDefault(); setZoom(z => Math.min(5, Math.max(0.5, +(z + (e.deltaY < 0 ? 0.1 : -0.1)).toFixed(2)))); } }}>
                        {!plan.file ? (
                            <div className="p-5 text-center text-secondary">
                                <i className="fas fa-file-circle-xmark fa-2x mb-2 d-block" />
                                {trans.no_plan_file_hint}
                                <div className="mt-3"><a className="btn btn-sm btn-primary" href={url(endpoints.productDocuments, plan.product_id)}>{trans.add_plan_file}</a></div>
                            </div>
                        ) : (
                            <div style={{ position: 'relative', margin: 12, width: pageSize.width || undefined, height: pageSize.height || undefined }}>
                                <canvas ref={canvasRef} style={{ display: 'block', boxShadow: '0 1px 6px rgba(0,0,0,.25)' }} />
                                {pageSize.width > 0 && (
                                    <Overlay points={pagePoints} selectedId={selectedId} width={pageSize.width} height={pageSize.height}
                                             mode={mode} editable={editable} onSelect={selectPoint} onDraw={createPoint}
                                             onMoveBubble={(id, pos) => {
                                                 const [bubble_x, bubble_y] = toPage(pos.x, pos.y, rotation);
                                                 updatePoint(id, { bubble_x, bubble_y });
                                             }} />
                                )}
                                {rendering && <div className="position-absolute top-0 end-0 m-2"><i className="fas fa-spinner fa-spin text-secondary" /></div>}
                            </div>
                        )}
                    </div>
                    {editable && plan.file && (
                        <div className="small text-secondary mt-1"><i className="fas fa-circle-info me-1" />{trans.draw_hint}</div>
                    )}
                </div>

                <div className="col-lg-4 col-xl-3" style={{ maxHeight: 'calc(100vh - 190px)', overflowY: 'auto' }}>
                    {selected && (
                        <PointForm point={selected} services={services} trans={trans} editable={editable}
                                   onChange={updatePoint} onDelete={deletePoint} errors={pointErrors[selected.id]}
                                   reading={reading[selected.id]} canReread={aiReading || !!doc}
                                   onReread={p => readZone(p, { forceAi: aiReading })} />
                    )}
                    <div className="d-flex align-items-center mb-1">
                        <strong className="flex-grow-1">{trans.characteristics} ({points.length})</strong>
                        <small className="text-secondary">{points.filter(p => p.is_critical).length} {trans.critical_short}</small>
                    </div>
                    <PointList points={points} selectedId={selectedId} onSelect={selectPoint} trans={trans} />
                </div>
            </div>
        </div>
    );
}
