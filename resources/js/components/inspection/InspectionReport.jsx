import React, { useEffect, useRef, useState } from 'react';
import { apiFetch } from '../../lib/http';

const fmtDate = (v, locale) => (v ? new Date(v).toLocaleDateString(locale) : '');

/** Case du formulaire 1 : intitulé (numéroté en format AS9102) + valeur ou saisie. */
function Box({ n, label, as9102, children, wide = 1 }) {
    return (
        <div className={`col-md-${3 * wide}`}>
            <div className="border rounded p-2 h-100">
                <div className="small text-secondary">{as9102 && n ? `${n}. ` : ''}{label}</div>
                {children}
            </div>
        </div>
    );
}

function ReadOnly({ value }) {
    return <div className="fw-semibold">{value || <span className="text-secondary">—</span>}</div>;
}

/**
 * Champ saisi : enregistré à la sortie du champ. `locked` : rapport approuvé.
 */
function Editable({ value, onSave, locked, placeholder }) {
    const [v, setV] = useState(value ?? '');
    useEffect(() => { setV(value ?? ''); }, [value]);
    return (
        <input type="text" className="form-control form-control-sm" value={v} disabled={locked} placeholder={placeholder}
               maxLength={255} onChange={e => setV(e.target.value)}
               onBlur={() => { if ((value ?? '') !== v) onSave(v || null); }} />
    );
}

function Signature({ label, n, date, by, as9102, canSign, signed, onSign, onUnsign, locked, trans, locale }) {
    return (
        <div className="row g-2 mb-2 align-items-stretch">
            <Box n={n} label={label} as9102={as9102} wide={2}>
                {signed
                    ? <div className="fs-5" style={{ fontFamily: '"Brush Script MT", "Segoe Script", cursive' }}>{by}</div>
                    : <div className="text-secondary">—</div>}
            </Box>
            <Box n={n + 1} label={trans.f_date} as9102={as9102}>
                <ReadOnly value={fmtDate(date, locale)} />
            </Box>
            <div className="col-md-3 d-flex align-items-center">
                {canSign && !signed && (
                    <button type="button" className="btn btn-sm btn-outline-primary" disabled={locked} onClick={onSign}>
                        <i className="fas fa-signature me-1" />{trans.sign_and_date}
                    </button>
                )}
                {canSign && signed && (
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onUnsign}>
                        <i className="fas fa-eraser me-1" />{trans.unsign}
                    </button>
                )}
            </div>
        </div>
    );
}

export default function InspectionReport({ endpoints, trans }) {
    const [report, setReport] = useState(null);
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);
    const [comment, setComment] = useState('');
    const locale = trans.locale || undefined;
    const commentTimer = useRef(null);

    const accept = (data) => { setReport(data); setComment(data.form1.comment ?? ''); setError(null); };
    const fail = (err) => setError(err?.message ?? trans.save_error);

    useEffect(() => { apiFetch(endpoints.show).then(accept).catch(fail); }, []);

    if (error && !report) return <div className="alert alert-danger">{error}</div>;
    if (!report) return <div className="text-center p-4"><i className="fas fa-spinner fa-spin fa-2x text-secondary" /></div>;

    const f1 = report.form1;
    const as9102 = report.format === 'as9102';
    const locked = !!f1.approved_at;

    const patch = (body) => apiFetch(endpoints.update, { method: 'PATCH', body: JSON.stringify(body) }).then(accept).catch(fail);
    const field = (key) => (value) => patch({ fields: { [key]: value } });
    const sign = (role, value) => apiFetch(endpoints.sign, { method: 'POST', body: JSON.stringify({ role, sign: value }) }).then(accept).catch(fail);

    const generate = () => {
        setBusy(true);
        apiFetch(endpoints.generate, { method: 'POST' })
            .then(data => { accept(data); window.open(data.file.view_url, '_blank', 'noopener'); })
            .catch(fail)
            .finally(() => setBusy(false));
    };

    return (
        <div>
            {error && <div className="alert alert-danger py-2">{error}</div>}
            {!report.closed && (
                <div className="alert alert-warning py-2 small">
                    <i className="fas fa-triangle-exclamation me-1" />{trans.report_not_closed}
                    {endpoints.control && <a className="ms-2" href={endpoints.control}>{trans.control_tab}</a>}
                </div>
            )}

            <div className="card mb-3">
                <div className="card-header d-flex align-items-center gap-2">
                    <strong className="flex-grow-1">{as9102 ? trans.form1_title_as9102 : trans.form1_title}</strong>
                    {locked && <span className="badge text-bg-success"><i className="fas fa-lock me-1" />{trans.report_approved}</span>}
                    {report.report_file_url && (
                        <a className="btn btn-sm btn-outline-secondary" href={report.report_file_url} target="_blank" rel="noreferrer">
                            <i className="fas fa-file-pdf me-1" />{trans.last_pdf}
                        </a>
                    )}
                    <button type="button" className="btn btn-sm btn-primary" disabled={busy} onClick={generate}>
                        <i className={`fas ${busy ? 'fa-spinner fa-spin' : 'fa-file-pdf'} me-1`} />{trans.generate_pdf}
                    </button>
                </div>
                <div className="card-body">
                    <div className="row g-2 mb-2">
                        <Box n={1} label={trans.f_part_number} as9102={as9102}><ReadOnly value={f1.part_number} /></Box>
                        <Box n={2} label={trans.f_part_name} as9102={as9102}><ReadOnly value={f1.part_name} /></Box>
                        <Box n={3} label={trans.f_serial_number} as9102={as9102}><Editable value={f1.serial_number} locked={locked} onSave={field('serial_number')} /></Box>
                        <Box n={4} label={trans.f_fair_identifier} as9102={as9102}><ReadOnly value={f1.fair_identifier} /></Box>
                    </div>
                    <div className="row g-2 mb-2">
                        <Box n={5} label={trans.f_part_revision} as9102={as9102}><ReadOnly value={f1.part_revision} /></Box>
                        <Box n={6} label={trans.f_drawing_number} as9102={as9102}><Editable value={f1.drawing_number} locked={locked} onSave={field('drawing_number')} /></Box>
                        <Box n={7} label={trans.f_drawing_revision} as9102={as9102}><Editable value={f1.drawing_revision} locked={locked} onSave={field('drawing_revision')} /></Box>
                        <Box n={8} label={trans.f_additional_changes} as9102={as9102}><Editable value={f1.additional_changes} locked={locked} onSave={field('additional_changes')} placeholder="N/A" /></Box>
                    </div>
                    <div className="row g-2 mb-2">
                        <Box n={9} label={trans.f_process_reference} as9102={as9102}><Editable value={f1.process_reference} locked={locked} onSave={field('process_reference')} /></Box>
                        <Box n={10} label={trans.f_organization} as9102={as9102}><ReadOnly value={f1.organization} /></Box>
                        <Box n={11} label={trans.f_supplier_code} as9102={as9102}><Editable value={f1.supplier_code} locked={locked} onSave={field('supplier_code')} /></Box>
                        <Box n={12} label={trans.f_po_number} as9102={as9102}><Editable value={f1.po_number} locked={locked} onSave={field('po_number')} /></Box>
                    </div>
                    <div className="row g-2 mb-3">
                        <Box n={13} label={trans.f_detail_assembly} as9102={as9102}>
                            <select className="form-select form-select-sm" disabled={locked} value={f1.detail_assembly}
                                    onChange={e => field('detail_assembly')(e.target.value)}>
                                <option value="detail">{trans.detail_assembly_detail}</option>
                                <option value="assembly">{trans.detail_assembly_assembly}</option>
                            </select>
                        </Box>
                        <Box n={14} label={trans.f_fai_type} as9102={as9102}>
                            <select className="form-select form-select-sm" disabled={locked} value={f1.fai_type}
                                    onChange={e => patch({ fai_type: e.target.value })}>
                                <option value="full">{trans.fai_type_full}</option>
                                <option value="partial">{trans.fai_type_partial}</option>
                            </select>
                        </Box>
                        <Box label={trans.f_partial_reason} as9102={as9102} wide={2}>
                            <Editable value={f1.partial_reason} locked={locked} onSave={field('partial_reason')} />
                        </Box>
                    </div>

                    <div className="border rounded p-2 mb-3 d-flex align-items-center gap-3">
                        <span className="small text-secondary">{as9102 ? '19. ' : ''}{trans.f_has_nc}</span>
                        <span className={`badge ${f1.has_nc ? 'text-bg-danger' : 'text-bg-success'}`}>{f1.has_nc ? trans.yes : trans.no}</span>
                        <span className="small text-secondary">{trans.f_has_nc_hint}</span>
                    </div>

                    <Signature label={trans.f_verified_by} n={20} by={f1.verified_by} date={f1.verified_at} signed={!!f1.verified_at}
                               as9102={as9102} canSign={report.can_verify} locked={locked} trans={trans} locale={locale}
                               onSign={() => sign('verified', true)} onUnsign={() => sign('verified', false)} />
                    <Signature label={trans.f_approved_by} n={22} by={f1.approved_by} date={f1.approved_at} signed={!!f1.approved_at}
                               as9102={as9102} canSign={report.can_approve} locked={!f1.verified_at} trans={trans} locale={locale}
                               onSign={() => sign('approved', true)} onUnsign={() => sign('approved', false)} />

                    <div className="row g-2 mb-2">
                        <Box n={24} label={trans.f_customer_approval} as9102={as9102} wide={2}>
                            <Editable value={f1.customer_approval} locked={locked} onSave={v => patch({ customer_approval: v })} />
                        </Box>
                        <Box n={25} label={trans.f_date} as9102={as9102}>
                            <input type="date" className="form-control form-control-sm" disabled={locked} value={f1.customer_approved_at ?? ''}
                                   onChange={e => patch({ customer_approved_at: e.target.value || null })} />
                        </Box>
                    </div>
                    <div className="border rounded p-2">
                        <div className="small text-secondary">{as9102 ? '26. ' : ''}{trans.f_comments}</div>
                        <textarea className="form-control form-control-sm" rows={2} disabled={locked} value={comment}
                                  onChange={e => {
                                      const next = e.target.value;
                                      setComment(next);
                                      clearTimeout(commentTimer.current);
                                      commentTimer.current = setTimeout(() => patch({ report_comment: next || null }), 800);
                                  }} />
                    </div>
                </div>
            </div>

            <div className="card mb-3">
                <div className="card-header"><strong>{as9102 ? trans.form2_title_as9102 : trans.form2_title}</strong></div>
                <div className="card-body p-0">
                    <table className="table table-sm mb-0">
                        <thead className="table-light">
                            <tr><th>{trans.f2_kind}</th><th>{trans.f2_label}</th><th>{trans.f2_supplier}</th><th>{trans.f2_reference}</th></tr>
                        </thead>
                        <tbody>
                            {report.form2.length === 0 && <tr><td colSpan={4} className="text-secondary">{trans.f2_empty}</td></tr>}
                            {report.form2.map((row, i) => (
                                <tr key={i}>
                                    <td>{trans[`f2_kind_${row.kind}`]}</td>
                                    <td>{row.label}</td>
                                    <td>{row.supplier}</td>
                                    <td>{row.reference}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card mb-3">
                <div className="card-header"><strong>{as9102 ? trans.form3_title_as9102 : trans.form3_title}</strong></div>
                <div className="card-body p-0 table-responsive">
                    <table className="table table-sm table-bordered mb-0 align-middle">
                        <thead className="table-light">
                            <tr>
                                <th>N°</th><th>{trans.f3_location}</th><th>{trans.f3_designator}</th><th>{trans.f3_requirement}</th>
                                <th>{trans.f3_results}</th><th>{trans.f3_conformity}</th><th>{trans.f3_tools}</th><th>{trans.f3_nc}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.form3.map(row => (
                                <tr key={row.number}>
                                    <td className="fw-bold">{row.number}</td>
                                    <td>{row.location}</td>
                                    <td>{row.designator}</td>
                                    <td>{row.requirement}</td>
                                    <td className="small">
                                        {row.results.length === 0 && <span className="text-secondary">{trans.not_measured}</span>}
                                        {row.results.map(r => (
                                            <div key={r.part} className={r.result === 'nok' ? 'text-danger fw-semibold' : ''}>
                                                {trans.part} {r.part} : {r.value ?? (r.result === 'ok' ? trans.mark_ok : trans.mark_nok)}
                                            </div>
                                        ))}
                                    </td>
                                    <td>
                                        {row.result && (
                                            <span className={`badge ${row.result === 'ok' ? 'text-bg-success' : 'text-bg-danger'}`}>
                                                {row.result === 'ok' ? trans.conforming : trans.non_conforming}
                                            </span>
                                        )}
                                    </td>
                                    <td className="small">{row.tools}</td>
                                    <td className="small">{row.nc}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
