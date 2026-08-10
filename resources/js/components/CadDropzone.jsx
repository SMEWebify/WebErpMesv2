import React, { useRef, useState } from 'react';

// ---------------------------------------------------------------------------
// CadDropzone — drag and drop of CAD files to create quote or order lines
//
// Shared by QuoteLinesPage and OrderLinesPage: both post the same multipart
// payload and get back { lines: [...], errors: [...] }.
// ---------------------------------------------------------------------------

// Kept in sync with App\Services\Cad\CadParserFactory, which is the authority:
// anything else is rejected server side with an explicit message.
const ACCEPTED = ['sym', 'geo', 'dxf', 'step', 'stp', 'svg'];

const ACCEPT_ATTRIBUTE = ACCEPTED.map((extension) => `.${extension}`).join(',');

export default function CadDropzone({ endpoint, disabled = false, onImported }) {
    const [dragging, setDragging] = useState(false);
    const [status,   setStatus]   = useState(null); // null | 'uploading' | 'done' | 'error'
    const [results,  setResults]  = useState([]);
    const inputRef = useRef(null);

    if (!endpoint || disabled) return null;

    const upload = async (files) => {
        if (status === 'uploading') return;

        const accepted = Array.from(files).filter((file) =>
            ACCEPTED.includes(file.name.split('.').pop()?.toLowerCase()));

        if (accepted.length === 0) {
            setStatus('error');
            setResults([{ ok: false, msg: `Aucun fichier reconnu (${ACCEPT_ATTRIBUTE}).` }]);
            return;
        }

        setStatus('uploading');
        setResults([]);

        const body = new FormData();
        accepted.forEach((file) => body.append('files[]', file));

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    Accept: 'application/json',
                },
                body,
            });

            const data = await response.json();

            if (!response.ok) {
                setStatus('error');
                setResults([{ ok: false, msg: data.error ?? "Erreur lors de l'import." }]);
                return;
            }

            const imported = (data.lines  ?? []).map((line) => ({ ok: true,  msg: line.label ?? line.code }));
            const failed   = (data.errors ?? []).map((error) => ({ ok: false, msg: error }));

            setStatus(failed.length > 0 && imported.length === 0 ? 'error' : 'done');
            setResults([...imported, ...failed]);

            if (imported.length > 0) {
                onImported(data.lines);
            }
        } catch {
            setStatus('error');
            setResults([{ ok: false, msg: "Erreur réseau lors de l'envoi." }]);
        }
    };

    const onDrop = (event) => {
        event.preventDefault();
        setDragging(false);
        upload(event.dataTransfer.files);
    };

    const borderColor = dragging  ? '#007bff'
        : status === 'done'       ? '#28a745'
        : status === 'error'      ? '#dc3545'
        : '#adb5bd';

    return (
        <div className="mt-3">
            <div
                onDragOver={(event) => { event.preventDefault(); setDragging(true); }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                onClick={() => inputRef.current?.click()}
                style={{
                    border: `2px dashed ${borderColor}`,
                    borderRadius: 6,
                    padding: '1.25rem',
                    textAlign: 'center',
                    cursor: 'pointer',
                    background: dragging ? '#e8f0fe' : '#f8f9fa',
                    transition: 'border-color .2s, background .2s',
                    userSelect: 'none',
                }}
            >
                <input
                    ref={inputRef}
                    type="file"
                    accept={ACCEPT_ATTRIBUTE}
                    multiple
                    style={{ display: 'none' }}
                    onChange={(event) => upload(event.target.files)}
                />
                {status === 'uploading' ? (
                    <span className="text-muted">
                        <i className="fas fa-spinner fa-spin mr-2" />
                        Import en cours…
                    </span>
                ) : (
                    <>
                        <div className="text-muted small">
                            <i className="fas fa-file-import mr-2 text-primary" />
                            Déposer des fichiers <strong>{ACCEPT_ATTRIBUTE.replace(/,/g, ' ')}</strong> ici
                            pour créer les lignes automatiquement
                            &nbsp;—&nbsp;ou cliquer pour sélectionner
                        </div>
                        <div className="text-muted mt-1" style={{ fontSize: '0.75rem' }}>
                            Le plan déposé est rattaché à la ligne dans la GED (hors .sym, entièrement lu à l'import).
                        </div>
                    </>
                )}
            </div>

            {results.length > 0 && (
                <ul className="list-group list-group-flush mt-2" style={{ fontSize: '0.85rem' }}>
                    {results.map((result, index) => (
                        <li
                            key={index}
                            className={`list-group-item py-1 px-2 ${result.ok ? 'list-group-item-success' : 'list-group-item-danger'}`}
                        >
                            <i className={`fas ${result.ok ? 'fa-check' : 'fa-times'} mr-2`} />
                            {result.msg}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
