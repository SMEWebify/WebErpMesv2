import React, { useEffect, useState } from 'react';
import { apiRequest } from '../../lib/http';
import {
    RESULTS, certificateGap, commentRequired, decisionBadge, gapMessage, statusBadge,
} from '../../lib/receiptDocumentCheck';

// Badges réutilisés par la fiche réception et la liste qualité
export function StatusBadge({ status, texts }) {
    const { theme, label } = statusBadge(status, texts);
    return <span className={`badge text-bg-${theme}`}>{label}</span>;
}

export function DecisionBadge({ decision, texts }) {
    const { theme, label } = decisionBadge(decision, texts);
    return <span className={`badge text-bg-${theme}`}>{label}</span>;
}

// Certificat exigé → reçu, avec l'écart en infobulle
export function CertificateCell({ line, texts }) {
    const required = line.required_certificate_type;
    const received = line.received_certificate_type;
    if (!required && !received) return <span className="text-body-secondary">—</span>;

    const gap = line.certificate_gap;
    return (
        <span className="text-nowrap" title={gapMessage(gap, texts) ?? texts.no_gap}>
            <span className="badge text-bg-secondary">{required ?? texts.none}</span>
            <i className="fas fa-arrow-right mx-1 text-body-secondary" style={{ fontSize: '0.7em' }} />
            <span className={`badge text-bg-${gap ? 'danger' : 'success'}`}>{received ?? texts.none}</span>
            {gap && <i className="fas fa-triangle-exclamation text-danger ms-1" />}
        </span>
    );
}

const EMPTY = { document_check_result: 'compliant', document_check_comment: '', received_certificate_type: '', create_non_conformity: false };

/**
 * Tiroir de visa documentaire. `config` : { canCheck, certificateTypes, texts } fourni par
 * ReceiptDocumentCheckController::frontConfig(). Sans permission, le tiroir est en lecture.
 */
export default function DocumentCheckDrawer({ line, endpoint, config, onClose, onSaved }) {
    const texts = config.texts ?? {};
    const certificateTypes = config.certificateTypes ?? [];
    const canCheck = !!config.canCheck;

    const [form, setForm]     = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!line) return;
        setForm({
            document_check_result:     line.document_check_result ?? 'compliant',
            document_check_comment:    line.document_check_comment ?? '',
            received_certificate_type: line.received_certificate_type ?? '',
            create_non_conformity:     false,
        });
        setErrors({});
    }, [line]);

    const open = !!line;
    const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

    const gap = line ? certificateGap(line.required_certificate_type, form.received_certificate_type, certificateTypes) : null;
    const needsComment = commentRequired(form.document_check_result, gap);
    const certificateChoices = [...certificateTypes];
    if (line?.received_certificate_type && !certificateChoices.includes(line.received_certificate_type)) {
        certificateChoices.push(line.received_certificate_type);
    }

    const submit = async (e) => {
        e.preventDefault();
        if (needsComment && !form.document_check_comment.trim()) {
            setErrors({ document_check_comment: [texts.comment_required_with_gap] });
            return;
        }
        setSaving(true);
        setErrors({});
        try {
            const res = await apiRequest(endpoint.replace('__ID__', line.id), { method: 'POST', body: JSON.stringify(form) });
            const data = await res.json().catch(() => ({}));
            if (res.status === 403) { setErrors({ _global: texts.forbidden }); return; }
            if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? texts.error }); return; }
            onSaved(data.line);
        } catch {
            setErrors({ _global: texts.error });
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <div onClick={onClose} style={{
                position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.35)', zIndex: 1040,
                opacity: open ? 1 : 0, pointerEvents: open ? 'auto' : 'none', transition: 'opacity 0.2s',
            }} />
            <div style={{
                position: 'fixed', top: 0, right: 0, bottom: 0, width: 480, maxWidth: '95vw',
                background: 'var(--bs-body-bg)', zIndex: 1050, display: 'flex', flexDirection: 'column',
                transform: open ? 'translateX(0)' : 'translateX(100%)', transition: 'transform 0.25s ease',
                boxShadow: '-4px 0 24px rgba(0,0,0,0.15)',
            }}>
                <div className="d-flex align-items-center justify-content-between px-3 py-2 text-bg-teal">
                    <strong>
                        <i className="fas fa-file-shield me-2" />{texts.drawer_title}
                        {line && <span className="ms-2 fw-normal small">— {line.purchase_line_label}</span>}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-light" onClick={onClose}>
                        <i className="fas fa-xmark" />
                    </button>
                </div>

                {line && (
                    <div className="flex-grow-1 overflow-auto p-3">
                        {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

                        <dl className="row small mb-3">
                            <dt className="col-6">{texts.required_certificate}</dt>
                            <dd className="col-6 mb-1">{line.required_certificate_type ?? <span className="text-body-secondary">{texts.none}</span>}</dd>
                            <dt className="col-6">{texts.physical}</dt>
                            <dd className="col-6 mb-1"><StatusBadge status={line.physical_status} texts={texts} /></dd>
                            <dt className="col-6">{texts.decision}</dt>
                            <dd className="col-6 mb-1"><DecisionBadge decision={line.final_decision} texts={texts} /></dd>
                        </dl>

                        {line.document_check_at && (
                            <div className="alert alert-secondary py-2 small">
                                <StatusBadge status={line.document_check_status} texts={texts} />{' '}
                                {(texts.checked_by ?? '').replace(':name', line.document_checker_name ?? '—').replace(':date', line.document_check_at)}
                                {line.document_check_comment && <div className="mt-1 fst-italic">{line.document_check_comment}</div>}
                            </div>
                        )}

                        <form id="document-check-form" onSubmit={submit}>
                            <fieldset disabled={!canCheck || saving}>
                                <div className="mb-3">
                                    <label className="form-label small fw-bold mb-1">{texts.received_certificate}</label>
                                    <select className={`form-select form-select-sm ${errors.received_certificate_type ? 'is-invalid' : ''}`}
                                        value={form.received_certificate_type}
                                        onChange={(e) => set('received_certificate_type', e.target.value)}>
                                        <option value="">{texts.none}</option>
                                        {certificateChoices.map((value) => <option key={value} value={value}>{value}</option>)}
                                    </select>
                                    {errors.received_certificate_type && <div className="invalid-feedback">{errors.received_certificate_type[0]}</div>}
                                </div>

                                {line.required_certificate_type
                                    ? (gap
                                        ? <div className="alert alert-danger py-2 small"><i className="fas fa-triangle-exclamation me-1" />{gapMessage(gap, texts)}</div>
                                        : <div className="alert alert-success py-2 small"><i className="fas fa-check me-1" />{texts.no_gap}</div>)
                                    : <div className="text-body-secondary small mb-3">{texts.not_required_hint}</div>}

                                <div className="mb-3">
                                    <label className="form-label small fw-bold mb-1">{texts.result}</label>
                                    <div className="btn-group btn-group-sm w-100" role="group">
                                        {RESULTS.map((value) => {
                                            const { theme, label } = statusBadge(value, texts);
                                            return (
                                                <React.Fragment key={value}>
                                                    <input type="radio" className="btn-check" name="document_check_result" id={`dc-${value}`}
                                                        checked={form.document_check_result === value}
                                                        onChange={() => set('document_check_result', value)} />
                                                    <label className={`btn btn-outline-${theme}`} htmlFor={`dc-${value}`}>{label}</label>
                                                </React.Fragment>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div className="mb-3">
                                    <label className="form-label small fw-bold mb-1">
                                        {texts.comment}{needsComment && <span className="text-danger"> *</span>}
                                    </label>
                                    <textarea rows="3" className={`form-control form-control-sm ${errors.document_check_comment ? 'is-invalid' : ''}`}
                                        value={form.document_check_comment}
                                        onChange={(e) => set('document_check_comment', e.target.value)} />
                                    {errors.document_check_comment && <div className="invalid-feedback">{errors.document_check_comment[0]}</div>}
                                </div>

                                {form.document_check_result === 'non_compliant' && (
                                    line.nc_code
                                        ? <div className="alert alert-warning py-2 small">{(texts.existing_nc ?? '').replace(':code', line.nc_code)}</div>
                                        : (
                                            <div className="form-check form-switch">
                                                <input type="checkbox" className="form-check-input" id="dc-create-nc"
                                                    checked={form.create_non_conformity}
                                                    onChange={(e) => set('create_non_conformity', e.target.checked)} />
                                                <label className="form-check-label" htmlFor="dc-create-nc">{texts.create_nc}</label>
                                            </div>
                                        )
                                )}
                            </fieldset>
                        </form>
                        {!canCheck && <div className="text-body-secondary small mt-3">{texts.forbidden}</div>}
                    </div>
                )}

                <div className="border-top px-3 py-2 d-flex justify-content-end bg-body-tertiary" style={{ gap: '0.5rem' }}>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>{texts.cancel}</button>
                    {canCheck && (
                        <button type="submit" form="document-check-form" className="btn btn-sm btn-teal" disabled={saving}>
                            {saving
                                ? <><i className="fas fa-spinner fa-spin me-1" />{texts.saving}</>
                                : <><i className="fas fa-stamp me-1" />{texts.save}</>}
                        </button>
                    )}
                </div>
            </div>
        </>
    );
}
