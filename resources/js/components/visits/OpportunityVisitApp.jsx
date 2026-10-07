import React, { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Écran « Visite sur site », pensé pour le téléphone.
 *
 * - Photos : appareil photo ou galerie, réduites dans le navigateur avant
 *   envoi (le réseau sur un chantier est rarement bon), rangées dans la GED de
 *   l'opportunité avec le hashtag de la visite.
 * - Cotes : lignes libellé / valeur / unité, sauvegardées au fil de la saisie.
 * - Mémo vocal : enregistré dans le navigateur, transcrit côté serveur
 *   (Whisper OVH) puis oublié. Sans transcription serveur, dictée du navigateur.
 * - Compte rendu : rédigé par l'IA à partir de tout ce qui précède, éditable,
 *   puis validé en événement « Visite sur site » de l'opportunité.
 */

const UNITS = ['mm', 'cm', 'm', 'm²', '°', 'u'];
const MAX_PHOTO_EDGE = 2560;
const MAX_RECORDING_SECONDS = 20 * 60;
const SAVE_DELAY_MS = 800;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function request(url, { method = 'GET', body } = {}) {
    const isForm = body instanceof FormData;
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(body && !isForm ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? (isForm ? body : JSON.stringify(body)) : undefined,
    });

    let data = null;
    try { data = await response.json(); } catch { /* corps vide */ }

    if (!response.ok) {
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;
        const error = new Error(firstError || data?.message || `HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return data;
}

/**
 * Ramène une photo de téléphone (souvent 4000 px, 5 Mo) à MAX_PHOTO_EDGE en
 * JPEG. En cas d'échec (format non décodable), on envoie le fichier d'origine.
 */
async function shrinkPhoto(file) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif') return file;

    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_PHOTO_EDGE / Math.max(bitmap.width, bitmap.height));
        if (scale === 1 && file.type === 'image/jpeg' && file.size < 2_000_000) {
            bitmap.close?.();
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close?.();

        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        if (!blob) return file;

        const base = (file.name || 'photo').replace(/\.[^.]+$/, '');
        return new File([blob], `${base}.jpg`, { type: 'image/jpeg' });
    } catch {
        return file;
    }
}

function pickRecorderMime() {
    if (typeof MediaRecorder === 'undefined') return null;
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    return candidates.find(type => MediaRecorder.isTypeSupported?.(type)) ?? '';
}

function extensionFor(mime) {
    if (mime.includes('mp4')) return 'm4a';
    if (mime.includes('ogg')) return 'ogg';
    return 'webm';
}

function formatDuration(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

function Section({ icon, title, children, extra }) {
    return (
        <div className="card mb-3 shadow-sm">
            <div className="card-header d-flex align-items-center justify-content-between py-2">
                <h2 className="h6 mb-0"><i className={`fas ${icon} me-2 text-secondary`} />{title}</h2>
                {extra}
            </div>
            <div className="card-body">{children}</div>
        </div>
    );
}

// ── Photos ────────────────────────────────────────────────────────────────

function PhotosSection({ visit, endpoints, opportunityId, imageAccept, readOnly, t }) {
    const [photos, setPhotos] = useState([]);
    const [queue, setQueue] = useState([]); // [{key, name, status: 'pending'|'error', error, file}]
    const [caption, setCaption] = useState('');
    const cameraInput = useRef(null);
    const galleryInput = useRef(null);

    const hashtag = visit.photo_hashtag.toLowerCase();

    const load = useCallback(async () => {
        const url = `${endpoints.filesList}?fileable_type=opportunity&fileable_id=${opportunityId}`;
        try {
            const data = await request(url);
            setPhotos((data.files ?? []).filter(f =>
                (f.hashtags ?? []).some(tag => String(tag).toLowerCase() === hashtag)
            ));
        } catch { /* la liste se recharge au prochain envoi */ }
    }, [endpoints.filesList, opportunityId, hashtag]);

    useEffect(() => { load(); }, [load]);

    const upload = async (item) => {
        setQueue(q => q.map(i => i.key === item.key ? { ...i, status: 'pending', error: null } : i));
        try {
            const file = await shrinkPhoto(item.file);
            const form = new FormData();
            form.append('fileable_type', 'opportunity');
            form.append('fileable_id', String(opportunityId));
            form.append('role', 'photo');
            form.append('hashtags', visit.photo_hashtag);
            form.append('comment', item.caption || t.photo_default_comment);
            form.append('files[]', file);
            await request(endpoints.filesStore, { method: 'POST', body: form });
            setQueue(q => q.filter(i => i.key !== item.key));
            load();
        } catch (e) {
            setQueue(q => q.map(i => i.key === item.key ? { ...i, status: 'error', error: e.message } : i));
        }
    };

    const onFiles = (fileList) => {
        const items = Array.from(fileList ?? []).map((file, index) => ({
            key: `${Date.now()}-${index}`,
            name: file.name,
            file,
            caption: caption.trim(),
            status: 'pending',
            error: null,
        }));
        if (!items.length) return;
        setCaption('');
        setQueue(q => [...q, ...items]);
        // Un à un : sur une 4G faible, dix envois parallèles échouent tous.
        items.reduce((chain, item) => chain.then(() => upload(item)), Promise.resolve());
    };

    return (
        <Section icon="fa-camera" title={`${t.photos} (${photos.length})`}>
            {!readOnly && (
                <>
                    <input
                        type="text"
                        className="form-control mb-2"
                        placeholder={t.photo_caption_placeholder}
                        value={caption}
                        onChange={e => setCaption(e.target.value)}
                        maxLength={250}
                    />
                    <div className="d-grid gap-2 d-sm-flex mb-3">
                        <button type="button" className="btn btn-primary btn-lg flex-fill" onClick={() => cameraInput.current?.click()}>
                            <i className="fas fa-camera me-2" />{t.take_photo}
                        </button>
                        <button type="button" className="btn btn-outline-secondary btn-lg flex-fill" onClick={() => galleryInput.current?.click()}>
                            <i className="far fa-images me-2" />{t.from_gallery}
                        </button>
                    </div>
                    <input ref={cameraInput} type="file" accept="image/*" capture="environment" hidden
                           onChange={e => { onFiles(e.target.files); e.target.value = ''; }} />
                    <input ref={galleryInput} type="file" accept={imageAccept || 'image/*'} multiple hidden
                           onChange={e => { onFiles(e.target.files); e.target.value = ''; }} />
                </>
            )}

            {queue.map(item => (
                <div key={item.key} className={`alert ${item.status === 'error' ? 'alert-danger' : 'alert-info'} py-2 d-flex align-items-center gap-2`}>
                    {item.status === 'pending'
                        ? <span className="spinner-border spinner-border-sm" />
                        : <i className="fas fa-exclamation-triangle" />}
                    <span className="flex-fill text-truncate">
                        {item.status === 'pending' ? t.uploading : item.error} — {item.name}
                    </span>
                    {item.status === 'error' && (
                        <>
                            <button type="button" className="btn btn-sm btn-light" onClick={() => upload(item)}>{t.retry}</button>
                            <button type="button" className="btn btn-sm btn-link text-danger"
                                    onClick={() => setQueue(q => q.filter(i => i.key !== item.key))}>
                                <i className="fas fa-times" />
                            </button>
                        </>
                    )}
                </div>
            ))}

            {photos.length === 0 && queue.length === 0 && <p className="text-muted mb-0">{t.no_photo}</p>}

            <div className="row g-2">
                {photos.map(photo => (
                    <div key={photo.id} className="col-4 col-md-3">
                        <a href={photo.view_url} target="_blank" rel="noopener noreferrer" className="d-block ratio ratio-1x1 bg-light rounded overflow-hidden">
                            <img src={photo.view_url} alt={photo.comment || photo.name} loading="lazy"
                                 style={{ objectFit: 'cover', width: '100%', height: '100%' }} />
                        </a>
                        {photo.comment && <small className="d-block text-muted text-truncate">{photo.comment}</small>}
                    </div>
                ))}
            </div>
        </Section>
    );
}

// ── Cotes ─────────────────────────────────────────────────────────────────

function MeasurementsSection({ rows, onChange, readOnly, t }) {
    const quickLabels = t.quick_labels ?? [];

    const update = (index, field, value) => {
        onChange(rows.map((row, i) => i === index ? { ...row, [field]: value } : row));
    };

    const add = (label = '') => onChange([...rows, { label, value: '', unit: 'mm', note: '' }]);

    if (readOnly) {
        return (
            <Section icon="fa-ruler-combined" title={t.measurements}>
                {rows.length === 0 ? <p className="text-muted mb-0">{t.no_measurement}</p> : (
                    <table className="table table-sm mb-0">
                        <tbody>
                            {rows.map((row, i) => (
                                <tr key={i}>
                                    <td>{row.label}</td>
                                    <td className="text-end text-nowrap fw-semibold">{row.value} {row.unit}</td>
                                    <td className="text-muted">{row.note}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Section>
        );
    }

    return (
        <Section icon="fa-ruler-combined" title={t.measurements}>
            {rows.map((row, index) => (
                <div key={index} className="border rounded p-2 mb-2">
                    <div className="d-flex gap-2 mb-2">
                        <input type="text" className="form-control" placeholder={t.measurement_label}
                               value={row.label ?? ''} maxLength={255}
                               onChange={e => update(index, 'label', e.target.value)} />
                        <button type="button" className="btn btn-outline-danger" aria-label={t.remove}
                                onClick={() => onChange(rows.filter((_, i) => i !== index))}>
                            <i className="fas fa-trash" />
                        </button>
                    </div>
                    <div className="d-flex gap-2">
                        <input type="text" inputMode="decimal" className="form-control form-control-lg"
                               placeholder={t.measurement_value} value={row.value ?? ''} maxLength={50}
                               onChange={e => update(index, 'value', e.target.value)} />
                        <select className="form-select form-select-lg" style={{ maxWidth: '6.5rem' }}
                                value={row.unit ?? 'mm'} onChange={e => update(index, 'unit', e.target.value)}>
                            {UNITS.map(unit => <option key={unit} value={unit}>{unit}</option>)}
                        </select>
                    </div>
                    <input type="text" className="form-control form-control-sm mt-2" placeholder={t.measurement_note}
                           value={row.note ?? ''} maxLength={255}
                           onChange={e => update(index, 'note', e.target.value)} />
                </div>
            ))}

            <div className="d-flex flex-wrap gap-2">
                <button type="button" className="btn btn-outline-primary" onClick={() => add()}>
                    <i className="fas fa-plus me-1" />{t.add_measurement}
                </button>
                {quickLabels.map(label => (
                    <button key={label} type="button" className="btn btn-sm btn-light border" onClick={() => add(label)}>
                        + {label}
                    </button>
                ))}
            </div>
        </Section>
    );
}

// ── Mémo vocal ────────────────────────────────────────────────────────────

function VoiceSection({ visit, transcript, onTranscript, onVisit, onBeforeSend, transcriptionAvailable, locale, readOnly, t }) {
    const recorderMime = pickRecorderMime();
    const canRecord = transcriptionAvailable && recorderMime !== null && !!navigator.mediaDevices?.getUserMedia;
    const SpeechRecognition = typeof window !== 'undefined' ? (window.SpeechRecognition || window.webkitSpeechRecognition) : null;
    const canDictate = !canRecord && !!SpeechRecognition;

    const [recording, setRecording] = useState(false);
    const [seconds, setSeconds] = useState(0);
    const [pending, setPending] = useState(null); // {blob, mime, duration}
    const [status, setStatus] = useState('idle'); // idle | sending | error
    const [error, setError] = useState(null);
    const [dictating, setDictating] = useState(false);

    const recorderRef = useRef(null);
    const chunksRef = useRef([]);
    const streamRef = useRef(null);
    const timerRef = useRef(null);
    const recognitionRef = useRef(null);

    const stopTracks = () => {
        streamRef.current?.getTracks().forEach(track => track.stop());
        streamRef.current = null;
        clearInterval(timerRef.current);
    };

    useEffect(() => () => {
        stopTracks();
        recognitionRef.current?.abort?.();
    }, []);

    const send = async (memo) => {
        setStatus('sending');
        // Le serveur ajoute le texte à la transcription enregistrée : on y
        // verse d'abord les corrections en attente pour ne rien écraser.
        await onBeforeSend?.();
        setError(null);
        try {
            const form = new FormData();
            form.append('audio', new File([memo.blob], `memo.${extensionFor(memo.mime)}`, { type: memo.mime }));
            const data = await request(visit.endpoints.transcribe, { method: 'POST', body: form });
            // Transcrit : l'enregistrement n'a plus de raison d'exister, nulle part.
            setPending(null);
            setStatus('idle');
            onVisit(data.visit);
        } catch (e) {
            setStatus('error');
            setError(e.message);
        }
    };

    const startRecording = async () => {
        setError(null);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            streamRef.current = stream;
            const options = { audioBitsPerSecond: 32000 };
            if (recorderMime) options.mimeType = recorderMime;
            const recorder = new MediaRecorder(stream, options);
            chunksRef.current = [];
            recorder.ondataavailable = e => { if (e.data.size) chunksRef.current.push(e.data); };
            recorder.onstop = () => {
                stopTracks();
                const mime = recorder.mimeType || recorderMime || 'audio/webm';
                const memo = { blob: new Blob(chunksRef.current, { type: mime }), mime };
                chunksRef.current = [];
                setRecording(false);
                if (memo.blob.size === 0) return;
                setPending(memo);
                send(memo);
            };
            recorderRef.current = recorder;
            recorder.start(1000);
            setSeconds(0);
            setRecording(true);
            timerRef.current = setInterval(() => {
                setSeconds(s => {
                    if (s + 1 >= MAX_RECORDING_SECONDS) recorderRef.current?.stop();
                    return s + 1;
                });
            }, 1000);
        } catch {
            stopTracks();
            setError(t.microphone_denied);
        }
    };

    const stopRecording = () => recorderRef.current?.state === 'recording' && recorderRef.current.stop();

    const discard = () => {
        setPending(null);
        setStatus('idle');
        setError(null);
    };

    const toggleDictation = () => {
        if (dictating) {
            recognitionRef.current?.stop();
            return;
        }
        const recognition = new SpeechRecognition();
        recognition.lang = locale === 'fr' ? 'fr-FR' : locale === 'es' ? 'es-ES' : 'en-US';
        recognition.continuous = true;
        recognition.interimResults = false;
        recognition.onresult = (event) => {
            const said = Array.from(event.results)
                .slice(event.resultIndex)
                .filter(result => result.isFinal)
                .map(result => result[0].transcript.trim())
                .join(' ');
            if (said) onTranscript(prev => (prev ? `${prev} ${said}` : said));
        };
        recognition.onerror = (event) => {
            if (event.error === 'not-allowed') setError(t.microphone_denied);
        };
        recognition.onend = () => setDictating(false);
        recognitionRef.current = recognition;
        recognition.start();
        setDictating(true);
    };

    return (
        <Section icon="fa-microphone" title={t.voice_memo}>
            {!readOnly && (
                <>
                    {canRecord && (
                        <>
                            {!recording ? (
                                <button type="button" className="btn btn-danger btn-lg w-100 mb-2"
                                        disabled={status === 'sending'} onClick={startRecording}>
                                    <i className="fas fa-microphone me-2" />{t.start_recording}
                                </button>
                            ) : (
                                <button type="button" className="btn btn-dark btn-lg w-100 mb-2" onClick={stopRecording}>
                                    <span className="spinner-grow spinner-grow-sm text-danger me-2" />
                                    {t.stop_recording} — {formatDuration(seconds)}
                                </button>
                            )}
                            {status === 'sending' && (
                                <div className="alert alert-info py-2"><span className="spinner-border spinner-border-sm me-2" />{t.transcribing}</div>
                            )}
                            {status === 'error' && pending && (
                                <div className="alert alert-warning py-2">
                                    <div className="mb-2">{error}</div>
                                    <div className="d-flex gap-2">
                                        <button type="button" className="btn btn-sm btn-primary" onClick={() => send(pending)}>{t.retry}</button>
                                        <button type="button" className="btn btn-sm btn-outline-danger" onClick={discard}>{t.delete_recording}</button>
                                    </div>
                                </div>
                            )}
                            <p className="small text-muted">{t.privacy_server}</p>
                        </>
                    )}

                    {canDictate && (
                        <>
                            <button type="button" className={`btn btn-lg w-100 mb-2 ${dictating ? 'btn-dark' : 'btn-danger'}`} onClick={toggleDictation}>
                                {dictating
                                    ? <><span className="spinner-grow spinner-grow-sm text-danger me-2" />{t.stop_dictation}</>
                                    : <><i className="fas fa-microphone me-2" />{t.start_dictation}</>}
                            </button>
                            <p className="small text-muted">{t.privacy_browser}</p>
                        </>
                    )}

                    {!canRecord && !canDictate && <p className="text-muted">{t.voice_unsupported}</p>}
                    {error && status !== 'error' && <div className="alert alert-danger py-2">{error}</div>}
                </>
            )}

            <label className="form-label small text-muted mb-1" htmlFor="visit-transcript">{t.transcript}</label>
            <textarea id="visit-transcript" className="form-control" rows={readOnly ? 4 : 6}
                      readOnly={readOnly} value={transcript ?? ''}
                      onChange={e => onTranscript(e.target.value)} />
        </Section>
    );
}

// ── Écran ─────────────────────────────────────────────────────────────────

export default function OpportunityVisitApp({ opportunity, visit: initialVisit, endpoints, transcriptionAvailable, imageAccept, locale, trans: t }) {
    const [visit, setVisit] = useState(initialVisit);
    const [fields, setFields] = useState(() => ({
        visited_at: initialVisit?.visited_at ?? '',
        notes: initialVisit?.notes ?? '',
        measurements: initialVisit?.measurements ?? [],
        transcript: initialVisit?.transcript ?? '',
        report: initialVisit?.report ?? '',
    }));
    const [saveState, setSaveState] = useState('saved'); // saved | dirty | saving | error
    const [busy, setBusy] = useState(null); // 'start' | 'report' | 'validate' | 'delete'
    const [message, setMessage] = useState(null);

    const dirtyRef = useRef({});
    const timerRef = useRef(null);
    const readOnly = visit ? !visit.is_draft : false;

    const flush = useCallback(async () => {
        clearTimeout(timerRef.current);
        const patch = dirtyRef.current;
        if (!visit || !Object.keys(patch).length) return true;
        dirtyRef.current = {};
        setSaveState('saving');
        try {
            await request(visit.endpoints.update, { method: 'PATCH', body: patch });
            setSaveState(Object.keys(dirtyRef.current).length ? 'dirty' : 'saved');
            return true;
        } catch {
            // On remet le patch en file : il repartira à la prochaine frappe.
            dirtyRef.current = { ...patch, ...dirtyRef.current };
            setSaveState('error');
            return false;
        }
    }, [visit]);

    const change = (field, value) => {
        setFields(prev => {
            const next = typeof value === 'function' ? value(prev[field]) : value;
            dirtyRef.current = { ...dirtyRef.current, [field]: next };
            return { ...prev, [field]: next };
        });
        setSaveState('dirty');
        clearTimeout(timerRef.current);
        timerRef.current = setTimeout(flush, SAVE_DELAY_MS);
    };

    // Ne pas perdre la dernière frappe si le téléphone verrouille l'écran.
    useEffect(() => {
        const onHide = () => { if (document.visibilityState === 'hidden') flush(); };
        const onUnload = (e) => {
            if (Object.keys(dirtyRef.current).length) { flush(); e.preventDefault(); e.returnValue = ''; }
        };
        document.addEventListener('visibilitychange', onHide);
        window.addEventListener('beforeunload', onUnload);
        return () => {
            document.removeEventListener('visibilitychange', onHide);
            window.removeEventListener('beforeunload', onUnload);
        };
    }, [flush]);

    // Le serveur a ajouté du texte (transcription, compte rendu) : on reprend
    // ses valeurs sans écraser ce que l'utilisateur est en train de taper.
    const absorb = (fresh) => {
        setVisit(fresh);
        setFields(prev => {
            const next = { ...prev };
            ['transcript', 'report'].forEach(field => {
                if (!(field in dirtyRef.current)) next[field] = fresh[field] ?? '';
            });
            return next;
        });
    };

    const start = async () => {
        setBusy('start');
        setMessage(null);
        try {
            const data = await request(endpoints.store, { method: 'POST' });
            setVisit(data.visit);
            setFields(f => ({ ...f, visited_at: data.visit.visited_at }));
        } catch (e) {
            setMessage({ type: 'danger', text: e.message });
        } finally {
            setBusy(null);
        }
    };

    const draftReport = async () => {
        if (fields.report.trim() && !window.confirm(t.report_overwrite_confirm)) return;
        setBusy('report');
        setMessage(null);
        if (!(await flush())) { setBusy(null); setMessage({ type: 'danger', text: t.save_failed }); return; }
        try {
            const data = await request(visit.endpoints.report, { method: 'POST' });
            setVisit(data.visit);
            setFields(f => ({ ...f, report: data.visit.report ?? '' }));
        } catch (e) {
            setMessage({ type: 'danger', text: e.message });
        } finally {
            setBusy(null);
        }
    };

    const validate = async () => {
        if (!window.confirm(t.validate_confirm)) return;
        setBusy('validate');
        setMessage(null);
        if (!(await flush())) { setBusy(null); setMessage({ type: 'danger', text: t.save_failed }); return; }
        try {
            const data = await request(visit.endpoints.validate, { method: 'POST' });
            window.location.href = data.redirect;
        } catch (e) {
            setMessage({ type: 'danger', text: e.message });
            setBusy(null);
        }
    };

    const discard = async () => {
        if (!window.confirm(t.delete_confirm)) return;
        setBusy('delete');
        clearTimeout(timerRef.current);
        dirtyRef.current = {};
        try {
            await request(visit.endpoints.destroy, { method: 'DELETE' });
            window.location.href = opportunity.url;
        } catch (e) {
            setMessage({ type: 'danger', text: e.message });
            setBusy(null);
        }
    };

    const saveBadge = {
        saved: <span className="badge text-bg-success">{t.saved}</span>,
        dirty: <span className="badge text-bg-secondary">{t.saving}</span>,
        saving: <span className="badge text-bg-secondary">{t.saving}</span>,
        error: <span className="badge text-bg-danger">{t.save_failed}</span>,
    }[saveState];

    return (
        <div className="container py-3" style={{ maxWidth: 720 }}>
            <div className="d-flex align-items-start justify-content-between gap-2 mb-3">
                <div className="min-w-0">
                    <a href={opportunity.url} className="small text-decoration-none">
                        <i className="fas fa-arrow-left me-1" />{t.back_to_opportunity}
                    </a>
                    <h1 className="h5 mb-0 mt-1 text-truncate">{opportunity.label}</h1>
                    <div className="small text-muted">
                        {opportunity.company}
                        {opportunity.contact && <> · {opportunity.contact}</>}
                    </div>
                    {opportunity.address && (
                        <a className="small" target="_blank" rel="noopener noreferrer"
                           href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(opportunity.address)}`}>
                            <i className="fas fa-map-marker-alt me-1" />{opportunity.address}
                        </a>
                    )}
                </div>
                {visit && !readOnly && saveBadge}
                {readOnly && <span className="badge text-bg-success">{t.validated}</span>}
            </div>

            {message && <div className={`alert alert-${message.type}`}>{message.text}</div>}

            {!visit ? (
                <div className="text-center py-5">
                    <p className="text-muted">{t.start_hint}</p>
                    <button type="button" className="btn btn-primary btn-lg" disabled={busy === 'start'} onClick={start}>
                        {busy === 'start' ? <span className="spinner-border spinner-border-sm me-2" /> : <i className="fas fa-play me-2" />}
                        {t.start_visit}
                    </button>
                </div>
            ) : (
                <>
                    <div className="mb-3">
                        <label className="form-label small text-muted mb-1" htmlFor="visit-date">{t.visited_at}</label>
                        <input id="visit-date" type="datetime-local" className="form-control" readOnly={readOnly}
                               value={fields.visited_at} onChange={e => change('visited_at', e.target.value)} />
                        {visit.user && <small className="text-muted">{t.by} {visit.user}</small>}
                    </div>

                    <PhotosSection visit={visit} endpoints={endpoints} opportunityId={opportunity.id}
                                   imageAccept={imageAccept} readOnly={readOnly} t={t} />

                    <MeasurementsSection rows={fields.measurements} readOnly={readOnly} t={t}
                                         onChange={rows => change('measurements', rows)} />

                    <Section icon="fa-sticky-note" title={t.notes}>
                        <textarea className="form-control" rows={5} readOnly={readOnly}
                                  placeholder={t.notes_placeholder} value={fields.notes}
                                  onChange={e => change('notes', e.target.value)} />
                    </Section>

                    <VoiceSection visit={visit} transcript={fields.transcript} readOnly={readOnly}
                                  onTranscript={value => change('transcript', value)} onVisit={absorb} onBeforeSend={flush}
                                  transcriptionAvailable={transcriptionAvailable} locale={locale} t={t} />

                    <Section icon="fa-file-alt" title={t.report}
                             extra={!readOnly && (
                                 <button type="button" className="btn btn-sm btn-outline-primary"
                                         disabled={busy === 'report'} onClick={draftReport}>
                                     {busy === 'report' ? <span className="spinner-border spinner-border-sm me-1" /> : <i className="fas fa-magic me-1" />}
                                     {t.draft_report}
                                 </button>
                             )}>
                        {busy === 'report' && <p className="small text-muted">{t.drafting}</p>}
                        <textarea className="form-control" rows={12} readOnly={readOnly}
                                  placeholder={t.report_placeholder} value={fields.report}
                                  onChange={e => change('report', e.target.value)} />
                    </Section>

                    {!readOnly && (
                        <div className="d-grid gap-2 mb-5">
                            <button type="button" className="btn btn-success btn-lg" disabled={!!busy} onClick={validate}>
                                {busy === 'validate' ? <span className="spinner-border spinner-border-sm me-2" /> : <i className="fas fa-check me-2" />}
                                {t.validate_visit}
                            </button>
                            <button type="button" className="btn btn-link text-danger" disabled={!!busy} onClick={discard}>
                                {t.delete_draft}
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
