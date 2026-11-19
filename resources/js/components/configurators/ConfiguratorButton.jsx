import React, { useEffect, useRef, useState } from 'react';
import { apiRequest } from '../../lib/http';
import { askCreditOverride } from '../../lib/creditLimit';
import { fmt } from '../../lib/i18n.js';
import { readIframeMessage, readReturnMessage } from '../../lib/configuratorMessage.js';

// Bouton « Configurer » des lignes de devis / commande : ouvre un configurateur externe
// (iframe ou onglet), relit le résultat par le serveur, montre l'aperçu, puis crée les
// lignes après confirmation. Rien n'est écrit avant « Ajouter les lignes ».
// Contrat : docs/conception/configurateurs-externes.md.

async function post(url, body = {}) {
    const res = await apiRequest(url, { method: 'POST', body: JSON.stringify(body) });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
}

const money = (value, locale) =>
    new Intl.NumberFormat(locale || undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value ?? 0);

export default function ConfiguratorButton({ endpoints, configurators = [], trans: t = {}, onApplied }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [active, setActive] = useState(null);   // configurateur choisi

    if (!configurators.length || !endpoints?.configuratorOpen) return null;

    const choose = (configurator) => {
        setMenuOpen(false);
        // En onglet, la fenêtre s'ouvre pendant le clic : ouverte après l'appel serveur,
        // le navigateur la bloquerait. L'adresse du configurateur y est chargée ensuite.
        const tab = configurator.display === 'tab' ? window.open('', '_blank') : null;
        setActive({ ...configurator, tab });
    };

    return (
        <>
            <div className="btn-group btn-group-sm position-relative">
                <button type="button" className="btn btn-outline-primary"
                    onClick={() => (configurators.length === 1 ? choose(configurators[0]) : setMenuOpen((v) => !v))}>
                    <i className="fas fa-sliders-h me-1" />{t.button ?? 'Configurer'}
                    {configurators.length > 1 && <i className="fas fa-caret-down ms-1" />}
                </button>
                {menuOpen && (
                    <div className="dropdown-menu show" style={{ top: '100%', left: 0 }}>
                        {configurators.map((c) => (
                            <button key={c.id} type="button" className="dropdown-item" onClick={() => choose(c)}>{c.label}</button>
                        ))}
                    </div>
                )}
            </div>
            {active && (
                <ConfiguratorModal
                    configurator={active}
                    endpoints={endpoints}
                    t={t}
                    onApplied={(result) => { setActive(null); onApplied?.(result); }}
                    onClose={() => setActive(null)}
                />
            )}
        </>
    );
}

function ConfiguratorModal({ configurator, endpoints, t, onApplied, onClose }) {
    const [step, setStep] = useState('opening');   // opening | iframe | tab | reading | preview | applying | error
    const [session, setSession] = useState(null);  // { session, url, display, origin }
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const iframeRef = useRef(null);
    const tabRef = useRef(null);
    const appliedRef = useRef(false);
    const locale = document.documentElement.lang || undefined;

    const sessionUrl = (key) => endpoints[key].replace('__SESSION__', session?.session ?? '');

    // Ouverture de la session ; en onglet, la fenêtre a été ouverte au clic (ConfiguratorButton).
    useEffect(() => {
        let cancelled = false;
        tabRef.current = configurator.tab ?? null;
        post(endpoints.configuratorOpen, { configurator_id: configurator.id }).then(({ ok, data }) => {
            if (cancelled) return;
            if (!ok) {
                tabRef.current?.close();
                setError(data.message ?? t.error ?? 'Erreur');
                setStep('error');
                return;
            }
            setSession(data);
            if (data.display === 'tab') {
                if (tabRef.current && !tabRef.current.closed) {
                    tabRef.current.location.href = data.url;
                } else {
                    tabRef.current = window.open(data.url, '_blank');
                }
                setStep('tab');
            } else {
                setStep('iframe');
            }
        });
        return () => { cancelled = true; };
    }, []);

    // Fin de configuration : message de l'iframe, ou de la page de retour de l'ERP (onglet).
    useEffect(() => {
        if (!session) return undefined;
        const onMessage = (event) => {
            if (step === 'iframe') {
                const message = readIframeMessage(event, session.origin, iframeRef.current?.contentWindow ?? null);
                if (message?.type === 'cancel') close();
                if (message?.type === 'done') readResult(message.resultId);
            } else if (step === 'tab') {
                const resultId = readReturnMessage(event, window.location.origin, session.session);
                if (resultId) readResult(resultId);
            }
        };
        window.addEventListener('message', onMessage);
        return () => window.removeEventListener('message', onMessage);
    }, [session, step]);

    const readResult = async (resultId) => {
        setStep('reading');
        setError(null);
        const { ok, data } = await post(sessionUrl('configuratorResult'), { result_id: resultId });
        if (!ok) {
            setError(data.message ?? t.error ?? 'Erreur');
            setStep('error');
            return;
        }
        setResult(data.result);
        setStep('preview');
    };

    const apply = async (creditOverride = null) => {
        setStep('applying');
        setError(null);
        const { ok, data } = await post(sessionUrl('configuratorApply'), creditOverride ?? {});
        if (!ok && data.credit_limit) {
            // Plafond d'encours dépassé (commande) : passe-droit motivé, puis nouvel envoi.
            setStep('preview');
            const override = await askCreditOverride(data.credit_limit);
            if (override) return apply(override);
            return undefined;
        }
        if (!ok) {
            setError(data.message ?? t.error ?? 'Erreur');
            setStep('preview');
            return undefined;
        }
        appliedRef.current = true;
        onApplied(data);
        return undefined;
    };

    const close = () => {
        if (session && !appliedRef.current) {
            post(sessionUrl('configuratorCancel')).catch(() => {});
        }
        onClose();
    };

    const wide = step === 'iframe' || step === 'preview' || step === 'applying';

    return (
        <div className="modal d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,0.5)' }}>
            <div className={`modal-dialog ${wide ? 'modal-xl' : ''} modal-dialog-scrollable`}>
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">
                            <i className="fas fa-sliders-h me-1" />{fmt(t.title ?? ':label', { label: configurator.label })}
                        </h5>
                        <button type="button" className="btn-close" aria-label="Close" onClick={close} />
                    </div>
                    <div className="modal-body" style={step === 'iframe' ? { padding: 0 } : undefined}>
                        {(step === 'opening' || step === 'reading') && (
                            <div className="text-center py-5 text-body-secondary">
                                <i className="fas fa-spinner fa-spin fa-2x mb-2 d-block" />
                                {step === 'opening' ? t.opening : t.reading}
                            </div>
                        )}

                        {step === 'iframe' && session && (
                            <iframe ref={iframeRef} src={session.url} title={configurator.label}
                                style={{ width: '100%', height: '75vh', border: 0, display: 'block' }} />
                        )}

                        {step === 'tab' && session && (
                            <div className="text-center py-4">
                                <p>{t.waiting_tab}</p>
                                {!tabRef.current && <p className="text-warning small">{t.popup_blocked}</p>}
                                <button type="button" className="btn btn-outline-primary btn-sm"
                                    onClick={() => { tabRef.current = window.open(session.url, '_blank'); }}>
                                    <i className="fas fa-external-link-alt me-1" />{t.reopen_tab}
                                </button>
                            </div>
                        )}

                        {error && <div className="alert alert-danger mb-3">{error}</div>}

                        {(step === 'preview' || step === 'applying') && result && (
                            <ResultPreview result={result} t={t} locale={locale} />
                        )}
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-outline-secondary" onClick={close}>
                            {step === 'error' ? (t.close ?? 'Fermer') : (t.cancel ?? 'Annuler')}
                        </button>
                        {(step === 'preview' || step === 'applying') && (
                            <button type="button" className="btn btn-success" disabled={step === 'applying'} onClick={() => apply()}>
                                {step === 'applying' ? <i className="fas fa-spinner fa-spin me-1" /> : <i className="fas fa-check me-1" />}
                                {t.confirm ?? 'Ajouter les lignes'}
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function ResultPreview({ result, t, locale }) {
    return (
        <>
            <h6 className="mb-2">{t.preview}</h6>
            <div className="table-responsive">
                <table className="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{t.line}</th>
                            <th className="text-end">{t.qty}</th>
                            <th className="text-end">{t.unit_price}</th>
                            <th className="text-end">{t.discount}</th>
                            <th className="text-end">{t.total}</th>
                            <th className="text-end">{t.lead_time}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {result.lines.map((line, index) => (
                            <tr key={index}>
                                <td>
                                    <div className="fw-semibold">{line.label}</div>
                                    {line.code && <div className="small text-body-secondary">{line.code}</div>}
                                    <div className="d-flex flex-wrap gap-1 mt-1">
                                        {line.items.filter((i) => i.kind !== 'supplier').map((item, k) => (
                                            <span key={k} className="badge text-bg-light border" title={item.unit_price ? money(item.unit_price, locale) : ''}>
                                                {item.label}{item.value ? ` : ${item.value}` : ''}{item.unit ? ` ${item.unit}` : ''}
                                            </span>
                                        ))}
                                        {line.files.length > 0 && (
                                            <span className="badge text-bg-secondary">
                                                <i className="fas fa-paperclip me-1" />{fmt(t.files ?? ':count', { count: line.files.length })}
                                            </span>
                                        )}
                                    </div>
                                </td>
                                <td className="text-end">{line.qty}{line.unit_label ? ` ${line.unit_label}` : ''}</td>
                                <td className="text-end">{money(line.unit_price, locale)}</td>
                                <td className="text-end">{line.discount ? `${line.discount} %` : '—'}</td>
                                <td className="text-end fw-semibold">{money(line.net_total, locale)}</td>
                                <td className="text-end">{line.lead_time_days !== null ? fmt(t.days ?? ':count', { count: line.lead_time_days }) : '—'}</td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colSpan={4} className="text-end">{t.total}</th>
                            <th className="text-end">{money(result.total, locale)}</th>
                            <th />
                        </tr>
                    </tfoot>
                </table>
            </div>
            {result.files.length > 0 && (
                <p className="small text-body-secondary mb-2">
                    <i className="fas fa-paperclip me-1" />{fmt(t.files ?? ':count', { count: result.files.length })}
                </p>
            )}
            {result.warnings.length > 0 && (
                <div className="alert alert-warning py-2 mb-0">
                    <strong>{t.warnings}</strong>
                    <ul className="mb-0 small">
                        {result.warnings.map((w, k) => <li key={k}>{w}</li>)}
                    </ul>
                </div>
            )}
        </>
    );
}
