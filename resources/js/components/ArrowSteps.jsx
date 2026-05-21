import React, { useState, useEffect } from 'react';

const STYLE_ID = 'arrow-steps-react-v1';

const CSS = `
.as-track {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding: 4px 0 10px;
}
.as-row {
    display: flex;
    width: 100%;
}
.as-btn {
    flex: 1 1 0;
    min-width: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 11px 22px;
    font-size: 12px;
    font-weight: 500;
    color: #fff;
    background: #6c757d;
    border: none;
    outline: none;
    cursor: pointer;
    transition: background 0.18s ease, filter 0.14s ease;
    clip-path: polygon(14px 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%, 14px 50%);
    margin-left: -14px;
    position: relative;
    text-align: center;
    white-space: nowrap;
    line-height: 1.3;
    letter-spacing: 0.01em;
}
.as-btn:first-child {
    clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
    border-radius: 5px 0 0 5px;
    padding-left: 14px;
    margin-left: 0;
}
.as-btn:last-child {
    clip-path: polygon(14px 0, 100% 0, 100% 100%, 0 100%, 14px 50%);
    border-radius: 0 5px 5px 0;
    padding-right: 14px;
}
.as-btn:only-child {
    clip-path: none;
    border-radius: 5px;
    padding: 11px 14px;
    margin-left: 0;
}
.as-btn.as-done    { background: #198754; }
.as-btn.as-current { background: #0d6efd; font-weight: 700; font-size: 13px; }
.as-btn:hover:not(:disabled):not(.as-current) { filter: brightness(1.18); }
.as-btn:disabled { cursor: wait; opacity: 0.72; }
.as-btn:nth-child(1) { z-index: 1; }
.as-btn:nth-child(2) { z-index: 2; }
.as-btn:nth-child(3) { z-index: 3; }
.as-btn:nth-child(4) { z-index: 4; }
.as-btn:nth-child(5) { z-index: 5; }
.as-btn:nth-child(6) { z-index: 6; }
.as-btn:nth-child(7) { z-index: 7; }
.as-btn:nth-child(8) { z-index: 8; }

@media (max-width: 576px) {
    .as-row {
        flex-direction: column;
        gap: 5px;
    }
    .as-btn,
    .as-btn:first-child,
    .as-btn:last-child,
    .as-btn:only-child {
        clip-path: none !important;
        border-radius: 6px !important;
        margin-left: 0 !important;
        padding: 10px 16px !important;
        width: 100%;
        justify-content: flex-start;
        font-size: 13px;
        z-index: auto !important;
    }
}
`;

function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const el = document.createElement('style');
    el.id = STYLE_ID;
    el.textContent = CSS;
    document.head.appendChild(el);
}

export default function ArrowSteps({ steps, currentStatu, endpoint, redirectUrl }) {
    const [busy, setBusy]   = useState(false);
    const [error, setError] = useState('');

    useEffect(() => { injectStyles(); }, []);

    const currentIndex = steps.findIndex(s => s.value === currentStatu);

    const go = async (value) => {
        if (busy || value === currentStatu) return;
        setBusy(true);
        setError('');
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ statu: value }),
            });
            if (res.ok) {
                window.location.href = redirectUrl;
                return;
            }
            const data = await res.json().catch(() => ({}));
            setError(data.error ?? data.message ?? 'Erreur lors du changement de statut.');
        } catch {
            setError('Erreur lors du changement de statut.');
        }
        setBusy(false);
    };

    return (
        <div className="as-track">
            {error && (
                <div className="alert alert-danger py-2 mb-2" role="alert">
                    <i className="fas fa-exclamation-triangle mr-1" />{error}
                </div>
            )}
            <div className="as-row">
                {steps.map((s, i) => {
                    const isCurrent = s.value === currentStatu;
                    const isDone    = currentIndex >= 0 && i < currentIndex;
                    return (
                        <button
                            key={s.value}
                            className={`as-btn${isDone ? ' as-done' : ''}${isCurrent ? ' as-current' : ''}`}
                            onClick={() => go(s.value)}
                            disabled={busy}
                            title={s.label}
                        >
                            {s.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
