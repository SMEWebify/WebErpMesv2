import React, { useEffect, useRef, useState } from 'react';
import { formatWorked, operatorExpired, parseQty, pushHistory, resultStyle } from '../../lib/scanStation.js';

/**
 * Écran de scan atelier (docs/pointage-scan.md) : champ toujours actif pour la
 * douchette (elle « tape » le code puis Entrée), lecture caméra quand le navigateur
 * sait lire les codes (BarcodeDetector). Le serveur décide de l'action selon le mode.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function postScan(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => null);
    if (!data || (!response.ok && !data.result)) throw new Error('scan_failed');
    return data;
}

export default function ScanStation({ scanUrl, modeLabel, trans, badgeRequired = false, badgeIdleMinutes = 15 }) {
    const t = (key) => trans?.[key] ?? key;
    const inputRef = useRef(null);
    const [code, setCode] = useState('');
    const [busy, setBusy] = useState(false);
    const [last, setLast] = useState(null);
    const [history, setHistory] = useState([]);
    const [qtyPrompt, setQtyPrompt] = useState(null);
    const [camera, setCamera] = useState(false);
    // Opérateur du poste (badge scanné) : { name, badge, lastAt }. Le serveur relit
    // le badge à chaque scan ; l'écran l'oublie après badgeIdleMinutes sans scan.
    const [operator, setOperator] = useState(null);

    useEffect(() => {
        if (!operator) return undefined;
        const timer = window.setInterval(() => {
            if (operatorExpired(operator, Date.now(), badgeIdleMinutes)) setOperator(null);
        }, 30000);
        return () => window.clearInterval(timer);
    }, [operator, badgeIdleMinutes]);

    // La douchette écrit là où est le curseur : on le ramène dans le champ dès
    // qu'il en sort, sauf pendant la saisie des quantités.
    useEffect(() => {
        if (qtyPrompt) return undefined;
        const focus = () => inputRef.current?.focus();
        focus();
        const timer = window.setInterval(() => {
            if (document.activeElement !== inputRef.current) focus();
        }, 1000);
        return () => window.clearInterval(timer);
    }, [qtyPrompt]);

    async function send(scanned, quantities = null) {
        const value = scanned.trim();
        if (!value || busy) return;
        setBusy(true);
        const active = operator && !operatorExpired(operator, Date.now(), badgeIdleMinutes) ? operator : null;
        try {
            const body = { code: value, ...(quantities ?? {}), ...(active ? { badge: active.badge } : {}) };
            const data = await postScan(scanUrl, body);
            if (data.result === 'badge' && data.operator) {
                setOperator({ ...data.operator, lastAt: Date.now() });
            } else if (data.result === 'badge_unknown' && active && !value.toUpperCase().startsWith('N2P-B:')) {
                // Le badge du poste n'existe plus (retiré entre-temps) : on l'oublie.
                setOperator(null);
            } else if (active) {
                setOperator({ ...active, lastAt: Date.now() });
            } else if (operator) {
                setOperator(null);
            }
            if (data.result === 'needs_qty') {
                setQtyPrompt({ code: value, task: data.task, good: String(data.task?.remaining_qty ?? 0), bad: '0' });
            } else {
                setQtyPrompt(null);
            }
            setLast(data);
            setHistory((h) => pushHistory(h, { ...data, at: new Date() }));
            if (data.result === 'opened' && data.url) window.location.href = data.url;
        } catch {
            const error = { result: 'error', message: t('error'), code: value };
            setLast(error);
            setHistory((h) => pushHistory(h, { ...error, at: new Date() }));
        } finally {
            setBusy(false);
            setCode('');
        }
    }

    function submitQty(e) {
        e.preventDefault();
        const good = parseQty(qtyPrompt.good);
        const bad = parseQty(qtyPrompt.bad);
        if (good === null || bad === null) return;
        send(qtyPrompt.code, { good_qty: good, bad_qty: bad });
    }

    const style = last ? resultStyle(last.result) : null;

    return (
        <div className="container-fluid py-3" style={{ maxWidth: 1100 }}>
            <div className="d-flex align-items-center gap-2 mb-3">
                <h1 className="h3 mb-0 me-auto"><i className="fas fa-barcode me-2" />{t('title')}</h1>
                <span className="badge text-bg-secondary fs-6">{t('mode')} : {modeLabel}</span>
                {'BarcodeDetector' in window ? (
                    <button type="button" className="btn btn-outline-secondary" onClick={() => setCamera((c) => !c)}>
                        <i className="fas fa-camera me-1" />{camera ? t('camera_stop') : t('camera')}
                    </button>
                ) : null}
            </div>

            <div className={`alert d-flex align-items-center gap-2 py-2 ${operator ? 'alert-info' : (badgeRequired ? 'alert-warning' : 'alert-secondary')}`}>
                <i className="fas fa-id-badge fs-4" />
                <span className="me-auto fs-5">
                    {operator
                        ? <>{t('operator')} : <strong>{operator.name}</strong></>
                        : (badgeRequired ? t('no_operator_required') : t('no_operator'))}
                </span>
                {operator ? (
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setOperator(null)}>
                        {t('change_operator')}
                    </button>
                ) : null}
            </div>

            <form onSubmit={(e) => { e.preventDefault(); send(code); }}>
                <input
                    ref={inputRef}
                    type="text"
                    className="form-control form-control-lg mb-3"
                    style={{ fontSize: '1.5rem' }}
                    value={code}
                    onChange={(e) => setCode(e.target.value)}
                    placeholder={t('placeholder')}
                    autoComplete="off"
                    autoCapitalize="off"
                    spellCheck={false}
                    disabled={busy || !!qtyPrompt}
                />
            </form>

            {camera ? <CameraReader onCode={(value) => { setCamera(false); send(value); }} /> : null}

            <div className={`card mb-3 ${style ? `text-bg-${style.tone}` : 'bg-body-tertiary'}`} style={{ minHeight: 220 }}>
                <div className="card-body d-flex flex-column justify-content-center text-center">
                    {last ? (
                        <>
                            <div style={{ fontSize: '3rem' }}><i className={`fas ${style.icon}`} /></div>
                            <div className="fw-bold" style={{ fontSize: '2.25rem' }}>{last.message}</div>
                            {last.task ? <TaskLine task={last.task} t={t} /> : <div className="opacity-75">{last.code}</div>}
                        </>
                    ) : (
                        <div className="text-body-secondary fs-4">{t('waiting')}</div>
                    )}
                </div>
            </div>

            {qtyPrompt ? (
                <form className="card mb-3 border-info" onSubmit={submitQty}>
                    <div className="card-body">
                        <h2 className="h4">{t('qty_title')} — {qtyPrompt.task?.label}</h2>
                        <div className="row g-3 align-items-end">
                            <div className="col-sm-4">
                                <label className="form-label" htmlFor="scan-good">{t('qty_good')}</label>
                                <input id="scan-good" className="form-control form-control-lg" inputMode="numeric" autoFocus
                                    value={qtyPrompt.good} onChange={(e) => setQtyPrompt({ ...qtyPrompt, good: e.target.value })}
                                    onFocus={(e) => e.target.select()} />
                            </div>
                            <div className="col-sm-4">
                                <label className="form-label" htmlFor="scan-bad">{t('qty_bad')}</label>
                                <input id="scan-bad" className="form-control form-control-lg" inputMode="numeric"
                                    value={qtyPrompt.bad} onChange={(e) => setQtyPrompt({ ...qtyPrompt, bad: e.target.value })}
                                    onFocus={(e) => e.target.select()} />
                            </div>
                            <div className="col-sm-4 d-flex gap-2">
                                <button type="submit" className="btn btn-primary btn-lg flex-fill" disabled={busy
                                    || parseQty(qtyPrompt.good) === null || parseQty(qtyPrompt.bad) === null}>
                                    {t('qty_confirm')}
                                </button>
                                <button type="button" className="btn btn-outline-secondary btn-lg" onClick={() => setQtyPrompt(null)}>
                                    {t('qty_cancel')}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            ) : null}

            {history.length > 0 ? (
                <div className="card mb-3">
                    <div className="card-header">{t('history')}</div>
                    <ul className="list-group list-group-flush">
                        {history.map((entry, i) => (
                            <li key={i} className="list-group-item d-flex align-items-center gap-2">
                                <span className={`badge text-bg-${resultStyle(entry.result).tone}`}>{entry.message}</span>
                                <span className="me-auto">{entry.task ? `${entry.task.order_code ?? ''} · ${entry.task.label}` : entry.code}</span>
                                <small className="text-body-secondary">{entry.at.toLocaleTimeString()}</small>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}
        </div>
    );
}

function TaskLine({ task, t }) {
    return (
        <div className="mt-2 fs-5">
            <div className="fw-semibold">{task.label}{task.operation ? ` (${task.operation})` : ''}</div>
            <div className="opacity-75">
                {t('order')} {task.order_code ?? '—'}{task.line_label ? ` · ${task.line_label}` : ''}
            </div>
            <div className="opacity-75">
                {t('worked')} {formatWorked(task.worked_seconds)} · {t('good')} {task.good_qty} · {t('bad')} {task.bad_qty}
                {' · '}{t('remaining')} {task.remaining_qty}
            </div>
        </div>
    );
}

// Lecture caméra (téléphone) : BarcodeDetector, sans paquet. Lit QR et Code128.
function CameraReader({ onCode }) {
    const videoRef = useRef(null);

    useEffect(() => {
        let stream = null;
        let timer = null;
        let stopped = false;
        const detector = new window.BarcodeDetector({ formats: ['qr_code', 'code_128'] });

        navigator.mediaDevices?.getUserMedia({ video: { facingMode: 'environment' } })
            .then((s) => {
                if (stopped) { s.getTracks().forEach((track) => track.stop()); return; }
                stream = s;
                videoRef.current.srcObject = s;
                videoRef.current.play();
                timer = window.setInterval(async () => {
                    try {
                        const codes = await detector.detect(videoRef.current);
                        if (codes.length > 0 && codes[0].rawValue) onCode(codes[0].rawValue);
                    } catch { /* image pas encore prête */ }
                }, 300);
            })
            .catch(() => {});

        return () => {
            stopped = true;
            window.clearInterval(timer);
            stream?.getTracks().forEach((track) => track.stop());
        };
    }, []);

    return (
        <div className="mb-3 text-center">
            <video ref={videoRef} muted playsInline style={{ width: '100%', maxWidth: 480, borderRadius: 8 }} />
        </div>
    );
}
