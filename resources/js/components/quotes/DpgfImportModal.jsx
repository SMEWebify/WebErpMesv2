import React, { useState } from 'react';
import { apiFetch, csrfToken } from '../../lib/http';
import { fill } from '../../lib/quoteDictation.js';
import { DPGF_FIELDS, DPGF_TYPES, toDrafts, toPayload, summarize } from '../../lib/dpgfImport.js';

const ROW_CLASS = {
    section: 'fw-bold table-light',
    subtotal: 'fst-italic',
    text: 'text-muted',
    ignore: 'opacity-50',
};

// Import d'un DPGF / BPU / DQE : dépôt du classeur → aperçu (onglet, en-tête,
// colonnes, type de chaque ligne) recalculé par le serveur → correction → création.
// Rien n'est enregistré avant « Créer ».
export default function DpgfImportModal({ endpoints, units = [], trans: t = {}, onCreated, onClose }) {
    const [analysis, setAnalysis] = useState(null);   // réponse de preview / analyse
    const [drafts, setDrafts]     = useState([]);
    const [busy, setBusy]         = useState(null);   // reading | creating
    const [error, setError]       = useState(null);

    const apply = (data) => {
        setAnalysis(data);
        setDrafts(toDrafts(data.lines));
    };

    const upload = async (file) => {
        if (!file) return;
        setBusy('reading');
        setError(null);
        try {
            const form = new FormData();
            form.append('file', file);
            const res = await fetch(endpoints.dpgfPreview, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: form,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw data;
            apply(data);
        } catch (e) {
            setError(e?.message ?? t.failed);
        } finally {
            setBusy(null);
        }
    };

    // Nouvel onglet : en-tête et colonnes redétectés ; sinon les choix de l'utilisateur.
    const reanalyse = async (changes) => {
        setBusy('reading');
        setError(null);
        try {
            const body = 'sheet' in changes
                ? { token: analysis.token, sheet: changes.sheet }
                : {
                    token: analysis.token,
                    sheet: analysis.sheet,
                    header_row: changes.header_row ?? analysis.header_row,
                    columns: { ...analysis.columns, ...(changes.columns ?? {}) },
                };
            const data = await apiFetch(endpoints.dpgfAnalyse, { method: 'POST', body: JSON.stringify(body) });
            apply({ ...data, filename: analysis.filename });
        } catch (e) {
            setError(e?.message ?? t.failed);
        } finally {
            setBusy(null);
        }
    };

    const create = async () => {
        setBusy('creating');
        setError(null);
        try {
            const data = await apiFetch(endpoints.dpgfStore, {
                method: 'POST',
                body: JSON.stringify({ token: analysis.token, filename: analysis.filename, lines: toPayload(drafts) }),
            });
            onCreated(data.lines ?? []);
        } catch (e) {
            setError(e?.message ?? t.failed);
            setBusy(null);
        }
    };

    const update = (key, field, value) =>
        setDrafts((ds) => ds.map((d) => (d.key === key ? { ...d, [field]: value } : d)));

    const summary = summarize(drafts);
    const fields = t.fields ?? {};
    const types = t.types ?? {};
    const columnOption = (h) => `${h.letter}${h.header ? ` — ${h.header}` : ''}`;

    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.5)' }}>
            <div className="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title"><i className="fas fa-file-excel me-2" />{t.title}</h5>
                        <button type="button" onClick={onClose} aria-label="Close" className="btn-close" disabled={busy === 'creating'}></button>
                    </div>
                    <div className="modal-body">
                        {error && <div className="alert alert-danger py-2">{error}</div>}

                        {!analysis && (
                            <>
                                <p>{t.intro}</p>
                                <label className="small fw-bold mb-1" htmlFor="dpgf-file">{t.file}</label>
                                <input id="dpgf-file" type="file" className="form-control" accept=".xlsx,.xls,.csv,.txt"
                                    disabled={busy !== null} onChange={(e) => upload(e.target.files?.[0])} />
                                <p className="small text-muted mt-2 mb-0">{t.ged_note}</p>
                            </>
                        )}

                        {busy === 'reading' && (
                            <div className="alert alert-info py-2 mt-2"><span className="spinner-border spinner-border-sm me-2" />{t.reading}</div>
                        )}

                        {analysis && (
                            <>
                                <div className="row g-2 align-items-end mb-2">
                                    <div className="col-12 col-md-3">
                                        <div className="small text-muted text-truncate" title={analysis.filename}>
                                            <i className="fas fa-file me-1" />{analysis.filename}
                                        </div>
                                        {analysis.sheets.length > 1 && (
                                            <>
                                                <label className="small mb-0">{t.sheet}</label>
                                                <select className="form-select form-select-sm" value={analysis.sheet} disabled={busy !== null}
                                                    onChange={(e) => reanalyse({ sheet: Number(e.target.value) })}>
                                                    {analysis.sheets.map((name, i) => <option key={i} value={i}>{name || i + 1}</option>)}
                                                </select>
                                            </>
                                        )}
                                    </div>
                                    <div className="col-4 col-md-1">
                                        <label className="small mb-0">{t.header_row}</label>
                                        <input type="number" min="0" className="form-control form-control-sm" defaultValue={analysis.header_row}
                                            key={`${analysis.sheet}-${analysis.header_row}`} disabled={busy !== null}
                                            onBlur={(e) => {
                                                const v = Math.max(0, parseInt(e.target.value, 10) || 0);
                                                if (v !== analysis.header_row) reanalyse({ header_row: v });
                                            }} />
                                    </div>
                                    {DPGF_FIELDS.map((field) => (
                                        <div key={field} className="col-4 col-md">
                                            <label className="small mb-0">{fields[field] ?? field}</label>
                                            <select className={`form-select form-select-sm ${field === 'label' && analysis.columns.label === null ? 'is-invalid' : ''}`}
                                                value={analysis.columns[field] ?? ''} disabled={busy !== null}
                                                onChange={(e) => reanalyse({ columns: { [field]: e.target.value === '' ? null : Number(e.target.value) } })}>
                                                <option value="">{t.column_none ?? '—'}</option>
                                                {analysis.headers.map((h) => <option key={h.index} value={h.index}>{columnOption(h)}</option>)}
                                            </select>
                                        </div>
                                    ))}
                                </div>

                                {analysis.truncated && <div className="alert alert-warning py-2">{fill(t.truncated, { max: t.max_rows })}</div>}

                                {analysis.columns.label === null && (
                                    <>
                                        <div className="alert alert-warning py-2">{t.not_detected}</div>
                                        <h6 className="small fw-bold">{t.sample}</h6>
                                        <div className="table-responsive" style={{ maxHeight: 320 }}>
                                            <table className="table table-sm table-bordered small mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>{t.row}</th>
                                                        {analysis.headers.map((h) => <th key={h.index}>{h.letter}</th>)}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {analysis.sample.map((r) => (
                                                        <tr key={r.row}>
                                                            <td className="text-muted">{r.row}</td>
                                                            {r.cells.map((c, i) => <td key={i} className="text-truncate" style={{ maxWidth: 200 }}>{c}</td>)}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </>
                                )}

                                {analysis.columns.label !== null && drafts.length === 0 && (
                                    <div className="alert alert-warning py-2">{t.no_lines}</div>
                                )}

                                {drafts.length > 0 && (
                                    <>
                                        <p className="small mb-1">
                                            {fill(t.summary, {
                                                articles: summary.counts.article, sections: summary.counts.section,
                                                subtotals: summary.counts.subtotal, texts: summary.counts.text, ignored: summary.counts.ignore,
                                            })}
                                        </p>
                                        {summary.qtyMissing > 0 && <div className="small text-warning-emphasis">{fill(t.qty_missing, { count: summary.qtyMissing })}</div>}
                                        {summary.unitUnknown > 0 && <div className="small text-danger">{fill(t.unit_unknown, { count: summary.unitUnknown })}</div>}

                                        <div className="table-responsive mt-2" style={{ maxHeight: '55vh' }}>
                                            <table className="table table-sm table-hover align-middle small mb-0">
                                                <thead className="sticky-top bg-body">
                                                    <tr>
                                                        <th style={{ width: 50 }}>{t.row}</th>
                                                        <th style={{ width: 120 }}>{t.type}</th>
                                                        <th style={{ width: 90 }}>{fields.code}</th>
                                                        <th>{t.label}</th>
                                                        <th style={{ width: 90 }}>{t.qty}</th>
                                                        <th style={{ width: 150 }}>{t.unit}</th>
                                                        <th style={{ width: 110 }}>{t.price}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {drafts.map((d) => {
                                                        const article = d.type === 'article';
                                                        const off = d.type === 'ignore' || busy !== null;
                                                        const noQty = article && !(parseFloat(String(d.qty).replace(',', '.')) > 0);
                                                        return (
                                                            <tr key={d.key} className={ROW_CLASS[d.type] ?? ''}>
                                                                <td className="text-muted">{d.row}</td>
                                                                <td>
                                                                    <select className="form-select form-select-sm" value={d.type} disabled={busy !== null}
                                                                        onChange={(e) => update(d.key, 'type', e.target.value)}>
                                                                        {DPGF_TYPES.map((type) => <option key={type} value={type}>{types[type] ?? type}</option>)}
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    {article && (
                                                                        <input type="text" className="form-control form-control-sm" maxLength={255}
                                                                            value={d.code} onChange={(e) => update(d.key, 'code', e.target.value)} disabled={off} />
                                                                    )}
                                                                </td>
                                                                <td>
                                                                    <input type="text" className="form-control form-control-sm" maxLength={255}
                                                                        value={d.label} onChange={(e) => update(d.key, 'label', e.target.value)} disabled={off} />
                                                                    {d.description && <div className="small text-muted"><i className="fas fa-align-left me-1" />{t.long_label}</div>}
                                                                </td>
                                                                <td>
                                                                    {article && (
                                                                        <input type="number" min="0" step="any" className={`form-control form-control-sm ${noQty ? 'is-invalid' : ''}`}
                                                                            value={d.qty} onChange={(e) => update(d.key, 'qty', e.target.value)} disabled={off} />
                                                                    )}
                                                                </td>
                                                                <td>
                                                                    {article && (
                                                                        <>
                                                                            <select className={`form-select form-select-sm ${d.unit_text && !d.methods_units_id ? 'is-invalid' : ''}`}
                                                                                value={d.methods_units_id} onChange={(e) => update(d.key, 'methods_units_id', e.target.value)} disabled={off}>
                                                                                <option value="">—</option>
                                                                                {units.map((u) => <option key={u.id} value={u.id}>{u.code} - {u.label}</option>)}
                                                                            </select>
                                                                            {d.unit_text && !d.methods_units_id && <div className="small text-danger">{fill(t.unit_was, { unit: d.unit_text })}</div>}
                                                                        </>
                                                                    )}
                                                                </td>
                                                                <td>
                                                                    {article && (
                                                                        <input type="number" min="0" step="0.01" placeholder="0" className="form-control form-control-sm"
                                                                            value={d.selling_price} onChange={(e) => update(d.key, 'selling_price', e.target.value)} disabled={off} />
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                        {analysis.sheets.length > 1 && <p className="small text-muted mt-2 mb-0">{t.other_sheet}</p>}
                                    </>
                                )}
                            </>
                        )}
                    </div>
                    <div className="modal-footer">
                        {analysis && (
                            <button type="button" className="btn btn-outline-secondary btn-sm me-auto" disabled={busy !== null}
                                onClick={() => { setAnalysis(null); setDrafts([]); setError(null); }}>
                                <i className="fas fa-arrow-left me-1" />{t.back}
                            </button>
                        )}
                        <button type="button" className="btn btn-secondary btn-sm" onClick={onClose} disabled={busy === 'creating'}>{t.cancel ?? 'Annuler'}</button>
                        {analysis && (
                            <button type="button" className="btn btn-primary btn-sm" disabled={busy !== null || summary.kept === 0} onClick={create}>
                                {busy === 'creating' ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-check me-1" />}
                                {fill(t.create, { count: summary.kept })}
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
