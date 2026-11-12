import React, { useCallback, useEffect, useRef, useState } from 'react';
import { jsonHeaders } from '../../lib/http.js';
import { isStepFile, readDoneMessage } from '../../lib/nest2bendMessage.js';

/** NEST2BEND lit le STEP avant de répondre : on lui laisse largement plus de 60 s. */
const START_TIMEOUT_MS = 120000;

async function post(url, body, timeoutMs = 30000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify(body ?? {}),
            signal: controller.signal,
        });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    } catch (err) {
        return { ok: false, status: 0, data: { message: err?.name === 'AbortError' ? null : err?.message } };
    } finally {
        clearTimeout(timer);
    }
}

/**
 * Bouton « Simuler le pliage » d'une ligne de devis.
 *
 * 1. Le serveur de l'ERP ouvre une session NEST2BEND avec le STEP de la GED (la clé API ne
 *    vient jamais ici) et renvoie l'URL d'iframe : code à usage unique, 60 s, jamais gardé.
 * 2. L'iframe signale la fin par postMessage ; seul `resultId` est lu, après contrôle de
 *    l'origine et de la fenêtre émettrice.
 * 3. Le serveur relit le résultat et l'ajoute au commentaire interne de la ligne ; le champ
 *    du formulaire de la page est mis à jour pour qu'un enregistrement ne l'écrase pas.
 * 4. Le serveur range la fiche de réglage PDF dans la GED de la ligne. En cas d'échec,
 *    « Récupérer la fiche » retente (toujours côté serveur, avec la clé).
 */
export default function BendingSimulationButton({
    sessionUrl,
    resultUrl,
    sheetUrl,
    last: initialLast,
    active,
    expired: initiallyExpired,
    hasStep: initiallyHasStep,
    stepName,
    warning,
    appUrl,
    target,
    trans,
}) {
    const t = useCallback((key) => trans?.[key] ?? key, [trans]);

    const [hasStep, setHasStep] = useState(!!initiallyHasStep);
    const [expired, setExpired] = useState(!!initiallyExpired);
    const [phase, setPhase] = useState('idle'); // idle | starting | open | saving
    const [frame, setFrame] = useState(null); // { url, origin }
    const [error, setError] = useState(null);
    const [notice, setNotice] = useState(null);
    const [remarks, setRemarks] = useState([]);
    const [last, setLast] = useState(initialLast ?? null);
    const [retrying, setRetrying] = useState(false);
    const iframeRef = useRef(null);

    // Un STEP déposé ou retiré dans la GED de la ligne met le bouton à jour sans recharger.
    useEffect(() => {
        const onFiles = (event) => {
            const detail = event.detail ?? {};
            if (detail.target?.type !== target?.type || Number(detail.target?.id) !== Number(target?.id)) return;
            setHasStep((detail.files ?? []).some(isStepFile));
        };
        window.addEventListener('wem:files-changed', onFiles);
        return () => window.removeEventListener('wem:files-changed', onFiles);
    }, [target]);

    // Fiche rangée : la GED de la page se recharge pour la montrer.
    const showLast = useCallback((value) => {
        setLast(value);
        if (value?.sheet === 'stored') {
            window.dispatchEvent(new CustomEvent('wem:files-refresh', { detail: { target } }));
        }
    }, [target]);

    const retrySheet = async () => {
        setError(null);
        setNotice(null);
        setRetrying(true);
        const res = await post(sheetUrl, {}, 90000);
        setRetrying(false);
        if (res.data?.last) showLast(res.data.last);
        if (res.ok) setNotice(res.data.message);
        else setError(res.data?.message || (res.status === 0 ? t('unavailable') : t('unexpected')));
    };

    const close = useCallback(() => {
        setFrame(null);
        setPhase((p) => (p === 'open' ? 'idle' : p));
    }, []);

    const saveResult = useCallback(async (resultId) => {
        setFrame(null);
        setPhase('saving');
        const res = await post(resultUrl, { result_id: resultId });
        setPhase('idle');

        if (!res.ok) {
            setError(res.data?.message || t('unexpected'));
            return;
        }

        const field = document.querySelector('textarea[name="internal_comment"]');
        if (field && typeof res.data.internal_comment === 'string') {
            field.value = res.data.internal_comment;
        }
        setNotice(res.data.message);
        showLast(res.data.last ?? null);
    }, [resultUrl, t, showLast]);

    // Écoute de l'iframe, seulement tant qu'elle est ouverte.
    useEffect(() => {
        if (!frame) return undefined;
        const onMessage = (event) => {
            const resultId = readDoneMessage(event, frame.origin, iframeRef.current?.contentWindow ?? null);
            if (resultId) saveResult(resultId);
        };
        const onKey = (event) => { if (event.key === 'Escape') close(); };
        window.addEventListener('message', onMessage);
        window.addEventListener('keydown', onKey);
        return () => {
            window.removeEventListener('message', onMessage);
            window.removeEventListener('keydown', onKey);
        };
    }, [frame, saveResult, close]);

    const start = async () => {
        setError(null);
        setNotice(null);
        setRemarks([]);
        setPhase('starting');

        const res = await post(sessionUrl, {}, START_TIMEOUT_MS);

        if (!res.ok) {
            setPhase('idle');
            if (res.status === 403) setExpired(true);
            // 422 : message de NEST2BEND (fichier refusé, origine non déclarée…), affiché tel quel.
            setError(res.data?.message || (res.status === 0 ? t('unavailable') : t('unexpected')));
            return;
        }

        // L'URL est ouverte tout de suite et n'est gardée nulle part ailleurs que dans l'iframe.
        setRemarks(Array.isArray(res.data.remarques) ? res.data.remarques : []);
        setFrame({ url: res.data.url, origin: res.data.origin });
        setPhase('open');
    };

    const disabled = !active || expired || !hasStep || phase !== 'idle';
    const reason = expired ? t('expired') : (!hasStep ? t('no_step') : null);

    return (
        <div className="nest2bend-simulation">
            <div className="d-flex flex-wrap align-items-center gap-2">
                <button type="button" className="btn btn-sm btn-teal" onClick={start} disabled={disabled}
                        title={reason ?? undefined}>
                    {phase === 'starting' || phase === 'saving'
                        ? <><i className="fas fa-spinner fa-spin me-1" />{phase === 'starting' ? t('starting') : t('saving')}</>
                        : <><i className="fas fa-bezier-curve me-1" />{t('label')}</>}
                </button>
                {hasStep && stepName && !expired && (
                    <small className="text-muted">{t('step_file').replace(':name', stepName)}</small>
                )}
                {reason && (
                    <small className={expired ? 'text-danger' : 'text-muted'}>
                        {reason}
                        {expired && appUrl && (
                            <a href={appUrl} target="_blank" rel="noopener noreferrer" className="ms-1">{t('renew')}</a>
                        )}
                    </small>
                )}
            </div>

            {last && (
                <div className="small border rounded px-2 py-1 mt-2">
                    <div className="fw-semibold mb-1"><i className="fas fa-history me-1" />{t('last_title')}{last.date ? ` — ${last.date}` : ''}</div>
                    {last.presse && <div><span className="text-muted">{t('last_press')} : </span>{last.presse}</div>}
                    {last.outils?.length > 0 && <div><span className="text-muted">{t('last_tools')} : </span>{last.outils.join(' · ')}</div>}
                    {last.sequence && <div><span className="text-muted">{t('last_sequence')} : </span>{last.sequence}</div>}
                    {last.sheet && (
                        <div className="d-flex flex-wrap align-items-center gap-2 mt-1">
                            <span className="text-muted">{t('last_sheet')} :</span>
                            {last.sheet === 'stored' && last.sheet_url && (
                                <a href={last.sheet_url} target="_blank" rel="noopener noreferrer">
                                    <i className="fas fa-file-pdf me-1" />{last.sheet_name || t('sheet_open')}
                                </a>
                            )}
                            {/* pending resté tel quel : téléchargement interrompu, on peut retenter. */}
                            {(last.sheet === 'failed' || last.sheet === 'pending') && (
                                <>
                                    <span className={last.sheet === 'failed' ? 'text-warning-emphasis' : ''}>
                                        {last.sheet === 'failed' ? last.sheet_error : t('sheet_pending')}
                                    </span>
                                    <button type="button" className="btn btn-xs btn-sm btn-outline-secondary py-0"
                                            onClick={retrySheet} disabled={retrying || phase !== 'idle'}>
                                        {retrying
                                            ? <><i className="fas fa-spinner fa-spin me-1" />{t('sheet_retrying')}</>
                                            : <><i className="fas fa-redo me-1" />{t('sheet_retry')}</>}
                                    </button>
                                </>
                            )}
                        </div>
                    )}
                </div>
            )}

            {warning && !expired && <div className="small text-warning-emphasis mt-1"><i className="fas fa-hourglass-half me-1" />{warning}</div>}
            {error && <div className="alert alert-danger py-2 mt-2 mb-0" role="alert">{error}</div>}
            {notice && <div className="alert alert-success py-2 mt-2 mb-0" role="status">{notice}</div>}

            {frame && (
                <div className="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column bg-body"
                     style={{ zIndex: 1080 }} role="dialog" aria-modal="true" aria-label={t('modal_title')}>
                    <div className="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                        <strong className="me-auto"><i className="fas fa-bezier-curve me-1" />{t('modal_title')}</strong>
                        {remarks.length > 0 && (
                            <small className="text-warning-emphasis" title={remarks.join('\n')}>
                                <i className="fas fa-exclamation-triangle me-1" />{t('remarks')} : {remarks.join(' · ')}
                            </small>
                        )}
                        {/* Un rechargement dans l'iframe la casse (code à usage unique) : on rouvre une session. */}
                        <button type="button" className="btn btn-sm btn-outline-primary" title={t('restart_hint')}
                                onClick={() => { setFrame(null); setPhase('idle'); start(); }}>
                            <i className="fas fa-redo me-1" />{t('restart')}
                        </button>
                        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={close}>
                            <i className="fas fa-times me-1" />{t('close')}
                        </button>
                    </div>
                    <iframe ref={iframeRef} src={frame.url} title="NEST2BEND" className="flex-grow-1 w-100 border-0"
                            referrerPolicy="origin" allow="fullscreen" />
                </div>
            )}
        </div>
    );
}
