import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { apiFetch, csrfToken } from '../../lib/http';
import { restoreMapping, saveMapping, requiredUnmapped, columnLabel } from '../../lib/importWizard';

/**
 * Assistant d'import (Votre société › Imports/Exports) :
 *   1. type + dépôt du fichier,
 *   2. correspondance champ → colonne (proposée d'après les en-têtes) et aperçu
 *      simulé par le serveur (créée / mise à jour / rejetée, avec la raison),
 *   3. import et résultat.
 *
 * Le serveur garde le fichier déposé (jeton) le temps de l'assistant ; la
 * correspondance choisie est mémorisée par type dans ce navigateur.
 */

const TYPE_ICONS = {
    companies: 'fa-solid fa-building',
    products:  'fa-solid fa-cubes',
    quotes:    'fa-solid fa-file-signature',
    orders:    'fa-solid fa-cart-shopping',
};

const fill = (text, values) => Object.entries(values).reduce((s, [k, v]) => s.replaceAll(`:${k}`, v), text ?? '');

export default function ImportWizard({ endpoints, trans: t, types, options: selectOptions, lists }) {
    const [type, setType]         = useState(types.find(x => x.enabled)?.key ?? 'companies');
    const [step, setStep]         = useState(1);
    const [file, setFile]         = useState(null);      // réponse de preview
    const [uploading, setUploading] = useState(false);
    const [error, setError]       = useState(null);
    const [header, setHeader]     = useState(true);
    const [mapping, setMapping]   = useState({});
    const [suggested, setSuggested] = useState({});
    const [options, setOptions]   = useState({ purchased: '2', sold: '1' });
    const [check, setCheck]       = useState(null);
    const [checking, setChecking] = useState(false);
    const [running, setRunning]   = useState(false);
    const [result, setResult]     = useState(null);
    const [over, setOver]         = useState(false);
    const inputRef = useRef(null);

    const url = (name) => endpoints[name].replace('__TYPE__', type);
    const typeInfo = types.find(x => x.key === type);

    // ── Étape 1 : dépôt ────────────────────────────────────────────────────

    const upload = async (picked) => {
        if (!picked || uploading) return;
        setUploading(true); setError(null);
        const fd = new FormData();
        fd.append('file', picked);
        try {
            const res  = await fetch(url('preview'), { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw data;
            const restored = restoreMapping(type, data.columns, data.mapping);
            setFile(data);
            setSuggested(data.mapping);
            setMapping(restored);
            setHeader(true);
            setCheck(null);
            setStep(2);
        } catch (e) {
            setError(e?.errors?.file?.[0] ?? e?.message ?? 'Erreur');
        } finally {
            setUploading(false);
            if (inputRef.current) inputRef.current.value = '';
        }
    };

    // ── Étape 2 : simulation à chaque changement ───────────────────────────

    const missing = useMemo(() => file ? requiredUnmapped(file.fields, mapping) : [], [file, mapping]);
    const productOptionsMissing = type === 'products'
        && !(options.methods_services_id && options.methods_families_id && options.methods_units_id);

    const payload = useCallback(() => ({
        token: file?.token, filename: file?.filename, mapping, header, options: type === 'products' ? options : {},
    }), [file, mapping, header, options, type]);

    useEffect(() => {
        if (step !== 2 || !file) return;
        if (missing.length || productOptionsMissing) { setCheck(null); return; }
        let cancelled = false;
        setChecking(true);
        const timer = setTimeout(async () => {
            try {
                const data = await apiFetch(url('check'), { method: 'POST', body: JSON.stringify(payload()) });
                if (!cancelled) { setCheck(data); setError(null); }
            } catch (e) {
                if (!cancelled) { setCheck(null); setError(e?.message); }
            } finally {
                if (!cancelled) setChecking(false);
            }
        }, 350);
        return () => { cancelled = true; clearTimeout(timer); };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [step, file, mapping, header, options, missing.length, productOptionsMissing]);

    // ── Étape 3 : import ───────────────────────────────────────────────────

    const run = async () => {
        setRunning(true); setError(null);
        try {
            const data = await apiFetch(url('run'), { method: 'POST', body: JSON.stringify(payload()) });
            if (header) saveMapping(type, file.columns, mapping);
            setResult(data);
            setStep(3);
        } catch (e) {
            setError(e?.message ?? 'Erreur');
        } finally {
            setRunning(false);
        }
    };

    const restart = () => {
        setStep(1); setFile(null); setCheck(null); setResult(null); setError(null); setMapping({});
    };

    const chooseType = (key) => {
        if (key === type) return;
        setType(key);
        restart();
    };

    // ── Rendu ──────────────────────────────────────────────────────────────

    const dataRows   = file ? (header ? file.rows.slice(1) : file.rows).slice(0, 20) : [];
    const mapped     = file ? file.fields.filter(f => mapping[f.key] !== undefined && mapping[f.key] !== null) : [];
    const firstData  = dataRows[0]?.cells ?? [];
    const toImport   = check ? check.created + check.updated : 0;

    return (
        <div className="wem-import">
            <Stepper step={step} t={t} />

            {error && <div className="alert alert-warning py-2"><i className="fa-solid fa-triangle-exclamation me-2" />{error}</div>}

            {step === 1 && (
                <>
                    <h5 className="mb-3">{t.what}</h5>
                    <div className="row g-3 mb-3">
                        {types.map(x => (
                            <div className="col-6 col-lg-3" key={x.key}>
                                <button type="button" disabled={!x.enabled}
                                        className={`wem-import-type card h-100 w-100 text-start ${type === x.key ? 'is-active' : ''}`}
                                        onClick={() => chooseType(x.key)}>
                                    <div className="card-body">
                                        <i className={`${TYPE_ICONS[x.key]} fa-lg mb-2`} />
                                        <div className="fw-semibold">{x.label}</div>
                                        <div className="small text-body-secondary">{x.hint}</div>
                                    </div>
                                </button>
                            </div>
                        ))}
                    </div>

                    {(type === 'quotes' || type === 'orders') && (
                        <div className="alert alert-info py-2 small"><i className="fa-solid fa-circle-info me-2" />{t.order_hint}</div>
                    )}

                    <div className={`wem-dropzone ${over ? 'is-over' : ''} ${uploading ? 'is-busy' : ''}`}
                         role="button" tabIndex={0}
                         onClick={() => !uploading && inputRef.current?.click()}
                         onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') inputRef.current?.click(); }}
                         onDragOver={(e) => { e.preventDefault(); setOver(true); }}
                         onDragLeave={() => setOver(false)}
                         onDrop={(e) => { e.preventDefault(); setOver(false); upload(e.dataTransfer.files?.[0]); }}>
                        <input ref={inputRef} type="file" className="d-none" accept=".xlsx,.xls,.csv,.txt"
                               onChange={(e) => upload(e.target.files?.[0])} />
                        {uploading
                            ? <><i className="fa-solid fa-spinner fa-spin fa-2x mb-2" /><div>{t.reading}</div></>
                            : <>
                                <i className="fa-solid fa-file-excel fa-2x mb-2 text-success" />
                                <div className="fw-semibold">{t.drop}</div>
                                <div className="small mt-1">{t.drop_hint}</div>
                              </>}
                    </div>

                    <div className="mt-2 small">
                        <a href={url('template')}><i className="fa-solid fa-download me-1" />{t.template} — {typeInfo?.label}</a>
                    </div>
                </>
            )}

            {step === 2 && file && (
                <>
                    <div className="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 rounded bg-body-tertiary">
                        <i className={`${TYPE_ICONS[type]} text-primary`} />
                        <span className="fw-semibold">{typeInfo?.label}</span>
                        <span className="text-body-secondary">·</span>
                        <i className="fa-solid fa-file-lines text-body-secondary" />
                        <span className="text-truncate" style={{ maxWidth: 320 }}>{file.filename}</span>
                        <span className="badge text-bg-secondary">{fill(t.file_summary, { rows: header ? file.total - 1 : file.total, columns: file.columns.length })}</span>
                        <button type="button" className="btn btn-sm btn-outline-secondary ms-auto" onClick={restart}>
                            <i className="fa-solid fa-rotate-left me-1" />{t.change_file}
                        </button>
                    </div>

                    <div className="row g-3">
                        <div className="col-xl-5">
                            <div className="card mb-3">
                                <div className="card-header d-flex align-items-center">
                                    <span className="fw-semibold"><i className="fa-solid fa-right-left me-2" />{t.step_mapping}</span>
                                    <button type="button" className="btn btn-sm btn-link ms-auto"
                                            onClick={() => setMapping(file.mapping)}>{t.reset_mapping}</button>
                                </div>
                                <div className="card-body pb-2">
                                    <div className="form-check form-switch mb-3">
                                        <input className="form-check-input" type="checkbox" role="switch" id="import-header"
                                               checked={header} onChange={(e) => setHeader(e.target.checked)} />
                                        <label className="form-check-label" htmlFor="import-header">{t.has_header}</label>
                                    </div>

                                    {file.fields.map(f => {
                                        const col = mapping[f.key];
                                        const isSet = col !== undefined && col !== null;
                                        return (
                                            <div className="row g-2 align-items-center mb-2" key={f.key}>
                                                <div className="col-5">
                                                    <label className="form-label mb-0 small fw-semibold" htmlFor={`map-${f.key}`}>{f.label}</label>
                                                    {f.required && <span className={`badge ms-1 ${isSet ? 'text-bg-light border' : 'text-bg-danger'}`}>{t.required}</span>}
                                                </div>
                                                <div className="col-7">
                                                    <select id={`map-${f.key}`}
                                                            className={`form-select form-select-sm ${f.required && !isSet ? 'is-invalid' : ''}`}
                                                            value={isSet ? String(col) : ''}
                                                            onChange={(e) => setMapping(m => ({ ...m, [f.key]: e.target.value === '' ? null : Number(e.target.value) }))}>
                                                        <option value="">{t.not_imported}</option>
                                                        {file.columns.map(c => (
                                                            <option key={c.index} value={c.index}>{columnLabel(c, header, t)}</option>
                                                        ))}
                                                    </select>
                                                    {isSet && (
                                                        <div className="small text-body-secondary text-truncate mt-1" title={firstData[col] ?? ''}>
                                                            {suggested[f.key] === col && <span className="badge text-bg-success me-1">{t.auto}</span>}
                                                            {t.sample} : {firstData[col] ?? '—'}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {type === 'products' && (
                                <div className="card mb-3">
                                    <div className="card-header fw-semibold"><i className="fa-solid fa-sliders me-2" />{t.options}</div>
                                    <div className="card-body row g-2">
                                        <OptionSelect id="methods_services_id" label={t.service} items={selectOptions.services} options={options} setOptions={setOptions} t={t} />
                                        <OptionSelect id="methods_families_id" label={t.family} items={selectOptions.families} options={options} setOptions={setOptions} t={t} />
                                        <OptionSelect id="methods_units_id" label={t.unit} items={selectOptions.units} options={options} setOptions={setOptions} t={t} />
                                        <OptionSelect id="purchased" label={t.purchased} items={[{ id: '1', label: t.yes }, { id: '2', label: t.no }]} options={options} setOptions={setOptions} t={t} small />
                                        <OptionSelect id="sold" label={t.sold} items={[{ id: '1', label: t.yes }, { id: '2', label: t.no }]} options={options} setOptions={setOptions} t={t} small />
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="col-xl-7">
                            <div className="card mb-3">
                                <div className="card-header d-flex flex-wrap align-items-center gap-2">
                                    <span className="fw-semibold"><i className="fa-solid fa-eye me-2" />{t.preview}</span>
                                    {checking && <span className="small text-body-secondary"><i className="fa-solid fa-spinner fa-spin me-1" />{t.checking}</span>}
                                    {check && !checking && (
                                        <span className="ms-auto d-flex gap-1 flex-wrap">
                                            <span className="badge text-bg-success">{check.created} {t.to_create}</span>
                                            {check.updated > 0 && <span className="badge text-bg-info">{check.updated} {t.to_update}</span>}
                                            {check.rejected > 0 && <span className="badge text-bg-danger">{check.rejected} {t.to_reject}</span>}
                                        </span>
                                    )}
                                </div>
                                <div className="card-body p-0">
                                    {(missing.length > 0 || productOptionsMissing) && (
                                        <div className="alert alert-warning m-2 py-2 small">
                                            <i className="fa-solid fa-hand-point-left me-2" />
                                            {missing.length > 0 ? `${t.required} : ${missing.map(f => f.label).join(', ')}` : t.options}
                                        </div>
                                    )}
                                    <div className="table-responsive" style={{ maxHeight: 460 }}>
                                        <table className="table table-sm table-hover mb-0 small align-middle">
                                            <thead className="table-light" style={{ position: 'sticky', top: 0 }}>
                                                <tr>
                                                    <th className="text-nowrap">{t.line}</th>
                                                    <th />
                                                    {mapped.map(f => <th key={f.key} className="text-nowrap">{f.label}</th>)}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {dataRows.map(row => {
                                                    const status = check?.lines?.[row.line];
                                                    return (
                                                        <tr key={row.line} className={status?.status === 'reject' ? 'table-danger' : ''}>
                                                            <td className="text-body-secondary">{row.line}</td>
                                                            <td className="text-nowrap"><StatusBadge status={status} t={t} /></td>
                                                            {mapped.map(f => (
                                                                <td key={f.key} className="text-truncate" style={{ maxWidth: 220 }} title={row.cells[mapping[f.key]] ?? ''}>
                                                                    {row.cells[mapping[f.key]] ?? <span className="text-body-tertiary">—</span>}
                                                                </td>
                                                            ))}
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div className="card-footer small text-body-secondary">{fill(t.preview_hint, { count: dataRows.length })}</div>
                            </div>

                            {check?.rejects?.length > 0 && <RejectList rejects={check.rejects} total={check.rejected} t={t} />}
                        </div>
                    </div>

                    <div className="d-flex gap-2 justify-content-end">
                        <button type="button" className="btn btn-outline-secondary" onClick={restart}>{t.back}</button>
                        <button type="button" className="btn btn-primary"
                                disabled={running || checking || !check || toImport === 0}
                                onClick={run}>
                            {running
                                ? <><i className="fa-solid fa-spinner fa-spin me-1" />{t.importing}</>
                                : <><i className="fa-solid fa-file-import me-1" />{fill(t.import, { count: toImport })}</>}
                        </button>
                    </div>
                </>
            )}

            {step === 3 && result && (
                <div className="text-center py-3">
                    {result.created + result.updated > 0
                        ? <i className="fa-solid fa-circle-check fa-3x text-success mb-3" />
                        : <i className="fa-solid fa-circle-exclamation fa-3x text-warning mb-3" />}
                    <h4>{result.created + result.updated > 0 ? t.done : t.nothing_done}</h4>
                    <p className="lead">{result.message}</p>
                    <div className="d-flex justify-content-center gap-2 mb-3">
                        {lists[type] && <a className="btn btn-primary" href={lists[type]}><i className="fa-solid fa-list me-1" />{t.open_list}</a>}
                        <button type="button" className="btn btn-outline-secondary" onClick={restart}><i className="fa-solid fa-plus me-1" />{t.again}</button>
                    </div>
                    {result.rejects?.length > 0 && (
                        <div className="text-start mx-auto" style={{ maxWidth: 720 }}>
                            <RejectList rejects={result.rejects} total={result.rejected} t={t} open />
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

function Stepper({ step, t }) {
    const steps = [t.step_file, t.step_mapping, t.step_result];
    return (
        <ol className="wem-import-steps mb-4">
            {steps.map((label, i) => (
                <li key={label} className={i + 1 === step ? 'is-current' : (i + 1 < step ? 'is-done' : '')}>
                    <span className="wem-import-step-dot">{i + 1 < step ? <i className="fa-solid fa-check" /> : i + 1}</span>
                    <span className="d-none d-sm-inline">{label}</span>
                </li>
            ))}
        </ol>
    );
}

function StatusBadge({ status, t }) {
    if (!status) return null;
    if (status.status === 'create') return <span className="badge text-bg-success">{t.status_create}</span>;
    if (status.status === 'update') return <span className="badge text-bg-info">{t.status_update}</span>;
    return (
        <span>
            <span className="badge text-bg-danger">{t.status_reject}</span>
            <div className="text-danger-emphasis" style={{ whiteSpace: 'normal', minWidth: 160 }}>{status.reason}</div>
        </span>
    );
}

function RejectList({ rejects, total, t, open = false }) {
    return (
        <details className="card mb-3" open={open}>
            <summary className="card-header fw-semibold text-danger" style={{ cursor: 'pointer' }}>
                <i className="fa-solid fa-ban me-2" />{t.all_rejects} ({total})
            </summary>
            <ul className="list-group list-group-flush small" style={{ maxHeight: 260, overflowY: 'auto' }}>
                {rejects.map(r => (
                    <li key={r.line} className="list-group-item py-1">
                        <span className="badge text-bg-light border me-2">{t.line} {r.line}</span>{r.reason}
                    </li>
                ))}
            </ul>
        </details>
    );
}

function OptionSelect({ id, label, items, options, setOptions, t, small = false }) {
    return (
        <div className={small ? 'col-6 col-md-3 col-xl-6' : 'col-md-6 col-xl-12'}>
            <label className="form-label small fw-semibold mb-0" htmlFor={`opt-${id}`}>{label}</label>
            <select id={`opt-${id}`} className={`form-select form-select-sm ${!small && !options[id] ? 'is-invalid' : ''}`}
                    value={options[id] ?? ''}
                    onChange={(e) => setOptions(o => ({ ...o, [id]: e.target.value || undefined }))}>
                {!small && <option value="">{t.choose}</option>}
                {items.map(x => <option key={x.id} value={x.id}>{x.label}</option>)}
            </select>
        </div>
    );
}
